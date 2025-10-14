selectedCostCenterId = null;
/*Approve modal*/
$(document).on("click", "#approved-button", (e) => {
    let actionType = "approve";
    $("#approveModal").find("#action_type").val(actionType);
    $("#approveModal #popupTitle").text("Approve Application");
    $("#approveModal").modal("show");
});

/*Reject modal*/
$(document).on("click", "#reject-button", (e) => {
    let actionType = "reject";
    $("#approveModal #popupTitle").text("Reject Application");
    $("#approveModal").find("#action_type").val(actionType);
    $("#approveModal").modal("show");
});

/*Tbl row highlight*/
$(document).on("click", ".mrntableselectexcel tr", (e) => {
    $(e.target.closest("tr"))
        .addClass("trselected")
        .siblings()
        .removeClass("trselected");
});

$(document).on("keydown", function (e) {
    if (e.which == 38) {
        /*bottom to top*/
        $(".trselected")
            .prev("tr")
            .addClass("trselected")
            .siblings()
            .removeClass("trselected");
    } else if (e.which == 40) {
        /*top to bottom*/
        $(".trselected")
            .next("tr")
            .addClass("trselected")
            .siblings()
            .removeClass("trselected");
    }
    // if($('.trselected').length) {
    //   $('html, body').scrollTop($('.trselected').offset().top - 200);
    // }
});

$(document).on("change", ".header_store_id", function () {
    const selectedStoreId = $(this).val();
    if (selectedStoreId) {
        getCostCenters(selectedStoreId);
    }
});

// 2. On page load: trigger if already selected
const selectedStoreId = $(".header_store_id").val();
if (selectedStoreId) {
    getCostCenters(selectedStoreId);
}

// Get Cost Centers
function getCostCenters(storeLocationId) {
    $("#cost_center_div").hide(); // Hide by default

    $.ajax({
        url: "/get-cost-centers",
        method: "GET",
        dataType: "json",
        data: {
            locationId: storeLocationId,
        },
        success: function (data) {
            if (Array.isArray(data) && data.length > 0) {
                let options = "";

                data.forEach(function (costcenter) {
                    const selected =
                        costcenter.id == selectedCostCenterId ? "selected" : "";
                    options += `<option value="${costcenter.id}" ${selected}>${costcenter.name}</option>`;
                });

                $(".cost_center").html(options);
                $("#cost_center_div").show();
            } else {
                $(".cost_center").empty();
                $("#cost_center_div").hide();
            }
        },
        error: function (xhr) {
            Swal.fire({
                title: "Error!",
                text:
                    xhr?.responseJSON?.message ||
                    "Failed to load cost centers.",
                icon: "error",
            });
        },
    });
}

/*Check box check and uncheck*/
$(document).on("change", "#itemTable > thead .form-check-input", (e) => {
    if (e.target.checked) {
        $("#itemTable > tbody .form-check-input").each(function () {
            $(this).prop("checked", true);
        });
    } else {
        $("#itemTable > tbody .form-check-input").each(function () {
            $(this).prop("checked", false);
        });
    }
});

$(document).on("change", "#itemTable > tbody .form-check-input", (e) => {
    if (!$("#itemTable > tbody .form-check-input:not(:checked)").length) {
        $("#itemTable > thead .form-check-input").prop("checked", true);
    } else {
        $("#itemTable > thead .form-check-input").prop("checked", false);
    }
});

/*Attribute on change*/
$(document).on("change", '[name*="comp_attribute"]', (e) => {
    let closestTr = e.target.closest("tr");
    let rowCount = e.target
        .closest("tr")
        .querySelector('[name*="row_count"]').value;
    let attrGroupId = e.target.getAttribute("data-attr-group-id");
    $(
        `[name="components[${rowCount}][attr_group_id][${attrGroupId}][attr_name]"]`
    ).val(e.target.value);
    // closestTr = $(`[name="components[${rowCount}][attr_group_id][${attrGroupId}][attr_name]"]`).closest('tr');
    // getItemDetail(closestTr);
    qtyEnabledDisabled();
    setSelectedAttribute(rowCount);
});

// Check Negative Values
let oldValue;
$(document).on("focus", ".checkNegativeVal", function (e) {
    oldValue = e.target.value; // Store the old value when the field gains focus
});

