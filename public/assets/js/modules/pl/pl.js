let prevBalance = 0;
let subStoreUrl = window.routes.subStores;

// $('#store_id_input').on('change', function () {
//     storeIdOnchange(this);
// });


function storeIdOnchange(element)
{
    let bookParam = '';
    let selectedValue = element.value;
    const tableBody = $('#itemTable tbody');
    $('#so_book_code_input_qt').val('');
    $('#so_document_no_input_qt').val('');
    $('#document_date_filter').val('');
    $('#customer_code_input_qt').val('');
    $('#trip_header_input').val('');
    apiUrl = "/pick-list/so/get/items";
    tableBody.html('<tr><td colspan="17" class="text-center">Loading...</td></tr>');
    let showAllItemsCheck = document.getElementById('out_of_stock_check');
    if (showAllItemsCheck) {
        showAllItemsCheck = showAllItemsCheck.checked;
    }
    $.ajax({
        url: apiUrl,
        type: 'GET',
        data: { store_id: selectedValue, sub_store_id: $("#main_sub_store_id_input").val(), header_book_id : $("#series_id_input").val(), show_all : showAllItemsCheck },
        success: function (response) {
            populateOrderTable(response.data);
        },
        error: function (xhr, status, error) {
            console.error('Error fetching orders:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to fetch orders. Please try again.',
                icon: 'error',
            });
            tableBody.html('<tr><td colspan="17" class="text-center">Failed to load data.</td></tr>');
        }
    });
}

if(order && order.document_status=="draft" && order.store_id)
{
    $("#store_id_input").trigger('change');
}

