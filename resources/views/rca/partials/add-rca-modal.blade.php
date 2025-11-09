    <div class="modal fade" id="addRcaModal" tabindex="-1" aria-labelledby="addRcaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
				<form class="ajax-submit3" data-module="rca" method="POST" action="{{ route('rca.item.updateRemark') }}" id="rcaForm" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="rca_item_id" id="rca_item_id" value="">

                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="addRcaModalLabel">RCA Remarks</h4>
                        <p class="mb-0">Select from the below list</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive-md customernewsection-form">
                        <table class="mt-1 mb-0 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
                            <tbody>
                                <tr valign="top">
                                    <td>Remarks <span class="text-danger">*</span></td>
                                    <td>
                                        <textarea class="form-control mw-100" name="remark" id="rca_remark"></textarea>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <td>Upload Picture</td>
                                    <td>
                                         <input type="file" name="media[]" class="form-control mw-100" multiple onchange="previewRcaImages(this, 'rca_item_preview')" />
                                        <div id="rca_item_preview" class="mt-1"></div>
										<div id="deleted_media_container"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="reset" class="btn btn-outline-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
				</form>
            </div>
        </div>
    </div>

