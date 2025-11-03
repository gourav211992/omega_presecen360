$(function () {
    const $table = $(".qo-order-detail");
    const $tbody = $table.find("tbody");
    const $headers = $table.find("th");
    const initialRows = $tbody.children().toArray();

    // Append arrow span and style headers
    $headers.each(function (index) {
        if (index === 0) return; // skip first column if checkbox
        $(this)
            .css({
                cursor: "pointer",
                position: "relative",
                "padding-right": "20px",
            })
            .append(
                '<span class="sort-arrow" style="position:absolute; right:8px; font-size:11px; color:#0d6efd;"></span>'
            );
        $(this).data("order", "desc");
    });

    $headers.not(":first").click(function () {
        const index = $(this).index();
        const order = $(this).data("order") === "asc" ? "desc" : "asc";
        $(this).data("order", order);

        // Reset all arrows
        $headers.find(".sort-arrow").text("");
        $(this)
            .find(".sort-arrow")
            .text(order === "asc" ? "▲" : "▼");

        const rows = $tbody.children().toArray();
        rows.sort((a, b) => {
            let aText = $(a).children().eq(index).text().trim().toLowerCase();
            let bText = $(b).children().eq(index).text().trim().toLowerCase();

            const aNum = parseFloat(aText.replace(/,/g, ""));
            const bNum = parseFloat(bText.replace(/,/g, ""));

            if (!isNaN(aNum) && !isNaN(bNum))
                return order === "asc" ? aNum - bNum : bNum - aNum;
            return order === "asc"
                ? aText.localeCompare(bText)
                : bText.localeCompare(aText);
        });

        $tbody.append(rows); // append sorted rows
    });

    $("#clearSortingBtn").click(function () {
        // reset headers and arrows
        $headers.data("order", "desc").find(".sort-arrow").text("");
        // reset original row order
        $tbody.append(initialRows);
    });
});

/*Approve modal*/
$(document).on("click", "#approved-button", (e) => {
    let actionType = "approve";
    $("#approveModal").find("#action_type").val(actionType);
    $("#approveModal #popupTitle").text("Approve Application");
    $("#approveModal").modal("show");
});
$(document).on("click", "#reject-button", (e) => {
    let actionType = "reject";
    $("#approveModal").find("#action_type").val(actionType);
    $("#approveModal #popupTitle").text("Reject Application");
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
});

/*Check box check and uncheck*/
$(document).on("change", "#itemTable > thead .form-check-input", (e) => {
    const isChecked = e.target.checked;
    $("#itemTable > tbody .form-check-input").each(function () {
        if (!$(this).is(":disabled")) {
            // Only check if the checkbox is not disabled
            $(this).prop("checked", isChecked);
        }
    });
});

$(document).on("change", "#itemTable > tbody .form-check-input", (e) => {
    const allChecked =
        $("#itemTable > tbody .form-check-input:not(:disabled)").length ===
        $("#itemTable > tbody .form-check-input:checked:not(:disabled)").length;

    $("#itemTable > thead .form-check-input").prop("checked", allChecked);
});

/*Attribute on change*/
$(document).on("change", '[name*="comp_attribute"]', (e) => {
    let rowCount = e.target
        .closest("tr")
        .querySelector('[name*="row_count"]').value;
    let attrGroupId = e.target.getAttribute("data-attr-group-id");
    $(
        `[name="components[${rowCount}][attr_group_id][${attrGroupId}][attr_name]"]`
    ).val(e.target.value);
    qtyEnabledDisabled();
    setSelectedAttribute(rowCount);
});

/*Edit mode table calculation filled*/
if ($("#itemTable .mrntableselectexcel tr").length) {
    setTimeout(() => {
        $("[name*='component_item_name[1]']").trigger("focus");
        $("[name*='component_item_name[1]']").trigger("blur");
    }, 100);
}

/*Open item remark modal*/
$(document).on("click", ".addRemarkBtn", (e) => {
    let rowCount = e.target.closest("div").getAttribute("data-row-count");
    $("#itemRemarkModal #row_count").val(rowCount);
    let remarkValue = $("#itemTable #row_" + rowCount).find("[name*='remark']");

    if (!remarkValue.length) {
        $("#itemRemarkModal textarea").val("");
    } else {
        $("#itemRemarkModal textarea").val(remarkValue.val());
    }
    $("#itemRemarkModal").modal("show");
});

/*Submit item remark modal*/
$(document).on("click", ".itemRemarkSubmit", (e) => {
    let rowCount = $("#itemRemarkModal #row_count").val();
    let remarkValue = $("#itemTable #row_" + rowCount).find("[name*='remark']");
    let textValue = $("#itemRemarkModal").find("textarea").val();
    if (!remarkValue.length) {
        rowHidden = `<input type="hidden" value="${textValue}" name="components[${rowCount}][remark]" />`;
        $("#itemTable #row_" + rowCount)
            .find(".addRemarkBtn")
            .after(rowHidden);
    } else {
        $("#itemTable #row_" + rowCount)
            .find("[name*='remark']")
            .val(textValue);
    }
    $("#itemRemarkModal").modal("hide");
});

$("#attribute").on("hidden.bs.modal", function () {
    let rowCount = $("[id*=row_].trselected").attr("data-index");
    if ($(`[name="components[${rowCount}][qty]"]`).is("[readonly]")) {
        $(`[name="components[${rowCount}][vendor_code]"]`).trigger("focus");
    } else {
        $(`[name="components[${rowCount}][qty]"]`).trigger("focus");
    }
});

/*Vendor change*/
$(document).on("blur", '[name*="[vendor_code]"]', (e) => {
    if (!e.target.value) {
        $(e.target).closest("tr").find('[name*="[vendor_name]"').val("");
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
                .find("[name*='[qty]']")
                .attr("readonly", Boolean(qtyDisabled));
            if (qtyDisabled) {
                $(item).find("[name*='[qty]']").val("");
            }
        } else {
            $(item).find("[name*='[qty]']").attr("readonly", false);
        }
    });
}

qtyEnabledDisabled();

$(document).on("blur", '[name*="component_item_name"]', (e) => {
    if (!e.target.value) {
        $(e.target).closest("tr").find('[name*="[item_name]"]').val("");
        $(e.target).closest("tr").find('[name*="[item_id]"]').val("");
    }
});

$(document).on("keyup", "input[name*='[qty]']", function (e) {
    validateItems(e.target, false);
});

function validateItems(inputEle, itemChange = false) {
    let items = [];
    $("tr[id*='row_']").each(function (index, item) {
        let itemId = $(item).find("input[name*='[item_id]']").val();
        let uomId = $(item).find("select[name*='[uom_id]']").val();
        let soId = $(item).find("input[name*='[so_id]']").val();
        if (itemId && uomId) {
            let attr = [];
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
            items.push({
                item_id: itemId,
                uom_id: uomId,
                attributes: attr,
                so_id: soId,
            });
        }
    });

    if (items.length && hasDuplicateObjects(items)) {
        Swal.fire({
            title: "Error!",
            text: "Duplicate item!",
            icon: "error",
        });
        $(inputEle).val("");
        if (itemChange) {
            $(inputEle)
                .closest("tr")
                .find("input[name*='[item_name]']")
                .val("");
            $(inputEle).closest("tr").find("[name*='[uom_id]']").empty();
        }
    }
}

