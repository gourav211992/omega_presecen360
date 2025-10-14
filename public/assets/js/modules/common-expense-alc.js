const isEditPage = window.location.pathname.includes("/edit");
let currentProcessType;
let po_header_ids = [];
let grn_header_ids = [];
let po_details_ids = [];
let grn_details_ids = [];

if (!isEditPage) {
    currentProcessType = null;
} else {
    currentProcessType = currentProcessType;
}
$(document).on("change", ".book_id", (e) => {
    let bookId = e.target.value;
    if (bookId) {
        getDocNumberByBookId(bookId);
    } else {
        $(".document_number").val("");
        $(".book_id").val("");
        $(".document_number").attr("readonly", false);
    }
});

function getDocNumberByBookId(bookId) {
    let document_date = $("[name='document_date']").val();
    let queryParams = new URLSearchParams({
        book_id: bookId,
        document_date: document_date,
    }).toString();

    let actionUrl = `${bookUrl}?${queryParams}`;
    fetch(actionUrl).then((response) => {
        return response.json().then((data) => {
            if (data.status == 200) {
                $(".book_code").val(data.data.book_code);
                if (!data.data.doc.document_number) {
                    $(".document_number").val("");
                }
                $(".document_number").val(data.data.doc.document_number);
                if (data.data.doc.type == "Manually") {
                    $(".document_number").attr("readonly", false);
                } else {
                    $(".document_number").attr("readonly", true);
                }
                const parameters = data.data.parameters;
                setServiceParameters(parameters);
            }
            if (data.status == 404) {
                $(".book_code").val("");
                $(".document_number").val("");
                const docDateInput = $("[name='document_date']");
                docDateInput.removeAttr("min");
                docDateInput.removeAttr("max");
                docDateInput.val(new Date().toISOString().split("T")[0]);
                alert(data.message);
            }
        });
    });
}
/*for trigger on edit cases*/
setTimeout(() => {
    let bookId = $(".book_id").val();
    getDocNumberByBookId(bookId);
}, 0);

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
        docDateInput.val(futureDate.toISOString().split("T")[0]);
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
        docDateInput.val(backDate.toISOString().split("T")[0]);
        // docDateInput.attr("max", "");
        isPast = true;
    } else {
        isPast = false;
        docDateInput.attr("min", new Date().toISOString().split("T")[0]);
    }
    /*Date Validation*/
    if (isFeature && isPast) {
        docDateInput.removeAttr("min");
        docDateInput.removeAttr("max");
    }

    /*Reference from*/
    let reference_from_service = parameters.reference_from_service;

    if (reference_from_service.length) {
        let po = "{{ AppHelpersConstantHelper::PO_SERVICE_ALIAS }}";
        let mrn = "{{ AppHelpersConstantHelper::MRN_SERVICE_ALIAS }}";
        if (
            reference_from_service.includes("po") ||
            reference_from_service.includes("mrn")
        ) {
            $(".reference_from").removeClass("d-none");
            if (reference_from_service.includes("po")) {
                $(".poSelect").removeClass("d-none");
            }
        } else {
            $(".reference_from").addClass("d-none");
        }
    } else {
        Swal.fire({
            title: "Error!",
            text: "Please update first reference from service param.",
            icon: "error",
        });
        setTimeout(() => {
            let redirectUrl = `${indexUrl}`;
            location.href = redirectUrl;
        }, 1500);
    }
}

/*Vendor drop down*/
function initializeAutocomplete1(selector, type) {
    $(selector)
        .autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/search",
                    method: "GET",
                    dataType: "json",
                    data: {
                        q: request.term,
                        type: "vendor_list",
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
            minLength: 0,
            select: function (event, ui) {
                var $input = $(this);
                var itemName = ui.item.value;
                var itemId = ui.item.id;
                var itemCode = ui.item.code;
                $input.attr("data-name", itemName);
                $input.val(itemName);
                $(".vendor_id").val(itemId);
                $(".vendor_code").val(itemCode);
                return false;
            },
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $(this).attr("data-name", "");
                }
            },
        })
        .focus(function () {
            if (this.value === "") {
                $(this).autocomplete("search", "");
            }
        });
}
initializeAutocomplete1(".vendor_name");

