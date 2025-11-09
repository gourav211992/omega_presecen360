<div class="modal fade" id="cancelEWayBillModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="ajax-submit-2" method="POST" action="{{ route('purchase-return.cancel-ewaybill') }}" data-redirect="{{ route('purchase-return.index') }}" enctype='multipart/form-data'>
                @csrf
                <input type="hidden" name="action_type" id="action_type">
                <input type="hidden" name="id" value="{{ $id ?? '' }}">
                <input type="hidden" name="irn_id" value="{{ $mrnData->id ?? '' }}">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="popupTitle">Cancel Eway Bill</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-2">
                    <div class="row mt-1">
                        <div class="col-md-12">
                            <div class="mb-1">
                                <label class="form-label">Reason<span class="text-danger">*</span></label>
                                <select class="form-select reasonselect" name="cancel_reason" id="reasonSelect" disabled="">
                                    @foreach (\App\Helpers\MasterIndiaConstants::E_INVOICE_CANCEL_REASONS as $reasonVal => $reasonLabel)
                                        <option value = "{{ $reasonVal }}">{{ $reasonLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Remarks {{-- <span class="text-danger">*</span> --}}</label>
                                <textarea maxlength="250" name="cancel_remarks" class="form-control"></textarea>
                            </div>
                            <div class="mt-2">
                                <div class="row" id="pr_popup_file_preview">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer cancel-modal-footer justify-content-center">
                    <button type="reset" data-bs-dismiss="modal" class="btn btn-outline-secondary me-1">Cancel</button>
                    <button type="button" id="ewaybillCancelBtnSubmit" class="btn btn-primary ewaybillCancelBtnSubmit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
