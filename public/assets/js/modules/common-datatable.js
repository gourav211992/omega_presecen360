// Define custom sorting type for "formatted-date"
$.fn.dataTable.ext.type.order["formatted-date-pre"] = function (data) {
    if (!data) return 0; // If data is undefined, return 0 for safe sorting
    // Parse date in the format "04 Nov, 2024" to "YYYY-MM-DD" for sorting
    const [day, month, year] = data.split(" ");
    const monthMap = {
        Jan: "01",
        Feb: "02",
        Mar: "03",
        Apr: "04",
        May: "05",
        Jun: "06",
        Jul: "07",
        Aug: "08",
        Sep: "09",
        Oct: "10",
        Nov: "11",
        Dec: "12",
    };
    // Ensure month is mapped correctly
    if (!monthMap[month]) return 0;
    return new Date(
        `${year}-${monthMap[month]}-${day.padStart(2, "0")}`
    ).getTime();
};

function initializeDataTable(
    selector,
    ajaxUrl,
    columns,
    filters = {},
    exportTitle = "Data",
    exportColumns = [],
    defaultOrder = [],
    pdfPageOrientation = "portrait",
    ajaxRequestType = "GET"
) {
    if ($("#datatable-loader").length === 0) {
        const loaderHtml = `
            <div id="datatable-loader">
                <div class="dt-processing">
                    <div></div>
                    <div>
                        <div></div><div></div><div></div><div></div>
                    </div>
                </div>
            </div>
        `;
        $("body").append(loaderHtml);
    }

    var table = $(selector).on(
        "processing.dt",
        function (e, settings, processing) {
            if (processing) {
                $("#datatable-loader").css("display", "flex");
            } else {
                $("#datatable-loader").hide();
            }
        }
    );

    var table = $(selector);
    if (table.length) {
        let dataTableInstance = table.DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            colReorder: true,
            lengthMenu: [
                [8, 10, 25, 50, 100, -1],
                [8, 10, 25, 50, 100, "All"],
            ],
            ajax: {
                url: ajaxUrl,
                type: ajaxRequestType,
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                data: function (d) {
                    // Loop through each filter key-value pair
                    $.each(filters, function (key, value) {
                        d[key] = $(value).val(); // Get the value from the HTML input
                    });
                },
            },
            order: defaultOrder,
            columns: columns,
            processing: true,
            columnDefs: [
                {
                    targets: "_all",
                    defaultContent: "N/A", // Set default content for missing data
                },
            ],
            dom: '<"d-flex justify-content-between align-items-center mx-2 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-3 dt-action-buttons text-end"B><"col-sm-12 col-md-3"f>>t<"d-flex justify-content-between mx-2 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                {
                    extend: "collection",
                    className: "btn btn-outline-secondary dropdown-toggle",
                    text:
                        feather.icons["share"].toSvg({
                            class: "font-small-4 mr-50",
                        }) + " Export",
                    buttons: [
                        {
                            extend: "print",
                            text:
                                feather.icons["printer"].toSvg({
                                    class: "font-small-4 mr-50",
                                }) + " Print",
                            className: "dropdown-item",
                            title: exportTitle,
                            exportOptions: { columns: exportColumns },
                        },
                        {
                            extend: "csv",
                            text:
                                feather.icons["file-text"].toSvg({
                                    class: "font-small-4 mr-50",
                                }) + " CSV",
                            className: "dropdown-item",
                            title: exportTitle,
                            exportOptions: { columns: exportColumns },
                        },
                        {
                            extend: "excel",
                            text:
                                feather.icons["file"].toSvg({
                                    class: "font-small-4 mr-50",
                                }) + " Excel",
                            className: "dropdown-item",
                            title: exportTitle,
                            exportOptions: { columns: exportColumns },
                        },
                        {
                            extend: "pdf",
                            text:
                                feather.icons["clipboard"].toSvg({
                                    class: "font-small-4 mr-50",
                                }) + " PDF",
                            className: "dropdown-item",
                            title: exportTitle,
                            exportOptions: { columns: exportColumns },
                            orientation: pdfPageOrientation,
                        },
                        {
                            extend: "copy",
                            text:
                                feather.icons["copy"].toSvg({
                                    class: "font-small-4 mr-50",
                                }) + " Copy",
                            className: "dropdown-item",
                            title: exportTitle,
                            exportOptions: { columns: exportColumns },
                        },
                    ],
                    init: function (api, node, config) {
                        $(node)
                            .removeClass("btn-secondary")
                            .parent()
                            .removeClass("btn-group");
                        setTimeout(function () {
                            $(node)
                                .closest(".dt-buttons")
                                .removeClass("btn-group")
                                .addClass("d-inline-flex");
                        }, 50);
                    },
                },
            ],
            drawCallback: function () {
                feather.replace();
                $(document).on("click", ".myrequesttablecbox tbody tr", (e) => {
                    $("tr").removeClass("trselected");
                    $(e.target).closest("tr").addClass("trselected");
                });

                $(document).on("keydown", function (e) {
                    if (e.which == 38) {
                        $(".trselected")
                            .prev("tr")
                            .addClass("trselected")
                            .siblings()
                            .removeClass("trselected");
                    } else if (e.which == 40) {
                        $(".trselected")
                            .next("tr")
                            .addClass("trselected")
                            .siblings()
                            .removeClass("trselected");
                    }
                    // $('html, body').scrollTop($('.trselected').offset().top - 100);
                });
            },
            language: {
                paginate: { previous: " ", next: " " },
            },
            search: { caseInsensitive: true },
        });
        return dataTableInstance;
    }
}