/*po qty on change in case of direct*/
$(document).on("change", "[name*='po_qty']", function (e) {
    edit = true; // Set edit to true when order_qty changes
    const $tr = $(e.target).closest("tr");
    const $poQtyInput = $tr.find("[name*='po_qty']");
    const $poRateInput = $tr.find("[name*='po_rate']");
    const $poValueInput = $tr.find("[name*='po_value']");
    const dataIndex = $tr.attr("data-index");
    const itemId = $tr.find("[name*='item_id']").val();

    let poQty = parseFloat($poQtyInput.val()) || 0;
    let poRate = parseFloat($poRateInput.val()) || 0;
    let poValue = parseFloat($poValueInput.val()) || 0;

    if (poQty <= 0) {
        Swal.fire({
            title: "Error!",
            text: "Po Qty. cannot be less than 1.",
            icon: "error",
        });
    }

    if (Number(poRate)) {
        let totalValue = parseFloat(poQty) * parseFloat(poRate);
        $poValueInput.val(totalValue.toFixed(6));
    } else {
        $poValueInput.val("");
    }
    setTableCalculation();
});

/*po qty on change in case of direct*/
$(document).on("change", "[name*='po_rate']", function (e) {
    edit = true; // Set edit to true when order_qty changes
    const $tr = $(e.target).closest("tr");
    const $poQtyInput = $tr.find("[name*='po_qty']");
    const $poRateInput = $tr.find("[name*='po_rate']");
    const $poValueInput = $tr.find("[name*='po_value']");
    const dataIndex = $tr.attr("data-index");
    const itemId = $tr.find("[name*='item_id']").val();

    let poQty = parseFloat($poQtyInput.val()) || 0;
    let poRate = parseFloat($poRateInput.val()) || 0;
    let poValue = parseFloat($poValueInput.val()) || 0;

    if (poRate <= 0) {
        Swal.fire({
            title: "Error!",
            text: "Po Rate cannot be less than 1.",
            icon: "error",
        });
    }

    if (Number(poRate)) {
        let totalValue = parseFloat(poQty) * parseFloat(poRate);
        $poValueInput.val(totalValue.toFixed(6));
    } else {
        $poValueInput.val("");
    }
    setTableCalculation();
});

function initializeAutocomplete2(selector, type) {
    $(selector)
        .autocomplete({
            source: function (request, response) {
                let selectedAllItemIds = [];
                $(".poItemsTable tbody [id*='row_']").each(function (
                    index,
                    item
                ) {
                    if (Number($(item).find('[name*="item_id"]').val())) {
                        selectedAllItemIds.push(
                            Number($(item).find('[name*="item_id"]').val())
                        );
                    }
                });
                $.ajax({
                    url: "/search",
                    method: "GET",
                    dataType: "json",
                    data: {
                        q: request.term,
                        type: "service_item_list",
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
                                };
                            })
                        );
                    },
                    error: function (xhr) {
                        console.error(
                            "Error fetching item data:",
                            xhr.responseText
                        );
                    },
                });
            },
            minLength: 0,
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
                let closestTr = $input.closest("tr");
                closestTr.find("[name*=item_id]").val(itemId);
                closestTr.find("[name*=item_code]").val(itemCode);
                closestTr.find("[name*=item_name]").val(itemN);
                closestTr.find("[name*=hsn_id]").val(hsnId);
                closestTr.find("[name*=hsn_code]").val(hsnCode);
                closestTr.find("td[id*='itemAttribute_']").html(defautAttrBtn);
                $input.val(itemCode);
                let uomOption = `<option value=${uomId}>${uomName}</option>`;
                if (ui.item?.alternate_u_o_ms) {
                    for (let alterItem of ui.item.alternate_u_o_ms) {
                        uomOption += `<option value="${alterItem.uom_id}" ${
                            alterItem.is_purchasing ? "selected" : ""
                        }>${alterItem.uom?.name}</option>`;
                    }
                }
                closestTr.find("[name*=uom_id]").append(uomOption);
                closestTr.find(".attributeBtn").trigger("click");
                setTimeout(() => {
                    $input
                        .closest("tr")
                        .find('[name*="[po_qty]"]')
                        .val("1")
                        .focus();
                }, 100);
                // getItemDetail(closestTr);
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
        });
}