function hasDuplicateObjects(arr) {
    let seen = new Set();
    return arr.some((obj) => {
        let key = JSON.stringify(obj);
        if (seen.has(key)) {
            return true;
        }
        seen.add(key);
        return false;
    });
}

function initAutocompVendor(selector, type) {
    $(selector)
        .autocomplete({
            minLength: 0,
            source: function (request, response) {
                let item_id = $(this.element)
                    .closest("tr")
                    .find("[name*='[item_id]']")
                    .val();
                $.ajax({
                    url: "/search",
                    method: "GET",
                    dataType: "json",
                    data: {
                        q: request.term,
                        type: "vendor_list",
                        item_id: item_id,
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                return {
                                    id: item.id,
                                    label: item.company_name,
                                    code: item.vendor_code,
                                    addresses: item.addresses,
                                };
                            })
                        );
                    },
                    error: function (xhr) {
                        console.error(
                            "Error fetching customer data:",
                            xhr.responseText
                        );
                    },
                });
            },
            select: function (event, ui) {
                let $input = $(this);
                let itemName = ui.item.value;
                let itemId = ui.item.id;
                let itemCode = ui.item.code;
                $input.attr("data-name", itemName);
                $input.val(itemCode);
                $input
                    .closest("tr")
                    .find("[name*='[vendor_name]']")
                    .val(itemName);
                $input.closest("tr").find("[name*='[vendor_id]']").val(itemId);
            },
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $(this).attr("data-name", "");
                    $(this)
                        .closest("tr")
                        .find("[name*='[vendor_name]']")
                        .val("");
                    $(this).closest("tr").find("[name*='[vendor_id]']").val("");
                }
            },
        })
        .focus(function () {
            if (this.value === "") {
                $(this).autocomplete("search", "");
                $(this).closest("tr").find("[name*='[vendor_name]']").val("");
                $(this).closest("tr").find("[name*='[vendor_id]']").val("");
            }
        })
        .on("input", function () {
            if ($(this).val().trim() === "") {
                $(this).removeData("selected");
                $(this).closest("tr").find("[name*='[vendor_name]']").val("");
                $(this).closest("tr").find("[name*='[vendor_id]']").val("");
            }
        });
}
if ($("[name*='[vendor_code]']").length) {
    initAutocompVendor("[name*='[vendor_code]']");
}

function updateIndentQty($row) {
    var reqQty = parseFloat($row.find('input[name$="[qty]"]').val()) || 0;
    var avlStock =
        parseFloat($row.find('input[name$="[avl_stock]"]').val()) || 0;
    var adjQtyInput = $row.find('input[name$="[adj_qty]"]');
    var adjQty = parseFloat(adjQtyInput.val()) || 0;
    if (adjQty > Math.min(reqQty, avlStock)) {
        adjQty = Math.min(reqQty, avlStock);
        adjQtyInput.val(adjQty);
    }

    var indentQty = reqQty - adjQty;
    $row.find('input[name$="[indent_qty]"]').val(indentQty.toFixed(2));
}

// When adj_qty changes
$(document).on(
    "keyup change",
    'input[name^="components"][name$="[adj_qty]"]',
    function () {
        var $row = $(this).closest("tr");
        updateIndentQty($row);
    }
);

// When req_qty changes
$(document).on(
    "keyup change",
    'input[name^="components"][name$="[qty]"]',
    function () {
        var $row = $(this).closest("tr");
        updateIndentQty($row);
    }
);

document.querySelectorAll("#orderTypeSelect").forEach((radio) => {
    radio.addEventListener("change", function () {
        document.getElementById("procurement_type").value = this.value;
    });
});

$(document).on("change", "#procurement_type", function () {
    let selectedValue = this.value;
    $("#procurement_type").val(selectedValue);
});

function copyItemRow() {
    let $rows = $("#itemTable > tbody tr");
    let $checked = $rows.find(".form-check-input:checked");

    if ($rows.length === 0) {
        Swal.fire({
            title: "Error!",
            text: "Please add at least one item to copy.",
            icon: "error",
        });
        return;
    }

    if ($checked.length === 0) {
        Swal.fire({
            title: "Error!",
            text: "Please select at least one item to copy.",
            icon: "error",
        });
        return;
    }

    let currentRowCount = $rows.length;
    if ($checked.length !== 1) {
        Swal.fire({
            title: "Error!",
            text: "Multiple items can not be cloned, Please check single item.",
            icon: "error",
        });
        return;
    }

    try {
        let $row = $checked.closest("tr");
        let $clone = cloneRow($row, ++currentRowCount);

        $("#itemTable .mrntableselectexcel tr[id*='row_']:last").after($clone);
        $clone.closest("tr").find("input[name*='attr_group_id']").remove();
        $clone.closest("tr").find("input[name*='pi_item_id']").val("");

        initAutocompVendor("[name*='[vendor_code]']");
        initializeAutocomplete2(".comp_item_code");

        setTimeout(() => {
            $clone.closest("tr").find(".attributeBtn").trigger("click");
        }, 100);

        $(".form-check-input").each(function () {
            $(this).prop("checked", false);
        });
    } catch (error) {
        console.error("Error copying item row:", error);
        Swal.fire({
            title: "Error!",
            text: "An unexpected error occurred while copying the item row.",
            icon: "error",
        });
    }
}

function cloneRow($row, newIndex) {
    let $clone = $row.clone();

    $clone.attr({
        id: "row_" + newIndex,
        "data-index": newIndex,
        "data-count": newIndex,
        "data-row-count": newIndex,
    });

    $clone.closest("tr").attr("id", "row_" + newIndex);
    $clone.find("[data-index]").attr("data-index", newIndex);
    $clone.find("[data-row-count]").attr("data-row-count", newIndex);
    $clone.find("[data-row-count]").attr("data-row-count", newIndex);

    $clone.find("[name]").each(function () {
        let name = $(this).attr("name");
        if (name) {
            name = name.replace(/\[\d+\]/g, "[" + newIndex + "]");
            $(this).attr("name", name);
        }
    });

    $clone.find("[id]").each(function () {
        let id = $(this).attr("id");
        if (id) {
            id = id.replace(/_\d+$/, "_" + newIndex);
            $(this).attr("id", id);
        }
    });

    $clone
        .find('input[type="checkbox"]')
        .prop("checked", false)
        .attr("data-id", "")
        .val(newIndex);
    return $clone;
}

