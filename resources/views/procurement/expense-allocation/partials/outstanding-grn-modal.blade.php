<div class="modal fade text-start grnModal" id="grnModal" tabindex="-1" aria-labelledby="grnModal" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="grnModal">
                        Select GRN
                    </h4>
                    <p class="mb-0">
                        Select from the below list
                    </p>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <button type="button" class="btn btn-primary btn-sm grnProcess">
                        <i data-feather="check-circle"></i> Process
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col">
                        <div class="mb-1">
                            <label class="form-label">Vendor</label>
                            <input type="text" id="grn_vendor_code_input_qt" placeholder="Select"
                                class="form-control mw-100 ledgerselecct ui-autocomplete-input grn_vendor_code_input_qt"
                                autocomplete="off" value="">
                            <input type="hidden" class="grn_vendor_id_qt_val" id="grn_vendor_id_qt_val"></input>
                        </div>
                    </div>
                    <div class="col">
                        <div class="mb-1">
                            <label class="form-label">Doc No.</label>
                            <input type="text" id="grn_document_no_input_qt" placeholder="Select"
                                class="form-control mw-100 ledgerselecct ui-autocomplete-input grn_document_no_input_qt"
                                autocomplete="off" value="">
                            <input type = "hidden" class="grn_document_id_qt_val" id="grn_document_id_qt_val"></input>
                        </div>
                    </div>
                    <div class="col">
                        <div class="mb-1">
                            <label class="form-label">Item</label>
                            <input type="text" name="grn_item_name_search" id="grn_item_name_search"
                                placeholder="Item Name/Code" class="form-control mw-100 grn_item_name_search"
                                autocomplete="off" value="">
                        </div>
                    </div>
                    <div class="col mb-1">
                        <label class="form-label">&nbsp;</label><br />
                        <button type="button" class="btn btn-warning btn-sm clearGrnFilter">
                            <i data-feather="x-circle"></i> Clear
                        </button>
                    </div>
                    <div class="col-md-12">
                        <div class="mrn-table-container">
                            <table
                                class="table table-striped table-bordered grn-order-detail myrequesttablecbox nowrap w-100">
                                <thead class="table-light header">
                                    <tr>
                                        <th class="d-none">ID</th>
                                        <th>
                                            <div class="form-check form-check-inline me-0">
                                                <input class="form-check-input" type="checkbox" name="mrndetail"
                                                    id="inlineCheckbox1">
                                            </div>
                                        </th>
                                        <th>SUPPLIER NAME</th>
                                        <th>GRN NO.</th>
                                        <th>GRN Date</th>
                                        <th>ITEM CODE</th>
                                        <th>Item Name</th>
                                        <th>ATTRIBUTES</th>
                                        <th class="text-end">GRN QTY</th>
                                        <th class="text-end">AVAIL STOCK</th>
                                        <th class="text-end">RATE</th>
                                        <th class="text-end">VALUE</th>
                                    </tr>
                                </thead>
                                <tbody id="grnDataTable">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