/*Add New Item Row*/
$(document).on("click", ".addNewItemBtn", (e) => {
    // for component item code
    initializeAutocomplete2();
    let rowsLength = $(".poItemsTable > tbody > tr").length;
    /*Check last tr data shoud be required*/
    let lastRow = $(".poItemsTable .mrntableselectexcel tr:last");
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
        let item_id = lastRow.find("[name*='item_id']").val();
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

    let queryParams = new URLSearchParams({
        count: rowsLength,
        component_item: JSON.stringify(lastTrObj),
    }).toString();

    let actionUrl = `${addItemRowUrl}?${queryParams}`;
    fetch(actionUrl).then((response) => {
        return response.json().then((data) => {
            if (data.status == 200) {
                // $("#submit-button").click();
                if (rowsLength) {
                    $(".poItemsTable > tbody > tr:last").after(data.data.html);
                } else {
                    $(".poItemsTable > tbody").html(data.data.html);
                }
                initializeAutocomplete2(".comp_item_code");
                $(".module_type").val("po");
                focusAndScrollToLastRowInput();
                setTableCalculation();
            } else if (data.status == 422) {
                Swal.fire({
                    title: "Error!",
                    text: data.message || "An unexpected error occurred.",
                    icon: "error",
                });
            } else {
                Swal.fire({
                    title: "Error!",
                    text: "Someting went wrong!",
                    icon: "error",
                });
            }
        });
    });
});

/*Open Po model*/
let poOrderTable;
$(document).on("click", ".poSelect", (e) => {
    tableRowCount = $(".mrntableselectexcel tr").length;
    $(".poModal").modal("show");
    currentProcessType = "po";
    openPurchaseRequest();
    const tableSelector = ".poModal .po-order-detail";
    $(tableSelector).DataTable().clear().destroy();
    getPurchaseOrders();
    if ($(tableSelector).length) {
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            poOrderTable = $(tableSelector).DataTable();
            poOrderTable.ajax.reload();
        }
        // Re-initialize DataTable
    }
});

function getSelectedPoTypes() {
    let moduleTypes = [];
    $(".po_item_checkbox:checked").each(function () {
        moduleTypes.push($(this).attr("data-module")); // Corrected: Get attribute value instead of setting it
    });
    return moduleTypes;
}

function openPurchaseRequest() {
    initializeAutocompleteQt(
        "po_vendor_code_input_qt",
        "po_vendor_id_qt_val",
        "vendor_list",
        "vendor_code",
        "company_name"
    );
    initializeAutocompleteQt(
        "po_document_no_input_qt",
        "po_document_id_qt_val",
        "po_document_qt",
        "document_number",
        ""
    );
}

function initializeAutocompleteQt(
    selector,
    selectorSibling,
    typeVal,
    labelKey1,
    labelKey2 = ""
) {
    let modalType = ".poModal";
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
                        vendor_id: $(".po_vendor_id_qt_val").val(),
                        header_book_id: $(".book_id").val(),
                        store_id: $(".store_id_po").val() || "",
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                let label = "";
                                if (
                                    "document_number" in item &&
                                    "book_code" in item
                                ) {
                                    label = `${item.document_number}`;
                                } else if ("company_name" in item) {
                                    label = item.company_name;
                                }

                                return {
                                    id: item.id,
                                    label: label,
                                    code:
                                        item.book_code ||
                                        item.vendor_code ||
                                        "",
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
            appendTo: modalType,
            minLength: 0,
            select: function (event, ui) {
                var $input = $(this);
                $input.val(ui.item.label);
                $("#" + selectorSibling).val(ui.item.id);
                $(".poModal .po-order-detail").DataTable().ajax.reload();
                return false;
            },
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $("#" + selectorSibling).val("");
                    $(".poModal .po-order-detail").DataTable().ajax.reload();
                }
            },
        })
        .focus(function () {
            if (this.value === "") {
                $("#" + selectorSibling).val("");
                $(".poModal .po-order-detail").DataTable().ajax.reload();
                $(this).autocomplete("search", "");
            }
        })
        .blur(function () {
            if (this.value === "") {
                $("#" + selectorSibling).val("");
                $(".poModal .po-order-detail").DataTable().ajax.reload();
            }
        });
}

function renderData(data) {
    return data ? data : "";
}