$(document).on("click", "#backBtn", (e) => {
    $("#soSubmitModal, #pwoSubmitModal, #analyzeModal").modal("hide");

    let isSo = $(e.target).closest("#soSubmitModal").length > 0;
    let isPwo = $(e.target).closest("#pwoSubmitModal").length > 0;

    setTimeout(() => {
        if (isPwo) {
            $("#pwoModal").modal("show");
        } else if (isSo) {
            $("#soModal").modal("show");
        } else {
            $("#soModal").modal("show");
        }
    }, 100);
});

$(document).on("click", ".toggle-expand", function (e) {
    e.preventDefault();
    var targetKey = $(this).data("target");
    var parentLevel = parseInt($(this).closest("tr").data("level"), 10);
    $('tr[data-row-key^="' + targetKey + '-"]').each(function () {
        var rowLevel = parseInt($(this).data("level"), 10);
        if (rowLevel === parentLevel + 1) {
            $(this).removeClass("d-none");
        }
    });
    $(this).addClass("d-none");
    $(this).siblings(".toggle-collapse").removeClass("d-none");
});

$(document).on("click", ".toggle-collapse", function (e) {
    e.preventDefault();
    var targetKey = $(this).data("target");
    $('tr[data-row-key^="' + targetKey + '-"]').addClass("d-none");
    $('tr[data-row-key^="' + targetKey + '-"] .toggle-collapse').addClass(
        "d-none"
    );
    $('tr[data-row-key^="' + targetKey + '-"] .toggle-expand').removeClass(
        "d-none"
    );
    $(this).addClass("d-none");
    $(this).siblings(".toggle-expand").removeClass("d-none");
});

function getSelectedPiIDS() {
    let ids = [];
    $(".pi_item_checkbox:checked").each(function () {
        ids.push($(this).val());
    });
    return ids;
}

function getSelectedPiIDS2() {
    let ids = [];
    $(".analyze_row:checked").each(function () {
        ids.push($(this).val());
    });
    return ids;
}

