<div class="modal fade" id="canceEWayBillModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form class="ajax-submit-2" method="POST" action="{{ route('sales.cancel.ewb') }}" data-redirect="{{ $redirect_url }}" enctype='multipart/form-data'>
          @csrf
          <input type="hidden" name="id" id="si_cancel_ewb_id">
         <div class="modal-header">
            <div>
               <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal">
                Cancel E-Way Bill
               </h4>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body pb-2">
            <div class="row mt-1">
               <div class="col-md-12">
                  <div class="mb-1">
                     <label class="form-label">Reason<span class="text-danger">*</span></label>
                     <select class="form-select cannot_disable" name = "cancel_reason">
                        @foreach (\App\Helpers\MasterIndiaConstants::E_INVOICE_CANCEL_REASONS as $reasonVal => $reasonLabel)
                            <option value = "{{$reasonVal}}">{{$reasonLabel}}</option>
                        @endforeach
                    </select>
                  </div>
                  <div class="row">
                    <div class = "col-md-12">
                        <div class="mb-1">
                            <label class="form-label">Remarks<span class="text-danger">*</span></label>
                            <input type="text" name = "cancel_remarks" class="form-control cannot_disable" />
                        </div>
                    </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer justify-content-center">
            <button type="reset" class="btn btn-outline-secondary me-1" onclick = "closeModal('canceEInvoiceModal');">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit</button>
         </div>
       </form>
      </div>
   </div>
</div>