function getDynamicParams() {
    let document_date = "",
        header_book_id = "",
        series_id = "",
        document_number = "",
        asn_number = "",
        item_id = "",
        vendor_id = "",
        store_id = "",
        so_id = "",
        item_search = "",
        selected_po_ids = "";

    if (currentProcessType === "po") {
        let selectedPoIds = localStorage.getItem("selectedPoIds") ?? "[]";
        selectedPoIds = JSON.parse(selectedPoIds);
        selectedPoIds = encodeURIComponent(JSON.stringify(selectedPoIds));
        (document_date = $("[name ='document_date']").val() || ""),
            (header_book_id = $(".book_id").val() || ""),
            (series_id = $(".book_id_qt_val").val() || ""),
            (document_number = $(".po_document_id_qt_val").val() || ""),
            (item_id = $(".po_item_id_qt_val").val() || ""),
            (vendor_id = $(".po_vendor_id_qt_val").val()),
            (store_id = $(".header_store_id").val() || ""),
            (item_search = $(".po_item_name_search").length
                ? $(".po_item_name_search").val()
                : ""),
            (selected_po_ids = selectedPoIds);
        selected_po_ids = encodeURIComponent(selectedPoIds);
    }
    if (currentProcessType === "grn") {
        let selectedGrnIds = localStorage.getItem("selectedGrnIds") ?? "[]";
        selectedGrnIds = JSON.parse(selectedGrnIds);
        selectedGrnIds = encodeURIComponent(JSON.stringify(selectedGrnIds));
        (document_date = $("[name ='document_date']").val() || ""),
            (header_book_id = $(".book_id").val() || ""),
            (series_id = $(".book_id_qt_val").val() || ""),
            (document_number = $(".grn_document_id_qt_val").val() || ""),
            (item_id = $(".grn_item_id_qt_val").val() || ""),
            (vendor_id = $(".grn_vendor_id_qt_val").val()),
            (store_id = $(".header_store_id").val() || ""),
            (item_search = $(".grn_item_name_search").length
                ? $(".grn_item_name_search").val()
                : ""),
            (selected_grn_ids = selectedGrnIds);
        selected_po_ids = encodeURIComponent(selectedGrnIds);
    }
    return {
        document_date: document_date,
        header_book_id: header_book_id,
        series_id: series_id,
        document_number: document_number,
        asn_number: asn_number,
        item_id: item_id,
        vendor_id: vendor_id,
        store_id: store_id,
        so_id: so_id,
        item_search: item_search,
        selected_po_ids: selected_po_ids,
        po_header_ids: encodeURIComponent(po_header_ids),
        po_details_ids: encodeURIComponent(po_details_ids),
        grn_header_ids: encodeURIComponent(grn_header_ids),
        grn_details_ids: encodeURIComponent(grn_details_ids),
    };
}