$(document).on("click", ".analyzeButton", (e) => {
    let ids = getSelectedPiIDS();
    if (!ids.length) {
        $("#soModal").modal("hide");
        Swal.fire({
            title: "Error!",
            text: "Please select at least one line item",
            icon: "error",
        });
        return false;
    }

    ids = JSON.stringify(ids);
    let d_date = $("input[name='document_date']").val() || "";
    let book_id = $("#book_id").val() || "";
    let rowCount = $("#itemTable tbody tr[id*='row_']").length;
    let isAttribute = 0;
    if ($("#attributeCheck").is(":checked")) {
        isAttribute = 1;
    } else {
        isAttribute = 0;
    }
    let selectedItems = [];
    if (!isAttribute) {
        $("#soModal .pi_item_checkbox:checked").each(function () {
            selectedItems.push({
                sale_order_id: Number($(this).val()),
                item_id: Number($(this).data("item-id")),
            });
        });
    }

    let selectedItemsParam = encodeURIComponent(JSON.stringify(selectedItems));
    let actionUrl =
        analyzeSoItemUrl +
        "?ids=" +
        ids +
        "&d_date=" +
        d_date +
        "&book_id=" +
        book_id +
        "&rowCount=" +
        rowCount +
        `&is_attribute=${isAttribute}&selected_items=${selectedItemsParam}`;
    fetch(actionUrl).then((response) => {
        return response.json().then((data) => {
            if (data.status == 200) {
                $("#analyzeDataTable").empty().append(data.data.pos);
                feather.replace();
                $("#soModal").modal("hide");
                $("#analyzeModal").modal("show");
            }
            if (data.status == 422) {
                Swal.fire({
                    title: "Error!",
                    text: data.message,
                    icon: "error",
                });
                return false;
            }
        });
    });
});
$(document).on("click", ".analyzeProcessBtn", (e) => {
    let ids = [];
    if ($("#analyzeDataTable .analyze_row:checked").length === 0) {
        Swal.fire({
            title: "Error!",
            text: "Please select at least one line item to process",
            icon: "error",
        });
        return false;
    }

    let validateItems = true;
    $("#analyzeDataTable tr").each(function () {
        let $row = $(this);
        let $checkbox = $row.find(".analyze_row");
        let reqQty =
            parseFloat($row.find('input[id^="analyse_required_qty_"]').val()) ||
            0;

        if ($checkbox.is(":checked") && reqQty <= 0) {
            $row.find('input[id^="analyse_required_qty_"]')
                .focus()
                .css("border", "1px solid red");
            validateItems = false;
        }
    });

    if (!validateItems) {
        Swal.fire({
            title: "Error!",
            text: `Please enter required qty!`,
            icon: "error",
        });
        return false;
    }

    let selectedItems = [];
    $("#analyzeDataTable .analyze_row:checked").each(function () {
        let $checkbox = $(this);
        let soItemIdsRaw = $checkbox.data("so-item-ids") || "";
        let soItemIds = soItemIdsRaw
            .toString()
            .split(",")
            .map((id) => id.trim().replace(/^['"]|['"]$/g, ""))
            .map((id) => Number(id))
            .filter((id) => id > 0);

        selectedItems.push({
            bom_id: Number($checkbox.val()),
            parent_bom_id: Number($checkbox.data("parent-bom-id")),
            so_id: Number($checkbox.data("so-id")),
            so_item_id: Number($checkbox.data("so-item-id")),
            so_item_ids: soItemIds,
            main_so_item: Number($checkbox.data("main-so-item")),
            level: Number($checkbox.data("level")),
            item_id: Number($checkbox.data("item-id")),
            total_qty: parseFloat($checkbox.data("total-qty")) || 0,
            rem_qty: parseFloat($checkbox.data("remaining-qty")) || 0,
            req_qty: parseFloat($checkbox.data("required-qty")) || 0,
        });

        ids.push(Number($checkbox.data("so-item-id")));
    });

    if (ids.length === 0) {
        Swal.fire({
            title: "Error!",
            text: "Please select at least one line item",
            icon: "error",
        });
        return false;
    }

    let isAttribute = $("#attributeCheck").is(":checked") ? 1 : 0;
    let procurementType = $("#orderTypeSelect").val() || "rm";
    let storeId = $("#store_id").val() || "";
    let soTracking = $("#so_tracking_required").val();

    let actionUrl = `${processAnalyzedBomItem}?ids=${encodeURIComponent(
        JSON.stringify(ids)
    )}&is_attribute=${isAttribute}&selected_items=${encodeURIComponent(
        JSON.stringify(selectedItems)
    )}&so_tracking_required=${soTracking}&procurement_type=${procurementType}&store_id=${storeId}`;

    fetch(actionUrl)
        .then((response) => response.json())
        .then((data) => {
            if (data.status == 200) {
                $("#soModal").modal("hide");

                if (data.data.procurement_type != "fg") {
                    $("#soSubmitDataTable").empty().append(data.data.pos);
                    $("#soSubmitModal").modal("show");
                } else {
                    $("#itemTable .mrntableselectexcel")
                        .empty()
                        .append(data.data.pos);

                    setTimeout(() => {
                        $("#itemTable .mrntableselectexcel tr").each(function (
                            index
                        ) {
                            let currentIndex = index + 1;
                            setAttributesUIHelper(currentIndex, "#itemTable");
                        });
                    }, 100);
                }
            } else {
                Swal.fire({
                    title: "Error!",
                    text: data.message,
                    icon: "error",
                });
            }
        })
        .catch((err) => {
            Swal.fire({
                title: "Error!",
                text: err.message,
                icon: "error",
            });
        });
});

/*Checkbox for pi item list*/
$(document).on("change", ".po-order-detail > thead .form-check-input", (e) => {
    if (e.target.checked) {
        $(".po-order-detail > tbody .form-check-input").each(function () {
            $(this).prop("checked", true);
        });
    } else {
        $(".po-order-detail > tbody .form-check-input").each(function () {
            $(this).prop("checked", false);
        });
    }
});
$(document).on("change", ".po-order-detail > tbody .form-check-input", (e) => {
    if (!$(".po-order-detail > tbody .form-check-input:not(:checked)").length) {
        $(".po-order-detail > thead .form-check-input").prop("checked", true);
    } else {
        $(".po-order-detail > thead .form-check-input").prop("checked", false);
    }
});

$(document).on("input", 'input[id^="analyse_required_qty_"]', function () {
    try {
        let $required = $(this);
        let $row = $required.closest("tr");

        let $total = $row.find('input[id^="analyse_total_qty_"]');
        let $remaining = $row.find('input[id^="analyse_remaining_qty_"]');

        let total = parseFloat($total.val()) || 0;
        let required = parseFloat($required.val()) || 0;

        if (required > total) {
            required = total;
            $required.val(required);
        }

        let remaining = parseFloat((total - required).toFixed(6));
        $remaining.val(remaining);

        let $checkbox = $row.find(".analyze_row");
        $checkbox.data("required-qty", required);
        $checkbox.data("remaining-qty", remaining);
        $checkbox.attr("data-required-qty", required);
        $checkbox.attr("data-remaining-qty", remaining);

        let currentKey = $required.data("current-key");
        cascadeToChildren(currentKey, required);
    } catch (error) {
        console.error("Error in required qty input handler:", error);
        alert("Error in required qty input handler: " + error);
        $("#analyzeModal").modal("hide");
        $("#soModal").modal("show");
    }
});

function cascadeToChildren(parentKey, parentRequired) {
    $("#analyzeDataTable")
        .find('tr[data-parent-key="' + parentKey + '"]')
        .each(function () {
            let $childRow = $(this);
            let childKey = $childRow.data("row-key");

            let $childTotal = $childRow.find('input[id^="analyse_total_qty_"]');
            let $childRequired = $childRow.find(
                'input[id^="analyse_required_qty_"]'
            );
            let $childRemaining = $childRow.find(
                'input[id^="analyse_remaining_qty_"]'
            );

            let childTotal = parseFloat($childTotal.val()) || 0.0;

            let parentTotal =
                parseFloat(
                    $("#analyse_total_qty_" + parentKey.split("-").pop()).val()
                ) || 0.0;

            let ratio = parentTotal > 0 ? parentRequired / parentTotal : 0;

            let childRequired = parseFloat((childTotal * ratio).toFixed(6));
            let childRemaining = parseFloat(
                (childTotal - childRequired).toFixed(6)
            );

            // Update UI fields
            $childRequired.val(childRequired);
            $childRemaining.val(childRemaining);

            let $checkbox = $childRow.find(".analyze_row");

            $checkbox.data("required-qty", childRequired);
            $checkbox.data("remaining-qty", childRemaining);
            $checkbox.attr("data-required-qty", childRequired);
            $checkbox.attr("data-remaining-qty", childRemaining);

            // Cascade deeper
            cascadeToChildren(childKey, childRequired);
        });
}

/*So modal*/
$(document).on("click", ".soSelect", (e) => {
    let paramValue = $("#procurement_type_param").val();
    let option = "";
    if (paramValue.includes("All")) {
        option += `<option value="rm">Make to order</option><option value="fg">Buy to order</option>`;
    }
    if (paramValue.includes("Make to order")) {
        option += `<option value="rm">Make to order</option>`;
    }
    if (paramValue.includes("Buy to order")) {
        option += `<option value="fg">Buy to order</option>`;
    }
    $("#orderTypeSelect").empty().append(option);
    $("#soModal").modal("show");
    openSaleRequest();
    getSoItems();
});

/*searchPiBtn*/
$(document).on("click", ".searchSoBtn", (e) => {
    getSoItems();
});

function openSaleRequest() {
    initializeAutocompleteQt(
        "customer_code_input_qt",
        "customer_id_qt_val",
        "customer",
        "customer_code",
        "company_name"
    );
    initializeAutocompleteQt(
        "book_code_input_qt",
        "book_id_qt_val",
        "book_so",
        "book_code",
        ""
    );
    initializeAutocompleteQt(
        "document_no_input_qt",
        "document_id_qt_val",
        "sale_order_document_qt_pi",
        "document_number",
        ""
    );
    initializeAutocompleteQt(
        "item_name_input_qt",
        "item_id_qt_val",
        "po_item_list",
        "item_code",
        "item_name"
    );
}

function initializeAutocompleteQt(
    selector,
    selectorSibling,
    typeVal,
    labelKey1,
    labelKey2 = ""
) {
    $("#" + selector)
        .autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/search",
                    method: "GET",
                    dataType: "json",
                    data: {
                        q: request.term,
                        type: typeVal,
                        cutomer_id: $("#cutomer_id_qt_val").val(),
                        header_book_id: $("#book_id").val(),
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                return {
                                    id: item.id,
                                    label: `${item[labelKey1]} ${
                                        labelKey2
                                            ? item[labelKey2]
                                                ? "(" + item[labelKey2] + ")"
                                                : ""
                                            : ""
                                    }`,
                                    code: item[labelKey1] || "",
                                };
                            })
                        );
                    },
                    error: function (xhr) {
                        console.error(
                            "Error fetching customer data:",
                            xhr.responseText
                        );
                    },
                });
            },
            appendTo: "#soModal",
            minLength: 0,
            select: function (event, ui) {
                var $input = $(this);
                $input.val(ui.item.label);
                $("#" + selectorSibling).val(ui.item.id);
                getSoItems();
                return false;
            },
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $("#" + selectorSibling).val("");
                    getSoItems();
                }
            },
        })
        .focus(function () {
            if (this.value === "") {
                $(this).autocomplete("search", "");
                $("#" + selectorSibling).val("");
                getSoItems();
            }
        })
        .blur(function () {
            if ($(this).val().trim() === "") {
                $("#" + selectorSibling).val("");
                getSoItems();
            }
        });
}

