// ===== Expense Allocation — Create + Edit (PO tab + GRN tab) =====
(function () {
    // ---------- CONFIG ----------
    const CFG = {
        tabs: { po: "#poItems", grn: "#grnItems" },

        // Buttons
        distributeBtn: ".distributeBtn, .allocateBtn",
        submitBtn: ".submitBtn", // optional: disable until distributed
        submitInterceptBtn: ".submit-button", // your Save as Draft / Submit buttons

        // PO (expense) grid
        expense: {
            row: ".expense-row",
            name: ".expense-name",
            amount: ".expense-amount",
            type: ".alloc-type", // QTY / VALUE / WEIGHT / VOLUME
            id: ".expense-id", // optional hidden id on EDIT, blank on CREATE
        },

        // GRN grid
        grn: {
            row: ".grn-row",
            qty: ".grn-qty",
            value: ".grn-value",
            weight: ".grn-weight",
            volume: ".grn-volume",
            allocated: ".allocated-exp",
            landed: ".landed-cost",
            itemName: ".item-name",
        },

        // Delete toolbars
        bulkDeleteBtn: ".delete-po-items, .delete-grn-items",

        scale: 2,
    };

    const PAGE_MODE = (
        document.querySelector('meta[name="route-type"]')?.content || "create"
    ).toLowerCase();

    // ---------- UTIL ----------
    const $ = window.jQuery || window.$;
    const toNum = (v) => {
        const n = parseFloat(
            String(v ?? "")
                .replace(/,/g, "")
                .trim()
        );
        return Number.isFinite(n) ? n : 0;
    };
    const round = (n, p = CFG.scale) =>
        Math.round((n + Number.EPSILON) * 10 ** p) / 10 ** p;
    const sum = (arr, key) => arr.reduce((s, x) => s + toNum(x[key]), 0);
    const esc = (s) =>
        String(s).replace(
            /[&<>"']/g,
            (m) =>
                ({
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                }[m])
        );

    function rowIsActive($r, { includeHidden = true } = {}) {
        const visibleOK = includeHidden || $r.is(":visible");
        const softRemoved =
            $r.hasClass("is-deleted") ||
            String($r.attr("data-removed")) === "1" ||
            $r.data("removed") === 1;
        const isTemplate = $r.is(
            '[data-template="1"], .template-row, .d-template'
        );
        return visibleOK && !softRemoved && !isTemplate;
    }

    const $poScope = () =>
        $(CFG.tabs.po).length ? $(CFG.tabs.po) : $(document.body);
    const $grnScope = () =>
        $(CFG.tabs.grn).length ? $(CFG.tabs.grn) : $(document.body);

    function detectTabScope($from) {
        if ($from.closest(CFG.tabs.po).length)
            return { key: "po", $scope: $from.closest(CFG.tabs.po) };
        if ($from.closest(CFG.tabs.grn).length)
            return { key: "grn", $scope: $from.closest(CFG.tabs.grn) };
        const $active = $(".tab-pane.active");
        if ($active.length) {
            if ($active.is(CFG.tabs.po)) return { key: "po", $scope: $active };
            if ($active.is(CFG.tabs.grn))
                return { key: "grn", $scope: $active };
        }
        return {
            key: "unknown",
            $scope: $from.closest("table").length
                ? $from.closest("table")
                : $(document.body),
        };
    }

    // ---------- Labels ----------
    function getItemLabel($tr) {
        const tryGet = (sel) => {
            if (!sel) return "";
            const $el = $tr.find(sel);
            if (!$el.length) return "";
            const v = ($el.val?.() ?? $el.text() ?? "").trim();
            return v || "";
        };
        let label = tryGet(CFG.grn.itemName);
        if (label) return label;

        label =
            tryGet('[name*="[item_name]"]') ||
            tryGet(".item_name") ||
            tryGet(".item-name");
        if (label) return label;

        const code = tryGet(".item-code") || tryGet('[name*="[item_code]"]');
        const name =
            tryGet(".item-name") ||
            tryGet('[name*="[item_name]"]') ||
            tryGet(".item_name");
        if (name || code)
            return [name, code && `(${code})`].filter(Boolean).join(" ");
        const idx = ($tr.data("id") ?? $tr.index()) + 1;
        return `Item #${String(idx).padStart(2, "0")}`;
    }

    // ---------- Hidden allocations helpers ----------
    function findGrnRowIndex($tr) {
        const cached = $tr.data("rowIndex");
        if (Number.isInteger(cached)) return cached;
        let idx = null;
        $tr.find(
            "input[name^='components[grn]'],select[name^='components[grn]'],textarea[name^='components[grn]']"
        ).each(function () {
            const m = (this.name || "").match(/^components\[grn]\[(\d+)]/);
            if (m) {
                idx = parseInt(m[1], 10);
                return false;
            }
        });
        if (idx == null) idx = $tr.index() + 1;
        $tr.data("rowIndex", idx);
        return idx;
    }

    // pairs: [{ id: "...", amout: number }]
    function writeAllocationsHiddenInputs($tr, pairs) {
        const idx = findGrnRowIndex($tr);
        $tr.find(
            `input.ea-alloc[name='components[grn][${idx}][allocations][]']`
        ).remove();

        let $holder = $tr.find(".ea-alloc-holder");
        if (!$holder.length)
            $holder = $(
                '<div class="ea-alloc-holder" style="display:none"></div>'
            ).appendTo($tr);

        pairs.forEach((p) => {
            const payload = {
                id: (p.id ?? "").toString(),
                amout: (Number(p.amout) || 0).toFixed(CFG.scale),
            }; // keep 'amout'
            $("<input>", {
                type: "hidden",
                class: "ea-alloc",
                name: `components[grn][${idx}][allocations][]`,
                value: JSON.stringify(payload),
                readonly: true,
            }).appendTo($holder);
        });
    }

    function getExpenseId($tr) {
        const trySel = (sel) => {
            const $el = $tr.find(sel);
            if (!$el.length) return null;
            const v = ($el.val?.() ?? $el.text() ?? "").trim();
            return v || null;
        };
        return (
            $tr.data("poItemId") ??
            $tr.data("id") ??
            trySel("[name*='[po_item_hidden_ids]']") ??
            trySel("[name*='[po_detail_id]']") ??
            trySel(".po-item-id") ??
            trySel(".expense-id") ??
            ""
        );
    }

    // ---------- Collectors ----------
    function collectExpenses({ requireAllocType = false } = {}) {
        const ALLOWED = ["QTY", "VALUE", "WEIGHT", "VOLUME"];
        const list = [],
            missing = [];
        $poScope()
            .find(CFG.expense.row)
            .filter(function () {
                return rowIsActive($(this), { includeHidden: true });
            })
            .each(function (i) {
                const $tr = $(this);
                const name =
                    (
                        $tr.find(CFG.expense.name).val?.() ??
                        $tr.find(CFG.expense.name).text() ??
                        ""
                    ).trim() || `Expense #${i + 1}`;
                const amount = toNum(
                    $tr.find(CFG.expense.amount).val?.() ??
                        $tr.find(CFG.expense.amount).text()
                );
                const raw = (
                    $tr.find(CFG.expense.type).val?.() ??
                    $tr.find(CFG.expense.type).text() ??
                    ""
                )
                    .trim()
                    .toUpperCase();
                const valid = ALLOWED.includes(raw);

                if (requireAllocType && !valid) {
                    missing.push({ index: i + 1, name });
                    $tr.find(CFG.expense.type).addClass("is-invalid");
                } else {
                    $tr.find(CFG.expense.type).removeClass("is-invalid");
                }

                const id =
                    (
                        $tr.find(CFG.expense.id).val?.() ??
                        $tr.find(CFG.expense.id).text() ??
                        ""
                    ).trim() ||
                    getExpenseId($tr) ||
                    "";
                list.push({ $tr, id, name, amount, type: valid ? raw : null });
            });
        return { list, missing };
    }

    function collectGrnItems() {
        const items = [];
        $grnScope()
            .find(CFG.grn.row)
            .filter(function () {
                return rowIsActive($(this), { includeHidden: true });
            })
            .each(function () {
                const $tr = $(this);
                items.push({
                    id: $tr.data("id") ?? $tr.index(),
                    $tr,
                    label: getItemLabel($tr),
                    qty: toNum(
                        $tr.find(CFG.grn.qty).val?.() ??
                            $tr.find(CFG.grn.qty).text()
                    ),
                    value: toNum(
                        $tr.find(CFG.grn.value).val?.() ??
                            $tr.find(CFG.grn.value).text()
                    ),
                    weight: toNum(
                        $tr.find(CFG.grn.weight).val?.() ??
                            $tr.find(CFG.grn.weight).text()
                    ),
                    volume: toNum(
                        $tr.find(CFG.grn.volume).val?.() ??
                            $tr.find(CFG.grn.volume).text()
                    ),
                });
            });
        return items;
    }

    // ---------- Allocation core ----------
    function allocateExpense(expense, items, basisKey) {
        const total = sum(items, basisKey);
        const out = [];
        if (total <= 0 || expense.amount <= 0) {
            for (const it of items) out.push({ itemId: it.id, amount: 0 });
            return out;
        }
        let running = 0;
        for (let i = 0; i < items.length; i++) {
            const it = items[i];
            const amt = round((toNum(it[basisKey]) / total) * expense.amount);
            out.push({ itemId: it.id, amount: amt });
            running += amt;
        }
        const drift = round(expense.amount - running);
        if (out.length)
            out[out.length - 1].amount = round(
                out[out.length - 1].amount + drift
            );
        return out;
    }

    function applyAllocationsToDOM(items, breakdown, expenses) {
        const expIdByName = {};
        (expenses || []).forEach((e) => (expIdByName[e.name] = e.id || ""));

        for (const it of items) {
            const row = breakdown[it.id] || { total: 0, byName: {} };
            const landed = round(toNum(it.value) + row.total);

            const $alloc = it.$tr.find(CFG.grn.allocated);
            if ($alloc.length) {
                if ($alloc.is("input,textarea")) $alloc.val(round(row.total));
                else $alloc.text(round(row.total).toFixed(CFG.scale));
            }
            const $landed = it.$tr.find(CFG.grn.landed);
            if ($landed.length) {
                if ($landed.is("input,textarea")) $landed.val(landed);
                else $landed.text(landed.toFixed(CFG.scale));
            }

            // Cache for popup
            it.$tr.data("eaBreakup", { ...row, label: it.label });

            // Hidden allocations (id blank on create)
            const pairs = Object.entries(row.byName).map(([name, amt]) => ({
                id: expIdByName[name] || "",
                amout: round(toNum(amt)),
            }));
            writeAllocationsHiddenInputs(it.$tr, pairs);
        }
    }

    // ---------- Submit guard ----------
    let distributedOK = false;
    function setSubmitState() {
        const $submit = $(CFG.submitBtn);
        if (!$submit.length) return;
        if (distributedOK)
            $submit.prop("disabled", false).removeClass("disabled");
        else $submit.prop("disabled", true).addClass("disabled");
    }
    function markDistributionDirty() {
        distributedOK = false;
        setSubmitState();
    }
    function wireDirtyHandlers() {
        $(document).on(
            "input change",
            `${CFG.grn.qty}, ${CFG.grn.value}, ${CFG.grn.weight}, ${CFG.grn.volume}, ${CFG.expense.amount}`,
            markDistributionDirty
        );
        $(document).on("change", CFG.expense.type, function () {
            $(this).removeClass("is-invalid");
            markDistributionDirty();
        });
    }

    function clearAllocations() {
        const items = collectGrnItems();
        for (const it of items) {
            const base = round(toNum(it.value));
            const $alloc = it.$tr.find(CFG.grn.allocated);
            const $landed = it.$tr.find(CFG.grn.landed);
            if ($alloc.length)
                $alloc.is("input,textarea")
                    ? $alloc.val(0)
                    : $alloc.text((0).toFixed(CFG.scale));
            if ($landed.length)
                $landed.is("input,textarea")
                    ? $landed.val(base.toFixed(CFG.scale))
                    : $landed.text(base.toFixed(CFG.scale));
            it.$tr.removeData("eaBreakup");
            it.$tr.find("input.ea-alloc").remove();
        }
    }

    // ---------- Distribution entry point ----------
    function runDistribution({ showSuccess = true } = {}) {
        const items = collectGrnItems();
        if (!items.length) {
            Swal.fire({
                icon: "error",
                title: "Add GRN items",
                text: "Please add GRN items in the GRN tab before distribution.",
            });
            distributedOK = false;
            setSubmitState();
            return false;
        }

        const { list: allExpenses, missing } = collectExpenses({
            requireAllocType: true,
        });
        if (missing.length) {
            const list = missing
                .map((m) => `• Row ${m.index} — ${esc(m.name)}`)
                .join("<br>");
            Swal.fire({
                icon: "error",
                title: "Allocation Type required",
                // html: `Select Allocation Type (QTY / VALUE / WEIGHT / VOLUME) for:<br>${list}`,
                html: "",
            });
            distributedOK = false;
            setSubmitState();
            return false;
        }

        const expenses = allExpenses.filter((e) => e.amount > 0);
        if (!expenses.length) {
            Swal.fire({
                icon: "warning",
                title: "No expenses",
                text: "Please add at least one expense amount > 0 in the PO tab.",
            });
            distributedOK = false;
            setSubmitState();
            return false;
        }

        const totals = {
            QTY: sum(items, "qty"),
            VALUE: sum(items, "value"),
            WEIGHT: sum(items, "weight"),
            VOLUME: sum(items, "volume"),
        };
        const pretty = {
            QTY: "Quantity",
            VALUE: "Value",
            WEIGHT: "Weight",
            VOLUME: "Volume",
        };
        const issues = {};
        for (const e of expenses)
            if (totals[e.type] <= 0) (issues[e.type] ||= []).push(e.name);
        if (Object.keys(issues).length) {
            const html = Object.entries(issues)
                .map(
                    ([t, names]) =>
                        `<div style="margin:.5rem 0">• <b>${
                            pretty[t]
                        }</b> on GRN items is zero.<br><span style="color:#6c757d">Affects: ${names
                            .map(esc)
                            .join(", ")}</span></div>`
                )
                .join("");
            clearAllocations();
            Swal.fire({
                icon: "error",
                title: "Missing basis data",
                html: `Cannot distribute because these bases are zero:<br>${html}`,
            });
            distributedOK = false;
            setSubmitState();
            return false;
        }

        const basisKeyMap = {
            QTY: "qty",
            VALUE: "value",
            WEIGHT: "weight",
            VOLUME: "volume",
        };
        const breakdown = {};
        for (const e of expenses) {
            const allocs = allocateExpense(e, items, basisKeyMap[e.type]);
            allocs.forEach(({ itemId, amount }) => {
                breakdown[itemId] ||= { total: 0, byName: {} };
                breakdown[itemId].byName[e.name] = round(amount);
                breakdown[itemId].total = round(
                    breakdown[itemId].total + amount
                );
            });
        }

        applyAllocationsToDOM(items, breakdown, expenses);
        distributedOK = true;
        setSubmitState();

        if (typeof window.setTableCalculation === "function")
            setTableCalculation();

        if (showSuccess) {
            Swal.fire({
                icon: "success",
                title: "Allocated successfully",
                timer: 1000,
                showConfirmButton: false,
            });
        }
        return true;
    }

    // Allocate button
    $(document).on("click", CFG.distributeBtn, function () {
        runDistribution({ showSuccess: true });
    });

    // ---------- DELETE (toolbar) ----------
    function markRowDeleted($row) {
        $row.addClass("is-deleted")
            .attr("data-removed", "1")
            .slideUp(120, function () {
                $(this).remove();
            });
    }
    function afterRowsDeleted() {
        setTimeout(() => {
            const ok = runDistribution({ showSuccess: false });
            if (ok)
                Swal.fire({
                    icon: "success",
                    title: "Recalculated after deletion",
                    timer: 900,
                    showConfirmButton: false,
                });
            else markDistributionDirty();
        }, 140);
    }
    $(document).on("click", CFG.bulkDeleteBtn, function (e) {
        const { key, $scope } = detectTabScope($(this));
        const rowSel =
            key === "grn" ? CFG.grn.row : key === "po" ? CFG.expense.row : null;
        if (!rowSel) return;
        const $rows = $scope.find(rowSel).filter(function () {
            return $(this).find('input[type="checkbox"]:checked').length;
        });
        if ($rows.length) {
            e.preventDefault();
            $rows.each(function () {
                markRowDeleted($(this));
            });
            afterRowsDeleted();

            setTimeout(() => {
                setTableCalculation();
            }, 300);
        }
    });

    // ---------- INIT ----------
    $(function () {
        distributedOK = false;
        setSubmitState();
        wireDirtyHandlers();
        $(".submit-button").attr("type", "button");

        if (PAGE_MODE === "edit") {
            hydrateAllRowsOnEdit(); // make Show work immediately & ensure hidden allocations are filled
        }
    });

    // === REPLACE your current submit-intercept handler with this ===
    let _eaSubmitting = false;

    $(document).on("click.ea", ".submit-button", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (_eaSubmitting) return;

        const $btn = $(this);
        const $form = $btn.closest("form");

        // 0) Must have at least one GRN item
        const grnCount =
            typeof collectGrnItems === "function"
                ? collectGrnItems().length
                : 0;

        if (grnCount === 0) {
            Swal.fire({
                icon: "error",
                title: "Add GRN items",
                text: "Please add at least one GRN item in the GRN tab before submitting.",
            });
            return;
        }

        _eaSubmitting = true;

        // 1) Run the same distribution logic (local function, not window.*)
        let ok = true;
        try {
            ok = runDistribution({ showSuccess: false });
        } catch (err) {
            ok = false;
        }

        if (!ok) {
            _eaSubmitting = false;
            return;
        }

        // 2) Ensure the clicked button's name/value are sent (draft/submitted)
        const btnName = $btn.attr("name") || "action";
        const btnValue = $btn.val() || $btn.attr("value") || "";
        $form
            .find("input._ea-submitter-helper[name='" + btnName + "']")
            .remove();
        $("<input>", {
            type: "hidden",
            class: "_ea-submitter-helper",
            name: btnName,
            value: btnValue,
        }).appendTo($form);

        // 3) requestSubmit(el) requires an enabled submit button
        const originalType = ($btn.attr("type") || "button").toLowerCase();
        const wasDisabled = !!$btn.prop("disabled");

        $btn.prop("disabled", false);
        $btn.attr("type", "submit");

        try {
            if ($form[0] && typeof $form[0].requestSubmit === "function") {
                $form[0].requestSubmit($btn[0]);
            } else if ($form[0] && typeof $form[0].submit === "function") {
                $form[0].submit();
            } else {
                $form.trigger("submit");
            }
        } finally {
            // If navigation didn’t happen (client-side validation), restore
            setTimeout(() => {
                if (document.body.contains($btn[0])) {
                    $btn.attr("type", originalType);
                    $btn.prop("disabled", wasDisabled);
                }
                _eaSubmitting = false;
            }, 200);
        }
    });

    // ================== Show popup (animated) ==================
    function renderBreakupPopup($tr, byName, total) {
        const value = toNum(
            $tr.find(CFG.grn.value).val?.() ?? $tr.find(CFG.grn.value).text()
        );
        const landed = round(value + total);
        const name = $tr.data("eaBreakup")?.label || getItemLabel($tr);

        const rowsHtml = Object.keys(byName || {}).length
            ? Object.keys(byName)
                  .sort()
                  .map((k) => {
                      const amt = byName[k] ?? 0;
                      return `<div class="ea-row"><div class="ea-exp-name">${esc(
                          k
                      )}</div><div class="ea-exp-amt">${amt.toLocaleString(
                          undefined,
                          {
                              minimumFractionDigits: CFG.scale,
                              maximumFractionDigits: CFG.scale,
                          }
                      )}</div></div>`;
                  })
                  .join("")
            : `<div class="ea-empty">No expenses allocated</div>`;

        const html = `
        <style>
          .ea-wrap{font-size:.95rem}
          .ea-header{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
          .ea-title{font-weight:700;font-size:1.05rem}
          .ea-sub{color:#6c757d}
          .ea-card{border:1px solid #e9ecef;border-radius:12px;padding:12px;background:linear-gradient(180deg,#fff,#fafbfc)}
          .ea-section{margin-top:10px}
          .ea-label{color:#6c757d;font-size:.85rem;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
          .ea-row{display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-radius:10px;background:#fff;margin-bottom:6px;border:1px solid #f1f3f5}
          .ea-exp-name{font-weight:500}
          .ea-exp-amt{font-variant-numeric:tabular-nums;font-weight:600}
          .ea-total,.ea-landed{display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:12px;margin-top:8px}
          .ea-total{background:#f6ffed;border:1px solid #d9f7be}
          .ea-landed{background:#eef7ff;border:1px solid #cfe7ff}
        </style>
        <div class="ea-wrap">
          <div class="ea-header"><div class="ea-title">Expense Allocation</div><div class="ea-sub">• ${esc(
              name
          )}</div></div>
          <div class="ea-card">
            <div class="ea-section"><div class="ea-label">Item Value</div><div class="ea-row"><div class="ea-exp-name">Value</div><div class="ea-exp-amt">${value.toLocaleString(
                undefined,
                {
                    minimumFractionDigits: CFG.scale,
                    maximumFractionDigits: CFG.scale,
                }
            )}</div></div></div>
            <div class="ea-section"><div class="ea-label">EXPENSES</div>${rowsHtml}</div>
            <div class="ea-section"><div class="ea-total"><div>Allocated Cost</div><div>${(
                total || 0
            ).toLocaleString(undefined, {
                minimumFractionDigits: CFG.scale,
                maximumFractionDigits: CFG.scale,
            })}</div></div></div>
            <div class="ea-section"><div class="ea-landed"><div>Landed Cost</div><div>${landed.toLocaleString(
                undefined,
                {
                    minimumFractionDigits: CFG.scale,
                    maximumFractionDigits: CFG.scale,
                }
            )}</div></div></div>
          </div>
        </div>`;
        Swal.fire({
            title: "",
            html,
            width: 720,
            showCloseButton: true,
            confirmButtonText: "Close",
        });
    }

    // ---------- Hydration for EDIT ----------
    function buildExpenseIdToName() {
        const map = {};
        const { list: exps } = collectExpenses({ requireAllocType: false });
        exps.forEach((e) => {
            const id = (e.id ?? "").toString().trim();
            if (id) map[id] = e.name;
        });
        return map;
    }

    function hydrateBreakupFromHiddenForRow($tr, idToNameMap = null) {
        const $h = $tr.find("input.ea-alloc[name*='[allocations][]']");
        if (!$h.length) return false;

        idToNameMap ||= buildExpenseIdToName();

        const byName = {};
        let total = 0;
        let found = false;
        $h.each(function () {
            const txt = $(this).val() || "";
            if (!txt) return;
            let obj = null;
            try {
                obj = JSON.parse(txt);
            } catch {
                obj = null;
            }
            if (!obj) return;
            const amt = toNum(obj.amout ?? obj.amount ?? 0);
            const id = (obj.id ?? "").toString().trim();
            const name =
                idToNameMap[id] ||
                obj.name ||
                (id ? `Expense ${id}` : "Expense");
            byName[name] = round((byName[name] || 0) + amt);
            total += amt;
            found = true;
        });

        if (!found) return false;

        const label = getItemLabel($tr);
        $tr.data("eaBreakup", { byName, total: round(total), label });

        // (optional) sync allocated/landed display if needed
        const $alloc = $tr.find(CFG.grn.allocated);
        const $landed = $tr.find(CFG.grn.landed);
        const value = toNum(
            $tr.find(CFG.grn.value).val?.() ?? $tr.find(CFG.grn.value).text()
        );
        if ($alloc.length)
            $alloc.is("input,textarea")
                ? $alloc.val(total.toFixed(CFG.scale))
                : $alloc.text(total.toFixed(CFG.scale));
        if ($landed.length)
            $landed.is("input,textarea")
                ? $landed.val((value + total).toFixed(CFG.scale))
                : $landed.text((value + total).toFixed(CFG.scale));

        return true;
    }

    function recomputeBreakupForRowFromCurrent($tr) {
        const items = collectGrnItems();
        const { list: exps, missing } = collectExpenses({
            requireAllocType: true,
        });
        if (missing.length || !exps.length) return false;

        const totals = {
            QTY: sum(items, "qty"),
            VALUE: sum(items, "value"),
            WEIGHT: sum(items, "weight"),
            VOLUME: sum(items, "volume"),
        };
        const rowObj = items.find((it) => it.$tr.is($tr));
        if (!rowObj) return false;

        const keyMap = {
            QTY: "qty",
            VALUE: "value",
            WEIGHT: "weight",
            VOLUME: "volume",
        };
        const byName = {};
        let total = 0;

        exps.filter((e) => e.amount > 0 && totals[e.type] > 0).forEach((e) => {
            const share =
                (toNum(rowObj[keyMap[e.type]]) / totals[e.type]) * e.amount;
            const amt = round(share);
            byName[e.name] = amt;
            total += amt;
        });

        $tr.data("eaBreakup", {
            byName,
            total: round(total),
            label: getItemLabel($tr),
        });

        // Write hidden (EDIT has expense ids)
        const pairs = exps
            .filter((e) => e.amount > 0 && totals[e.type] > 0)
            .map((e) => ({ id: e.id || "", amout: byName[e.name] || 0 }));
        writeAllocationsHiddenInputs($tr, pairs);

        // sync allocated/landed UI
        const $alloc = $tr.find(CFG.grn.allocated);
        const $landed = $tr.find(CFG.grn.landed);
        const value = toNum(
            $tr.find(CFG.grn.value).val?.() ?? $tr.find(CFG.grn.value).text()
        );
        if ($alloc.length)
            $alloc.is("input,textarea")
                ? $alloc.val(total.toFixed(CFG.scale))
                : $alloc.text(total.toFixed(CFG.scale));
        if ($landed.length)
            $landed.is("input,textarea")
                ? $landed.val((value + total).toFixed(CFG.scale))
                : $landed.text((value + total).toFixed(CFG.scale));

        return true;
    }

    function hydrateAllRowsOnEdit() {
        let any = false;
        const idToName = buildExpenseIdToName();
        collectGrnItems().forEach((it) => {
            if (hydrateBreakupFromHiddenForRow(it.$tr, idToName)) {
                any = true;
                return;
            }
            if (recomputeBreakupForRowFromCurrent(it.$tr)) any = true;
        });
        if (any) {
            distributedOK = true;
            setSubmitState();
        }
        return any;
    }

    // ---------- Show popup (works on create + edit) ----------
    function showRowDistributionPopup($tr) {
        // Try cached
        let stored = $tr.data("eaBreakup");

        // Edit: hydrate from hidden or recompute if needed
        if (!stored || !stored.byName || !Object.keys(stored.byName).length) {
            if (PAGE_MODE === "edit") {
                if (hydrateBreakupFromHiddenForRow($tr))
                    stored = $tr.data("eaBreakup");
                else if (recomputeBreakupForRowFromCurrent($tr))
                    stored = $tr.data("eaBreakup");
            }
        }
        // Create or still missing → recompute on the fly
        if (!stored || !stored.byName || !Object.keys(stored.byName).length) {
            if (recomputeBreakupForRowFromCurrent($tr))
                stored = $tr.data("eaBreakup");
        }
        if (!stored || !stored.byName || !Object.keys(stored.byName).length) {
            return Swal.fire({
                icon: "info",
                title: "Distribution not available",
                text: "Please run allocation or add valid expenses/basis.",
            });
        }
        renderBreakupPopup($tr, stored.byName, stored.total);
    }

    $(document)
        .off("click.ea-show")
        .on(
            "click.ea-show",
            ".showDistBreakup, [data-action='show-breakup'], [data-ea='show-breakup'], .btn-show-breakup, .alloc-show",
            function (e) {
                e.preventDefault();
                const $tr = $(this).closest(CFG.grn.row).length
                    ? $(this).closest(CFG.grn.row)
                    : $(this).closest("tr");
                if ($tr.length) showRowDistributionPopup($tr);
            }
        );

    // expose (optional)
    window._ea = {
        runDistribution,
        collectGrnItems,
        collectExpenses,
        showRowDistributionPopup,
        hydrateAllRowsOnEdit,
    };
})();