function populateOrderTable(orders) {
    const tableBody = $('#itemTable tbody');
    tableBody.empty(); // Clear existing rows
    
    if (orders.length > 0) {
        const prevBalanceMap = new Map();
        orders.forEach((norder, index) => {
            console.log('norder', norder);
            const tripDetails = Array.isArray(norder.trip_details) && norder.trip_details.length > 0 
                ? norder.trip_details 
                : [null]; 

            tripDetails.forEach((tripDetail, subIndex) => {
                // attributes: use tripDetail’s JSON if present, else norder’s ready HTML
                let attributesHtml = norder.attributes || 'N/A';
                if (tripDetail && tripDetail.attributes) {
                    try {
                        const attrs = JSON.parse(tripDetail.attributes);
                        attributesHtml = attrs.map(attr => {
                            const selected = attr.values_data.find(v => v.selected);
                            return `<span class="badge rounded-pill badge-light-primary">
                                        <strong>${attr.group_name}: ${selected ? selected.value : ''}</strong>
                                    </span>`;
                        }).join(' ');
                    } catch (e) {
                        attributesHtml = 'N/A';
                    }
                }

                const row = `
                    <tr>
                        <!-- Select -->
                        <td>
                            <div class="form-check form-check-primary custom-checkbox">
                                <input type="checkbox"
                                    name="selected_deliveries[]"
                                    class="form-check-input"
                                    id="order_checkbox_${index}_${subIndex}"
                                    value="${norder.id}"
                                    ${tripDetail 
                                        ? (tripDetail.balance_planned_qty > 0 && norder.avl_stock > 0 ? '' : 'disabled') 
                                        : (norder.avl_stock > 0 ? '' : 'disabled')}
                                        >
                                <input type="hidden" name="trip_detail_id[]" value="${tripDetail ? tripDetail.id : ''}" />    
                                <label class="form-check-label" for="order_checkbox_${index}_${subIndex}"></label>
                            </div>
                        </td>

                        <!-- Series -->
                        <td>${norder.item.header.book_code || 'N/A'}</td>

                        <!-- Trip No -->
                        <td>${tripDetail ? `${tripDetail.header.document_number}-${tripDetail.header.book_code}` : ''}</td>

                        <!-- Doc No -->
                        <td>${norder.item.header.document_number || 'N/A'}</td>

                        <!-- Doc Date -->
                        <td>${norder.item.header.document_date || 'N/A'}</td>

                        <!-- Delivery Date -->
                        <td>${tripDetail ? tripDetail.delivery_date : norder.delivery_date || 'N/A'}</td>

                        <!-- Item Code -->
                        <td>${norder.item.item_code}</td>

                        <!-- Item Name -->
                        <td>${norder.item.item_name}</td>

                        <!-- Currency -->
                        <td>${norder.item.header.currency_code}</td>

                        <!-- Attributes -->
                        <td>${attributesHtml}</td>

                        <!-- UOM -->
                        <td>${norder.item.uom.name}</td>

                        <!-- Order Qty -->
                        <td class="text-end">${norder.item.order_qty}</td>

                        <!-- Balance Qty -->
                        <td class="text-end">${norder.item.balance_qty}</td>

                        <!-- Trip Qty -->
                        <td class="text-end">${tripDetail ? tripDetail.balance_planned_qty : '---'}</td>

                        <!-- Avl Stk -->
                        <td class="text-end">${norder.avl_stock}</td>

                        <!-- Pick Qty -->
                        <td class="text-end balance-qty-cell">
                            ${tripDetail ? tripDetail.picked_qty : (norder ? norder.item.picked_qty : "0.00")}
                        </td>

                        <!-- Rate -->
                        <td>${tripDetail ? tripDetail.rate : norder.item.rate}</td>

                        <!-- Customer -->
                        <td>${norder.item.header.customer?.name || 'N/A'}</td>
                    </tr>
                `;
                const $row = $(row);
                const $checkbox = $row.find(`#order_checkbox_${index}_${subIndex}`);
                const $balanceQtyCell = $row.find('.balance-qty-cell');

                function createValidatedInput(norder, savedQty = null) {
                    const currentQty = Math.min(norder.item.balance_qty, norder.avl_stock, tripDetail ? tripDetail.planned_qty : norder.avl_stock) || '0.00';

                    const $input = $(`<input type="number" name="picked_qty[]" class="form-control" 
                                        value="${savedQty ?? currentQty}" max="${currentQty}" />`);

                    $input.on('input', function () {
                        const value = parseFloat($(this).val());
                        if (value < 0 || isNaN(value)) {
                            setTimeout(() => {
                                // Only fire if the value is still invalid after 1s
                                if ($(this).val() <= 0 || isNaN($(this).val())) {
                                    Swal.fire({
                                        title: 'Invalid Input',
                                        text: 'Quantity must be greater than zero.',
                                        icon: 'warning',
                                    });
                                    $(this).val(0);
                                }
                            }, 1000); // 1 second delay
                        } else if (value > currentQty) {
                            const qtyLabel = norder.item.balance_qty < norder.avl_stock ? 'Balance Qty' : 'Available Stock';
                            Swal.fire({
                                title: 'Invalid Input',
                                text: `Quantity cannot be greater than ${qtyLabel}.`,
                                icon: 'warning',
                            });
                            $(this).val(currentQty);
                        }
                    });

                    return $input;
                }

                $checkbox.on('change', function () {
                    if (this.checked) {
                        const savedQty = prevBalanceMap.get(norder.id) ?? (order ? order.picked_qty : null);
                        const $input = createValidatedInput(norder, savedQty ?? (tripDetail ? tripDetail.planned_qty : null));
                        $balanceQtyCell.html($input);
                    } else {
                        const currentInput = $balanceQtyCell.find('input');
                        if (currentInput.length) {
                            prevBalanceMap.set(norder.id, currentInput.val());
                        }
                        $balanceQtyCell.text(tripDetail ? tripDetail.planned_qty : (norder.item.balance_qty || '0.00'));
                    }
                });

                if (order) {
                    const matchedItem = order.items.find(item => {
                        if (tripDetail) {
                            // Match delivery AND trip_detail
                            return item.order_item_delivery_id === norder.id &&
                                item.trip_detail_id === tripDetail.id;
                        } else {
                            // No trip detail → fallback to old check
                            return item.order_item_delivery_id === norder.id;
                        }
                    });

                    if (matchedItem) {
                        $checkbox.prop('checked', true);
                        $checkbox.trigger('change');

                        // Use matched picked qty if present
                        const $input = createValidatedInput(norder, matchedItem.picked_qty);
                        $balanceQtyCell.html($input);
                    }
                }


                tableBody.append($row);
            });
        });
        // Re-render Feather icons
        if (feather) {
            feather.replace();
        }
    } else {
        const noDataRow = `
            <tr>
                <td colspan="17" class="text-center">No orders found for the selected location.</td>
            </tr>
        `;
        tableBody.append(noDataRow);
    }
}