function getSoItems() {
    let isAttribute = 0;
    if ($("#attributeCheck").is(":checked")) {
        isAttribute = 1;
    } else {
        isAttribute = 0;
    }
    let header_book_id = $("#book_id").val() || "";
    let series_id = $("#book_id_qt_val").val() || "";
    let document_number = $("#document_no_input_qt").val() || "";
    let item_id = $("#item_id_qt_val").val() || "";
    let customer_id = $("#customer_id_qt_val").val() || "";
    let item_search = $("#item_name_search").val();
    let fullUrl = `${getSoUrl}?series_id=${encodeURIComponent(
        series_id
    )}&document_number=${encodeURIComponent(
        document_number
    )}&item_id=${encodeURIComponent(item_id)}&customer_id=${encodeURIComponent(
        customer_id
    )}&header_book_id=${encodeURIComponent(
        header_book_id
    )}&is_attribute=${isAttribute}&item_search=${item_search}`;
    fetch(fullUrl).then((response) => {
        return response.json().then((data) => {
            $(".po-order-detail #soDataTable").empty().append(data.data.pis);
            if (data.data.isAttribute) {
                $("#soHeaderAttribute").removeClass("d-none");
            } else {
                $("#soHeaderAttribute").addClass("d-none");
            }
        });
    });
}

$(document).on("keyup", "#item_name_search", (e) => {
    getSoItems();
});

$(document).on("change", "#attributeCheck", (e) => {
    if (e.target.checked) {
        $("#show_attribute").val(1);
    } else {
        $("#show_attribute").val(0);
    }
    getSoItems();
});

$(document).on("blur", "#customer_code_input_qt", (e) => {
    getSoItems();
});

/*Checkbox for pi item list*/
$(document).on(
    "change",
    "#soModal .po-order-detail > thead .form-check-input",
    (e) => {
        if (e.target.checked) {
            $("#soModal .po-order-detail > tbody .form-check-input").each(
                function () {
                    $(this).prop("checked", true);
                }
            );
        } else {
            $("#soModal .po-order-detail > tbody .form-check-input").each(
                function () {
                    $(this).prop("checked", false);
                }
            );
        }
    }
);

$(document).on(
    "change",
    "#soModal .po-order-detail > tbody .form-check-input",
    (e) => {
        if (
            !$(
                "#soModal .po-order-detail > tbody .form-check-input:not(:checked)"
            ).length
        ) {
            $("#soModal .po-order-detail > thead .form-check-input").prop(
                "checked",
                true
            );
        } else {
            $("#soModal .po-order-detail > thead .form-check-input").prop(
                "checked",
                false
            );
        }
    }
);

/*Checkbox for selected pi item list*/
$(document).on(
    "change",
    "#soSubmitModal .po-order-detail > thead .form-check-input",
    (e) => {
        if (e.target.checked) {
            $("#soSubmitModal .po-order-detail > tbody .form-check-input").each(
                function () {
                    $(this).prop("checked", true);
                }
            );
        } else {
            $("#soSubmitModal .po-order-detail > tbody .form-check-input").each(
                function () {
                    $(this).prop("checked", false);
                }
            );
        }
    }
);

$(document).on(
    "change",
    "#soSubmitModal .po-order-detail > tbody .form-check-input",
    (e) => {
        if (
            !$(
                "#soSubmitModal .po-order-detail > tbody .form-check-input:not(:checked)"
            ).length
        ) {
            $("#soSubmitModal .po-order-detail > thead .form-check-input").prop(
                "checked",
                true
            );
        } else {
            $("#soSubmitModal .po-order-detail > thead .form-check-input").prop(
                "checked",
                false
            );
        }
    }
);

function getSelectedSoIDS() {
    let ids = [];
    $("#soModal .pi_item_checkbox:checked").each(function () {
        ids.push($(this).val());
    });
    return ids;
}

function getSelectedItemIDS() {
    let ids = [];
    $("#soModal .pi_item_checkbox:checked").each(function () {
        if (Number($(this).data("item-id"))) {
            ids.push(Number($(this).data("item-id")));
        }
    });
    return ids;
}

