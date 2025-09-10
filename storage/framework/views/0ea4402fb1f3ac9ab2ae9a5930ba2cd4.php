<?php $__env->startSection('css'); ?>
    <style type="text/css">
        .image-uplodasection {
            position: relative;
            margin-bottom: 10px;
        }

        .fileuploadicon {
            font-size: 24px;
        }



        .delete-img {
            position: absolute;
            top: 5px;
            right: 5px;
            cursor: pointer;
        }

        .preview-image {
            max-width: 100px;
            max-height: 100px;
            display: block;
            margin-top: 10px;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-5 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Fixed Asset Registration</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo e(route('/')); ?>">Home</a></li>
                                    <li class="breadcrumb-item active">Asset List</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-sm-end col-md-7 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter"
                            data-bs-toggle="modal"><i data-feather="filter"></i> Filter</button>
                            <a href="<?php echo e(route('finance.fixed-asset.show.import')); ?>" class="btn btn-secondary btn-sm mb-50 mb-sm-0">
                            <i data-feather="upload"></i> Import
                        </a> 
                        <a class="btn btn-primary btn-sm mb-50 mb-sm-0"
                            href="<?php echo e(route('finance.fixed-asset.registration.create')); ?>"><i data-feather="plus-circle"></i>
                            Add New</a>
                    </div>
                </div>
            </div>
            <div class="content-body">



                <section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">


                                <div class="table-responsive">
                                    <table class="datatables-basic table myrequesttablecbox tableistlastcolumnfixed ">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th width="100px">Series</th>
                                                <th width="100px">Doc. No</th>
                                                <th>Asset Name</th>
                                                <th>Asset Code</th>
                                                <th>Dep. Method</th>
                                                <th>Cap. Date</th>
                                                <th>Qty</th>
                                                <th>Location</th>
                                                <th>Cost Center</th>
                                                <th>Ledger Name</th>
                                                <th>Book Date</th>
                                                <th class="text-end">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(isset($data)): ?>
                                                <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                    <tr>
                                                        <td class="text-nowrap"><?php echo e($loop->iteration); ?></td>
                                                        <td class="fw-bolder text-dark text-nowrap">
                                                            <?php echo e(\Carbon\Carbon::parse($asset->document_date)->format('d-m-Y') ?? '-'); ?>

                                                        </td>
                                                        <td class="text-nowrap"><?php echo e($asset?->book?->book_code ?? '-'); ?></td>
                                                        <td class="text-nowrap"><?php echo e($asset->document_number ?? '-'); ?></td>
                                                        <td class="text-nowrap">
                                                            <?php echo e($asset->asset_name ?? '-'); ?></td>
                                                        <td class="text-nowrap"><?php echo e($asset->asset_code ?? '-'); ?></td>
                                                        <td class="text-nowrap"><?php echo e($asset->depreciation_method ?? '-'); ?></td>
                                                        <td class="text-nowrap">
                                                            <?php echo e($asset->capitalize_date != null ? \Carbon\Carbon::parse($asset->capitalize_date)->format('d-m-Y') : '-'); ?>

                                                        </td>
                                                        <td class="text-nowrap"><?php echo e($asset->quantity ?? '-'); ?></td>
                                                        <td class="text-nowrap"><?php echo e($asset?->location?->store_name ?? '-'); ?>

                                                        </td>
                                                        <td class="text-nowrap"><?php echo e($asset?->cost_center?->name ?? '-'); ?></td>
                                                        <td class="text-nowrap"><?php echo e($asset->ledger->name ?? '-'); ?></td>
                                                        <td class="text-nowrap">
                                                            <?php echo e($asset->book_date != null ? \Carbon\Carbon::parse($asset->book_date)->format('d-m-Y') : '-'); ?>

                                                        </td>
                                                        <td class="tableactionnew">
                                                            <div class="d-flex align-items-center justify-content-end">
                                                                <?php $statusClasss = App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$asset->document_status??"draft"];  ?>
                                                                <span
                                                                    class='badge rounded-pill <?php echo e($statusClasss); ?> badgeborder-radius'>
                                                                    <?php if($asset->document_status == App\Helpers\ConstantHelper::APPROVAL_NOT_REQUIRED): ?>
                                                                        Approved
                                                                    <?php else: ?>
                                                                        <?php echo e(ucfirst($asset->document_status)); ?>

                                                                    <?php endif; ?>
                                                                </span>


                                                                <div class="dropdown">
                                                                    <button type="button"
                                                                        class="btn btn-sm dropdown-toggle hide-arrow p-0"
                                                                        data-bs-toggle="dropdown">
                                                                        <i data-feather="more-vertical"></i>
                                                                    </button>
                                                                    <div class="dropdown-menu dropdown-menu-end">
                                                                        <?php if($asset->document_status == 'draft'): ?>
                                                                            <a class="dropdown-item"
                                                                                href="<?php echo e(route('finance.fixed-asset.registration.edit', $asset->id)); ?>">
                                                                                <i data-feather="edit" class="me-50"></i>
                                                                                <span>View</span>
                                                                            </a>
                                                                        <?php else: ?>
                                                                            <a class="dropdown-item"
                                                                                href="<?php echo e(route('finance.fixed-asset.registration.show', $asset->id)); ?>">
                                                                                <i data-feather="edit" class="me-50"></i>
                                                                                <span>View</span>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                    <tr>
                                                        <td colspan="14" class="text-center">No data available</td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>





                            </div>
                        </div>
                    </div>
                    <!-- Modal to add new record -->
                        <div class="modal modal-slide-in fade filterpopuplabel" id="filter">
        <div class="modal-dialog sidebar-sm">
            <form class="add-new-record modal-content pt-0" method="POST"
                action="<?php echo e(route('finance.fixed-asset.registration.filter')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-header mb-1">
                    <h5 class="modal-title" id="exampleModalLabel">Apply Filter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label" for="fp-range">Select Date</label>
                        <input type="text" id="fp-range" name="date" value="<?php echo e(request('date')); ?>"
                            class="form-control flatpickr-range bg-white" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Asset Code</label>
                        <select class="form-select" name="filter_asset">
                            <option value="">Select</option>
                            <?php $__currentLoopData = $assetCodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assetCode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($assetCode->id); ?>"
                                    <?php echo e(request('filter_asset') == $assetCode->id ? 'selected' : ''); ?>>
                                    <?php echo e($assetCode->asset_code); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Ledger Name</label>
                        <select class="form-select" name="filter_ledger">
                            <option value="">Select</option>
                            <?php $__currentLoopData = $ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ledger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($ledger->id); ?>"
                                    <?php echo e(request('filter_ledger') == $ledger->id ? 'selected' : ''); ?>>
                                    <?php echo e($ledger->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="filter_status">
                            <option value="">Select</option>
                            <?php $__currentLoopData = App\Helpers\ConstantHelper::DOCUMENT_STATUS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($status); ?>"
                                    <?php echo e(request('filter_status') == $status ? 'selected' : ''); ?>>
                                    <?php echo e(ucfirst($status)); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>


                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-primary data-submit mr-1">Apply</button>
                    <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
                    <div class="modal modal-slide-in fade" id="modals-slide-in">
                        <div class="modal-dialog sidebar-sm">
                            <form class="add-new-record modal-content pt-0">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close">×</button>
                                <div class="modal-header mb-1">
                                    <h5 class="modal-title" id="exampleModalLabel">New Record</h5>
                                </div>
                                <div class="modal-body flex-grow-1">
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-fullname">Full Name</label>
                                        <input type="text" class="form-control dt-full-name"
                                            id="basic-icon-default-fullname" placeholder="John Doe" aria-label="John Doe" />
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-post">Post</label>
                                        <input type="text" id="basic-icon-default-post" class="form-control dt-post"
                                            placeholder="Web Developer" aria-label="Web Developer" />
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-email">Email</label>
                                        <input type="text" id="basic-icon-default-email" class="form-control dt-email"
                                            placeholder="john.doe@example.com" aria-label="john.doe@example.com" />
                                        <small class="form-text"> You can use letters, numbers & periods </small>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-date">Joining Date</label>
                                        <input type="text" class="form-control dt-date" id="basic-icon-default-date"
                                            placeholder="MM/DD/YYYY" aria-label="MM/DD/YYYY" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="basic-icon-default-salary">Salary</label>
                                        <input type="text" id="basic-icon-default-salary"
                                            class="form-control dt-salary" placeholder="$12000" aria-label="$12000" />
                                    </div>
                                    <button type="button" class="btn btn-primary data-submit me-1">Submit</button>
                                    <button type="reset" class="btn btn-outline-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>


            </div>
        </div>
    </div>
    <!-- END: Content-->
<?php $__env->stopSection(); ?>
<?php $__env->startSection('scripts'); ?>
    <script type="text/javascript" src="<?php echo e(asset('assets/js/modules/finance-table.js')); ?>"></script>
    <script>
       $(function() {
        // Get all request parameters from Laravel and convert to query string
        const requestParams = <?php echo json_encode(request()->all(), 15, 512) ?>;
        const queryString = new URLSearchParams(requestParams).toString();

        // Compose the export URL with query params
        const exportUrl = '<?php echo e(route("finance.fixed-asset.export")); ?>' + (queryString ? '?' + queryString : '');

        // Initialize DataTable
        const dt = initializeBasicDataTable('.datatables-basic', 'Asset_RegistrationReport', exportUrl);

        // Set label
        $('div.head-label').html('<h6 class="mb-0">Asset Registration</h6>');
    });



        function showToast(icon, title) {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                },
            });
            Toast.fire({
                icon,
                title
            });
        }

        <?php if(session('success')): ?>
            showToast("success", "<?php echo e(session('success')); ?>");
        <?php endif; ?>

        <?php if(session('error')): ?>
            showToast("error", "<?php echo e(session('error')); ?>");
        <?php endif; ?>


        <?php if($errors->any()): ?>
            showToast('error',
                "<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>"
            );
        <?php endif; ?>
        handleRowSelection('.datatables-basic');
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/erp_presence360/resources/views/fixed-asset/registration/index.blade.php ENDPATH**/ ?>