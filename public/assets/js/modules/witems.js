let levelCounter = 1;
let levelCounter2 = 0;

// Loop through each row inside the .mrntableselectexcel table
$(".mrntableselectexcel tr").each(function () {
    const index = parseInt($(this).data("index"), 10);
    if (!isNaN(index) && index > levelCounter2) {
        levelCounter2 = index;
    }
});
levelCounter = levelCounter2 + 1; // Start from the next index

/*Check filled all basic detail*/
function checkBasicFilledDetail() {
    let filled = false;
    let store = $("[name='store_id']").val() || "";
    let subStore = $("[name='sub_store_id']").val() || "";
    if (store && subStore) {
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

// Get Store Wise Sub Stores
function getSubStores(storeLocationId) {
    console.log("storeLocationId", storeLocationId);

    const storeId = storeLocationId;
    $.ajax({
        url: "/sub-stores/store-wise",
        method: "GET",
        dataType: "json",
        data: {
            store_id: storeId,
        },
        success: function (data) {
            if (data.status == 200 && data.data.length) {
                let options = '<option value="">Select Warehouse</option>';
                data.data.forEach(function (location) {
                    options += `<option value="${location.id}">${location.name}</option>`;
                });
                $(".sub_store").empty();
                $(".sub_store").html(options);
            } else {
                $(".sub_store").empty();
                Swal.fire({
                    title: "Error!",
                    text: "Warehouse does not exist for location.",
                    icon: "error",
                });
            }
        },
        error: function (xhr) {
            Swal.fire({
                title: "Error!",
                text: xhr?.responseJSON?.message,
                icon: "error",
            });
        },
    });
}

// Function to capitalize the first letter of a string
function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

/* =========================
   CATEGORY HIERARCHY HELPERS
   ========================= */
const categoryHierarchyCache = {};

// Fetch breadcrumb using your getSubCategories controller with single leaf id
function fetchCategoryHierarchy(leafId) {
    if (!leafId) return Promise.resolve("");
    if (categoryHierarchyCache[leafId]) {
        return Promise.resolve(categoryHierarchyCache[leafId]);
    }
    return $.ajax({
        type: "GET",
        url: "/warehouse-item-mappings/get-sub-categories",
        data: { parent_id: leafId },
    })
        .then((res) => {
            if (res?.status === 200) {
                const crumb = res?.breadcrumb_full || "";
                categoryHierarchyCache[leafId] = crumb;
                return crumb;
            }
            return "";
        })
        .catch(() => "");
}

// Ensure only 1 category per row (keep last chosen if multiple are present)
function enforceSingleCategoryForRow($row) {
    const $sel = $row.find(".category-select");
    let vals = $sel.val() || [];
    if (vals.length > 1) {
        const keep = vals[vals.length - 1];
        $sel.val([keep]);
    }
}

// Render breadcrumb under the category select (assumes we've enforced single category)
function renderCategoryBreadcrumbForRow($row) {
    const $crumb = $row.find(".category-breadcrumb");
    const selected = $row.find(".category-select").val() || [];
    const leafId = selected.length ? selected[selected.length - 1] : null;

    if (!leafId) {
        $crumb.text("");
        return;
    }
    fetchCategoryHierarchy(leafId).then((crumb) => {
        $crumb.text(crumb || "");
    });
}

/* =========================
   EXISTING DETAILS
   ========================= */
function getDetails(subStoreId) {
    const storeId = $("[name='store_id']").val();

    if (!checkBasicFilledDetail()) {
        Swal.fire({
            title: "Error!",
            text: "Please fill header detail first",
            icon: "error",
        });
        return false;
    }

    $.ajax({
        type: "GET",
        url: `/warehouse-item-mappings/existing-details?store_id=${storeId}&sub_store_id=${subStoreId}`,
        success: function (data) {
            if (data.status === 200 && data.is_exist === 1) {
                const mappings = data.mappings || [];
                $(".mrntableselectexcel").html("");

                mappings.forEach((mapping, index) => {
                    const categories = Array.isArray(mapping.categories)
                        ? mapping.categories
                        : [];
                    const items = Array.isArray(mapping.items)
                        ? mapping.items
                        : [];
                    const structures = Array.isArray(mapping.structures)
                        ? mapping.structures
                        : [];
                    const detail_id = mapping.detail_id ?? null; // null fallback

                    let categoryOptions = categories
                        .map(
                            (cat) =>
                                `<option value="${cat.id}" ${
                                    cat.selected ? "selected" : ""
                                }>${cat.name}</option>`
                        )
                        .join("");

                    let itemOptions = items
                        .map(
                            (item) =>
                                `<option value="${item.id}" ${
                                    item.selected ? "selected" : ""
                                }>${item.name}</option>`
                        )
                        .join("");

                    let structureHtml = "";
                    structures.forEach((structure, levelIndex) => {
                        const levelName = structure.name.toLowerCase(); // normalize
                        const levelOptions = (structure.options || [])
                            .map(
                                (opt) =>
                                    `<option value="${opt.id}" ${
                                        opt.selected ? "selected" : ""
                                    }>${opt.name}</option>`
                            )
                            .join("");

                        structureHtml += `
                            <div class="form-group me-2" style="min-width: 220px; display: flex; align-items: center;">
                                <label class="form-label" style="font-weight: bold; padding-right:10px; margin-bottom: 4px;">
                                    ${structure.name}
                                </label>
                                <select class="form-select select2 child-dropdown"
                                        data-level-index="${levelIndex}"
                                        data-level-name="${levelName}"
                                        name="details[${
                                            index + 1
                                        }][${levelName}][]"
                                        multiple>
                                    ${levelOptions}
                                </select>
                            </div>`;
                    });

                    let rowHtml = `
                        <tr class="item-row">
                            <td class="customernewsection-form">
                                <div class="form-check form-check-primary custom-checkbox">
                                    <input type="checkbox" class="form-check-input" />
                                    <label class="form-check-label"></label>
                                </div>
                                <input type="hidden" name="details[${
                                    index + 1
                                }][detail_id]" value="${detail_id}">
                            </td>

                            <td>
                                <div class="d-flex align-items-start flex-column">
                                    <div class="form-group me-2" style="min-width: 420px; display: flex; align-items: center;">
                                        <select class="form-select select2 category-select" name="details[${
                                            index + 1
                                        }][category_id][]" multiple>
                                            ${categoryOptions}
                                        </select>
                                    </div>
                                    <!-- Breadcrumb -->
                                    <div class="category-breadcrumb text-muted small" style="margin-top: 4px;"></div>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="form-group me-2" style="min-width: 220px; display: flex; align-items: center;">
                                        <select class="form-select select2 item-select" name="details[${
                                            index + 1
                                        }][item_id][]" multiple>
                                            ${itemOptions}
                                        </select>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center">
                                    ${structureHtml}
                                </div>
                            </td>
                        </tr>
                    `;

                    $(".mrntableselectexcel").append(rowHtml);

                    // Enforce single category per row BEFORE select2 init
                    const $justAddedRow = $(
                        ".mrntableselectexcel tr.item-row"
                    ).last();
                    enforceSingleCategoryForRow($justAddedRow);
                });

                // Initialize Select2 after all rows appended
                $(".select2").select2({ width: "100%" });

                // Render breadcrumbs for all existing rows
                $(".mrntableselectexcel tr.item-row").each(function () {
                    renderCategoryBreadcrumbForRow($(this));
                });

                // Restrict duplicate items across rows
                updateUniqueSelectOptions();
            } else {
                $(".mrntableselectexcel").html("");
            }
        },
        error: function () {
            Swal.fire({
                title: "Error!",
                text: "An error occurred while fetching mapping data",
                icon: "error",
            });
            $(".mrntableselectexcel").html("");
        },
    });
}

/*Add New Row*/
$(document).on("click", ".addNewItemBtn", (e) => {
    levelCounter++;

    let storeId = $("[name='store_id']").val();
    let subStoreId = $("[name='sub_store_id']").val();
    let moduleType = $("[name='module_type']").val();
    if (!checkBasicFilledDetail()) {
        Swal.fire({
            title: "Error!",
            text: "Please fill header detail first",
            icon: "error",
        });
        return false;
    }

    let categories = [];
    let structures = [];

    var url =
        "/warehouse-item-mappings/details?" +
        "store_id=" +
        storeId +
        "&sub_store_id=" +
        subStoreId;
    $.ajax({
        type: "GET",
        url: url,
        async: false,
        success: function (data) {
            if (data.status == 200) {
                categories = Array.isArray(data.categories)
                    ? data.categories
                    : [];
                structures = Array.isArray(data.structures)
                    ? data.structures
                    : [];

                // Generate categories
                let categoryOptions = ``;
                categories.forEach((category) => {
                    categoryOptions += `<option value="${category.id}">${category.name}</option>`;
                });

                // Generate structures (we keep for first dropdown options)
                let structureOptions = ``;
                structures.forEach((structure) => {
                    structureOptions += `<option value="${structure.id}">${structure.name}</option>`;
                });

                // Build rowHtml
                let rowHtml = `
                    <tr class="item-row">
                        <td class="customernewsection-form">
                            <div class="form-check form-check-primary custom-checkbox">
                                <input type="checkbox" class="form-check-input" />
                                <label class="form-check-label"></label>
                            </div>
                        </td>

                        <td>
                            <div class="d-flex align-items-start flex-column">
                                <div class="form-group me-2" style="min-width: 420px; display: flex; align-items: center;">
                                    <select class="form-select select2 category-select" multiple>
                                        ${categoryOptions}
                                    </select>
                                </div>
                                <!-- Breadcrumb -->
                                <div class="category-breadcrumb text-muted small" style="margin-top: 4px;"></div>
                            </div>
                        </td>

                        <!--  <td>
                             <div class="d-flex align-items-center">
                                 <div class="form-group me-2"  style="min-width: 220px; display: flex; align-items: center;">
                                     <select class="form-select select2 sub-category-select" multiple>
                                         <option value="">Select Sub Category</option>
                                     </select>
                                 </div>
                             </div>
                         </td> -->

                        <td>
                            <div class="d-flex align-items-center">
                                <div class="form-group me-2"  style="min-width: 220px; display: flex; align-items: center;">
                                    <select class="form-select select2 item-select" multiple>
                                        <option value="">Select Item</option>
                                    </select>
                                </div>
                            </div>
                        </td>`;

                rowHtml += `<td><div class="d-flex align-items-center">`;

                structures.forEach((level, index) => {
                    let selectOptions = (level.storage_point_details || [])
                        .map(
                            (option) =>
                                `<option value="${option.id}">${option.name}</option>`
                        )
                        .join("");

                    let isFirst = index === 0;

                    rowHtml += `
                        <div class="form-group me-2" style="min-width: 220px; display: flex; align-items: center;">
                            <label class="form-label" style="font-weight: bold; padding-right:10px; margin-bottom: 4px;">
                                ${level.name}
                            </label>
                            <select class="form-select select2 child-dropdown"
                                    data-level-index="${index}"
                                    data-level-name="${level.name.toLowerCase()}"
                                    multiple>
                                <option value="">Select</option>
                                ${isFirst ? selectOptions : ""}
                            </select>
                        </div>`;
                });

                rowHtml += `</div></td></tr>`;

                $(".mrntableselectexcel").append(rowHtml);
                updateUniqueSelectOptions();

                // Re-initialize select2 for newly added elements
                $(".select2").select2({
                    width: "100%",
                });
            } else {
                Swal.fire({
                    title: "Error!",
                    text: "Failed to fetch records",
                    icon: "error",
                });
            }
        },
        error: function () {
            Swal.fire({
                title: "Error!",
                text: "An error occurred while fetching records",
                icon: "error",
            });
        },
    });

    // Assign name attributes for all rows (including the newly added one)
    $(".mrntableselectexcel tr").each(function (levelCounter) {
        // Category
        $(this)
            .find("select.category-select")
            .attr("name", `details[${levelCounter + 1}][category_id][]`);

        // Sub-category (if present)
        // $(this).find("select.sub-category-select").attr("name", `details[${levelCounter + 1}][sub_category_id][]`);

        // Item
        $(this)
            .find("select.item-select")
            .attr("name", `details[${levelCounter + 1}][item_id][]`);

        // Structure child dropdowns
        $(this)
            .find("select.child-dropdown")
            .each(function (levelIndex) {
                const levelName =
                    $(this).data("level-name") || `level_${levelIndex}`;
                $(this).attr(
                    "name",
                    `details[${levelCounter + 1}][${levelName}][]`
                );
            });
    });
});

// --- Single-category enforcement without event loops ---
function setSingleCategory($select, idOrNull) {
    if ($select.data("enforcing")) return; // guard against recursion
    $select.data("enforcing", true);
    if (idOrNull) {
        $select.val([String(idOrNull)]).trigger("change.select2");
    } else {
        $select.val([]).trigger("change.select2");
    }
    $select.data("enforcing", false);
}

// Fetch subcats/items for the current row (rebuilds options fresh)
function fetchSubcatsAndItems($row, selectedIds) {
    const subCatSelect = $row.find(".sub-category-select");
    const itemSelect = $row.find(".item-select");

    if (!selectedIds || selectedIds.length === 0) {
        subCatSelect.empty().trigger("change.select2");
        itemSelect.empty().trigger("change.select2");
        return;
    }

    $.ajax({
        type: "GET",
        url: "/warehouse-item-mappings/get-sub-categories",
        data: { "parent_ids[]": selectedIds },
        traditional: true,
        success: function (response) {
            if (response.status == 200) {
                subCatSelect.empty();
                itemSelect.empty();

                // (response.data || []).forEach(function (subCat) {
                //     subCatSelect.append(
                //         `<option value="${subCat.id}">${subCat.name}</option>`
                //     );
                // });
                console.log("response.items", response);

                (response.items || []).forEach(function (item) {
                    itemSelect.append(
                        `<option value="${item.id}">${item.item_name}</option>`
                    );
                });

                subCatSelect.trigger("change.select2");
                itemSelect.trigger("change.select2");
                updateUniqueSelectOptions();
            }
        },
    });
}

// A) When a category is selected: keep only that one, refresh children, show breadcrumb
$(document).on("select2:select", ".category-select", function (e) {
    const $select = $(this);
    const $row = $select.closest(".item-row");
    const pickedId = e.params?.data?.id;

    // Enforce single category without loops
    setSingleCategory($select, pickedId);

    // Fetch/refresh subcats & items, show breadcrumb
    fetchSubcatsAndItems($row, [pickedId]);
    renderCategoryBreadcrumbForRow($row);
});

// B) When a category is unselected: either keep the last remaining one or clear all
$(document).on("select2:unselect", ".category-select", function () {
    const $select = $(this);
    const $row = $select.closest(".item-row");
    const vals = $select.val() || [];
    const keep = vals.length ? vals[vals.length - 1] : null;

    setSingleCategory($select, keep);

    if (keep) {
        fetchSubcatsAndItems($row, [keep]);
    } else {
        fetchSubcatsAndItems($row, []); // clears
    }
    renderCategoryBreadcrumbForRow($row);
});

// C) Safety net: if something triggers a generic change, normalize to single category
$(document).on("change", ".category-select", function () {
    const $select = $(this);
    if ($select.data("enforcing")) return; // skip if we're in a programmatic set
    const $row = $select.closest(".item-row");
    let vals = $select.val() || [];
    if (vals.length > 1) {
        const keep = vals[vals.length - 1];
        setSingleCategory($select, keep);
        fetchSubcatsAndItems($row, [keep]);
    } else {
        fetchSubcatsAndItems($row, vals);
    }
    renderCategoryBreadcrumbForRow($row);
});

const $newRow = $(".mrntableselectexcel tr.item-row").last();
const $cat = $newRow.find(".category-select");

if ($cat.length) {
    const vals = $cat.val() || [];
    const keep = vals.length ? vals[vals.length - 1] : null;
    setSingleCategory($cat, keep);
    if (keep) fetchSubcatsAndItems($newRow, [keep]);
    renderCategoryBreadcrumbForRow($newRow);
}

// Child Dropdowns
$(document).on("change", ".child-dropdown", function () {
    let $currentDropdown = $(this);
    let selectedIds = $currentDropdown.val();
    let currentLevelIndex = parseInt($currentDropdown.data("level-index"));
    let $row = $currentDropdown.closest("tr");
    let $nextDropdown = $row.find(
        `select.child-dropdown[data-level-index="${currentLevelIndex + 1}"]`
    );

    // Clear dropdowns after next level
    $row.find(`select.child-dropdown`).each(function () {
        let index = parseInt($(this).data("level-index"));
        if (index > currentLevelIndex + 1) {
            $(this).empty().append('<option value="">Select</option>');
        }
    });

    if ($nextDropdown.length && selectedIds && selectedIds.length > 0) {
        $.ajax({
            type: "GET",
            url: "/warehouse-item-mappings/get-childs",
            data: { "parent_ids[]": selectedIds },
            traditional: true,
            success: function (response) {
                if (response.status === 200) {
                    let existingSelected = $nextDropdown.val() || [];
                    let newOptions = "";
                    let validValues = [];

                    response.data.forEach((item) => {
                        newOptions += `<option value="${item.id}">${item.name}</option>`;
                        validValues.push(String(item.id));
                    });

                    $nextDropdown.empty().append(newOptions);

                    // Restore previously selected values if still valid
                    let retained = existingSelected.filter((val) =>
                        validValues.includes(val)
                    );
                    if (retained.length > 0) {
                        $nextDropdown.val(retained).trigger("change");
                    }
                }
            },
        });
    }
});

// Delete selected rows
$(document).on("click", ".deleteBtn", (e) => {
    let itemIdsToDelete = [];
    $(".mrntableselectexcel tr").each(function () {
        let checkbox = $(this).find("td:first-child .form-check-input");
        if (checkbox.is(":checked")) {
            let itemId = $(this).find("input[name$='[detail_id]']").val();
            if (itemId) {
                itemIdsToDelete.push(itemId);
            }
        }
    });

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (!result.isConfirmed) return;

        const removeCheckedRowsFromDOM = () => {
            $(".mrntableselectexcel tr").each(function () {
                let checkbox = $(this).find("td:first-child .form-check-input");
                if (checkbox.is(":checked")) {
                    $(this).remove();
                }
            });

            // Re-index remaining rows' name attributes
            $(".mrntableselectexcel tr").each(function (index) {
                $(this)
                    .find("input[name^='details'], select[name^='details']")
                    .each(function () {
                        let nameAttr = $(this).attr("name");
                        if (nameAttr) {
                            let updatedName = nameAttr.replace(
                                /\[\d+\]/,
                                `[${index + 1}]`
                            );
                            $(this).attr("name", updatedName);
                        }
                    });
            });

            if ($(".mrntableselectexcel tr").length === 0) {
                $(".level_id").prop("disabled", false);
                $(".sub_store_id").prop("disabled", false);
            }

            // Update duplicate item restrictions
            updateUniqueSelectOptions();
        };

        // If there are IDs to delete in DB
        if (itemIdsToDelete.length > 0) {
            $.ajax({
                url: "/warehouse-item-mappings/delete-details",
                type: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    ids: itemIdsToDelete,
                },
                success: function (response) {
                    // DOM cleanup regardless of response content
                    removeCheckedRowsFromDOM();
                    Swal.fire(
                        "Deleted!",
                        "Selected rows have been deleted.",
                        "success"
                    );
                },
                error: function () {
                    Swal.fire(
                        "Error",
                        "Failed to delete from database",
                        "error"
                    );
                },
            });
        } else {
            // No DB ids; just remove from DOM
            removeCheckedRowsFromDOM();
            Swal.fire(
                "Deleted!",
                "Selected rows have been deleted.",
                "success"
            );
        }
    });
});