$(document).on("click", ".soProcess", (e) => {
    $("#soSubmitModal th .form-check-input").prop("checked", false);
    let ids = getSelectedSoIDS();
    if (!ids.length) {
        $("[name='so_item_ids']").val("");
        $("[name='item_ids']").val("");
        $("#soModal").modal("hide");
        Swal.fire({
            title: "Error!",
            text: "Please select at least one one so item.",
            icon: "error",
        });
        return false;
    }
    $("[name='so_item_ids']").val(ids);
    let itemIds = getSelectedItemIDS();
    $("[name='item_ids']").val(itemIds);

    // for component item code
    function initializeAutocomplete2(selector, type) {
        $(selector)
            .autocomplete({
                minLength: 0,
                source: function (request, response) {
                    let selectedAllItemIds = [];
                    $("#itemTable tbody [id*='row_']").each(function (
                        index,
                        item
                    ) {
                        if (Number($(item).find('[name*="[item_id]"]').val())) {
                            selectedAllItemIds.push(
                                Number(
                                    $(item).find('[name*="[item_id]"]').val()
                                )
                            );
                        }
                    });
                    $.ajax({
                        url: "/search",
                        method: "GET",
                        dataType: "json",
                        data: {
                            q: request.term,
                            type: "pi_comp_item",
                            selectedAllItemIds:
                                JSON.stringify(selectedAllItemIds),
                        },
                        success: function (data) {
                            response(
                                $.map(data, function (item) {
                                    return {
                                        id: item.id,
                                        label: `${item.item_name} (${item.item_code})`,
                                        code: item.item_code || "",
                                        item_id: item.id,
                                        item_name: item.item_name,
                                        uom_name: item.uom?.name,
                                        uom_id: item.uom_id,
                                        hsn_id: item.hsn?.id,
                                        hsn_code: item.hsn?.code,
                                        alternate_u_o_ms: item.alternate_u_o_ms,
                                        is_attr: item.item_attributes_count,
                                    };
                                })
                            );
                        },
                        error: function (xhr) {
                            console.error(
                                "Error fetching customer data:",
                                xhr.responseText
                            );
                        },
                    });
                },
                select: function (event, ui) {
                    let $input = $(this);
                    let itemCode = ui.item.code;
                    let itemName = ui.item.value;
                    let itemN = ui.item.item_name;
                    let itemId = ui.item.item_id;
                    let uomId = ui.item.uom_id;
                    let uomName = ui.item.uom_name;
                    let hsnId = ui.item.hsn_id;
                    let hsnCode = ui.item.hsn_code;
                    $input.attr("data-name", itemName);
                    $input.attr("data-code", itemCode);
                    $input.attr("data-id", itemId);
                    $input
                        .closest("tr")
                        .find('[name*="[item_id]"]')
                        .val(itemId);
                    $input
                        .closest("tr")
                        .find("[name*=item_code]")
                        .val(itemCode);
                    $input.closest("tr").find("[name*=item_name]").val(itemN);
                    $input.closest("tr").find("[name*=hsn_id]").val(hsnId);
                    $input.closest("tr").find("[name*=hsn_code]").val(hsnCode);
                    $input.val(itemCode);
                    let uomOption = `<option value=${uomId}>${uomName}</option>`;
                    if (ui.item?.alternate_u_o_ms) {
                        for (let alterItem of ui.item.alternate_u_o_ms) {
                            uomOption += `<option value="${alterItem.uom_id}" ${
                                alterItem.is_purchasing ? "selected" : ""
                            }>${alterItem.uom?.name}</option>`;
                        }
                    }
                    $input
                        .closest("tr")
                        .find("[name*=uom_id]")
                        .empty()
                        .append(uomOption);
                    $input
                        .closest("tr")
                        .find("input[name*='attr_group_id']")
                        .remove();

                    setTimeout(() => {
                        if (ui.item.is_attr) {
                            $input
                                .closest("tr")
                                .find(".attributeBtn")
                                .trigger("click");
                        } else {
                            $input
                                .closest("tr")
                                .find(".attributeBtn")
                                .trigger("click");
                            $input
                                .closest("tr")
                                .find('[name*="[qty]"]')
                                .val("")
                                .focus();
                        }
                    }, 100);
                    validateItems($input, true);
                    return false;
                },
                change: function (event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        // $('#itemId').val('');
                        $(this).attr("data-name", "");
                        $(this).attr("data-code", "");
                    }
                },
            })
            .focus(function () {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                }
            })
            .on("input", function () {
                if ($(this).val().trim() === "") {
                    $(this).removeData("selected");
                    $(this)
                        .closest("tr")
                        .find("input[name*='component_item_name']")
                        .val("");
                    $(this)
                        .closest("tr")
                        .find("input[name*='item_name']")
                        .val("");
                    $(this)
                        .closest("tr")
                        .find("td[id*='itemAttribute_']")
                        .html(defautAttrBtn);
                    $(this)
                        .closest("tr")
                        .find("input[name*='[item_id]']")
                        .val("");
                    $(this)
                        .closest("tr")
                        .find("input[name*='item_code']")
                        .val("");
                    $(this)
                        .closest("tr")
                        .find("input[name*='attr_name']")
                        .remove();
                }
            });
    }

    let isAttribute = 0;
    if ($("#attributeCheck").is(":checked")) {
        isAttribute = 1;
    } else {
        isAttribute = 0;
    }

    let procurementType = $("#orderTypeSelect").val() || "rm";
    let selectedItems = [];
    if (!isAttribute) {
        $("#soModal .pi_item_checkbox:checked").each(function () {
            selectedItems.push({
                sale_order_id: Number($(this).val()),
                item_id: Number($(this).data("item-id")),
            });
        });
    }
    let storeId = $("#store_id").val() || "";
    let selectedItemsParam = encodeURIComponent(JSON.stringify(selectedItems));

    ids = JSON.stringify(ids);
    let soTracking = $("#so_tracking_required").val() || "";
    let actionUrl =
        processSoItemUrl +
        `?ids=${ids}&is_attribute=${isAttribute}&selected_items=${selectedItemsParam}&so_tracking_required=${soTracking}&procurement_type=${procurementType}&store_id=${storeId}`;
    fetch(actionUrl).then((response) => {
        return response.json().then((data) => {
            if (data.status == 200) {
                // $("#itemTable .mrntableselectexcel").empty().append(data.data.pos);
                // initializeAutocomplete2(".comp_item_code");
                $("#soModal").modal("hide");
                // $(".soSelect").prop('disabled',true);

                if (data.data.procurement_type != "fg") {
                    $("#soSubmitDataTable").empty().append(data.data.pos);
                    $("#soSubmitModal").modal("show");
                } else {
                    $("#itemTable .mrntableselectexcel")
                        .empty()
                        .append(data.data.pos);
                    setTimeout(() => {
                        $("#itemTable .mrntableselectexcel tr").each(function (
                            index,
                            item
                        ) {
                            let currentIndex = index + 1;
                            setAttributesUIHelper(currentIndex, "#itemTable");
                        });
                    }, 100);
                }
            } else {
                Swal.fire({
                    title: "Error!",
                    text: data.message,
                    icon: "error",
                });
            }
        });
    });
});
/*So modal*/

/*Final process submit*/
$(document).on("click", ".soSubmitProcess", (e) => {
    if ($("#soSubmitModal tbody .form-check-input:checked").length) {
        $("#soSubmitModal").modal("hide");
        let selectedData = [];
        $("#soSubmitModal tbody .form-check-input:checked").each(function (
            index,
            item
        ) {
            let dataItem = JSON.parse($(item).attr("data-item"));
            selectedData.push(dataItem);
        });

        if (selectedData.length) {
            let soTracking = $("#so_tracking_required").val() || "";
            let storeId = $("#store_id").val() || "";
            fetch(processSoActionUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                },
                body: JSON.stringify({
                    selectedData: selectedData,
                    so_tracking_required: soTracking,
                    store_id: storeId,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status == 200) {
                        $("#itemTable .mrntableselectexcel")
                            .empty()
                            .append(data.data.pos);
                        initAutocompVendor("[name*='[vendor_code]']");
                        initializeAutocomplete2(".comp_item_code");
                        $(".soSelect").prop("disabled", true);
                        $("#soSubmitModal").modal("hide");
                        setTimeout(() => {
                            $("#itemTable .mrntableselectexcel tr").each(
                                function (index, item) {
                                    let currentIndex = index + 1;
                                    setAttributesUIHelper(
                                        currentIndex,
                                        "#itemTable"
                                    );
                                }
                            );
                        }, 100);
                    }
                });
        }
    } else {
        // $("#soSubmitModal").modal('hide');
        Swal.fire({
            title: "Error!",
            text: "Please select at least one one so item.",
            icon: "error",
        });
        return false;
    }
});

/*Check attrubute*/
$(document).on("click", ".attributeBtn", (e) => {
    let tr = e.target.closest("tr");
    let item_name = tr.querySelector("[name*=item_code]").value;
    let item_id = tr.querySelector('[name*="[item_id]"]').value;
    let selectedAttr = [];
    const attrElements = tr.querySelectorAll("[name*=attr_name]");
    if (attrElements.length > 0) {
        selectedAttr = Array.from(attrElements).map((element) => element.value);
        selectedAttr = JSON.stringify(selectedAttr);
    }
    if (item_name && item_id) {
        let rowCount = tr.getAttribute("data-index");
        getItemAttribute(item_id, rowCount, selectedAttr, tr);
    } else {
        alert("Please select first item name.");
    }
});

