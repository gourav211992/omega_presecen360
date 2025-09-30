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
                if (
                    parameters?.tax_required.some(
                        (val) => val.toLowerCase() === "yes"
                    )
                ) {
                    $(".tax_required").val(parameters?.tax_required[0]);
                } else {
                    $(".tax_required").val("");
                }
                implementBookDynamicFields(
                    data.data.dynamic_fields_html,
                    data.data.dynamic_fields
                );
            }
            if (data.status == 404) {
                $(".book_code").val("");
                $(".document_number").val("");
                $(".tax_required").val("");
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

function implementBookDynamicFields(html, data) {
    let dynamicBookSection = document.getElementById("dynamic_fields_section");
    dynamicBookSection.innerHTML = html;
    if (data && data.length > 0) {
        dynamicBookSection.classList.remove("d-none");
    } else {
        dynamicBookSection.classList.add("d-none");
    }
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
        "vendor_code_input_qt",
        "vendor_id_qt_val",
        "vendor_list",
        "vendor_code",
        "company_name"
    );
    initializeAutocompleteQt(
        "document_no_input_qt",
        "document_id_qt_val",
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
                        vendor_id: $("#vendor_id_qt_val").val(),
                        header_book_id: $("#book_id").val(),
                        store_id: $("#store_id_po").val() || "",
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                // return {
                                //     id: item.id,
                                //     label: `${item[labelKey1]} ${labelKey2 ? (item[labelKey2] ? '(' + item[labelKey2] + ')' : '') : ''}`,
                                //     code: item[labelKey1] || '',
                                // };
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
            (header_book_id = $("#book_id").val() || ""),
            (series_id = $("#book_id_qt_val").val() || ""),
            (document_number = $("#document_id_qt_val").val() || ""),
            (asn_number = $("#asn_id_qt_val").val() || ""),
            (item_id = $("#item_id_qt_val").val() || ""),
            (vendor_id = $("#vendor_id_qt_val").val()),
            (store_id = $(".header_store_id").val() || ""),
            (so_id = $("#po_so_qt_val").val() || ""),
            (item_search = $("#item_name_search").length
                ? $("#item_name_search").val()
                : ""),
            (selected_po_ids = selectedPoIds);
        selected_po_ids = encodeURIComponent(selectedPoIds);
    }
    if (currentProcessType === "grn") {
        let selectedGrnIds = localStorage.getItem("selectedGrnIds") ?? "[]";
        selectedGrnIds = JSON.parse(selectedGrnIds);
        selectedGrnIds = encodeURIComponent(JSON.stringify(selectedGrnIds));
        (document_date = $("[name ='document_date']").val() || ""),
            (header_book_id = $("#book_id").val() || ""),
            (series_id = $("#book_id_qt_val").val() || ""),
            (document_number = $("#document_id_qt_val").val() || ""),
            (asn_number = $("#asn_id_qt_val").val() || ""),
            (item_id = $("#item_id_qt_val").val() || ""),
            (vendor_id = $("#vendor_id_qt_val").val()),
            (store_id = $(".header_store_id").val() || ""),
            (so_id = $("#po_so_qt_val").val() || ""),
            (item_search = $("#item_name_search").length
                ? $("#item_name_search").val()
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

$(document).on("keyup", "#item_name_search", (e) => {
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
    let asnIds = [];
    let asnItemIds = [];
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

    let currencyId = $("select[name='currency_id']").val();
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
        type: "po",
        module_type: moduleTypes,
    };
    $(".reference_type_input").val("po");
    asnProcess(processData, "po-process");
});

$(document).on("click", ".clearPoFilter", (e) => {
    $("#item_name_input_qt").val("");
    $("#item_id_qt_val").val("");
    $("#store_po").val("");
    $("#store_id_po").val("");
    $("#sub_store_po").val("");
    $("#sub_store_id_po").val("");
    $("#vendor_code_input_qt").val("");
    $("#vendor_id_qt_val").val("");
    $("#book_code_input_qt").val("");
    $("#book_id_qt_val").val("");
    $("#document_no_input_qt").val("");
    $("#document_id_qt_val").val("");
    $("#po_so_no_input_qt").val("");
    $("#po_so_qt_val").val("");
    $("#item_name_search").val("");
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
    initializeAutocompleteQt(
        "vendor_code_input_qt",
        "vendor_id_qt_val",
        "vendor_list",
        "vendor_code",
        "company_name"
    );
    initializeAutocompleteQt(
        "document_no_input_qt",
        "document_id_qt_val",
        "mrn_document_qt",
        "document_number",
        ""
    );
    initializeAutocompleteQt(
        "so_no_input_qt",
        "so_qt_val",
        "so_qt",
        "book_code",
        "document_number"
    );
}

function initializeAutocompleteQt(
    selector,
    selectorSibling,
    typeVal,
    labelKey1,
    labelKey2 = ""
) {
    let modalType = ".grnModal";
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
                        vendor_id: $("#vendor_id_qt_val").val(),
                        header_book_id: $("#book_id").val(),
                        store_id: $("#store_id").val() || "",
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

$(document).on(
    "autocompletechange autocompleteselect",
    "#store_po",
    function (event, ui) {
        let storeId = ui?.item?.id || "";
        initializeAutocompleteQt(
            "sub_store_po",
            "sub_store_id_po",
            "sub_store",
            "name",
            ""
        );
    }
);

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

$(document).on("keyup", "#grn_item_name_search", (e) => {
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
            text: "Please select at least one one grn",
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
                                .find(".attributeBtn")
                                .trigger("click");
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

// Asn Process
function asnProcess(asnData, moduleProcess) {
    const current_row_count = $("tbody tr[id*='row_']").length;
    const processType = asnData.type;
    let selectedIds =
        processType === "grn"
            ? localStorage.getItem("selectedGrnIds") ?? []
            : localStorage.getItem("selectedPoIds") ?? [];

    const ids = JSON.stringify(asnData.ids);
    const moduleTypes = JSON.stringify(asnData.module_type);
    const moduleType = asnData.module_type?.[0] ?? "po";

    const currencyId = $("[name='currency_id']").val();
    const transactionDate = $("[name='document_date']").val();
    const type = $("meta[name='route-type']").attr("content"); // blade->meta

    const baseRoute = processType === "po" ? processPoUrl : processGrnUrl;
    console.log(baseRoute, processType);

    const actionUrl =
        baseRoute.replace(":type", type) +
        "?ids=" +
        encodeURIComponent(ids) +
        "&moduleTypes=" +
        moduleTypes +
        "&tableRowCount=" +
        tableRowCount +
        "&d_date=" +
        encodeURIComponent(transactionDate) +
        "&selected_po_ids=" +
        encodeURIComponent(selectedIds) +
        "&type=" +
        "create" +
        "&current_row_count=" +
        current_row_count;

    fetch(actionUrl)
        .then((res) => res.json())
        .then((data) => {
            if (data.status !== 200) return handleAsnError(data.message);

            const { pos, moduleType } = data.data;
            const modelType = processType === "grn" ? "grn" : "po";
            const order =
                modelType === "grn"
                    ? data.data.grnOrder
                    : data.data.purchaseOrder;

            const getSelectedIdsFn =
                modelType === "grn" ? getSelectedGrnIDS : getSelectedPoIDS;
            const hiddenFieldName =
                modelType === "grn" ? "grn_item_ids" : "po_item_ids";
            const localStorageKey =
                modelType === "grn" ? "selectedGrnIds" : "selectedPoIds";

            const newIds = getSelectedIdsFn().ids;
            const existingIds = JSON.parse(
                localStorage.getItem(localStorageKey) || "[]"
            );
            const mergedIds = Array.from(new Set([...existingIds, ...newIds]));
            localStorage.setItem(localStorageKey, JSON.stringify(mergedIds));
            $(`[name='${hiddenFieldName}']`).val(mergedIds.join(","));

            $(".module_type").val(modelType);
            console.log("moduleType", modelType);

            if (modelType === "po") {
                $(".poItemsTable .mrntableselectexcel").append(pos);
            } else {
                $(".grnItemsTable .mrntableselectexcel").append(pos);
            }
            initializeAutocomplete2(".comp_item_code");

            $(".poModal, .grnModal").modal("hide");

            switch (moduleProcess) {
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

            // Supplier details
            $(
                "[name='supplier_invoice_no'], [name='supplier_invoice_date'], [name='consignment_no'], [name='eway_bill_no'], [name='transporter_name'], [name='vehicle_no']"
            ).val("");

            setTimeout(() => {
                setTableCalculation();
                $(".itemTable .mrntableselectexcel tr").each((index, item) => {
                    setAttributesUIHelper(index + 1, ".itemTable");
                });
            }, 3000);
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