/*qty on change*/
$(document).on("change", "[name*='accepted_qty']", async function (e) {
    const $tr = $(e.target).closest("tr");
    const $qtyInput = $(e.target);
    const acceptedQty = parseFloat($qtyInput.val()) || 0;

    const dataIndex = $tr.attr("data-index");
    const itemId = $tr.find("[name*='item_id']").val();

    $qtyInput.val(acceptedQty.toFixed(6));
    checkDuplicateObjects($qtyInput);

    if (acceptedQty <= 0) {
        Swal.fire({
            title: "Error!",
            text: "Qty. cannot be zero.",
            icon: "error",
        });
        $qtyInput.val(acceptedQty.toFixed(6));
        return;
    }

    const getVal = (selector) => {
        const el = $tr.find(selector);
        return el.length ? el.val() : "";
    };

    const data = {};
    const safeSet = (key, val) => {
        if (val) data[key] = val;
    };

    safeSet("item_id", itemId);
    safeSet("purchase_order_id", getVal("[name*='[purchase_order_id]']"));
    safeSet("po_detail_id", getVal("[name*='[po_detail_id]']"));
    safeSet("job_order_id", getVal("[name*='[job_order_id]']"));
    safeSet("jo_detail_id", getVal("[name*='[jo_detail_id]']"));
    safeSet("detail_id", getVal("[name*='[detail_id]']"));
    safeSet("qty", acceptedQty.toFixed(6));
    safeSet("type", currentProcessType);

    try {
        const response = await fetch(
            qtyChangeUrl + "?" + new URLSearchParams(data).toString()
        );
        const result = await response.json();
        const resultQty = parseFloat(result.accepted_qty) || 0;
        const finalQty = resultQty.toFixed(6);
        $qtyInput.val(finalQty);

        if (result.status !== 200 && result.message) {
            Swal.fire({ title: "Error!", text: result.message, icon: "error" });
            return false;
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            title: "Error!",
            text: "Quantity validation failed.",
            icon: "error",
        });
    }
});

// Set Each Row Item Calculation
function setTableCalculation() {
    let totalPoQty = 0;
    let totalPoOldValue = 0;
    let totalPoValue = 0;
    let totalGrnQty = 0;
    let totalGrnWeight = 0;
    let totalGrnVolume = 0;
    let totalOldGrnValue = 0;
    let totalGrnValue = 0;
    let totalAllocatedCost = 0;
    let totalLandedCost = 0;

    $(".poItemsTable [id*='row_']").each(function (index, item) {
        let rowCount = Number($(item).attr("data-index"));
        let po_qty = $(item).find("[name*='[po_qty]']").val() || 0;
        let po_old_value = $(item).find("[name*='[old_amt_po]']").val() || 0;
        let po_value = $(item).find("[name*='[po_value]']").val() || 0;

        totalPoQty += po_qty ? Number(po_qty) : 0;
        totalPoOldValue += po_old_value ? Number(po_old_value) : 0;
        totalPoValue += po_value ? Number(po_value) : 0;
    });

    $(".grnItemsTable [id*='row_']").each(function (index, item) {
        let rowCount = Number($(item).attr("data-index"));
        let grn_qty = $(item).find("[name*='[grn_qty]']").val() || 0;
        let grn_value = $(item).find("[name*='[grn_value]']").val() || 0;
        let old_grn_value =
            $(item).find("[name*='[old_grn_value]']").val() || 0;
        let grn_weight = $(item).find("[name*='[grn_weight]']").val() || 0;
        let grn_volume = $(item).find("[name*='[grn_volume]']").val() || 0;
        let allocation_cost =
            $(item).find("[name*='[allocation_cost]']").val() || 0;
        let landed_cost = $(item).find("[name*='[landed_cost]']").val() || 0;

        totalGrnQty += grn_qty ? Number(grn_qty) : 0;
        totalGrnWeight += grn_weight ? Number(grn_weight) : 0;
        totalGrnVolume += grn_volume ? Number(grn_volume) : 0;
        totalGrnValue += grn_value ? Number(grn_value) : 0;
        totalOldGrnValue += old_grn_value ? Number(old_grn_value) : 0;
        totalAllocatedCost += allocation_cost ? Number(allocation_cost) : 0;
        totalLandedCost += landed_cost ? Number(landed_cost) : 0;
    });

    /*Bind table footer*/
    $(".total-po-qty").text(totalPoQty.toFixed(2));
    $(".total-po-old-value").text(totalPoOldValue.toFixed(2));
    $(".total-po-value").text(totalPoValue.toFixed(2));
    $(".total-grn-qty").text(totalGrnQty.toFixed(2));
    $(".total-grn-weight").text(totalGrnWeight.toFixed(2));
    $(".total-grn-volume").text(totalGrnVolume.toFixed(2));
    $(".total-old-grn-value").text(totalOldGrnValue.toFixed(2));
    $(".total-grn-value").text(totalGrnValue.toFixed(2));
    $(".total-allocated-cost").text(totalAllocatedCost.toFixed(2));
    $(".total-landed-cost").text(totalLandedCost.toFixed(2));
}

