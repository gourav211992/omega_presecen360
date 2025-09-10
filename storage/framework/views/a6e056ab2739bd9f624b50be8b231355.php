<?php $__empty_1 = true; $__currentLoopData = $grn_details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php if(isset($grn->header->vendor->company_name)): ?>
    <tr class="<?php if((isset($selected_grn_id) && $selected_grn_id == $grn->id) ): ?> table-active <?php endif; ?>">
        <td>
            <div class="form-check form-check-inline me-0">
           
                <input 
                    class="form-check-input" 
                    type="radio" 
                    name="grn_id" 
                    id="grn_<?php echo e($loop->index); ?>" 
                    value="<?php echo e($grn->id); ?>" 
                    data-grn="<?php echo e(json_encode($grn)); ?>"
                    <?php if((isset($selected_grn_id) && $selected_grn_id == $grn->id) ): ?> checked <?php endif; ?>
                >
            </div>
        </td>
        <td><?php echo e($grn->header->document_number); ?></td>
        <td><?php echo e($grn->header->created_at->format('d-m-Y')); ?></td>
        <td class="fw-bolder text-dark"><?php echo e($grn->header->vendor_code); ?></td>
        <td><?php echo e($grn->header->vendor->company_name); ?></td>
        <td><?php echo e($grn->item->item_name); ?></td>
        <td><?php echo e($grn->accepted_qty); ?></td>
    </tr>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr>
    <td colspan="7" class="text-center">No data available</td>
</tr>
<?php endif; ?>
<?php /**PATH /var/www/html/erp_presence360/resources/views/fixed-asset/registration/grn_rows.blade.php ENDPATH**/ ?>