<!-- Item Batch Modal -->
<div class="modal" id="item-batch-modal" tabindex="-1" aria-labelledby="itemBatchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="text-center modal-title mb-1" id="itemBatchLabel">Item Batches</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body item-batch-modal-body">
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary add-batch-row-header">
                            <i data-feather="plus"></i> Add
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-batch-row-header">
                            <i data-feather="trash"></i> Delete
                        </button>
                    </div>
                </div>
                <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id="itemBatchTable">
                    <thead>
                        <tr>
                            <th width="30px">#</th>
                            <th width="100px">Batch Number</th>
                            <th width="100px">Manufacturing Year</th>
                            <th width="120px">Expiry Date</th>
                            <th width="80px">Quantity</th>
                            <th width="50px">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total Quantity:</th>
                            <th id="totalBatchQty">0</th>
                            <th></th>
                        </tr>
                </table>

                <!-- Context for JS -->
                <input type="hidden" id="itemBatchRowIndex" />
                <input type="hidden" id="itemBatchIsExpiry" value="0" />
                <input type="hidden" id="itemBatchIsEdit" value="0" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveItemBatchBtn">Save</button>
            </div>
        </div>
    </div>
</div>
<!-- Item Batch Modal End -->

<!-- Asset Detail Modal -->
<div class="modal fade" id="assetDetailModal" tabindex="-1" aria-labelledby="assetDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="text-center modal-title mb-1" id="assetDetailLabel">Asset Details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body asset-detail-modal-body">
                <!-- Populated dynamically via JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary submitAssetBtn">Save</button>
            </div>
        </div>
    </div>
</div>
<!-- Asset Detail Modal End -->