$(document).on(
    "change focus blur",
    ".category-select, .sub-category-select, .item-select",
    function () {
        updateUniqueSelectOptions();
    }
);

/* =========================
   UNIQUE ITEMS (ACROSS ROWS)
   ========================= */
function updateUniqueSelectOptions() {
    // Step 1: Collect selected values for ITEMS ONLY
    let usedItems = new Map();

    $(".mrntableselectexcel tr").each(function () {
        const $row = $(this);
        const rowEl = $row.get(0);

        ($row.find(".item-select").val() || []).forEach((val) => {
            val = String(val);
            if (!usedItems.has(val)) usedItems.set(val, []);
            usedItems.get(val).push(rowEl);
        });
    });

    // Step 2: Disable reused item options in other rows
    $(".mrntableselectexcel tr").each(function () {
        const $row = $(this);
        const rowEl = $row.get(0);

        $row.find(".item-select option").each(function () {
            const val = String($(this).attr("value"));
            const owners = usedItems.get(val) || [];
            const selectedHere = $row.find(".item-select").val() || [];

            if (
                owners.length > 0 &&
                !selectedHere.includes(val) &&
                owners.some((r) => r !== rowEl)
            ) {
                $(this).prop("disabled", true);
            } else {
                $(this).prop("disabled", false);
            }
        });
    });

    // Step 3: Refresh select2
    $(".item-select").each(function () {
        $(this).trigger("change.select2");
    });
}
