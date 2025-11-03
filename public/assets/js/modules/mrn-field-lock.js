/**
 * Lock a Bootstrap modal into read-only mode.
 * Usage:
 *   lockReadOnlyModal('#itemBatchesModal');
 *   lockReadOnlyModal(document.getElementById('assetDetailModal'));
 *   lockReadOnlyModal($('.modal-class')); // jQuery object also fine
 *
 * Options (all optional):
 *   { keepClose: true, extraBlock: ['.add-batch-row-header', '.delete-batch-row-header', '.remove-batch-row'] }
 */
window.lockReadOnlyModal = function (modalRef, opts) {
    const opt = Object.assign(
        {
            keepClose: true,
            // extra selectors to hard-block (e.g., +/− icons)
            extraBlock: [
                ".add-batch-row-header",
                ".delete-batch-row-header",
                ".remove-batch-row",
            ],
        },
        opts || {}
    );

    // resolve to a jQuery object
    const $m = modalRef instanceof jQuery ? modalRef : $(modalRef);
    if (!$m.length) return;

    const allowClose = opt.keepClose
        ? '[data-bs-dismiss="modal"], .btn-close'
        : "";

    // disable inputs/selects/textarea
    $m.find("input, textarea, select")
        .prop("readonly", true)
        .prop("disabled", true)
        .addClass("bg-light");

    // Select2 (if used)
    $m.find(".select2, .select2-container").each(function () {
        $(this).closest(".form-group").find("select").prop("disabled", true);
        $(this)
            .find(".select2-selection")
            .addClass("disabled")
            .css("pointer-events", "none")
            .attr("tabindex", "-1");
    });

    // disable buttons/links (keep Close if opted)
    $m.find("button, .btn, a")
        .filter(function () {
            return !allowClose || !$(this).is(allowClose);
        })
        .addClass("disabled")
        .attr({ "aria-disabled": "true", tabindex: "-1" })
        .prop("disabled", true)
        .off(".viewlock")
        .on("click.viewlock", function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
        });

    // explicitly block extra actions
    if (opt.extraBlock && opt.extraBlock.length) {
        $m.find(opt.extraBlock.join(","))
            .addClass("disabled")
            .attr({ "aria-disabled": "true", tabindex: "-1" })
            .prop("disabled", true)
            .off(".viewlock")
            .on("click.viewlock", function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
            });
    }

    // watch for dynamic rows/inputs added after open
    if ("MutationObserver" in window) {
        // disconnect any previous observer we attached
        $m.data("lockMo")?.disconnect();
        const mo = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                m.addedNodes &&
                    m.addedNodes.forEach((node) => {
                        if (!(node instanceof HTMLElement)) return;
                        const $n = $(node);

                        $n.addBack("input, textarea, select")
                            .filter("input, textarea, select")
                            .prop("readonly", true)
                            .prop("disabled", true)
                            .addClass("bg-light");

                        $n.addBack("button, .btn, a")
                            .filter(function () {
                                return !allowClose || !$(this).is(allowClose);
                            })
                            .addClass("disabled")
                            .attr({ "aria-disabled": "true", tabindex: "-1" })
                            .prop("disabled", true)
                            .off(".viewlock")
                            .on("click.viewlock", function (e) {
                                e.preventDefault();
                                e.stopImmediatePropagation();
                            });

                        if (opt.extraBlock && opt.extraBlock.length) {
                            $n.addBack(opt.extraBlock.join(","))
                                .addClass("disabled")
                                .attr({
                                    "aria-disabled": "true",
                                    tabindex: "-1",
                                })
                                .prop("disabled", true)
                                .off(".viewlock")
                                .on("click.viewlock", function (e) {
                                    e.preventDefault();
                                    e.stopImmediatePropagation();
                                });
                        }
                    });
            });
        });
        mo.observe($m[0], { childList: true, subtree: true });
        $m.data("lockMo", mo);
    }
};

/* -------- optional: auto-hook by selector or title text --------
   Call once per page if you want automatic locking as soon as the modal opens.
   Example:
     initReadOnlyModalAuto([
       '#itemBatchesModal',
       '#assetDetailModal',
       '.item-batches-modal',
       { title: /Item Batches/i },
       { title: /Assets Detail/i }
     ]);
*/
window.initReadOnlyModalAuto = function (matchers, options) {
    $(document).on("shown.bs.modal", ".modal", function () {
        const $m = $(this);
        const title = ($m.find(".modal-title").text() || "").trim();
        const ok = (matchers || []).some((m) =>
            typeof m === "string"
                ? $m.is(m)
                : m && m.title instanceof RegExp
                ? m.title.test(title)
                : false
        );
        if (ok) window.lockReadOnlyModal($m, options);
    });
};