/**
 * Custom DataTable initializer
 * @param {string} selector - table selector
 * @param {string} ajaxUrl - API URL
 * @param {Array} columns - DataTable columns config
 * @param {string} ajaxRequestType - GET/POST (default: GET)
 * @param {Object} options - optional overrides
 */
function initializeDataTableCustom(
    selector,
    ajaxUrl,
    columns,
    ajaxRequestType = "GET",
    options = {}
) {
    const table = $(selector);
    if (!table.length) return;

    // Destroy existing instance if exists
    if ($.fn.DataTable.isDataTable(selector)) {
        table.DataTable().destroy();
        table.find("tbody").empty();
    }

    const defaults = {
        processing: true,
        serverSide: true,
        scrollY: options.scrollY || "300px",
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        responsive: false,
        fixedHeader: true,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"],
        ],
        searching: false,
        order: [[0, "desc"]],
        ajax: {
            url: ajaxUrl,
            type: ajaxRequestType,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: function (d) {
                const dynamicParams =
                    typeof getDynamicParams === "function"
                        ? getDynamicParams()
                        : {};
                Object.assign(d, dynamicParams);
            },
        },
        columns: columns,
        dom:
            "<'row'<'col-sm-12'tr>>" +
            "<'row align-items-center'" +
            "<'col-md-4 text-start'l>" +
            "<'col-md-4 text-center'i>" +
            "<'col-md-4 text-end'p>>",
        drawCallback: function (settings) {
            feather.replace(); // Replace feather icons

            // Adjust columns (fix header/body misalignment)
            this.api().columns.adjust();

            // Optional: re-initialize select2 inside table if needed
            if (options.select2Selector) {
                const $selects = $(options.select2Selector);
                $selects.each(function () {
                    const $select = $(this);
                    if ($select.data("select2")) $select.select2("destroy");

                    $select.select2({
                        width: "100%",
                        placeholder: $select.data("placeholder") || "Select",
                        allowClear: true,
                        dropdownParent: $select.closest(".modal, body"),
                        language: {
                            noResults: function () {
                                return "No results found";
                            },
                        },
                        escapeMarkup: function (markup) {
                            return markup;
                        },
                    });
                });
            }
        },
        rowCallback: function (row, data) {
            if (data.DT_RowIndex !== undefined) {
                $(row).attr("id", "row_" + data.DT_RowIndex);
                $(row).attr("data-index", data.DT_RowIndex);
            }
        },
        language: { paginate: { previous: " ", next: " " } },
        search: { caseInsensitive: true },
        columnDefs: options.columnDefs || [],
    };

    // Initialize table
    const dataTableInstance = table.DataTable(defaults);

    // Recalculate widths if inside modal
    const $modal = table.closest(".modal");
    if ($modal.length) {
        $modal.on("shown.bs.modal", function () {
            dataTableInstance.columns.adjust();
        });
    }

    // Recalculate on window resize / zoom
    $(window).on("resize", function () {
        dataTableInstance.columns.adjust();
    });

    return dataTableInstance;
}
