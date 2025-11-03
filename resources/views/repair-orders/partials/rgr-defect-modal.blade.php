<!-- Single Modal -->
<div class="modal fade" id="attribute" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal">RGR Remarks</h4>
          <p class="mb-0">View the Details below</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- ✅ Status Checkboxes -->
        <div class="table-responsive-md customernewsection-form mb-2"> 
          <table class="mb-0 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
            <tr>
              <td>
                <div class="form-check form-check-primary custom-checkbox">
                  <input class="form-check-input opacity-100" type="checkbox" id="chkPacking" disabled>
                  <label class="form-check-label opacity-100" for="chkPacking">Packaging</label>
                </div>
              </td>
              <td>
                <div class="form-check form-check-primary custom-checkbox">
                  <input class="form-check-input opacity-100" type="checkbox" id="chkLabel" disabled>
                  <label class="form-check-label opacity-100" for="chkLabel">Label</label>
                </div>
              </td>
              <td>
                <div class="form-check form-check-primary custom-checkbox">
                  <input class="form-check-input opacity-100" type="checkbox" id="chkDelivery" disabled>
                  <label class="form-check-label opacity-100" for="chkDelivery">Delivery Cancel</label>
                </div>
              </td>
              <td>
                <div class="form-check form-check-primary custom-checkbox">
                  <input class="form-check-input opacity-100" type="checkbox" id="chkWrongProduct" disabled>
                  <label class="form-check-label opacity-100" for="chkWrongProduct">Wrong Product</label>
                </div>
              </td>
            </tr>
          </table>
        </div>

        <!-- ✅ Particulars Table -->
        <div class="table-responsive-md">
          <table class="mt-1 mb-0 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
            <thead>
              <tr>
                <th width="180px">Particulars</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong class="fw-bolder">Segregation Category</strong></td>
                <td id="modalSegregationCategory"></td>
              </tr>
              <tr>
                <td><strong class="fw-bolder">Defect Type</strong></td>
                <td id="modalDefectType"></td>
              </tr>
              <tr>
                <td><strong class="fw-bolder">Damage Type</strong></td>
                <td id="modalDamageNature"></td>
              </tr>
              <tr>
                <td><strong class="fw-bolder">Remarks</strong></td>
                <td id="modalDefectRemarks"></td>
              </tr>
              <tr valign="top">
                <td><strong class="fw-bolder">Pictures</strong></td>
                <td id="modalDefectImages" class="d-flex flex-wrap gap-2"></td>
              </tr>

              <!-- ✅ Actual Product Section -->
              <tr>
                <td><strong class="fw-bolder">Actual Product</strong></td>
                <td style="background-color: #fff6ed;">
                  <input type="hidden" id="SegregationId">
                  <input type="hidden" id="modalNewItemId">
                  <p class="mb-0">
                    <strong>Code:</strong>
                    <span id="modalNewItemCode"></span>
                  </p>
                  <p class="mb-0">
                    <strong>Name:</strong>
                    <span id="modalNewItemName"></span>
                  </p>
                  <div id="modalNewItemAttributes"
                      class="flex-wrap align-items-center gap-2"></div>
                </td>
             </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