function getPurchaseOrders() {
    const ajaxUrl = getPoUrl;
    var columns = [];
    columns = [
        {
            data: "id",
            visible: false,
            orderable: true,
            searchable: false,
        },
        {
            data: "select_checkbox",
            name: "select_checkbox",
            orderable: false,
            searchable: false,
        },
        {
            data: "vendor",
            name: "vendor",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "po_doc",
            name: "po_doc",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "po_date",
            name: "po_date",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "item_code",
            name: "item_code",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "item_name",
            name: "item_name",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "attributes",
            name: "attributes",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "order_qty",
            name: "order_qty",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "inv_order_qty",
            name: "inv_order_qty",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "expense_advise_qty",
            name: "expense_advise_qty",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "balance_qty",
            name: "balance_qty",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "rate",
            name: "rate",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "total_amount",
            name: "total_amount",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
    ];
    initializeDataTableCustom(".poModal .po-order-detail", ajaxUrl, columns);
}

$(document).on("keyup", ".po_item_name_search", (e) => {
    $(".poModal .po-order-detail").DataTable().ajax.reload();
});

/*Checkbox for po/si item list*/
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

function getSelectedPoIDS() {
    let ids = [];
    let referenceNos = [];

    $(".po_item_checkbox:checked").each(function () {
        ids.push($(this).val());
        referenceNo = $(this)
            .siblings("input[type='hidden'][name='reference_no']")
            .val();
        if (referenceNo) {
            referenceNos.push(referenceNo);
        }
    });
    return {
        ids: ids,
        referenceNos: referenceNos,
    };
}

$(document).on("click", ".poProcess", (e) => {
    let result = getSelectedPoIDS();
    let ids = result.ids;
    let referenceNo = result.referenceNos[0];
    currentProcessType = "po";
    if (!ids.length) {
        $(".poModal").modal("hide");
        Swal.fire({
            title: "Error!",
            text: "Please select at least one one po",
            icon: "error",
        });
        return false;
    }

    let moduleTypes = getSelectedPoTypes();

    $("[name='po_item_ids']").val(ids);
    // $("#addNewItemBtn").hide();
    if (referenceNo) {
        $("#referenceNoDiv").show();
        // $("#reference_number_input").val(referenceNo);
    } else {
        $("#referenceNoDiv").hide();
        // $("#reference_number_input").val('');
    }
    $(".reference_type_input").val("po");

    // for component item code
    function initializeAutocomplete2(selector, type) {
        $(selector)
            .autocomplete({
                minLength: 0,
                source: function (request, response) {
                    let selectedAllItemIds = [];
                    $(".itemTable tbody [id*='row_']").each(function (
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
                            type: "service_item_list",
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
                    $input
                        .closest("tr")
                        .find("td[id*='itemAttribute_']")
                        .html(defautAttrBtn);
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
                                .find('[name*="[accepted_qty]"]')
                                .val("")
                                .focus();
                        }
                    }, 100);
                    getItemDetail($input.closest("tr"), currentProcessType);
                    getItemCostPrice($input.closest("tr"));
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
            });
    }

    let groupItems = [];
    $("tr[data-group-item]").each(function () {
        let groupItemData = $(this).data("group-item");
        groupItems.push(groupItemData);
    });

    groupItems = JSON.stringify(groupItems);
    let current_row_count = $("tbody tr[id*='row_']").length;
    let processData = {
        ids: ids,
        type: "po",
        module_type: moduleTypes,
    };
    $(".reference_type_input").val("po");
    asnProcess(processData, "po-process");
});

$(document).on("click", ".clearPoFilter", (e) => {
    $(".po_item_name_input_qt").val("");
    $(".po_item_id_qt_val").val("");
    $(".po_vendor_code_input_qt").val("");
    $(".po_vendor_id_qt_val").val("");
    $(".po_document_no_input_qt").val("");
    $(".po_document_id_qt_val").val("");
    $(".po_item_name_search").val("");
    $(".poModal .po-order-detail").DataTable().ajax.reload();
});

/*Open GRN model*/
let grnOrderTable;
$(document).on("click", ".grnSelect", (e) => {
    tableRowCount = $(".mrntableselectexcel tr").length;
    $(".grnModal").modal("show");
    currentProcessType = "grn";
    openGrnRequest();
    const tableSelector = ".grnModal .grn-order-detail";
    $(tableSelector).DataTable().clear().destroy();
    getGrn();
    if ($(tableSelector).length) {
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            grnOrderTable = $(tableSelector).DataTable();
            grnOrderTable.ajax.reload();
        }
        // Re-initialize DataTable
    }
});

function getSelectedGrnTypes() {
    let moduleTypes = [];
    $(".grn_item_checkbox:checked").each(function () {
        moduleTypes.push($(this).attr("data-module")); // Corrected: Get attribute value instead of setting it
    });
    return moduleTypes;
}

function openGrnRequest() {
    initializeAutocompleteQtGrn(
        "grn_vendor_code_input_qt",
        "grn_vendor_id_qt_val",
        "vendor_list",
        "vendor_code",
        "company_name"
    );
    initializeAutocompleteQtGrn(
        "grn_document_no_input_qt",
        "grn_document_id_qt_val",
        "mrn_document_qt",
        "document_number",
        ""
    );
}

function initializeAutocompleteQtGrn(
    selector,
    selectorSibling,
    typeVal,
    labelKey1,
    labelKey2 = ""
) {
    let modalType = ".grnModal";

    $("." + selector)
        .autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/search",
                    method: "GET",
                    dataType: "json",
                    data: {
                        q: request.term,
                        type: typeVal,
                        vendor_id: $(".grn_vendor_id_qt_val").val(),
                        header_book_id: $(".book_id").val(),
                        store_id: $(".store_id").val() || "",
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                let label = "";

                                if (
                                    "document_number" in item &&
                                    "book_code" in item
                                ) {
                                    label = `${item.document_number}`;
                                } else if ("company_name" in item) {
                                    label = item.company_name;
                                }

                                return {
                                    id: item.id,
                                    label: label,
                                    code:
                                        item.book_code ||
                                        item.vendor_code ||
                                        "",
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
            appendTo: modalType,
            minLength: 0,
            select: function (event, ui) {
                var $input = $(this);
                $input.val(ui.item.label);
                $("#" + selectorSibling).val(ui.item.id);
                $(".grnModal .mrn-order-detail").DataTable().ajax.reload();
                return false;
            },
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $("#" + selectorSibling).val("");
                    $(".grnModal .mrn-order-detail").DataTable().ajax.reload();
                }
            },
        })
        .focus(function () {
            if (this.value === "") {
                $("#" + selectorSibling).val("");
                $(".grnModal .mrn-order-detail").DataTable().ajax.reload();
                $(this).autocomplete("search", "");
            }
        })
        .blur(function () {
            if (this.value === "") {
                $("#" + selectorSibling).val("");
                $(".grnModal .mrn-order-detail").DataTable().ajax.reload();
            }
        });
}

