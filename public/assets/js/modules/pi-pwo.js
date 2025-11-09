$(function () {
    const $table = $(".a-order-detail");
    const $tbody = $table.find("tbody");
    const $headers = $table.find("th");
    const initialRows = $tbody.children().toArray();

    $headers.each(function (index) {
        if (index === 0) return;
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

        $tbody.append(rows);
    });

    $("#clearSortingBtn").click(function () {
        $headers.data("order", "desc").find(".sort-arrow").text("");
        $tbody.append(initialRows);
    });
});

$(document).on("keyup", "#item_name_search", (e) => {
    getPwoItems();
});

$(document).on(
    "change",
    "#pwoModal .po-order-detail > thead .form-check-input",
    (e) => {
        if (e.target.checked) {
            $("#pwoModal .po-order-detail > tbody .form-check-input").each(
                function () {
                    $(this).prop("checked", true);
                }
            );
        } else {
            $("#pwoModal .po-order-detail > tbody .form-check-input").each(
                function () {
                    $(this).prop("checked", false);
                }
            );
        }
    }
);

$(document).on(
    "change",
    "#pwoModal .po-order-detail > tbody .form-check-input",
    (e) => {
        if (
            !$(
                "#pwoModal .po-order-detail > tbody .form-check-input:not(:checked)"
            ).length
        ) {
            $("#pwoModal .po-order-detail > thead .form-check-input").prop(
                "checked",
                true
            );
        } else {
            $("#pwoModal .po-order-detail > thead .form-check-input").prop(
                "checked",
                false
            );
        }
    }
);

$(document).on(
    "change",
    "#pwoSubmitModal .po-order-detail > thead .form-check-input",
    (e) => {
        if (e.target.checked) {
            $(
                "#pwoSubmitModal .po-order-detail > tbody .form-check-input"
            ).each(function () {
                $(this).prop("checked", true);
            });
        } else {
            $(
                "#pwoSubmitModal .po-order-detail > tbody .form-check-input"
            ).each(function () {
                $(this).prop("checked", false);
            });
        }
    }
);

$(document).on(
    "change",
    "#pwoSubmitModal .po-order-detail > tbody .form-check-input",
    (e) => {
        if (
            !$(
                "#pwoSubmitModal .po-order-detail > tbody .form-check-input:not(:checked)"
            ).length
        ) {
            $(
                "#pwoSubmitModal .po-order-detail > thead .form-check-input"
            ).prop("checked", true);
        } else {
            $(
                "#pwoSubmitModal .po-order-detail > thead .form-check-input"
            ).prop("checked", false);
        }
    }
);

function getSelectedPwoIDS() {
    let ids = [];
    $("#pwoModal .pi_item_checkbox:checked").each(function () {
        ids.push($(this).val());
    });
    return ids;
}

function getSelectedPwoItemIDS() {
    let ids = [];
    $("#pwoModal .pi_item_checkbox:checked").each(function () {
        if (Number($(this).data("item-id"))) {
            ids.push(Number($(this).data("item-id")));
        }
    });
    return ids;
}

// Get PWO Code
function getPwoItems() {
    let selectedPiIds = localStorage.getItem("selectedPiIds") ?? "[]";
    selectedPiIds = JSON.parse(selectedPiIds);
    selectedPiIds = encodeURIComponent(JSON.stringify(selectedPiIds));
    let document_date = $("[name='document_date']").val() || "";
    let header_book_id = $("#book_id").val() || "";
    let series_id = $("#pwo_book_id_qt_val").val() || "";
    let document_number = $("#pwo_document_no_input_qt").val() || "";
    let store_id = $("#store_id").val() || "";
    let so_book_id = $("#so_book_id_qt_val").val() || "";
    let so_doc_number = $("#so_document_no_input_qt").val() || "";
    let item_search = $("#item_name_search").val();
    let fullUrl = `${getPwoUrl}?series_id=${encodeURIComponent(
        series_id
    )}&document_number=${encodeURIComponent(
        document_number
    )}&header_book_id=${encodeURIComponent(
        header_book_id
    )}&store_id=${encodeURIComponent(
        store_id
    )}&selected_pi_ids=${selectedPiIds}&document_date=${document_date}&item_search=${item_search}&so_book_id=${so_book_id}&so_doc_number=${so_doc_number}`;
    fetch(fullUrl).then((response) => {
        return response.json().then((data) => {
            $(".po-order-detail #pwoDataTable").empty().append(data.data.pis);
            $(".select2").select2({
                dropdownParent: $("#pwoModal"),
            });
        });
    });
}

$(document).on("keyup", "#item_name_search", (e) => {
    getPwoItems();
});

$(document).on("keyup", "#pwo_document_no_input_qt", (e) => {
    getPwoItems();
});

$(document).on("keyup", "#so_document_no_input_qt", (e) => {
    getPwoItems();
});

$(document).on("click", ".clearPiFilter", (e) => {
    $("#pwo_book_code_input_qt").val("");
    $("#pwo_book_id_qt_val").val("");
    $("#pwo_document_no_input_qt").val("");
    $("#so_book_code_input_qt").val("");
    $("#so_book_id_qt_val").val("");
    $("#so_document_no_input_qt").val("");
    $("#item_name_search").val("");
    getPwoItems();
});

