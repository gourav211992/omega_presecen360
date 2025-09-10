<?php $__env->startSection('styles'); ?>
    <style type="text/css">
        #map {
            width: 100%;
            height: 550px;
            border: 10px solid #fff;
            box-shadow: 0 0px 20px rgba(0, 0, 0, 0.1);
        }
    </style>

    <style type="text/css">
        #pac-input {
            margin-top: 10px;
            padding: 10px;
            width: 95% !important;
            font-size: 16px;
            position: relative !important;
            left: 0 !important;
            top: 51px !important;
            border: #eee thin solid;
            font-size: 14px;
            border-radius: 6px;
            margin-left: 11px;
        }

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
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo e(env('GOOGLE_MAPS_API_KEY')); ?>&libraries=places" async defer>
    </script>
<?php $__env->startSection('content'); ?>
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
                <div class="row">
                    <div class="content-header-left col-md-6 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0">Setup</h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a
                                                href="<?php echo e(route('finance.fixed-asset.setup.index')); ?>">Home</a>
                                        </li>
                                        <li class="breadcrumb-item active">Add New</li>


                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                        <div class="form-group breadcrumb-right">
                            <button onClick="javascript: history.go(-1)" class="btn btn-secondary btn-sm mb-50 mb-sm-0"><i
                                    data-feather="arrow-left-circle"></i> Back</button>
                            <button form="setup" class="btn btn-primary btn-sm mb-50 mb-sm-0"><i
                                    data-feather="check-circle"></i> Create</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">

                <form id="setup" method="POST" action="<?php echo e(route('finance.fixed-asset.setup.store')); ?>"
                    enctype="multipart/form-data">


                    <section id="basic-datatable">
                        <div class="row">
                            <div class="col-12">

                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="newheader border-bottom mb-2 pb-25">
                                                    <h4 class="card-title text-theme">Basic Information</h4>
                                                    <p class="card-text">Fill the details</p>
                                                </div>
                                            </div>
                                            <input type="hidden" name="asset_category_id" id="asset_category_id">

                                            <div class="col-md-9">
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Act Type <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-8">
                                                        <div class="demo-inline-spacing">
                                                            <div class="form-check form-check-primary mt-25">
                                                                <input type="radio" id="company" name="act_type"
                                                                    value="company" class="form-check-input" checked>
                                                                <label class="form-check-label fw-bolder"
                                                                    for="company">Company</label>
                                                            </div>
                                                            <div class="form-check form-check-primary mt-25">
                                                                <input type="radio" id="income_tax" name="act_type"
                                                                    value="income_tax" class="form-check-input">
                                                                <label class="form-check-label fw-bolder"
                                                                    for="income_tax">Income Tax</label>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Asset Category <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" id="asset_category" name="asset_category"
                                                            class="form-control" oninput="generatePrefix()" placeholder="Enter Category Name"
                                                            value="<?php echo e(old('asset_category')); ?>" required />
                                                        <?php $__errorArgs = ['asset_category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Prefix <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" name="prefix" required 
                                                            oninput="checkUnique()" class="form-control text-uppercase company-field"
                                                            maxlength="3" pattern="[A-Z]{1,3}"
                                                            title="Enter up to 3 uppercase letters"
                                                            value="<?php echo e(old('prefix')); ?>" required
                                                            oninput="this.value = this.value.toUpperCase()" />
                                                        <?php $__errorArgs = ['prefix'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <span class="alert alert-danger"><?php echo e($message); ?></span>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                        <span id="prefix-feedback" class="text-danger small"></span>
                                                    </div>
                                                </div>

                                                

                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Ledger <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2 company-field" name="ledger_id" id="ledger"
                                                            required>
                                                          
                                                            <?php $__currentLoopData = $ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ledger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($ledger->id); ?>"
                                                                    <?php echo e(old('ledger') == $ledger->id ? 'selected' : ''); ?>>
                                                                    <?php echo e($ledger->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Ledger Group <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select company-field" name="ledger_group_id" id="ledger_group"
                                                            required>
                                                        </select>

                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Salvage % <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="number" class="form-control company-field"
                                                            name="salvage_percentage" id="salvage_percentage" required min=1 max=100
                                                            value="<?php echo e($dep_percentage); ?>" />
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1 income_tax">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Dep % <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="number" class="form-control income-field" name="dep_percentage" id="dep_percentage" min=1 max=100
                                                            required />
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Expected Life in Yrs. <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="number" class="form-control company-field"
                                                            name="expected_life_years" required
                                                            value="<?php echo e(old('expected_life_years')); ?>" />
                                                    </div>
                                                </div>



                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Maintenance Schedule</label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" name="maintenance_schedule">
                                                            <option value=""
                                                                <?php echo e(old('maintenance_schedule') == '' ? 'selected' : ''); ?>>
                                                                Select</option>
                                                            <option value="weekly"
                                                                <?php echo e(old('maintenance_schedule') == 'Weekly' ? 'selected' : ''); ?>>
                                                                Weekly</option>
                                                            <option value="monthly"
                                                                <?php echo e(old('maintenance_schedule') == 'Monthly' ? 'selected' : ''); ?>>
                                                                Monthly</option>
                                                            <option value="quarterly"
                                                                <?php echo e(old('maintenance_schedule') == 'Quarterly' ? 'selected' : ''); ?>>
                                                                Quarterly</option>
                                                            <option value="semi-annually"
                                                                <?php echo e(old('maintenance_schedule') == 'Semi-Annually' ? 'selected' : ''); ?>>
                                                                Semi-Annually</option>
                                                            <option value="annually"
                                                                <?php echo e(old('maintenance_schedule') == 'Annually' ? 'selected' : ''); ?>>
                                                                Annually</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Dep. Ledger <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2 company-field" name="dep_ledger_id"
                                                            id="dep_ledger" required>
                                                            <option value="" <?php echo e(old('ledger') ? '' : 'selected'); ?>>
                                                                Select</option>
                                                            <?php $__currentLoopData = $dep_ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ledgeri): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($ledgeri->id); ?>"
                                                                    <?php echo e($dep_ledger_id == $ledgeri->id ? 'selected' : ''); ?>>
                                                                    <?php echo e($ledgeri->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Dep. Ledger Group <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select company-field" name="dep_ledger_group_id"
                                                            id="dep_ledger_group" required>
                                                        </select>

                                                    </div>
                                                </div>


                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Revaluation Ledger </label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" name="rev_ledger_id"
                                                            id="rev_ledger">
                                                            <option value=""
                                                                <?php echo e(old('rev_ledger') ? '' : 'selected'); ?>>Select</option>
                                                            <?php $__currentLoopData = $sur_ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($rev->id); ?>">
                                                                    <?php echo e($rev->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 d-none">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Rev. Ledger Group <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" name="rev_ledger_group_id"
                                                            id="rev_ledger_group">
                                                        </select>

                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Impairement Ledger </label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" name="imp_ledger_id"
                                                            id="imp_ledger">
                                                            <option value="">Select</option>
                                                            <?php $__currentLoopData = $sales_exp_ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $imp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($imp->id); ?>">
                                                                    <?php echo e($imp->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 d-none">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Imp. Ledger Group <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" name="imp_ledger_group_id"
                                                            id="imp_ledger_group">
                                                        </select>

                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Writeoff Ledger </label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" name="wri_ledger_id"
                                                            id="wri_ledger">
                                                            <option value="">Select</option>
                                                            <?php $__currentLoopData = $sales_exp_ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wri): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($wri->id); ?>">
                                                                    <?php echo e($wri->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 d-none">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Wri. Ledger Group <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" name="wri_ledger_group_id"
                                                            id="wri_ledger_group">
                                                        </select>

                                                    </div>
                                                </div>
                                                
                                                <div class="row align-items-center mb-1 company">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Sales Ledger </label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" name="sales_ledger_id"
                                                            id="sales_ledger">
                                                            <option value="">Select</option>
                                                            <?php $__currentLoopData = $sales_exp_ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sales): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($sales->id); ?>">
                                                                    <?php echo e($sales->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1 d-none">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Sales Ledger Group <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" name="sales_ledger_group_id"
                                                            id="sales_ledger_group">
                                                        </select>

                                                    </div>
                                                </div>
                                            </div>





                                            <div class="col-md-3 border-start">
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-md-12">
                                                        <label
                                                            class="form-label text-primary"><strong>Status</strong></label>
                                                        <div class="demo-inline-spacing">
                                                            <div class="form-check form-check-primary mt-25">
                                                                <input type="radio" id="customColorRadio3"
                                                                    name="status" value="active"
                                                                    class="form-check-input"
                                                                    <?php echo e(old('status', 'active') == 'active' ? 'checked' : ''); ?> />
                                                                <label class="form-check-label fw-bolder"
                                                                    for="customColorRadio3">Active</label>
                                                            </div>
                                                            <div class="form-check form-check-primary mt-25">
                                                                <input type="radio" id="customColorRadio4"
                                                                    name="status" value="inactive"
                                                                    class="form-check-input"
                                                                    <?php echo e(old('status') == 'inactive' ? 'checked' : ''); ?> />
                                                                <label class="form-check-label fw-bolder"
                                                                    for="customColorRadio4">Inactive</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal to add new record -->

                    </section>
                </form>


            </div>
        </div>
    </div>
    <!-- END: Content-->
<?php $__env->startSection('scripts'); ?>

    <script type="text/javascript">
    
        $('#setup').on('submit', function(e) {
            e.preventDefault();
        if (($('#prefix-feedback').text().trim()) != "" && $('#company').is(':checked')) {
    showToast('error', 'Prefix already taken');
    return;
}
        if($('#income_tax').is(':checked'))
        $('input[name="prefix"]').val('');
        
            $('.preloader').show();
           
            
            this.submit();
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
            $('.preloader').hide();
            showToast("success", "<?php echo e(session('success')); ?>");
        <?php endif; ?>

        <?php if(session('error')): ?>
            $('.preloader').hide();
            showToast("error", "<?php echo e(session('error')); ?>");
        <?php endif; ?>

        <?php if($errors->any()): ?>
            $('.preloader').hide();
            showToast('error',
                "<?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>"
            );
        <?php endif; ?>

        function handleLedgerChange(ledgerSelector, groupSelector, selectedGroupId = null) {
            $(ledgerSelector).change(function() {
                const ledgerId = $(this).val();
                 const groupDropdown = $(groupSelector);
                 if (ledgerId === '') {
                        groupDropdown.empty(); // Optional: Clear group dropdown if ledger is empty
                        return;
                }
                $.ajax({
                    url: '<?php echo e(route('finance.fixed-asset.getLedgerGroups')); ?>',
                    method: 'GET',
                    data: {
                        ledger_id: ledgerId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        groupDropdown.empty();
                        response.forEach(item => {
                            const selected = (selectedGroupId == item.id) ? 'selected' : '';
                            groupDropdown.append(
                                `<option value="${item.id}" ${selected}>${item.name}</option>`
                            );
                        });
                    },
                    error: function() {
                        alert('Error fetching group items.');
                    }
                });
            });
        }
        handleLedgerChange('#ledger', '#ledger_group');
        handleLedgerChange('#rev_ledger', '#rev_ledger_group');
        handleLedgerChange('#imp_ledger', '#imp_ledger_group');
        handleLedgerChange('#sales_ledger', '#sales_ledger_group');
        handleLedgerChange('#wri_ledger', '#wri_ledger_group');
        handleLedgerChange('#dep_ledger', '#dep_ledger_group', "<?php echo e($dep_ledger_group_id); ?>");
        $('#ledger').trigger('change');


        var categories = [
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                {
                    id: <?php echo e($category->id); ?>,
                    label: "<?php echo e($category->name); ?>"
                },
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ];

        $("#asset_category").autocomplete({
            source: categories,
            minLength: 1,
            select: function(event, ui) {
                $("#asset_category").val(ui.item.label); // Set textbox value
                $("#asset_category_id").val(ui.item.id); // Set hidden category_id
                return false;
            }
        });
        $("#asset_category").on("input", function() {
            $("#asset_category_id").val(''); // Set to empty string or null
        });
        $('#dep_ledger').trigger('change');

        function toggleFields() {
            if ($('#income_tax').is(':checked')) {
                    $('.income_tax').removeClass('d-none');
                     $('.company').addClass('d-none');
                    $('.company-field').removeAttr('required').val('');
                    $('.income-field').attr('required', true);
                    $('#salvage_percentage').val('');
            } else {
                $('.income_tax').addClass('d-none');
                $('.company').removeClass('d-none');
                $('.company-field').attr('required', true);
                $('.income-field').removeAttr('required').val('');
                $('#salvage_percentage').val('<?php echo e($dep_percentage); ?>');
            }
        }
        $('input[name="act_type"]').on('change', toggleFields);
        toggleFields();

        const prefix = $('input[name="prefix"]');
        const name = $('input[name="asset_category"]');

        function generatePrefix() {

            $.ajax({
                url: '<?php echo e(route('generate-setup-prefix')); ?>',
                method: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    name: name.val().trim(),
                },
                success: function(response) {
                    prefix.val((response.prefix || ''));
                },
                error: function() {
                    prefix.val('');
                }
            });
        }

        function checkUnique() {
            var feedback = $('#prefix-feedback');

            $.ajax({
                url: '<?php echo e(route('setup-check-prefix')); ?>',
                method: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    prefix: prefix.val().trim()
                },
                success: function(response) {
                    if (response.is_unique) {
                        feedback.text('');
                    } else {
                        feedback.text('Prefix is already in use.');
                    }

                    // Optionally update the field with suggested unique prefix
                    if (response.prefix) {
                        prefix.val(response.prefix);
                    }
                },
                error: function() {
                    feedback.text('Error checking prefix.');
                }
            });
        }
  
    </script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/erp_presence360/resources/views/fixed-asset/setup/create.blade.php ENDPATH**/ ?>