/*For comp attr*/
function getItemAttribute(itemId, rowCount, selectedAttr, tr) {
    let piItemId = $(tr).find('[name*="[pi_item_id]"]').length
        ? $(tr).find('[name*="[pi_item_id]"]').val()
        : "";
    let isSo = $(tr).find('[name*="so_item_id"]').length ? 1 : 0;
    if (!isSo) {
        isSo = $(tr).find('[name*="so_pi_mapping_item_id"]').length ? 1 : 0;
    }
    if (!isSo) {
        if ($(tr).find('td[id*="itemAttribute_"]').data("disabled")) {
            isSo = 1;
        }
    }

    let actionUrl =
        getPiItemAttUrl +
        "?item_id=" +
        itemId +
        `&rowCount=${rowCount}&selectedAttr=${selectedAttr}&pi_item_id=${piItemId}&isSo=${isSo}`;
    fetch(actionUrl).then((response) => {
        return response.json().then((data) => {
            if (data.status == 200) {
                $("#attribute tbody").empty();
                $("#attribute table tbody").append(data.data.html);
                $(tr)
                    .find("td:nth-child(2)")
                    .find("[name*='[attr_name]']")
                    .remove();
                $(tr).find("td:nth-child(2)").append(data.data.hiddenHtml);
                $(tr)
                    .find("td[id*='itemAttribute_']")
                    .attr(
                        "attribute-array",
                        JSON.stringify(data.data.itemAttributeArray)
                    );
                if (data.data.attr) {
                    $("#attribute").modal("show");
                    $("#attribute").on("shown.bs.modal", function () {
                        $("#attribute .select2").select2({
                            dropdownParent: $("#attribute"),
                            searchInputPlaceholder: "Search",
                        });
                        feather.replace();
                    });
                }
                qtyEnabledDisabled();
            }
        });
    });
}

/*Display item detail*/
$(document).on("input change focus", "#itemTable tr input", (e) => {
    let currentTr = e.target.closest("tr");
    let rowCount = $(currentTr).attr("data-index");
    let pName = $(currentTr).find("[name*='component_item_name']").val();
    let itemId = $(currentTr).find("[name*='[item_id]']").val();
    let remark = "";
    if ($(currentTr).find("[name*='remark']")) {
        remark = $(currentTr).find("[name*='remark']").val() || "";
    }
    if (itemId) {
        let selectedAttr = [];
        $(currentTr)
            .find("[name*='attr_name']")
            .each(function (index, item) {
                if ($(item).val()) {
                    selectedAttr.push($(item).val());
                }
            });

        let uomId = $(currentTr).find("[name*='[uom_id]']").val() || "";
        let qty = $(currentTr).find("[name*='[qty]']").val() || "";
        let pi_item_id =
            $(currentTr).find("[name*='[pi_item_id]']").val() || "";
        let so_id = $(currentTr).find("[name*='[so_id]']").val() || "";
        let store_id = $("#store_id").val() || "";
        let sub_store_id = $("#sub_store_id").val() || "";
        let actionUrl =
            getItemDetailsUrl +
            "?item_id=" +
            itemId +
            "&selectedAttr=" +
            JSON.stringify(selectedAttr) +
            "&remark=" +
            remark +
            "&uom_id=" +
            uomId +
            "&qty=" +
            qty +
            "&pi_item_id=" +
            pi_item_id +
            "&so_id=" +
            so_id +
            "&store_id=" +
            store_id +
            "&sub_store_id=" +
            sub_store_id;
        fetch(actionUrl).then((response) => {
            return response.json().then((data) => {
                if (data.status == 200) {
                    $("#itemDetailDisplay").html(data.data.html);
                    let avlStock = data.data?.inventoryStock.confirmedStocks;
                    $(`input[name="components[${rowCount}][avl_stock]"]`).val(
                        Number(avlStock).toFixed(2)
                    );
                    $(`input[name="components[${rowCount}][pending_po]"]`).val(
                        Number(data.data.pendingPo).toFixed(2)
                    );
                }
            });
        });
    }
});

$(document).on("click", "#addNewItemBtn", (e) => {
    let rowsLength = $("#itemTable > tbody > tr").length;
    /*Check last tr data shoud be required*/
    let lastRow = $("#itemTable .mrntableselectexcel tr:last");
    let lastTrObj = {
        item_id: "",
        attr_require: true,
        row_length: lastRow.length,
    };

    if (lastRow.length == 0) {
        lastTrObj.attr_require = false;
        lastTrObj.item_id = "0";
    }

    if (lastRow.length > 0) {
        let item_id = lastRow.find("[name*='[item_id]']").val();
        if (lastRow.find("[name*='attr_name']").length) {
            var emptyElements = lastRow
                .find("[name*='attr_name']")
                .filter(function () {
                    return $(this).val().trim() === "";
                });
            attr_require = emptyElements?.length ? true : false;
        } else {
            attr_require = true;
        }

        lastTrObj = {
            item_id: item_id,
            attr_require: attr_require,
            row_length: lastRow.length,
        };

        if (
            $("tr[id*='row_']:last").find("[name*='[attr_group_id]']").length ==
                0 &&
            item_id
        ) {
            lastTrObj.attr_require = false;
        }
    }
    let soTracking = $("#so_tracking_required").val() || "";
    let actionUrl =
        getPiItemRowUrl +
        "?count=" +
        rowsLength +
        "&component_item=" +
        JSON.stringify(lastTrObj) +
        `&so_tracking_required=${soTracking}`;
    fetch(actionUrl).then((response) => {
        return response.json().then((data) => {
            if (data.status == 200) {
                if (rowsLength) {
                    $("#itemTable > tbody > tr:last").after(data.data.html);
                } else {
                    $("#itemTable > tbody").html(data.data.html);
                }
                initializeAutocomplete2(".comp_item_code");
                $(".soSelect").prop("disabled", true);
                $(".pwoSelect").prop("disabled", true);
                initAutocompVendor("[name*='[vendor_code]']");
                document.getElementById("copy_item_section").style.display = "";
            } else if (data.status == 422) {
                Swal.fire({
                    title: "Error!",
                    text: data.message || "An unexpected error occurred.",
                    icon: "error",
                });
            } else {
                console.log("Someting went wrong!");
            }
        });
    });
});

initializeAutocomplete2(".comp_item_code");