/*Edit mode table calculation filled*/
if ($("#itemTable .mrntableselectexcel tr").length) {
    setTimeout(() => {
        $("[name*='component_item_name[1]']").trigger("focus");
        $("[name*='component_item_name[1]']").trigger("blur");
        setTableCalculation();
    }, 100);
}

$(document).on("input change", "#itemTable input", (e) => {
    setTableCalculation();
});

/*Check filled all basic detail*/
function checkBasicFilledDetail() {
    let filled = false;
    let bookId = $("#book_id").val() || "";
    let documentNumber = $("#document_number").val() || "";
    let documentDate = $("[name='document_date']").val() || "";
    if (bookId && documentNumber && documentDate) {
        filled = true;
    }
    return filled;
}

/*Check filled vendor detail*/
function checkVendorFilledDetail() {
    let filled = false;
    let vName = $("#vendor_name").val();
    let vCurrency = $("[name='currency_id']").val();
    let vPaymentTerm = $("[name='payment_term_id']").val();
    let shippingId = $("#shipping_id").val();
    let billingId = $("#billing_id").val();
    if (vName && vCurrency && vPaymentTerm && shippingId && billingId) {
        filled = true;
    }
    return filled;
}

/*Check filled component*/
function checkComponentRowExist() {
    let filled = false;
    let rowCount = $("#itemTable [id*='row_']").length;
    if (rowCount) {
        filled = true;
    }
    return filled;
}

$("#attribute").on("hidden.bs.modal", function () {
    let rowCount = $("[id*=row_].trselected").attr("data-index");
    let qty = $(`[name="components[${rowCount}][qty]"]`).val() || "";
    $(`[name="components[${rowCount}][qty]"]`).val(qty).focus();
});

/*Vendor change update field*/
$(document).on("blur", "#vendor_name", (e) => {
    if (!e.target.value) {
        $("#vendor_id").val("");
        $("#vendor_code").val("");
        $("#shipping_id").val("");
        $("#billing_id").val("");
        $("[name='currency_id']").val("").trigger("change");
        $("[name='payment_term_id']").val("").trigger("change");
        $(".shipping_detail").text("-");
        $(".billing_detail").text("-");
    }
});

//Disable form submit on enter button
document.querySelector("form").addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault(); // Prevent form submission
    }
});
$("input[type='text']").on("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault(); // Prevent form submission
    }
});
$("input[type='number']").on("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault(); // Prevent form submission
    }
});

/*Qty enabled and disabled*/
function qtyEnabledDisabled() {
    $("tr[id*='row_']").each(function (index, item) {
        let qtyDisabled = false;
        if ($(item).find("[name*='[attr_name]']").length) {
            $(item)
                .find("[name*='[attr_name]']")
                .each(function () {
                    if ($(this).val().trim() === "") {
                        qtyDisabled = true;
                    }
                });
            $(item)
                .find("[name*='[accepted_qty]']")
                .attr("readonly", Boolean(qtyDisabled));
            if (qtyDisabled) {
                $(item).find("[name*='[accepted_qty]']").val("");
            }
        } else {
            $(item).find("[name*='[accepted_qty]']").attr("readonly", false);
        }
    });
}
qtyEnabledDisabled();

setTimeout(() => {
    if ($("tr[id*='row_']").length) {
        setTableCalculation();
    }
}, 0);