function getGrn() {
    const ajaxUrl = getGrnUrl;
    var columns = [];
    columns = [
        { data: "id", visible: false, orderable: true, searchable: false },
        {
            data: "select_checkbox",
            name: "select_checkbox",
            orderable: false,
            searchable: false,
        },
        {
            data: "vendor",
            name: "vendor",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "doc_no",
            name: "doc_no",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "doc_date",
            name: "doc_date",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "item_code",
            name: "item_code",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "item_name",
            name: "item_name",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "attributes",
            name: "attributes",
            render: renderData,
            orderable: false,
            searchable: false,
        },
        {
            data: "order_qty",
            name: "order_qty",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "available_qty",
            name: "available_qty",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "rate",
            name: "rate",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
        {
            data: "amount",
            name: "amount",
            render: renderData,
            orderable: false,
            searchable: false,
            createdCell: function (td, cellData, rowData, row, col) {
                $(td).addClass("text-end");
            },
        },
    ];
    initializeDataTableCustom(".grnModal .grn-order-detail", ajaxUrl, columns);
}

$(document).on("keyup", ".grn_item_name_search", (e) => {
    $(".grnModal .grn-order-detail").DataTable().ajax.reload();
});

/*Checkbox for po/si item list*/
$(document).on("change", ".grn-order-detail > thead .form-check-input", (e) => {
    if (e.target.checked) {
        $(".grn-order-detail > tbody .form-check-input").each(function () {
            $(this).prop("checked", true);
        });
    } else {
        $(".grn-order-detail > tbody .form-check-input").each(function () {
            $(this).prop("checked", false);
        });
    }
});

function getSelectedGrnIDS() {
    let ids = [];
    let referenceNos = [];

    $(".grn_item_checkbox:checked").each(function () {
        ids.push($(this).val());
        referenceNo = $(this)
            .siblings("input[type='hidden'][name='reference_no']")
            .val();
        if (referenceNo) {
            referenceNos.push(referenceNo);
        }
    });
    return {
        ids: ids,
        referenceNos: referenceNos,
    };
}

$(document).on("click", ".grnProcess", (e) => {
    let result = getSelectedGrnIDS();
    let ids = result.ids;
    let referenceNo = result.referenceNos[0];
    let idsLength = ids.length;
    currentProcessType = "grn";
    let header_store_id = $(".header_store_id").val();

    if (!ids.length) {
        $(".grnModal").modal("hide");
        Swal.fire({
            title: "Error!",
            text: "Please select at least one grn",
            icon: "error",
        });
        return false;
    }

    let moduleTypes = getSelectedGrnTypes();
    $("[name='mrn_item_ids']").val(ids);
    if (referenceNo) {
        $(".referenceNoDiv").show();
        $(".reference_number_input").val(referenceNo);
    } else {
        $(".referenceNoDiv").hide();
        $(".reference_number_input").val("");
    }
    $(".reference_type_input").val("mrn");

    // for component item code
    function initializeAutocomplete2(selector, type) {
        $(selector)
            .autocomplete({
                minLength: 0,
                source: function (request, response) {
                    let selectedAllItemIds = [];
                    $(".grnItemsTable tbody [id*='row_']").each(function (
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
                            type: "goods_item_list",
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
                    $input
                        .closest("tr")
                        .find("td[id*='itemAttribute_']")
                        .html(defautAttrBtn);
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
                                .find('[name*="[order_qty]"]')
                                .val("")
                                .focus();
                        }
                    }, 100);
                    getItemDetail($input.closest("tr"), currentProcessType);
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
            });
    }

    let transactionDate = $("input[name='document_date']").val() || "";
    let groupItems = [];
    $("tr[data-group-item]").each(function () {
        let groupItemData = $(this).data("group-item");
        groupItems.push(groupItemData);
    });

    groupItems = JSON.stringify(groupItems);
    let current_row_count = $("tbody tr[id*='row_']").length;
    let processData = {
        ids: ids,
        type: "grn",
        referenceNo: referenceNo,
        store_id: header_store_id,
        module_type: moduleTypes,
    };

    asnProcess(processData, "grn-process");
});

$(document).on("click", ".clearGrnFilter", (e) => {
    $(".grn_item_name_input_qt").val("");
    $(".grn_item_id_qt_val").val("");
    $(".grn_vendor_code_input_qt").val("");
    $(".grn_vendor_id_qt_val").val("");
    $(".grn_document_no_input_qt").val("");
    $(".grn_document_id_qt_val").val("");
    $(".grn_item_name_search").val("");
    $(".grnModal .grn-order-detail").DataTable().ajax.reload();
});

// Asn Process
function asnProcess(asnData, moduleProcess) {
    // ---------- tiny helpers (scoped to this function) ----------
    const safeJSON = (v, fallback) => {
        try {
            return JSON.parse(v);
        } catch {
            return fallback;
        }
    };
    const getMeta = (name, def = "") =>
        $(`meta[name='${name}']`).attr("content") || def;
    const toArr = (v) => (Array.isArray(v) ? v : v == null ? [] : [v]);

    // Map any incoming module_type to tab key
    const resolveTabKey = (mt) => {
        // normalize single value or first of array
        const raw = Array.isArray(mt) ? mt[0] || "" : mt || "";
        const s = String(raw).toLowerCase();
        if (s === "mrn-order" || s === "grn" || s === "ge" || s === "asn")
            return "grn";
        // default to PO (purchase order / expense side)
        return "po";
    };

    // Tab containers (adjust if your IDs differ)
    const TABS = { po: ".poItems", grn: ".grnItems" };
    // Row selectors inside each tab (adjust if your classes differ)
    const ROWS = { po: ".po-row", grn: ".grn-row" };

    // Count rows in a specific tab (even if hidden); ignore soft-deleted/template rows
    function countRowsInTab(tabKey) {
        const $scope = $(TABS[tabKey]).length
            ? $(TABS[tabKey])
            : $(document.body);
        const rowSel = ROWS[tabKey];
        return $scope.find(rowSel).filter(function () {
            const $r = $(this);
            const softRemoved =
                $r.hasClass("is-deleted") ||
                String($r.attr("data-removed")) === "1" ||
                $r.data("removed") === 1;
            const isTemplate = $r.is(
                '[data-template="1"], .template-row, .d-template'
            );
            return !softRemoved && !isTemplate; // allow hidden (other tab)
        }).length;
    }

    // Append HTML `pos` into the correct table inside a tab
    function appendRowsIntoTab(tabKey, html) {
        if (tabKey === "grn") {
            return $(".grnItemsTable .mrntableselectexcel").append(html);
        }
        // default PO
        return $(".poItemsTable .mrntableselectexcel").append(html);
    }

    // ---------- inputs & normalization ----------
    const processType = (asnData?.type || "po").toLowerCase(); // "po" or "grn"
    const idsJson = JSON.stringify(toArr(asnData?.ids));
    const moduleTypesJson = JSON.stringify(toArr(asnData?.module_type));

    const tabKey = resolveTabKey(asnData?.module_type); // "po" | "grn"
    let current_row_count = countRowsInTab(tabKey);

    // Selected IDs per processType (stored in localStorage as JSON array)
    const lsKeys = { po: "selectedPoIds", grn: "selectedGrnIds" };
    const selectedLsKey = lsKeys[processType] || lsKeys.po;
    const selectedIdsArr = safeJSON(
        localStorage.getItem(selectedLsKey) || "[]",
        []
    );

    // Build base route & params
    const baseRoute = processType === "po" ? processPoUrl : processGrnUrl;
    const routeType = getMeta("route-type", "create"); // e.g., "create" | "edit"
    const transactionDate = $("[name='document_date']").val();

    // Some backends expect tableRowCount; keep both names to be safe
    const tableRowCount = current_row_count;

    // Param for selected ids differs by type
    const selectedParamKey =
        processType === "grn" ? "selected_grn_ids" : "selected_po_ids";

    const actionUrl =
        baseRoute.replace(":type", routeType) +
        "?ids=" +
        encodeURIComponent(idsJson) +
        "&moduleTypes=" +
        encodeURIComponent(moduleTypesJson) +
        "&tableRowCount=" +
        tableRowCount +
        "&current_row_count=" +
        current_row_count +
        "&d_date=" +
        encodeURIComponent(transactionDate || "") +
        `&${selectedParamKey}=` +
        encodeURIComponent(JSON.stringify(selectedIdsArr)) +
        "&type=create";

    // ---------- request ----------
    fetch(actionUrl)
        .then((res) => res.json())
        .then((data) => {
            console.log("ASN response", data);

            if (data.status !== 200) {
                return handleAsnError?.(
                    data.message || "Unable to process ASN."
                );
            }

            // Server payload
            const payload = data.data || {};
            const posHtml = payload.pos || ""; // HTML rows
            const serverModuleType = payload.moduleType || tabKey; // fallback to our tabKey

            // Final model type we operate on in UI ("po" or "grn")
            const modelType = processType === "grn" ? "grn" : "po";

            // Merge & persist selected IDs
            const getSelectedFn =
                modelType === "grn"
                    ? window.getSelectedGrnIDS
                    : window.getSelectedPoIDS;
            const fromUI =
                typeof getSelectedFn === "function"
                    ? getSelectedFn().ids || []
                    : [];
            const existing = safeJSON(
                localStorage.getItem(selectedLsKey) || "[]",
                []
            );
            const merged = Array.from(
                new Set([...(existing || []), ...fromUI])
            );
            localStorage.setItem(selectedLsKey, JSON.stringify(merged));

            // Hidden field per model
            const hiddenFieldName =
                modelType === "grn" ? "grn_item_ids" : "po_item_ids";
            $(`[name='${hiddenFieldName}']`).val(merged.join(","));

            // Stamp module type for form state/submit
            $(".module_type").val(modelType);

            // Append new rows into the correct tab table
            appendRowsIntoTab(modelType, posHtml);

            // Recompute the tab-wise row count after insertion
            current_row_count = countRowsInTab(modelType);

            // Init any UI features for the inserted rows
            try {
                initializeAutocomplete2?.(".comp_item_code");
            } catch (e) {}
            $(".poModal, .grnModal").modal("hide");

            // Reference type (drives server-side processing later)
            switch ((moduleProcess || "").toLowerCase()) {
                case "po-process":
                    $(".reference_type_input").val("po");
                    break;
                case "grn-process":
                    $(".reference_type_input").val("grn");
                    break;
                default:
                    $(".reference_type_input").val("");
                    break;
            }

            // Supplier header fields reset
            $(
                "[name='supplier_invoice_no'], [name='supplier_invoice_date'], [name='consignment_no'], [name='eway_bill_no'], [name='transporter_name'], [name='vehicle_no']"
            ).val("");

            // Post-insert calculations & per-row attribute UI
            setTimeout(() => {
                setTableCalculation();
                const $targetTable =
                    modelType === "grn"
                        ? $(".grnItemsTable .mrntableselectexcel")
                        : $(".poItemsTable .mrntableselectexcel");

                const $tabTable =
                    modelType === "grn" ? ".grnItemsTable" : ".poItemsTable";

                $targetTable.find("tr").each((idx, tr) => {
                    try {
                        setAttributesUIHelper?.(idx + 1, $tabTable);
                    } catch (e) {}
                });

                // If your distribution engine exists, mark dirty or auto-run
                if (typeof window.markDistributionDirty === "function") {
                    window.markDistributionDirty();
                }
                if (
                    typeof window.runDistribution === "function" &&
                    window.distributedOK
                ) {
                    // keep allocations in sync if a distribution was already confirmed
                    try {
                        window.runDistribution({ showSuccess: false });
                    } catch (e) {}
                }
            }, 1000); // faster feedback than 3000ms; adjust if your HTML needs longer
        })
        .catch(() => {
            Swal.fire({
                title: "Error!",
                text: "An unexpected error occurred while processing ASN.",
                icon: "error",
            });
        });
}

function handleAsnError(message = "Invalid data") {
    $("#reference_from").removeClass("d-none");
    Swal.fire({
        title: "Error!",
        text: message,
        icon: "error",
    });
}

setTimeout(() => {
    $(".grnItemsTable .grnItemsBody tr").each(function (index, item) {
        let currentIndex = index + 1;
        setAttributesUIHelper(currentIndex, ".grnItemsTable");
    });
}, 100);

$(document).on("click", 'td[id*="itemAttribute_"]', (e) => {
    let dataAttributes = $(e.target).attr("data-attributes");
});