function viewOrderDetails(orderId) {
    Swal.fire({
        title: 'Order Details',
        text: `Details for Order ID: ${orderId}`,
        icon: 'info',
    });
}
let debounceTimer;
$('#delivery_date_filter, #so_document_no_input_qt, #document_date_filter, #customer_code_input_qt','#trip_header_input').on('input change', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const storeId = $('#store_id_input').val();
        const soBookCode = $('#delivery_date_filter').val();
        const soDocumentNo = $('#so_document_no_input_qt').val();
        const documentDate = $('#document_date_filter').val();
        const customerCode = $('#customer_code_input_qt').val();
        const tripId = $('#trip_header_input').val();
        const book_id = $('#series_id_input').val();
        const tableBody = $('#itemTable tbody');
        tableBody.html('<tr><td colspan="17" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: "/pick-list/so/get/items",
            type: 'GET',
            data: {
                store_id: storeId,
                delivery_date: soBookCode,
                so_document_no: soDocumentNo,
                document_date: documentDate,
                customer_code: customerCode,
                header_book_id : book_id,
                trip_id : tripId,
            },
            success: function (response) {
                populateOrderTable(response.data);
            },
            error: function (xhr, status, error) {
                console.error('Error fetching filtered orders:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to fetch filtered orders. Please try again.',
                    icon: 'error',
                });
                tableBody.html('<tr><td colspan="17" class="text-center">Failed to load data.</td></tr>');
            }
        });
    }, 800);
});
$(".clearPiFilter").on('click',function(){
    
$('#delivery_date_filter, #so_document_no_input_qt, #document_date_filter, #customer_code_input_qt, #trip_header_input').val('');
$('#delivery_date_filter, #so_document_no_input_qt, #document_date_filter, #customer_code_input_qt, #trip_header_input').trigger('change');
});

