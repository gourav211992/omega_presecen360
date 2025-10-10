<div class="modal fade text-start" id="rescedulesinv" tabindex="-1" aria-labelledby="header_pull_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 1250px">
        <div class="modal-content">
            <div class="modal-header">
                <div class="col-md-9">
                    <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="header_pull_label">Select Document</h4>
                    <p class="mb-0">Select from the below list</p>
                </div>
                <div class="text-end col-md-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i data-feather="x-circle"></i> Cancel
                    </button>
                    <button type="button" class="ml-1 btn btn-primary btn-sm" onclick="processOrder();" data-bs-dismiss="modal">
                        <i data-feather="check-circle"></i> Process
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    
                    <div class="col">
                        <div class="mb-1">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" id="customer_code_input_qt_di" placeholder="Select"
                                class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off" value="">
                            <input type="hidden" id="customer_id_qt_di_val">
                        </div>
                    </div>

                    <div class="col">
                        <div class="mb-1">
                            <label class="form-label">Series <span class="text-danger">*</span></label>
                            <input type="text" id="book_code_input_qt_di" placeholder="Select"
                                class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off" value="">
                            <input type="hidden" id="book_id_qt_di_val">
                        </div>
                    </div>

                    <div class="col">
                        <div class="mb-1">
                            <label class="form-label">Document No. <span class="text-danger">*</span></label>
                            <input type="text" id="document_no_input_qt_di" placeholder="Select"
                                class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off" value="">
                            <input type="hidden" id="document_id_qt_di_val">
                        </div>
                    </div>

                    <div class="col">
                        <div class="mb-1">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" id="item_name_input_qt_di" placeholder="Select"
                                class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off" value="">
                            <input type="hidden" id="item_id_qt_di_val">
                        </div>
                    </div>

                    <div class="col mb-1">
                        <label class="form-label">&nbsp;</label><br />
                        <button onclick="getOrders();" type="button" class="btn btn-warning btn-sm">
                            <i data-feather="search"></i> Search
                        </button>
                        <button onclick="clearFilters('qt');" type="button" class="btn btn-danger btn-sm">
                            <i data-feather="trash"></i> Clear
                        </button>
                    </div>

                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="dataTables_scroll datatables-basic table-sm table-bordered table myrequesttablecbox po-order-detail" id="si_dnote_table">
                                <input type = "hidden" id = "si_dnote_table_value" value = "{{App\Helpers\ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS}}">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="form-check form-check-inline me-0">
                                                <input class="form-check-input" type="checkbox" id="checkAllQtElement" onchange="checkAllQt(this);">
                                            </div>
                                        </th>
                                        <th>Series</th>
                                        <th>Document No.</th>
                                        <th>Document Date</th>
                                        <th>Customer Code</th>
                                        <th>Customer Name</th>
                                        <th>Item</th>
                                        <th>Attributes</th>
                                        <th>Quantity</th>
                                        <th>Balance Qty</th>
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody id="qts_data_table"></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
