<?php $__env->startSection('content'); ?>
    <!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">

            <div class="content-body">

                <section id="basic-datatable">
                    <div class="card border  overflow-hidden">

                    <div class="row">
                        <div class="col-md-12 bg-light border-bottom mb-1 po-reportfileterBox">
                            <div class="row pofilterhead action-button align-items-center">
                                <div class="col-md-4">
                                    <h3>Vouchers</h3>
                                    <p><?php echo e($date2); ?></p>
                                </div>
                                <div class="col-md-8 text-sm-end">
                                    <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter"
                            data-bs-toggle="modal"><i data-feather="filter"></i> Filter</button>
                                <?php if(isset($fyearLocked) && $fyearLocked): ?>
                                    <a class="btn btn-primary btn-sm" href="<?php echo e(route('vouchers.create')); ?>"><i
                                    data-feather="plus-circle"></i> Add New</a>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="table-responsive">
                                    <table class="datatables-basic table myrequesttablecbox tableistlastcolumnfixed">
                                        <thead>
                                            <tr>
                                                <th>Sr. No</th>
                                                <th>Date</th>
                                                <th>Document Type</th>
                                                <th>Series</th>
                                                <th>Voucher No.</th>
                                                <th>Ledger</th>
                                                <th class="text-end">Amount</th>
                                                <th>Location</th>
                                                <th>Cost Center</th>
                                                <th>Document</th>
                                                <th>Remarks</th>
                                                <th class="text-end">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                use App\Helpers\Helper;
                                            ?>

                                            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $mainBadgeClass = match ($item->approvalStatus) {
                                                        'approved' => 'success',
                                                        'approval_not_required' => 'success',
                                                        'draft' => 'warning',
                                                        'submitted' => 'info',
                                                        'partially_approved' => 'warning',
                                                        default => 'danger',
                                                    };
                                                ?>
                                                <tr>
                                                    <td><?php echo e($index+1); ?></td>
                                                    <td class="fw-bolder text-nowrap text-dark">
                                                        <?php echo e(date('d-m-Y', strtotime($item->document_date))); ?></td>
                                                    <td class="text-nowrap"><?php echo e($item->series->service->name ?? '-'); ?></td>
                                                    <td class="text-nowrap"><?php echo e($item->series->book_code ?? '-'); ?></td>
                                                    <td class="text-nowrap"><?php echo e($item->voucher_no ?? '-'); ?></td>
                                                    <td class="text-nowrap"><?php echo e($item?->items?->first()?->ledger?->name ?? '-'); ?></td>
                                                    <td class="text-nowrap" style="text-align: end;">
                                                        <?php echo e(Helper::formatIndianNumber($item->amount) ?? '-'); ?></td>
                                                    <td class="text-nowrap"><?php echo e($item?->ErpLocation?->store_name ?? ''); ?></td>
                                                    <td class="text-nowrap"><?php echo e($item?->items?->first()?->costCenter?->name ?? '-'); ?></td>

                                                    <td>
                                                        <?php $documents = $item->document
                                                                                                                            ? json_decode($item->document, true)
                                                                                                                            : [];
                                                                                                                        if (!is_array($documents) && $item->document) {
                                                                                                                            $documents[] = $item->document;
                                                                                                                        }

                                                                                                                ?>
                                                        <?php if($documents): ?>
                                                            <span style="display: flex;margin:0;padding:0">
                                                                <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <a style="display: flex;margin:0;padding:0"
                                                                        class="dropdown-item"
                                                                        href="voucherDocuments/<?php echo e($doc); ?>"
                                                                        target="_blank">
                                                                        <i data-feather="file-text"
                                                                            class="fileuploadicon"></i>
                                                                    </a>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </span>
                                                        <?php endif; ?>




                                                    <td class="text-nowrap">
                                                        <?php echo e($item->remarks ?? '-'); ?>

                                                    </td>

                                                    <td class="tableactionnew">
                                                        <div class="d-flex align-items-center justify-content-end">
                                                        <?php $statusClasss = App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$item->document_status??"draft"];  ?>
                                                        <span
                                                            class='badge rounded-pill <?php echo e($statusClasss); ?> badgeborder-radius'>
                                                            <?php if($item->document_status == App\Helpers\ConstantHelper::APPROVAL_NOT_REQUIRED): ?>
                                                                Approved
                                                            <?php else: ?>
                                                                <?php echo e(ucfirst($item->document_status)); ?>

                                                            <?php endif; ?>
                                                        </span>
                                                        <div class="dropdown">
                                                            <button type="button"
                                                                class="btn btn-sm dropdown-toggle hide-arrow p-0"
                                                                data-bs-toggle="dropdown">
                                                                <i data-feather="more-vertical"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-end">
                                                                <a class="dropdown-item"
                                                                    href="<?php echo e(route('vouchers.edit', ['voucher' => $item->id])); ?>">
                                                                    <i data-feather="edit-3" class="me-50"></i>
                                                                    <span>View</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal modal-slide-in fade filterpopuplabel" id="filter">
                        <div class="modal-dialog sidebar-sm">
                            <form class="add-new-record modal-content pt-0">
                                <div class="modal-header mb-1">
                                    <h5 class="modal-title" id="exampleModalLabel">Apply Filter</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close">×</button>
                                </div>
                                <div class="modal-body flex-grow-1">
                                    <div class="mb-1">
                                        <label class="form-label" for="fp-range">Select Date</label>
                                        <input type="text" id="fp-range"
                                            class="form-control flatpickr-range bg-white" name="date"
                                            placeholder="YYYY-MM-DD to YYYY-MM-DD" value="<?php echo e($date); ?>" />
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label">Document Type</label>
                                        <select class="form-select" id="book_type" name="book_type">
                                            <option value="">Select</option>
                                            <?php $__currentLoopData = $bookTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($bookType->id); ?>"
                                                    <?php if($bookType->id == $book_type): ?> selected <?php endif; ?>><?php echo e($bookType->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label">Voucher Name</label>
                                        <input class="form-control" type="text" name="voucher_name" id="voucher_name"
                                            value="<?php echo e(request('voucher_name')); ?>" />
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label">Voucher No.</label>
                                        <input class="form-control" type="text" name="voucher_no" id="voucher_no"
                                            value="<?php echo e(request('voucher_no')); ?>" />
                                    </div>
                                    

                                    <div class="mb-1">
                                        <label class="form-label">Organization</label>
                                        <select id="filter-organization" class="form-select select2" multiple
                                            name="filter_organization">
                                            <option value="" disabled>Select</option>
                                            <?php $__currentLoopData = $mappings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $organization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($organization->organization->id); ?>"
                                                    <?php echo e($organization->organization->id == $organizationId ? 'selected' : ''); ?>>
                                                    <?php echo e($organization->organization->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label">Location</label>
                                        <select name="location_id" id="location_id" class="form-select select2">
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label">Cost Group</label>
                                        <select id="cost_group_id" name="cost_group_id" class="form-select select2">
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label">Cost Center</label>
                                        <select id="cost_center_id" class="form-select select2"
                                            name="cost_center_id">
                                        </select>
                                    </div>

                                </div>
                                <div class="modal-footer justify-content-start">
                                    <button type="submit"
                                        class="btn btn-primary data-submit mr-1 apply-filter">Apply</button>
                                    <button type="reset" class="btn btn-outline-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                </section>


            </div>
        </div>
    </div>
    <!-- END: Content-->

    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="ajax-input-form" method="POST" action="<?php echo e(route('approveVoucher')); ?>"
                    data-redirect="<?php echo e(route('vouchers.index')); ?>" enctype='multipart/form-data'>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action_type" id="action_type">
                    <input type="hidden" name="id" id="id">
                    <div class="modal-header">
                        <div>
                            <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="myModalLabel17">Approve
                                Voucher</h4>
                            <p class="mb-0 fw-bold voucehrinvocetxt mt-0"><?php echo e(Carbon\Carbon::now()->format('d-m-Y')); ?>

                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pb-2">
                        <div class="row mt-1">
                            <div class="col-md-12">
                                <div class="mb-1">
                                    <label class="form-label">Remarks <span class="text-danger">*</span></label>
                                    <textarea name="remarks" class="form-control"></textarea>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Upload Document</label>
                                    <input type="file" multiple class="form-control" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="reset" class="btn btn-outline-secondary me-1">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submit-button">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    const locations = <?php echo json_encode($locations, 15, 512) ?>;
    const costCenters = <?php echo json_encode($cost_centers, 15, 512) ?>;
    const costGroups = <?php echo json_encode($cost_groups, 15, 512) ?>;
</script>
<script type="text/javascript" src="<?php echo e(asset('assets/js/modules/finance-table.js')); ?>"></script>

    <script>
        function setApproval(id) {
            document.getElementById('id').value = id;
            document.getElementById('action_type').value = "approve";
        }

        function setReject(id) {
            document.getElementById('id').value = id;
            document.getElementById('action_type').value = "reject";
        }
        function updateLocationsDropdown(selectedOrgIds) {
        const filteredLocations = locations.filter(loc =>
            selectedOrgIds.includes(String(loc.organization_id))
        );

        const $locationDropdown = $('#location_id');
        $locationDropdown.empty().append('<option value="">Select</option>');

        filteredLocations.forEach(loc => {
            $locationDropdown.append(`<option value="${loc.id}">${loc.store_name}</option>`);
        });

        $locationDropdown.trigger('change');
    }

     function loadCostGroupsByLocation(locationId) {
        const filteredCenters = costCenters.filter(center => {
            if (!center.location) return false;
            const locationArray = Array.isArray(center.location)
                ? center.location.flatMap(loc => loc.split(','))
                : [];
            return locationArray.includes(String(locationId));
        });

        const costGroupIds = [...new Set(filteredCenters.map(center => center.cost_group_id))];
        
        const filteredGroups = costGroups.filter(group => costGroupIds.includes(group.id));
        console.log(filteredCenters,costGroupIds,filteredGroups);

        const $groupDropdown = $('#cost_group_id');
        $groupDropdown.empty().append('<option value="">Select Cost Group</option>');

        filteredGroups.forEach(group => {
            $groupDropdown.append(`<option value="${group.id}">${group.name}</option>`);
        });

        $('#cost_group_id').trigger('change');
    }

    function loadCostCentersByGroup(locationId, groupId) {
        const costCenter = $('#cost_center_id');
        costCenter.empty();

        const filteredCenters = costCenters.filter(center => center.cost_group_id === groupId);

        if (filteredCenters.length === 0) {
            costCenter.prop('required', false);
            $('#cost_center_id').hide();
        } else {
            costCenter.append('<option value="">Select Cost Center</option>');
            $('#cost_center_id').show();

            filteredCenters.forEach(center => {
                costCenter.append(`<option value="${center.id}">${center.name}</option>`);
            });
        }
        costCenter.val(<?php echo json_encode(request('cost_center_id'), 15, 512) ?> || "");
        costCenter.trigger('change');
    }


    $(document).ready(function () {
        // On change of organization
        $('#filter-organization').on('change', function () {
            const selectedOrgIds = $(this).val() || [];
            updateLocationsDropdown(selectedOrgIds);
        });

        // On page load, check for preselected orgs
        const preselectedOrgIds = $('#filter-organization').val() || [];
        if (preselectedOrgIds.length > 0) {
            updateLocationsDropdown(preselectedOrgIds);
        }
        // On location change, load cost centers
        $('#location_id').on('change', function () {
            const locationId = $(this).val();
          if (!locationId) {
        // $('#cost_center_id').empty().append('<option value="">Select Cost Center</option>');
            // $('.cost_center').hide(); // Optional: hide the section if needed
                return;
            }
                loadCostGroupsByLocation(locationId);
        });
        $('#cost_group_id').on('change', function () {
                const locationId = $('#location_id').val();
                const groupId = parseInt($(this).val());

                if (!locationId || !groupId) {
                    $('#cost_center_id').empty().append('<option value="">Select Cost Center</option>');
                    return;
                }

                loadCostCentersByGroup(locationId, groupId);
            });
            // Initialize DataTable on the table with the class .datatables-basic
            $('.datatables-basic').DataTable({
                processing: true, // Show processing indicator
                serverSide: false, // Disable server-side processing since data is already loaded
                scrollX: true,
                drawCallback: function() {
                    feather
                        .replace(); // Re-initialize feather icons if needed (for custom icons like edit)
                },
                order: [[0, 'asc']], // Default ordering by the first column (Date)
                dom: '<"d-flex justify-content-between align-items-center mx-2 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-3 withoutheadbuttin dt-action-buttons text-end"B><"col-sm-12 col-md-3"f>>t<"d-flex justify-content-between mx-2 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                lengthMenu: [7, 10, 25, 50, 75, 100], // Options for number of rows to show
                 buttons:
                [{
                    extend: 'excel',
                            text: feather.icons['file'].toSvg({ class: 'font-small-4 me-50' }) + 'Excel',
                            className: 'btn btn-outline-secondary',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6, 7,8]
                            },
                            filename: 'Vouchers Report'
                    ,
                    init: function (api, node, config) {
                        $(node).removeClass('btn-secondary');
                        $(node).parent().removeClass('btn-group');
                        setTimeout(function () {
                            $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
                        }, 50);
                    }
                    }],
                // buttons: [{
                //     extend: 'collection',
                //     className: 'btn btn-outline-secondary dropdown-toggle',
                //     text: feather.icons['share'].toSvg({
                //         class: 'font-small-4 mr-50'
                //     }) + 'Export',
                //     buttons: [{
                //             extend: 'print',
                //             text: feather.icons['printer'].toSvg({
                //                 class: 'font-small-4 mr-50'
                //             }) + 'Print',
                //             className: 'dropdown-item',
                //             exportOptions: {
                //                 columns: [0, 1, 2, 3, 4, 5, 6, 7]
                //             },
                //             filename: 'Vouchers Report'
                //         },
                //         {
                //             extend: 'csv',
                //             text: feather.icons['file-text'].toSvg({
                //                 class: 'font-small-4 mr-50'
                //             }) + 'Csv',
                //             className: 'dropdown-item',
                //             exportOptions: {
                //                 columns: [0, 1, 2, 3, 4, 5, 6, 7]
                //             },
                //             filename: 'Vouchers Report'
                //         },
                //         {
                //             extend: 'excel',
                //             text: feather.icons['file'].toSvg({
                //                 class: 'font-small-4 mr-50'
                //             }) + 'Excel',
                //             className: 'dropdown-item',
                //             exportOptions: {
                //                 columns: [0, 1, 2, 3, 4, 5, 6, 7]
                //             },
                //             filename: 'Vouchers Report'
                //         },
                //         {
                //             extend: 'pdf',
                //             text: feather.icons['clipboard'].toSvg({
                //                 class: 'font-small-4 mr-50'
                //             }) + 'Pdf',
                //             className: 'dropdown-item',
                //             exportOptions: {
                //                 columns: [0, 1, 2, 3, 4, 5, 6, 7]
                //             },
                //             filename: 'Vouchers Report'
                //         },
                //         {
                //             extend: 'copy',
                //             text: feather.icons['copy'].toSvg({
                //                 class: 'font-small-4 mr-50'
                //             }) + 'Copy',
                //             className: 'dropdown-item',
                //             exportOptions: {
                //                 columns: [0, 1, 2, 3, 4, 5, 6, 7]
                //             },
                //             filename: 'Vouchers Report'
                //         }
                //     ],
                //     init: function(api, node, config) {
                //         $(node).removeClass('btn-secondary');
                //         $(node).parent().removeClass('btn-group');
                //         setTimeout(function() {
                //             $(node).closest('.dt-buttons').removeClass('btn-group')
                //                 .addClass('d-inline-flex');
                //         }, 50);
                //     }
                // }],
                columnDefs: [{
                        "orderable": false,
                        "targets": [8]
                    } // Disable sorting on the action column
                ],
                language: {
                    paginate: {
                        previous: '&nbsp;',
                        next: '&nbsp;'
                    }
                }
            });

          handleRowSelection('.datatables-basic');

            // Optionally, you can add some custom logic or event listeners here
        });
    </script>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/erp_presence360/resources/views/voucher/view_vouchers.blade.php ENDPATH**/ ?>