function locationChange(element)
    {
        $.ajax({
            url: subStoreUrl,
            method: 'GET',
            dataType: 'json',
            data: {
            store_id: element.value,
            sub_type : "main",
            },
            success: function(data) {
            if (data.data && data.data.length > 0) {
                let options = '';
                data.data.forEach(function(subStore) {
                    options += `<option value="${subStore.id}">${subStore.name}</option>`;
                });
                $('#main_sub_store_id_input').empty().html(options);
                storeIdOnchange(element);
            }
            else{
                $('#main_sub_store_id_input').empty();
                storeIdOnchange(element);
                // Swal.fire({
                //     title: 'Error!',
                //     text: 'No Store Found On this Location.',
                //     icon: 'warning',
                // });
            }
            // Handle the response data as needed
            },
            error: function(xhr) {
            console.error('Error fetching sub-stores:', xhr.responseText);
            }
        });
        $.ajax({
            url: subStoreUrl,
            method: 'GET',
            dataType: 'json',
            data: {
            store_id: element.value,
            sub_type : "packing",
            },
            success: function(data) {
            if (data.data && data.data.length > 0) {
                let options = '';
                data.data.forEach(function(subStore) {
                    options += `<option value="${subStore.id}">${subStore.name}</option>`;
                });
                $('#staging_sub_store_id_input').empty().html(options);
            }
            else{
                $('#staging_sub_store_id_input').empty();
                // Swal.fire({
                //     title: 'Error!',
                //     text: 'No Store Found On this Location.',
                //     icon: 'warning',
                // });
            }
            // Handle the response data as needed
            },
            error: function(xhr) {
            console.error('Error fetching sub-stores:', xhr.responseText);
            }
        });
        getTripData();
    }


    function subStoreIdOnchange(element)
{
    let selectedValue = element.value;
    const tableBody = $('#itemTable tbody');
    $('#so_book_code_input_qt').val('');
    $('#so_document_no_input_qt').val('');
    $('#document_date_filter').val('');
    $('#customer_code_input_qt').val('');
    tableBody.html('<tr><td colspan="17" class="text-center">Loading...</td></tr>');
    let showAllItemsCheck = document.getElementById('out_of_stock_check');
    if (showAllItemsCheck) {
        showAllItemsCheck = showAllItemsCheck.checked;
    }

    $.ajax({
        url: "/pick-list/so/get/items",
        type: 'GET',
        data: { store_id: $("#store_id_input").val() , sub_store_id: selectedValue, header_book_id : $("#series_id_input").val(), show_all : showAllItemsCheck },
        success: function (response) {
            populateOrderTable(response.data);
        },
        error: function (xhr, status, error) {
            console.error('Error fetching orders:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to fetch orders. Please try again.',
                icon: 'error',
            });
            tableBody.html('<tr><td colspan="17" class="text-center">Failed to load data.</td></tr>');
        }
    });
}

    function loadOrders()
{
    let element = document.getElementById('main_sub_store_id_input');
    let selectedValue = element.value;
    const tableBody = $('#itemTable tbody');
    $('#so_book_code_input_qt').val('');
    $('#so_document_no_input_qt').val('');
    $('#document_date_filter').val('');
    $('#customer_code_input_qt').val('');
    tableBody.html('<tr><td colspan="17" class="text-center">Loading...</td></tr>');
    let showAllItemsCheck = document.getElementById('out_of_stock_check');
    if (showAllItemsCheck) {
        showAllItemsCheck = showAllItemsCheck.checked;
    }
    $.ajax({
        url: "/pick-list/so/get/items",
        type: 'GET',
        data: { store_id: $("#store_id_input").val() , sub_store_id: selectedValue, header_book_id : $("#series_id_input").val(), trip_id : $("#trip_header_input").val() ,show_all : showAllItemsCheck },
        success: function (response) {
            populateOrderTable(response.data);
        },
        error: function (xhr, status, error) {
            console.error('Error fetching orders:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to fetch orders. Please try again.',
                icon: 'error',
            });
            tableBody.html('<tr><td colspan="17" class="text-center">Failed to load data.</td></tr>');
        }
    });
}


// var sub_store_element = document.getElementById('sub_store_id_input');
// if (sub_store_element) {
//     console.log('sub_store_element', sub_store_element);
//     $("#store_id_input").on('change', function() {
//         const storeId = $(this).val();
//         $("#item_header").html('');
//         const sub_store_id  = order ? order.sub_store_id : null;
//         $('#sub_store_id_input').empty();
//         if (storeId) {
//             $.ajax({
//                 url: subStoreUrl,
//                 method: 'GET',
//                 dataType: 'json',
//                 data: {
//                 store_id: storeId,
//                 types : Stockk,
//                 },
//                 success: function(data) {
//                 console.log('Sub-stores fetched successfully:', data);
//                 if (data.data && data.data.length > 0) {
//                     let options = '<option value="" disabled selected>Select</option>';
//                     data.data.forEach(function(subStore) {
//                         options += `<option value="${subStore.id}" ${subStore.id == sub_store_id ? 'selected' : ''}>${subStore.name}</option>`;
//                     });
//                     $('#sub_store_id_input').empty().html(options);
//                 }
//                 else{
//                     $('#sub_store_id_input').empty();
//                     Swal.fire({
//                         title: 'Error!',
//                         text: 'No Store Found On this Location.',
//                         icon: 'warning',
//                     });
//                 }
//                 // Handle the response data as needed
//                 },
//                 error: function(xhr) {
//                 console.error('Error fetching sub-stores:', xhr.responseText);
//                 }
//             });
//         }
//     });
// }

