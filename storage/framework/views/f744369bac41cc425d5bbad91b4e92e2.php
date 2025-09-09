<?php if((isset($approvalHistory) && count($approvalHistory) > 0) || $revision_number): ?>
    <?php if($document_status != \App\Helpers\ConstantHelper::DRAFT || in_array('bid-reopened',$approvalHistory?->pluck('approval_type')->toArray())): ?>
    <div class="col-md-<?php echo e(isset($colspan)?$colspan:4); ?>">
       <div class="step-custhomapp bg-light p-1 customerapptimelines customerapptimelinesapprovalpo">
          <h5 class="mb-2 text-dark border-bottom pb-50 d-flex align-items-center justify-content-between">
             <strong><i data-feather="arrow-right-circle"></i> Approval History</strong>
             <strong class="badge rounded-pill badge-light-secondary amendmentselect">Rev. No.
            <?php if($revision_number > intval(request('revisionNumber')) && request()->has('revisionNumber')): ?>
            <select onclick="return false" class="form-select" id="revisionNumber">
             <option value="<?php echo e(request('revisionNumber')); ?>"><?php echo e(request('revisionNumber')); ?></option>
             </select>
            <?php else: ?>
             <select class="form-select" id="revisionNumber">
             <?php for($i=$revision_number; $i >= 0; $i--): ?>
             <option value="<?php echo e($i); ?>" <?php echo e(request('revisionNumber',$revision_number) == $i ? 'selected' : ''); ?>><?php echo e($i); ?></option>
             <?php endfor; ?>
             </select>
            <?php endif; ?>
             </strong>
          </h5>
          <ul class="timeline ms-50 newdashtimline ">
             <?php $__currentLoopData = $approvalHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approvalHist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
             <li class="timeline-item">
                <span class="timeline-point timeline-point-indicator"></span>
                <div class="timeline-event">
                   <div class="d-flex justify-content-between flex-sm-row flex-column mb-sm-0 mb-1">
                      <h6><?php echo e(ucfirst($approvalHist->name ?? $approvalHist?->user?->name ?? 'NA')); ?></h6>
                      <?php if($approvalHist->approval_type == 'approve'): ?>
                      <span class="badge rounded-pill badge-light-success"><?php echo e(ucfirst($approvalHist->approval_type)); ?></span>
                      <?php elseif($approvalHist->approval_type == 'submit'): ?>
                      <span class="badge rounded-pill badge-light-primary"><?php echo e(ucfirst($approvalHist->approval_type)); ?></span>
                      <?php elseif($approvalHist->approval_type == 'reject'): ?>
                      <span class="badge rounded-pill badge-light-danger"><?php echo e(ucfirst($approvalHist->approval_type)); ?></span>
                      <?php elseif($approvalHist->approval_type == 'posted'): ?>
                      <span class="badge rounded-pill badge-light-info"><?php echo e(ucfirst($approvalHist->approval_type)); ?></span>
                      <?php else: ?>
                      <span class="badge rounded-pill badge-light-danger"><?php echo e(ucfirst($approvalHist->approval_type)); ?></span>
                      <?php endif; ?>
                   </div>
                    <?php if($approvalHist->created_at): ?>
                    <h6>
                     <?php echo e(\Carbon\Carbon::parse($approvalHist->created_at)->timezone('Asia/Kolkata')->format('d/m/Y | h.iA')); ?>

                    </h6>
                    <?php endif; ?>
                   <?php if($approvalHist->remarks): ?>
                   <p><?php echo $approvalHist->remarks; ?></p>
                   <?php endif; ?>
                   <?php if($approvalHist->getDocuments()->isNotEmpty()): ?>
                      <p>
                      <?php $__currentLoopData = $approvalHist->getDocuments(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $getDocument): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                         <a href="<?php echo e($approvalHist->getDocumentUrl($getDocument)); ?>" download>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download">
                               <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                               <polyline points="7 10 12 15 17 10"></polyline>
                               <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                         </a>
                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </p>
                   <?php endif; ?>
                </div>
             </li>
             <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
       </div>
    </div>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /var/www/html/erp_presence360/resources/views/partials/approval-history.blade.php ENDPATH**/ ?>