function checkDuplicateObjects(inputEle) {
    let items = [];
    $("tr[id*='row_']").each(function (index, item) {
        let itemId = $(item).find("input[name*='[item_id]']").val();
        let attrName = $(item).find("input[name*='[attr_name]']").val();
        let uomId = $(item).find("select[name*='[uom_id]']").val();
        if (itemId && attrName && uomId) {
            let attr = [];
            // Collect attributes
            $(item)
                .find("input[name*='[attr_name]']")
                .each(function (ind, it) {
                    const matches = it.name.match(
                        /components\[\d+\]\[attr_group_id\]\[(\d+)\]\[attr_name\]/
                    );
                    if (matches) {
                        const attr_id = parseInt(matches[1], 10);
                        const attr_value = parseInt(it.value, 10);
                        if (attr_id && attr_value) {
                            attr.push({ attr_id, attr_value });
                        }
                    }
                });
            // Add item details to the items array
            items.push({
                item_id: itemId,
                uom_id: uomId,
                attributes: attr,
            });
        }
    });
    if (items.length) {
        if (hasDuplicateObjects(items)) {
            Swal.fire({
                title: "Error!",
                text: "Duplicate item!",
                icon: "error",
            });
            $(inputEle).val("");
        }
    }
}

function hasDuplicateObjects(array, inputEle) {
    const seen = new Set();
    for (const obj of array) {
        const objString = JSON.stringify(obj);
        if (seen.has(objString)) {
            return true;
        }
        seen.add(objString);
    }
    return false;
}

// UOM on change bind rate
$(document).on("change", 'select[name*="[uom_id]"]', (e) => {
    let tr = $(e.target).closest("tr");
    getItemCostPrice(tr);
    setTableCalculation();
});

function initAttributeAutocomplete(context = document) {
    $(context)
        .find(".attr-autocomplete")
        .each(function () {
            let $input = $(this);
            $input.autocomplete({
                minLength: 0,
                source: function (request, response) {
                    let itemId =
                        $input
                            .closest("tr")
                            .find("input[name*='item_id']")
                            .val() || "";
                    let attrGroupId = $input.data("attr-group-id");
                    $.ajax({
                        url: "/search",
                        method: "GET",
                        dataType: "json",
                        data: {
                            q: request.term,
                            type: "item_attr_value",
                            item_id: itemId,
                            attr_group_id: attrGroupId,
                        },
                        success: function (data) {
                            response(
                                $.map(data, function (item) {
                                    return {
                                        id: item.id,
                                        label: item.value,
                                        value: item.value,
                                    };
                                })
                            );
                        },
                        error: function (xhr) {
                            console.error(
                                "Error fetching attribute values:",
                                xhr.responseText
                            );
                        },
                    });
                },
                select: function (event, ui) {
                    const row = $input.closest("tr");
                    const rowCount = row.find('[name*="row_count"]').val();
                    const attrGroupId = $input.data("attr-group-id");
                    $input.val(ui.item.label);
                    $(
                        `[name="components[${rowCount}][attr_group_id][${attrGroupId}][attr_name]"]`
                    ).val(ui.item.id);
                    qtyEnabledDisabled();
                    setSelectedAttribute(rowCount);
                    const itemId = $("#attribute tbody tr")
                        .find('[name*="[item_id]"]')
                        .val();
                    const itemAttributes = [];
                    $("#attribute tbody tr").each(function () {
                        const attr_id = $(this)
                            .find('[name*="[attribute_id]"]')
                            .val();
                        const attr_value = $(this)
                            .find('[name*="[attribute_value]"]')
                            .val();
                        itemAttributes.push({
                            attr_id: attr_id,
                            attr_value: attr_value,
                        });
                    });
                    return false;
                },
                focus: function (event, ui) {
                    event.preventDefault();
                },
            });
            $input.on("focus", function () {
                if (!$(this).val()) {
                    $(this).autocomplete("search", "");
                }
            });
            $input.on("input", function () {
                if (!$(this).val()) {
                    const row = $input.closest("tr");
                    const rowCount = row.find('[name*="row_count"]').val();
                    const attrGroupId = $input.data("attr-group-id");
                    $(
                        `[name="components[${rowCount}][attr_group_id][${attrGroupId}][attr_name]"]`
                    ).val("");
                    qtyEnabledDisabled();
                }
            });
        });
}

// Auto scroll when row added
function focusAndScrollToLastRowInput(
    inputSelector = ".comp_item_code",
    tableSelector = "#itemTable"
) {
    let $lastRow = $(`${tableSelector} > tbody > tr`).last();
    let $input = $lastRow.find(inputSelector);
}

function renderBreakupHtml(breakup) {
    if (!breakup || !breakup.length) return "";
    let html = "";
    breakup.forEach((group) => {
        group.taxes.forEach((tax) => {
            html += `${tax.tax_code} (${tax.tax_percent}%) : ${parseFloat(
                tax.tax_amount
            ).toFixed(2)}<br>`;
        });
    });
    return html;
}