/*Open Pwo model*/
$(document).on("click", ".pwoSelect", (e) => {
    $("#pwoModal").modal("show");
    openPurchaseRequest();
    getPwoItems();
});

/*searchPiBtn*/
$(document).on("click", ".searchPiBtn", (e) => {
    getPwoItems();
});

function openPurchaseRequest() {
    initializeAutocompleteQt2(
        "pwo_book_code_input_qt",
        "pwo_book_id_qt_val",
        "book_pwo",
        "book_code",
        ""
    );
    initializeAutocompleteQt2(
        "so_book_code_input_qt",
        "so_book_id_qt_val",
        "document_book",
        "book_code",
        ""
    );
}

function initializeAutocompleteQt2(
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
                        vendor_id: $("#vendor_id_qt_val").val(),
                        header_book_id: $("#book_id").val(),
                        store_id: $("#store_id_po").val() || "",
                        service_alias:
                            typeVal == "document_book" ? pwoServiceAlias : "",
                    },
                    success: function (data) {
                        response(
                            $.map(data, function (item) {
                                return {
                                    id: item.id,
                                    label: `${item[labelKey1]}${
                                        labelKey2
                                            ? item[labelKey2]
                                                ? "-" + item[labelKey2]
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
            appendTo: "#pwoModal",
            minLength: 0,
            select: function (event, ui) {
                var $input = $(this);
                $input.val(ui.item.label);
                $("#" + selectorSibling).val(ui.item.id);
                getPwoItems();
                return false;
            },
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $("#" + selectorSibling).val("");
                    getPwoItems();
                }
            },
        })
        .focus(function () {
            if (this.value === "") {
                $("#" + selectorSibling).val("");
                getPwoItems();
                $(this).autocomplete("search", "");
            }
        })
        .blur(function () {
            if (this.value === "") {
                $("#" + selectorSibling).val("");
                getPwoItems();
            }
        });
}

$(document).on("click", ".pwoProcess", (e) => {
    e.preventDefault();
    $("#pwoSubmitModal th .form-check-input").prop("checked", false);
    let ids = getSelectedPwoIDS();
    if (!ids.length) {
        $("[name='pwo_item_ids']").val("");
        $("[name='item_ids']").val("");
        $("#pwoModal").modal("hide");
        Swal.fire({
            title: "Error!",
            text: "Please select at least one one pwo item.",
            icon: "error",
        });
        return false;
    }

    $("[name='pwo_item_ids']").val(ids);
    let itemIds = getSelectedPwoItemIDS();
    $("[name='item_ids']").val(itemIds);
    ids = JSON.stringify(ids);
    let soTracking = $("#so_tracking_required").val();

    let selectedItems = [];
    let storeId = $("#store_id").val() || "";
    let selectedItemsParam = encodeURIComponent(JSON.stringify(selectedItems));
    let actionUrl =
        processPwoItemUrl +
        `?ids=${ids}&selected_items=${selectedItemsParam}&store_id=${storeId}&so_tracking_required=${soTracking}s`;

    fetch(actionUrl).then((response) => {
        return response.json().then((data) => {
            if (data.status == 200) {
                $("#pwoModal").modal("hide");
                $("#pwoSubmitDataTable").empty().append(data.data.pos);
                $("#pwoSubmitModal").modal("show");
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

/* Final PWO process submit */
$(document).on("click", ".pwoSubmitProcess", (e) => {
    const $checked = $("#pwoSubmitModal tbody .form-check-input:checked");
    if (!$checked.length) {
        Swal.fire({
            title: "Error!",
            text: "Please select at least one PWO item.",
            icon: "error",
        });
        return false;
    }

    let pwo_bom_mapping_ids = $checked
        .map((i, el) => parseInt($(el).val()))
        .get();
    let storeId = $("#store_id").val() || "";
    let soTracking = $("#so_tracking_required").val() || "i";

    fetch(processPwoActionUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        body: JSON.stringify({
            store_id: storeId,
            so_tracking_required: soTracking,
            pwo_bom_mapping_ids: pwo_bom_mapping_ids,
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.status === 200) {
                $("#itemTable .mrntableselectexcel").html(data.data.pos);
                initAutocompVendor("[name*='[vendor_code]']");
                initializeAutocomplete2(".comp_item_code");
                $(".soSelect, .pwoSelect").prop("disabled", true);
                $("#soSubmitModal, #pwoSubmitModal").modal("hide");
                $("#copy_item_section").show();

                setTimeout(() => {
                    $("#itemTable .mrntableselectexcel tr").each(
                        (index, item) => {
                            console.log(index, index + 1, item);

                            setAttributesUIHelper(index + 1, "#itemTable");
                        }
                    );
                }, 100);
            } else {
                Swal.fire("Error!", data.message, "error");
            }
        })
        .catch((err) => {
            Swal.fire("Error!", "Something went wrong!", "error");
            console.error(err);
        });
});