function initializeAutocomplete2(selector, type) {
    $(selector)
        .autocomplete({
            minLength: 0,
            source: function (request, response) {
                let selectedAllItemIds = [];
                $("#itemTable tbody [id*='row_']").each(function (index, item) {
                    if (Number($(item).find('[name*="[item_id]"]').val())) {
                        selectedAllItemIds.push(
                            Number($(item).find('[name*="[item_id]"]').val())
                        );
                    }
                });
                $.ajax({
                    url: "/search",
                    method: "GET",
                    dataType: "json",
                    data: {
                        q: request.term,
                        type: "pi_comp_item",
                        selectedAllItemIds: JSON.stringify(selectedAllItemIds),
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                return {
                                    id: item.id,
                                    label: `${item.item_name} (${item.item_code})`,
                                    code: item.item_code || "",
                                    item_id: item.id,
                                    item_name: item.item_name,
                                    uom_name: item.uom?.name,
                                    uom_id: item.uom_id,
                                    hsn_id: item.hsn?.id,
                                    hsn_code: item.hsn?.code,
                                    alternate_u_o_ms: item.alternate_u_o_ms,
                                    is_attr: item.item_attributes_count,
                                };
                            })
                        );
                    },
                    error: function (xhr) {
                        console.error(
                            "Error fetching customer data:",
                            xhr.responseText
                        );
                    },
                });
            },
            select: function (event, ui) {
                let $input = $(this);
                let itemCode = ui.item.code;
                let itemName = ui.item.value;
                let itemN = ui.item.item_name;
                let itemId = ui.item.item_id;
                let uomId = ui.item.uom_id;
                let uomName = ui.item.uom_name;
                let hsnId = ui.item.hsn_id;
                let hsnCode = ui.item.hsn_code;
                $input.attr("data-name", itemName);
                $input.attr("data-code", itemCode);
                $input.attr("data-id", itemId);
                $input.closest("tr").find('[name*="[item_id]"]').val(itemId);
                $input.closest("tr").find("[name*=item_code]").val(itemCode);
                $input.closest("tr").find("[name*=item_name]").val(itemN);
                $input.closest("tr").find("[name*=hsn_id]").val(hsnId);
                $input.closest("tr").find("[name*=hsn_code]").val(hsnCode);
                $input.val(itemCode);
                let uomOption = `<option value=${uomId}>${uomName}</option>`;
                if (ui.item?.alternate_u_o_ms) {
                    for (let alterItem of ui.item.alternate_u_o_ms) {
                        uomOption += `<option value="${alterItem.uom_id}" ${
                            alterItem.is_purchasing ? "selected" : ""
                        }>${alterItem.uom?.name}</option>`;
                    }
                }
                $input
                    .closest("tr")
                    .find("[name*=uom_id]")
                    .empty()
                    .append(uomOption);
                $input
                    .closest("tr")
                    .find("input[name*='attr_group_id']")
                    .remove();
                setTimeout(() => {
                    if (ui.item.is_attr) {
                        $input
                            .closest("tr")
                            .find(".attributeBtn")
                            .trigger("click");
                    } else {
                        $input
                            .closest("tr")
                            .find(".attributeBtn")
                            .trigger("click");
                        $input
                            .closest("tr")
                            .find('[name*="[qty]"]')
                            .val("")
                            .focus();
                    }
                }, 100);
                validateItems($input, true);
                return false;
            },
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    // $('#itemId').val('');
                    $(this).attr("data-name", "");
                    $(this).attr("data-code", "");
                }
            },
        })
        .focus(function () {
            if (this.value === "") {
                $(this).autocomplete("search", "");
            }
        })
        .on("input", function () {
            if ($(this).val().trim() === "") {
                $(this).removeData("selected");
                $(this)
                    .closest("tr")
                    .find("input[name*='component_item_name']")
                    .val("");
                $(this).closest("tr").find("input[name*='item_name']").val("");
                $(this)
                    .closest("tr")
                    .find("td[id*='itemAttribute_']")
                    .html(defautAttrBtn);
                $(this).closest("tr").find("input[name*='[item_id]']").val("");
                $(this).closest("tr").find("input[name*='item_code']").val("");
                $(this).closest("tr").find("input[name*='attr_name']").remove();
            }
        });
}

/*Set Service Parameter*/
function setServiceParameters(parameters) {
    /*Date Validation*/
    const docDateInput = $("[name='document_date']");
    let isFeature = false;
    let isPast = false;
    if (
        parameters.future_date_allowed &&
        parameters.future_date_allowed.includes("yes")
    ) {
        let futureDate = new Date();
        futureDate.setDate(
            futureDate.getDate() /*+ (parameters.future_date_days || 1)*/
        );
        // docDateInput.val(futureDate.toISOString().split('T')[0]);
        docDateInput.attr("min", new Date().toISOString().split("T")[0]);
        isFeature = true;
    } else {
        isFeature = false;
        docDateInput.attr("max", new Date().toISOString().split("T")[0]);
    }
    if (
        parameters.back_date_allowed &&
        parameters.back_date_allowed.includes("yes")
    ) {
        let backDate = new Date();
        backDate.setDate(
            backDate.getDate() /*- (parameters.back_date_days || 1)*/
        );
        // docDateInput.val(backDate.toISOString().split('T')[0]);
        // docDateInput.attr("max", "");
        isPast = true;
    } else {
        setServiceParameters;
        isPast = false;
        docDateInput.attr("min", new Date().toISOString().split("T")[0]);
    }
    /*Date Validation*/
    if (isFeature && isPast) {
        docDateInput.removeAttr("min");
        docDateInput.removeAttr("max");
    }

    /*Reference from*/
    $("#procurement_type_param").val(parameters.procurement_type);
    if (parameters.procurement_type.includes("all")) {
        $("#procurement_type").val("rm");
    }
    if (parameters.procurement_type.includes("Make to order")) {
        $("#procurement_type").val("rm");
    }
    if (parameters.procurement_type.includes("Buy to order")) {
        $("#procurement_type").val("fg");
    }

    /* ------------------ Reference From ------------------ */
    const reference_from_service = parameters.reference_from_service || [];
    console.log(
        parameters,
        reference_from_service,
        pwoServiceAlias,
        soServiceAlias
    );

    if (reference_from_service.length && reference_from_service.length > 0) {
        if (
            reference_from_service.includes(pwoServiceAlias) ||
            reference_from_service.includes(soServiceAlias) ||
            reference_from_service.includes("d")
        ) {
            $("#reference_from").removeClass("d-none");
            if (reference_from_service.includes(pwoServiceAlias)) {
                $(".pwoSelect").prop("disabled", false);
            }
            if (reference_from_service.includes(soServiceAlias)) {
                $(".soSelect").prop("disabled", false);
            }
            if (reference_from_service.includes("d")) {
                $("#deleteBtn, #addNewItemBtn").removeClass("d-none");
            }
        } else {
            $("#reference_from").addClass("d-none");
            $("#deleteBtn, #addNewItemBt, #copy_item_sectionn").addClass(
                "d-none"
            );
        }
    } else {
        Swal.fire({
            title: "Error!",
            text: "Please update first reference from service param.",
            icon: "error",
        });
        setTimeout(() => {
            location.href = piIndexUrl;
        }, 1500);
    }

    setTimeout(() => {
        if ($("#pi_item_count").length > 0 && $("#pi_item_count").val() > 0) {
            $("#reference_from").addClass("d-none");
        }
    }, 100);

    let requesterType = parameters?.requester_type || "";
    if (requesterType.includes("Department")) {
        $("#user_id_header").addClass("d-none");
        $("#department_id_header").removeClass("d-none");
        $("#requester_type").val("Department");
    } else {
        $("#user_id_header").removeClass("d-none");
        $("#department_id_header").addClass("d-none");
        $("#requester_type").val("User");
    }
    let soTrackingRequired = parameters?.so_tracking_required || "";
    $("#so_tracking_required").val(soTrackingRequired);
    if (soTrackingRequired.includes("yes")) {
        $("#soTrackingText").removeClass("d-none");
        $("#soTrackingNo").removeClass("d-none");
        $("#so_no").removeClass("d-none");
    } else {
        $("#soTrackingText").addClass("d-none");
        $("#soTrackingNo").addClass("d-none");
        $("#so_no").addClass("d-none");
    }
}
