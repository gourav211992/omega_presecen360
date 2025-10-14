// ===== Expense Allocation for Two Tabs: PO (Expenses) & GRN =====
(function () {
    // ---------- CONFIG ----------
    const CFG = {
        // Tab wrappers (parents containing each grid)
        tabs: {
            po: "#poItems", // PO / Expenses tab container
            grn: "#grnItems", // GRN tab container
        },

        distributeBtn: ".distributeBtn, .allocateBtn",
        submitBtn: ".submitBtn",

        // PO (EXPENSES) ROW + columns
        expense: {
            row: ".expense-row",
            name: ".expense-name",
            amount: ".expense-amount",
            type: ".alloc-type", // QTY / VALUE / WEIGHT / VOLUME
        },

        // GRN ROW + columns
        grn: {
            row: ".grn-row",
            qty: ".grn-qty",
            value: ".grn-value",
            weight: ".grn-weight",
            volume: ".grn-volume",
            allocated: ".allocated-exp",
            landed: ".landed-cost",
            itemName: ".item-name", // optional
        },

        // Delete buttons (bulk or per-row)
        bulkDeleteBtn: ".delete-po-items, .delete-grn-items",
        perRowDeleteBtn: ".delete-po-items, .delete-grn-items",

        scale: 2,
    };

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

    // Only exclude soft-deleted/template rows; allow hidden (other tab)
    function rowIsActive($r, { includeHidden = true } = {}) {
        const visibleOK = includeHidden ? true : $r.is(":visible");
        const softRemoved =
            $r.hasClass("is-deleted") ||
            String($r.attr("data-removed")) === "1" ||
            $r.data("removed") === 1;
        const isTemplate = $r.is(
            '[data-template="1"], .template-row, .d-template'
        );
        return visibleOK && !softRemoved && !isTemplate;
    }

    // ---------- TAB SCOPES ----------
    function $poScope() {
        return $(CFG.tabs.po).length ? $(CFG.tabs.po) : $(document.body);
    }
    function $grnScope() {
        return $(CFG.tabs.grn).length ? $(CFG.tabs.grn) : $(document.body);
    }

    function detectTabScope($from) {
        if ($from.closest(CFG.tabs.po).length)
            return { key: "po", $scope: $from.closest(CFG.tabs.po) };
        if ($from.closest(CFG.tabs.grn).length)
            return { key: "grn", $scope: $from.closest(CFG.tabs.grn) };
        // Fallback to active tab if using .tab-pane.active
        const $active = $(".tab-pane.active");
        if ($active.length) {
            if ($active.is(CFG.tabs.po)) return { key: "po", $scope: $active };
            if ($active.is(CFG.tabs.grn))
                return { key: "grn", $scope: $active };
        }
        // Last resort: default to the closest table
        return {
            key: "unknown",
            $scope: $from.closest("table").length
                ? $from.closest("table")
                : $(document.body),
        };
    }

    // ---------- LABEL ----------
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

    // ---------- COLLECTORS ----------
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
                list.push({ $tr, name, amount, type: valid ? raw : null });
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

    // ---------- ALLOCATION ----------
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

    function applyAllocationsToDOM(items, breakdown) {
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
            it.$tr.data("eaBreakup", { ...row, label: it.label });
        }
    }

    // ---------- SUBMIT GUARD ----------
    let lastSignature = null;
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
        // Any change in either tab invalidates distribution
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

    // ---------- CLEAR ----------
    function clearAllocations() {
        const items = collectGrnItems();
        for (const it of items) {
            const base = round(toNum(it.value));
            const $alloc = it.$tr.find(CFG.grn.allocated);
            const $landed = it.$tr.find(CFG.grn.landed);
            if ($alloc.length) {
                if ($alloc.is("input,textarea")) $alloc.val(0);
                else $alloc.text((0).toFixed(CFG.scale));
            }
            if ($landed.length) {
                if ($landed.is("input,textarea"))
                    $landed.val(base.toFixed(CFG.scale));
                else $landed.text(base.toFixed(CFG.scale));
            }
            it.$tr.removeData("eaBreakup");
        }
    }

    // ---------- DISTRIBUTION (Direct Apply) ----------
    function buildSignature(items, expenses) {
        const iSig = items
            .map((i) => `${i.id}:${i.qty}:${i.value}:${i.weight}:${i.volume}`)
            .join("|");
        const eSig = expenses
            .map((e) => `${e.name}:${e.amount}:${e.type}`)
            .join("|");
        return `${iSig}#${eSig}`;
    }

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
            const msg = missing
                .map((m) => `• Row ${m.index} — ${esc(m.name)}`)
                .join("<br>");
            Swal.fire({
                icon: "error",
                title: "Allocation Type required",
                html: `Select Allocation Type (<b>QTY</b>, <b>VALUE</b>, <b>WEIGHT</b>, or <b>VOLUME</b>)`,
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
                    ([type, names]) =>
                        `<div style="margin:.5rem 0">• <b>${
                            pretty[type]
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

        // Compute & apply
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
        applyAllocationsToDOM(items, breakdown);

        lastSignature = buildSignature(items, expenses);
        distributedOK = true;
        setSubmitState();

        if (showSuccess) {
            setTimeout(() => {
                setTableCalculation();
            }, 2000);
            Swal.fire({
                icon: "success",
                title: "Allocated successfully",
                timer: 1100,
                showConfirmButton: false,
            });
        }

        setTableCalculation();
        return true;
    }

    // ---------- CLICK: DISTRIBUTE ----------
    $(document).on("click", CFG.distributeBtn, function () {
        runDistribution({ showSuccess: true });
    });

    // ---------- DELETE (Scoped to Tab) ----------
    function markRowDeleted($row) {
        $row.addClass("is-deleted")
            .attr("data-removed", "1")
            .slideUp(120, function () {
                $(this).remove();
            });
    }

    function afterRowsDeleted(tabKey) {
        setTimeout(() => {
            try {
                recomputePoGrnMatrix();
            } catch (e) {}
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

    // Per-row delete inside a tab: deletes only the row of that tab
    $(document).on("click", CFG.perRowDeleteBtn, function (e) {
        e.preventDefault();
        const $btn = $(this);
        const { key, $scope } = detectTabScope($btn);
        let rowSel =
            key === "grn" ? CFG.grn.row : key === "po" ? CFG.expense.row : null;
        const $row = $btn.closest(rowSel || "tr");
        if ($row.length) {
            markRowDeleted($row);
            afterRowsDeleted(key);
        }
    });

    // Bulk delete: only checked rows in the tab you clicked in
    $(document).on("click", CFG.bulkDeleteBtn, function (e) {
        const $btn = $(this);
        const { key, $scope } = detectTabScope($btn);
        let rowSel =
            key === "grn" ? CFG.grn.row : key === "po" ? CFG.expense.row : null;
        if (!rowSel) return; // unknown scope, ignore

        const $rows = $scope.find(rowSel).filter(function () {
            return $(this).find('input[type="checkbox"]:checked').length;
        });

        if ($rows.length) {
            e.preventDefault();
            $rows.each(function () {
                markRowDeleted($(this));
            });
            afterRowsDeleted(key);
        }
    });

    // Optional: observer limited to both tab wrappers (if other scripts remove rows)
    const obsTargets = [$poScope()[0], $grnScope()[0]].filter(Boolean);
    if (obsTargets.length) {
        const obs = new MutationObserver((muts) => {
            let touched = false;
            for (const m of muts) {
                (m.removedNodes || []).forEach((n) => {
                    if (n.nodeType !== 1) return;
                    const el = /** @type {Element} */ (n);
                    if (
                        el.matches?.(CFG.grn.row) ||
                        el.querySelector?.(CFG.grn.row) ||
                        el.matches?.(CFG.expense.row) ||
                        el.querySelector?.(CFG.expense.row)
                    ) {
                        touched = true;
                    }
                });
            }
            if (touched) afterRowsDeleted("unknown");
        });
        obsTargets.forEach((t) =>
            obs.observe(t, { childList: true, subtree: true })
        );
    }

    // ---------- OPTIONAL: PO⇄GRN proportional matrix ----------
    window.lastPoGrnMatrix = null;
    function recomputePoGrnMatrix() {
        // PO items = expenses rows with a qty? If you have a real PO qty column, plug it here.
        // For now, derive PO qty as the SUM of all expenses amount normalized by GRN total? (skip)
        // Simple: maintain mapping only from GRN (by qty) proportions for each expense total.
        const grn = collectGrnItems();
        const totalGrnQty = sum(grn, "qty");

        const matrix = {}; // matrix[expenseName] = { grnId: amountShare }
        const { list: exps } = collectExpenses({ requireAllocType: false });

        for (const e of exps.filter((x) => x.amount > 0)) {
            matrix[e.name] = {};
            if (totalGrnQty <= 0) continue;
            let running = 0;
            for (let i = 0; i < grn.length; i++) {
                const g = grn[i];
                const share = round((toNum(g.qty) / totalGrnQty) * e.amount);
                matrix[e.name][g.id ?? i] = share;
                running += share;
            }
            const lastKey = grn.length
                ? grn[grn.length - 1].id ?? grn.length - 1
                : null;
            if (lastKey != null) {
                const drift = round(e.amount - running);
                matrix[e.name][lastKey] = round(
                    (matrix[e.name][lastKey] || 0) + drift
                );
            }
        }

        window.lastPoGrnMatrix = { matrix, totals: { totalGrnQty } };
    }

    // ---------- INIT ----------
    $(function () {
        distributedOK = false;
        setSubmitState();
        wireDirtyHandlers();
    });

    // (Optional) expose for debugging
    window._ea = { runDistribution, collectGrnItems, collectExpenses };

    // ================== Show Distribution Breakup (animated popup) ==================
    function renderBreakupPopup($tr, byName, total) {
        const name = $tr.data("eaBreakup")?.label || getItemLabel($tr);
        const value = toNum(
            $tr.find(CFG.grn.value).val?.() ?? $tr.find(CFG.grn.value).text()
        );
        const landed = round(value + total);

        const rowsHtml = Object.keys(byName || {}).length
            ? Object.keys(byName)
                  .sort()
                  .map((k) => {
                      const amt = byName[k] ?? 0;
                      return `
            <div class="ea-row">
              <div class="ea-exp-name">${esc(k)}</div>
              <div class="ea-exp-amt ea-count" data-amt="${amt.toFixed(
                  CFG.scale
              )}">${(0).toFixed(CFG.scale)}</div>
            </div>`;
                  })
                  .join("")
            : `<div class="ea-empty">No expenses allocated</div>`;

        const html = `
      <style>
        .ea-wrap{font-size:.95rem}
        .ea-header{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;animation:ea-slide .35s ease-out both}
        .ea-title{font-weight:700;font-size:1.05rem}
        .ea-sub{color:#6c757d}
        .ea-card{border:1px solid #e9ecef;border-radius:12px;padding:12px;background:linear-gradient(180deg,#fff,#fafbfc);box-shadow:0 1px 2px rgba(0,0,0,.04);animation:ea-fade .35s .05s ease-out both}
        .ea-section{margin-top:10px}
        .ea-label{color:#6c757d;font-size:.85rem;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
        .ea-row{display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-radius:10px;background:#fff;margin-bottom:6px;border:1px solid #f1f3f5}
        .ea-exp-name{font-weight:500}
        .ea-exp-amt{font-variant-numeric:tabular-nums;font-weight:600}
        .ea-pill{display:inline-block;padding:6px 10px;border-radius:999px;background:#f8f9fa;border:1px solid #eef1f4;font-weight:600;font-variant-numeric:tabular-nums}
        .ea-total{display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:12px;background:#f6ffed;border:1px solid #d9f7be;font-weight:700;margin-top:8px}
        .ea-landed{display:flex;justify-content:space-between;align-items:center;padding:14px;border-radius:12px;background:#eef7ff;border:1px solid #cfe7ff;font-weight:800;margin-top:8px}
        .ea-count{opacity:0}
        .ea-kv{display:flex;justify-content:space-between;align-items:center}
        @keyframes ea-slide{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}
        @keyframes ea-fade{from{opacity:0}to{opacity:1}}
      </style>
  
      <div class="ea-wrap">
        <div class="ea-header">
          <div class="ea-title">Expense Allocation</div>
          <div class="ea-sub">• ${esc(name)}</div>
        </div>
  
        <div class="ea-card">
          <div class="ea-section">
            <div class="ea-kv">
              <div class="ea-sub">Item Value</div>
              <div class="ea-pill ea-count" data-amt="${value.toFixed(
                  CFG.scale
              )}">${(0).toFixed(CFG.scale)}</div>
            </div>
          </div>
  
          <div class="ea-section">
            <div class="ea-label">Expenses</div>
            ${rowsHtml}
          </div>
  
          <div class="ea-section">
            <div class="ea-total">
              <div>Total Allocation</div>
              <div class="ea-count" data-amt="${total.toFixed(
                  CFG.scale
              )}">${(0).toFixed(CFG.scale)}</div>
            </div>
          </div>
  
          <div class="ea-section">
            <div class="ea-landed">
              <div>Landed Cost</div>
              <div class="ea-count" data-amt="${landed.toFixed(
                  CFG.scale
              )}">${(0).toFixed(CFG.scale)}</div>
            </div>
          </div>
        </div>
      </div>
    `;

        Swal.fire({
            title: "",
            html,
            width: 720,
            showCloseButton: true,
            confirmButtonText: "Close",
            didOpen: (el) => {
                const els = el.querySelectorAll(".ea-count");
                els.forEach((node) => {
                    const target =
                        parseFloat(node.getAttribute("data-amt")) || 0;
                    const duration = 700,
                        start = performance.now();
                    node.style.opacity = "1";
                    const step = (t) => {
                        const p = Math.min(1, (t - start) / duration);
                        const val = target * p;
                        node.textContent = val.toLocaleString(undefined, {
                            minimumFractionDigits: CFG.scale,
                            maximumFractionDigits: CFG.scale,
                        });
                        if (p < 1) requestAnimationFrame(step);
                    };
                    requestAnimationFrame(step);
                });
            },
        });
    }

    function showRowDistributionPopup($tr) {
        // Prefer stored breakup (from applyAllocationsToDOM)
        const stored = $tr.data("eaBreakup");
        if (stored && stored.byName && Object.keys(stored.byName).length) {
            return renderBreakupPopup($tr, stored.byName, stored.total);
        }

        // Fallback: recompute from current tables
        const items = collectGrnItems();
        const { list: exps, missing } = collectExpenses({
            requireAllocType: true,
        });
        if (missing.length || !exps.length) {
            return Swal.fire({
                icon: "info",
                title: "Distribution not available",
                text: "Please run expense distribution first (and select allocation types).",
            });
        }
        const totals = {
            QTY: sum(items, "qty"),
            VALUE: sum(items, "value"),
            WEIGHT: sum(items, "weight"),
            VOLUME: sum(items, "volume"),
        };
        const rowObj = items.find((it) => it.$tr.is($tr));
        if (!rowObj) return;

        const keyMap = {
            QTY: "qty",
            VALUE: "value",
            WEIGHT: "weight",
            VOLUME: "volume",
        };
        const byName = {};
        let total = 0;
        for (const e of exps.filter(
            (e) => e.amount > 0 && totals[e.type] > 0
        )) {
            const share =
                (toNum(rowObj[keyMap[e.type]]) / totals[e.type]) * e.amount;
            const amt = round(share);
            byName[e.name] = amt;
            total += amt;
        }
        renderBreakupPopup($tr, byName, total);
    }

    // Delegate click for GRN tab rows
    $(document).on(
        "click",
        ".showDistBreakup, [data-action='show-breakup']",
        function (e) {
            e.preventDefault();
            const $tr = $(this).closest(CFG.grn.row);
            if ($tr.length) showRowDistributionPopup($tr);
        }
    );

    let _eaSubmitting = false;

    $(document).on("click.ea", ".submit-button", function (e) {
        $(".submit-button").attr("type", "button");
        e.preventDefault();
        e.stopImmediatePropagation(); // avoid any other .distributeBtn handlers attached to these buttons

        if (_eaSubmitting) return;

        const $btn = $(this);
        const $form = $btn.closest("form");

        _eaSubmitting = true;
        $btn.prop("disabled", true).addClass("disabled");

        // 1) Run same distribution flow as Allocate
        let ok = true;
        if (typeof window.runDistribution === "function") {
            ok = window.runDistribution({ showSuccess: false }); // show same validation popups, but no success toast
        }

        if (!ok) {
            // Distribution failed; do not submit
            _eaSubmitting = false;
            $btn.prop("disabled", false).removeClass("disabled");
            return;
        }

        // 2) Distribution succeeded → submit this exact button
        // ensure the button's name/value (action=draft/submitted) reach server
        const btnName = $btn.attr("name") || "action";
        const btnValue = $btn.val() || $btn.attr("value") || "";

        // remove any previous helper to avoid duplicates
        $form
            .find("input._ea-submitter-helper[name='" + btnName + "']")
            .remove();

        $("<input>", {
            type: "hidden",
            class: "_ea-submitter-helper",
            name: btnName,
            value: btnValue,
        }).appendTo($form);

        // flip to submit **only now** and submit the form
        $btn.attr("type", "submit").attr("data-ea-bypass", "1");

        try {
            // Prefer requestSubmit so submit event + HTML5 validation + submitter are preserved
            if ($form[0] && typeof $form[0].requestSubmit === "function") {
                $form[0].requestSubmit($btn[0]);
            } else if ($form[0] && typeof $form[0].submit === "function") {
                // Fallback (may bypass HTML5 client validation, server should still validate)
                $form[0].submit();
            } else {
                $form.trigger("submit");
            }
        } finally {
            // In case navigation doesn't happen (validation upstream), restore after a brief delay
            setTimeout(() => {
                _eaSubmitting = false;
                $btn.prop("disabled", false)
                    .removeClass("disabled")
                    .attr("type", "submit")
                    .removeAttr("data-ea-bypass");
            }, 1200);
        }
    });
})();
