<?php
    use App\Helpers\ConstantHelper;
?>


<?php $__env->startSection('styles'); ?>
    <link rel="stylesheet" type="text/css" href="<?php echo e(url('/app-assets/css/core/menu/menu-types/vertical-menu.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('/app-assets/js/jquery-ui.css')); ?>">
    <style>
        .badge-light-primary span {
            font-weight: bold;
            /* Makes the INR text bold */
            color: #6B12B7;
            /* Sets the text color to blue (you can change this to any color) */
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
      <?php
        $unauthorizedMonths = [];
        foreach ($fy_months as $month) {
            if (!$month['authorized']) {
                $unauthorizedMonths[] = $month['fy_month'];
            }
        }
    ?>
    
    <script>
        const locationCostCentersMap = <?php echo json_encode($cost_centers, 15, 512) ?>;
        const unauthorizedMonths = <?php echo json_encode($unauthorizedMonths, 15, 512) ?>;
        const fy = <?php echo json_encode($fy_months, 15, 512) ?>;
        console.log("fy",fy);
        console.log("unauthorizedMonths",unauthorizedMonths);
        
    </script>
    <!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">

            <form id="voucherForm" action="<?php echo e(route('vouchers.store')); ?>" method="POST" enctype="multipart/form-data"
                onsubmit="return check_amount()">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="doc_number_type" id="doc_number_type">
                <input type="hidden" name="doc_reset_pattern" id="doc_reset_pattern">
                <input type="hidden" name="doc_prefix" id="doc_prefix">
                <input type="hidden" name="doc_suffix" id="doc_suffix">
                <input type="hidden" name="doc_no" id="doc_no">
                <input type="hidden" name="org_currency_id" id="org_currency_id">
                <input type="hidden" name="org_currency_code" id="org_currency_code">
                <input type="hidden" name="org_currency_exg_rate" id="org_currency_exg_rate">

                <input type="hidden" name="comp_currency_id" id="comp_currency_id">
                <input type="hidden" name="comp_currency_code" id="comp_currency_code">
                <input type="hidden" name="comp_currency_exg_rate" id="comp_currency_exg_rate">

                <input type="hidden" name="group_currency_id" id="group_currency_id">
                <input type="hidden" name="group_currency_code" id="group_currency_code">
                <input type="hidden" name="group_currency_exg_rate" id="group_currency_exg_rate">

                <input type="hidden" name="currency_code" id="currency_code">


                <input type="hidden" name="status" id="status">

                <div class="content-header pocreate-sticky">
                    <div class="row">
                        <div class="content-header-left col-md-6 col-6 mb-2">
                            <div class="row breadcrumbs-top">
                                <div class="col-12">
                                    <h2 class="content-header-title float-start mb-0">New Voucher</h2>
                                    <div class="breadcrumb-wrapper">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="<?php echo e(route('/')); ?>">Home</a></li>
                                            <li class="breadcrumb-item"><a href="<?php echo e(route('vouchers.index')); ?>">Vouchers
                                                    List</a></li>
                                            <li class="breadcrumb-item active">Add New</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-header-right text-end col-md-6 col-6 mb-2 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                <button onClick="javascript: history.go(-1)" class="btn btn-secondary btn-sm"><i
                                        data-feather="arrow-left-circle"></i> Back</button>
                                <button type="button" onclick="submitForm('draft');" id="draft"
                                    class="btn btn-outline-primary btn-sm mb-50 mb-sm-0"><i data-feather='save'></i> Save as
                                    Draft</button>
                                <button type="button" onclick="submitForm('submitted');"
                                    class="btn btn-primary btn-sm mb-50 mb-sm-0" id="submitted"><i
                                        data-feather="check-circle"></i>
                                    Submit</button>
                                <input id="submitButton" type="submit" value="Submit" class="hidden" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-body">
                    <?php if(session('success')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session('success')); ?>

                        </div>
                    <?php endif; ?>

                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>


                    <section id="basic-datatable">
                        <div class="row">
                            <div class="col-12">

                                <div class="card">
                                    <div class="card-body customernewsection-form">

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="newheader  border-bottom mb-2 pb-25">
                                                    <h4 class="card-title text-theme">Basic Information</h4>
                                                    <p class="card-text">Fill the details</p>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-md-12">

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Voucher Type <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select class="form-select" name="book_type_id"
                                                            id="book_type_id" required onchange="getBooks()">
                                                            <?php $__currentLoopData = $bookTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($bookType->id); ?>"
                                                                    data-alias="<?php echo e($bookType->alias); ?>"
                                                                    <?php if($lastVoucher): ?> <?php if($lastVoucher->book_type_id == $bookType->id): ?> selected <?php endif; ?>
                                                                    <?php endif; ?>><?php echo e($bookType->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Series <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select class="form-select" id="book_id" name="book_id"
                                                            required onchange="getDocNumberByBookId()">
                                                            <option disabled selected value="">Select</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div hidden class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Voucher Name <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="voucher_name"
                                                            id="voucher_name" required value="<?php echo e(old('voucher_name')); ?>"
                                                            readonly />
                                                        <?php $__errorArgs = ['voucher_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <span class="text-danger" style="font-size:12px"><?php echo e($message); ?></span>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Voucher No. <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" id="voucher_no"
                                                            name="voucher_no" required value="<?php echo e(old('voucher_no')); ?>"
                                                            readonly />
                                                        <?php $__errorArgs = ['voucher_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <span class="text-danger" style="font-size:12px"><?php echo e($message); ?></span>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                    </div>

                                                </div>


                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Date <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="date" class="form-control" id="date"
                                                            name="date" required value="<?php echo e(date('Y-m-d')); ?>"
                                                            max="<?php echo e(date('Y-m-d')); ?>" />
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Location <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select id="locations" class="form-select"
                                                            name="location" data-row="${rowCount + 1}" required>
                                                            <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($location->id); ?>">
                                                                    <?php echo e($location->store_name); ?></option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>

                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Currency <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select class="form-control select2" name="currency_id"
                                                            id="currency_id" onchange="getExchangeRate()">
                                                            <option value="" <?php if($orgCurrency==null): ?> selected <?php endif; ?>>Select Currency</option>
                                                            <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $currency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($currency->id); ?>"
                                                                    <?php if($orgCurrency == $currency->id): ?> selected <?php endif; ?>>
                                                                    <?php echo e($currency->name . ' (' . $currency->short_name . ')'); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label mt-50">Exchange Rate</label>


                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" id="orgExchangeRate"
                                                            id="orgExchangeRate" oninput="calculate_cr_dr()"
                                                            onclick="rate_change()" />
                                                    </div>
                                                    <div hidden class="col-md-7">

                                                        <div class="d-flex align-items-center">
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="d-flex">
                                                                        <input type="text" class="form-control"
                                                                            readonly id="base_currency_code"
                                                                            style="text-transform:uppercase;width: 80px; border-right: none; border-radius: 7px 0 0 7px" />



                                                                    </div>
                                                                    <label class="form-label">Base</label>
                                                                </div>

                                                                <div hidden class="col-md-4">
                                                                    <div class="d-flex">
                                                                        <input type="text" class="form-control"
                                                                            readonly id="company_currency_code"
                                                                            style="text-transform:uppercase;width: 80px; border-right: none; border-radius: 7px 0 0 7px" />

                                                                        <input type="text" class="form-control"
                                                                            readonly id="company_exchange_rate"
                                                                            style="width: 80px;  border-radius:0 7px 7px 0" />


                                                                    </div>
                                                                    <label class="form-label">Company</label>
                                                                </div>

                                                                <div hidden class="col-md-4">
                                                                    <div class="d-flex">
                                                                        <input type="text" class="form-control"
                                                                            readonly id="grp_currency_code"
                                                                            style="text-transform:uppercase;width: 80px; border-right: none; border-radius: 7px 0 0 7px" />

                                                                        <input type="text" class="form-control"
                                                                            readonly id="grp_exchange_rate"
                                                                            style="width: 80px;  border-radius:0 7px 7px 0" />


                                                                    </div>
                                                                    <label class="form-label">Group</label>
                                                                </div>
                                                            </div>



                                                        </div>
                                                    </div>

                                                </div>



                                                <div
                                                    class="newheader d-flex justify-content-between align-items-end mt-2 border-top pt-2">
                                                    <div class="header-left">
                                                        <h4 class="card-title text-theme">Item Wise Detail</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                    <div class="header-right">
                                                        <a href="<?php echo e(route('ledgers.create')); ?>"
                                                            class="btn btn-outline-primary btn-sm" target="_blank"><i
                                                                data-feather="plus"></i> Add Ledger</a>
                                                    </div>
                                                </div>



                                                <div class="table-responsive pomrnheadtffotsticky mt-1">
                                                    <table
                                                        class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th width="200px">Ledger Name</th>
                                                                <th>Group</th>
                                                                <th width="150px" class="text-end">Debit Amt</th>
                                                                <th width="150px" class="text-end">Credit Amt</th>
                                                                <th width="200px">Cost Center</th>
                                                                <th>Remarks</th>
                                                                <th width="60px">Action</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody class="mrntableselectexcel" id="item-details-body">
                                                            <tr id="1">
                                                                <td class="number">1</td>
                                                                <td class="poprod-decpt">
                                                                    <input type="text"
                                                                        class="form-control mw-100 ledgerselect"
                                                                        placeholder="Select Ledger" name="ledger_name1"
                                                                        id="ledger_name1" required data-id="1" />
                                                                    <input type="hidden" name="ledger_id[]"
                                                                        type="hidden" id="ledger_id1" class="ledgers" />
                                                                    <!--<input placeholder="Line Notes" type="text"
                                                                                                                                                                                                    class="form-control mw-100 mt-50"
                                                                                                                                                                                                    name="notes1" />-->
                                                                </td>
                                                                <td>
                                                                    <select required id="groupSelect1"
                                                                        name="parent_ledger_id[]" data-id="1"
                                                                        class="ledgerGroup form-select mw-100">
                                                                    </select>
                                                                </td>
                                                                <input type="hidden" name="group_debit_amt[]"
                                                                    id="group_debit_amt_1" value="0">
                                                                <input type="hidden" name="comp_debit_amt[]"
                                                                    id="comp_debit_amt_1" value="0">
                                                                <input type="hidden" name="group_credit_amt[]"
                                                                    id="group_credit_amt_1" value="0">
                                                                <input type="hidden" name="comp_credit_amt[]"
                                                                    id="comp_credit_amt_1" value="0">
                                                                <input type="hidden" class="dbt_amt_inr debt_inr_1"
                                                                    name="org_debit_amt[]" id="dept_inr_1" />
                                                                <input type="hidden" class="crd_amt_inr crd_inr_1"
                                                                    name="org_credit_amt[]" id="crd_inr_1" />

                                                                <td><input type="number"
                                                                        class="form-control mw-100 dbt_amt debt_1 text-end"
                                                                        onfocus="focusInput(this)" name="debit_amt[]"
                                                                        id="dept_1" min="0" step="0.01"
                                                                        value="0" />
                                                                </td>

                                                                <td><input type="number"
                                                                        class="form-control mw-100 crd_amt crd_1 text-end"
                                                                        onfocus="focusInput(this)" name="credit_amt[]"
                                                                        id="crd_1" min="0" step="0.01"
                                                                        value="0" />
                                                                </td>

                                                                <td>
                                                                    <select class="costCenter form-select mw-100"
                                                                        name="cost_center_id[]" id="cost_center_id1">
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text"
                                                                        class="form-control mw-100 remarks_"
                                                                        placeholder="Enter Remarks" id="hiddenRemarks_1"
                                                                        name="item_remarks[]" value="">
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex">
                                                                        <div hidden class="me-50 cursor-pointer remark-btn"
                                                                            data-row-id="1" data-bs-toggle="modal"
                                                                            data-bs-target="#remarksModal"><span
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-placement="top" title="Remarks"
                                                                                class="text-primary"><i
                                                                                    data-feather="file-text"></i></span>
                                                                        </div>
                                                                        <div class="me-50 cursor-pointer"><span
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-placement="top" title="Delete"
                                                                                class="text-danger remove-item"><i
                                                                                    data-feather="trash-2"></i></span>
                                                                        </div>
                                                                    </div>

                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="totalsubheadpodetail voucher-tab-foot">
                                                                <td colspan="3"></td>
                                                                <td class="text-end">
                                                                    <h5 id="dbt_total">0.00</h5>
                                                                    <input type="hidden" name="amount" id="amount">
                                                                </td>
                                                                <td hidden class="text-end">
                                                                    <h5 id="dbt_total_inr">0.00</h5>
                                                                </td>
                                                                <td class="text-end">
                                                                    <h5 id="crd_total">0.00</h5>
                                                                </td>
                                                                <td hidden class="text-end">
                                                                    <h5 id="crd_total_inr">0.00</h5>
                                                                </td>
                                                                <td colspan="3" class="text-end">
                                                                    <a href="#"
                                                                        class="text-primary add-contactpeontxt mt-0 add-item-row"
                                                                        id="addnew"><i data-feather='plus'></i> Add New
                                                                        Item</a>
                                                                </td>

                                                            </tr>
                                                            <tr valign="top" class="voucher_details"
                                                                id="voucher-details-row">
                                                                <td colspan="9" rowspan="10">
                                                                    <table class="table border">
                                                                        <tr>
                                                                            <td class="p-0">
                                                                                <h6
                                                                                    class="text-dark mb-0 bg-light-primary py-1 px-50">
                                                                                    <strong>Voucher Details</strong>
                                                                                </h6>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="poprod-decpt"><span class="poitemtxt mw-100"><strong>Ledger Name:</strong><span id="ledger_name_details">-</span></span>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="poprod-decpt">
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Base
                                                                                        Currency:</strong> <span
                                                                                        id="base-currency"
                                                                                        class="text-uppercase">-</span></span>
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Debit
                                                                                        Amt:</strong> <span
                                                                                        id="base-debit">-</span></span>
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Credit
                                                                                        Amt:</strong> <span
                                                                                        id="base-credit">-</span></span>
                                                                            </td>
                                                                        </tr>

                                                                        <tr>
                                                                            <td class="poprod-decpt">
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Company
                                                                                        Currency:</strong> <span
                                                                                        id="company-currency"
                                                                                        class="text-uppercase">-</span></span>
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Debit
                                                                                        Amt:</strong> <span
                                                                                        id="company-debit">-</span></span>
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Credit
                                                                                        Amt:</strong> <span
                                                                                        id="company-credit">-</span></span>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="poprod-decpt">
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Group
                                                                                        Currency:</strong> <span
                                                                                        id="group-currency"
                                                                                        class="text-uppercase">-</span></span>
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Debit
                                                                                        Amt:</strong> <span
                                                                                        id="group-debit">-</span></span>
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-primary"><strong>Credit
                                                                                        Amt:</strong> <span
                                                                                        id="group-credit">-</span></span>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="poprod-decpt">
                                                                                <span
                                                                                    class="badge rounded-pill badge-light-secondary"><strong>Remarks:</strong>
                                                                                    <span id="remarks" style="font-size:12px">Description will
                                                                                        come here for items...</span></span>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>



                                                <div class="row mt-2">

                                                    <div class="col-md-4 mb-1">
                                                        <label class="form-label">Document</label>
                                                        <input type="file" multiple
                                                            onchange="checkFileTypeandSize(event)" class="form-control"
                                                            name="document[]" id="document" />

                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label"></label>
                                                        <div id="preview"></div>

                                                    </div>

                                                    <div class="col-md-12">
                                                        <div class="mb-1">
                                                            <label class="form-label">Final Remarks</label>
                                                            <textarea type="text" rows="4" class="form-control" placeholder="Enter Remarks here..." name="remarks"></textarea>

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

                </div>
            </form>

        </div>
    </div>
    <!-- END: Content-->
    <div class="modal fade" id="remarksModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-2 mx-50 pb-2">
                    <h1 class="text-center mb-1" id="shareProjectTitle">Add/Edit Remarks</h1>
                    <p class="text-center">Enter the details below.</p>
                    <div class="row mt-2">
                        <div class="col-md-12 mb-1">
                            <label class="form-label">Remarks <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="remarksInput" placeholder="Enter Remarks"></textarea>
                        </div>
                    </div>
                    <!-- Hidden field to store the current row ID -->
                    <input type="hidden" id="currentRowId">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="reset" class="btn btn-outline-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitRemarks">Submit</button>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
    <script src="<?php echo e(url('/app-assets/js/jquery-ui.js')); ?>"></script>
    <script>
        function getMonthName(ym) {
            // ym = '2024-07'
            const [year, month] = ym.split('-');
            const d = new Date(year, parseInt(month) - 1);
            return d.toLocaleString('default', { month: 'long', year: 'numeric' });
        }

        document.getElementById('date').addEventListener('input', function() {
             if (!isDateAuthorized(this.value)) {
                this.value = '';
                this.focus();
            }
        });

        window.addEventListener('DOMContentLoaded', function() {
            var dateInput = document.getElementById('date');
            if (dateInput && dateInput.value && !isDateAuthorized(dateInput.value)) {
                dateInput.value = '';
                dateInput.focus();
            }
        });
        // $('#voucherForm').on('submit', function () {
        //     $('.preloader').show();
        // });
        $('.voucher_details').hide();

        function getMonthName(ym) {
            const [y, m] = ym.split('-').map(Number);
            return new Intl.DateTimeFormat('en', {month:'long', year:'numeric'}).format(new Date(y, m-1, 1));
        }

        function isDateAuthorized(dateValue) {
            if (!dateValue) return true;
            var selectedMonth = dateValue.substring(0, 7);
            if (unauthorizedMonths.includes(selectedMonth)) {
                var monthLabel = getMonthName(selectedMonth);
                Swal.fire({
                    icon: 'error',
                    title: 'Not allowed',
                    text: `You are not authorized to select dates from ${monthLabel}. Please pick another month.`,
                    confirmButtonText: 'OK'
                    });
                return false;
            }
            else{
                console.log("authorized month name",selectedMonth);
                
            }
            return true;
        }
       
        const dateEl = document.getElementById('date');
        let lastValid = dateEl.value;  // keep previous value

        dateEl.addEventListener('change', function () {
            if (!isDateAuthorized(this.value)) {
                this.value = lastValid; // revert if unauthorized
            } else {
                lastValid = this.value; // update last valid
            }
        });
        
        var currencies = <?php echo json_encode($currencies); ?>;
        var orgCurrency = <?php echo e($orgCurrency); ?>;
        var orgCurrencyName = '';


        $(document).ready(function() {
            $('#book_type_id').trigger('change');
            $('#locations').trigger('change');
            // Trigger change event to update voucher details
            if (orgCurrency != "") {
                $.each(currencies, function(key, value) {
                    if (value['id'] == orgCurrency) {
                        orgCurrencyName = value['short_name'];
                    }
                });
                //$('#orgCurrencyName').text(orgCurrencyName);
            }
            getExchangeRate();



            // Unified event handler for row and input/select clicks
            $('#item-details-body').on('click', 'tr, input, select', function(event) {
                const row = $(this).closest('tr'); // Get the closest tr element from the clicked element
                const rowId = row.attr('id'); // Get the row ID
                $('#item-details-body tr').removeClass('trselected');
                row.addClass('trselected');
                handleRowClick(rowId);
            });


            $('.remark-btn').on('click', function() {
                const rowId = $(this).data('row-id'); // Get the row ID
                const currentRemarks = $(`#hiddenRemarks_${rowId}`)
                    .val(); // Fetch the current remarks from the hidden input

                // Populate the modal
                $('#currentRowId').val(rowId);
                $('#remarksInput').val(currentRemarks.trim());
            })
            // Handle modal submission
            $('#submitRemarks').on('click', function() {
                const rowId = $('#currentRowId').val();
                const newRemarks = $('#remarksInput').val();

                // Update the hidden input
                $(`#hiddenRemarks_${rowId}`).val(newRemarks);
                handleRowClick(rowId);


                $('#remarksModal').modal('hide'); // Close the modal

            });


        });
        /*
                $(document).on('change', '.ledgerselect', function() {
                    let ledgerId = $(this).val(); // The value of the selected ledger
                    let rowId = $(this).data('id'); // The unique ID for the row

                    console.log(`Selected Ledger ID: ${ledgerId}, Row ID: ${rowId}`);

                    // Use rowId to target the corresponding group dropdown
                    let groupDropdown = $(`#groupSelect${rowId}`);

                    if (ledgerId) {
                        $.ajax({
                            url: '<?php echo e(route('voucher.getLedgerGroups')); ?>',
                            method: 'POST',
                            data: {
                                ledger_id: ledgerId,
                                _token: $('meta[name="csrf-token"]').attr('content') // CSRF token
                            },
                            success: function(response) {
                                groupDropdown.empty(); // Clear previous options
                                groupDropdown.append('<option>Select</option>'); // Default option

                                response.forEach(item => {
                                    groupDropdown.append(
                                        `<option value="${item.id}">${item.name}</option>`);
                                });
                            },
                            error: function() {
                                showToast("error",'Error fetching group items.');
                            }
                        });
                    }
                });
        */

        function getExchangeRate() {
            $('#item-details-body tr').removeClass('trselected');
            $('.voucher_details').hide();
            $('.selectedCurrencyName').text('');

            if (orgCurrency != "") {
                let currency = parseFloat($('#currency_id').val()) || 0;
                if (currency != 0) {
                    console.log(currency);
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        url: '<?php echo e(route('getExchangeRate')); ?>',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            date: $('#date').val(),
                            '_token': '<?php echo csrf_token(); ?>',
                            currency: currency
                        },
                        success: function(response) {
                            if (response.status) {

                                $('#orgExchangeRate').val(response.data.org_currency_exg_rate)
                                    .trigger(
                                        'change');

                                $('#currency_code').val(response.data.party_currency_code);
                                $('#org_currency_id').val(response.data.org_currency_id);
                                $('#org_currency_code').val(response.data.org_currency_code);
                                $('#base_currency_code').val(response.data.org_currency_code);

                                $('.selectedCurrencyName').text("(" + $('#org_currency_code').val() + ")");

                                $('#org_currency_exg_rate').val(response.data
                                    .org_currency_exg_rate);

                                $('#comp_currency_id').val(response.data.comp_currency_id);
                                $('#comp_currency_code').val(response.data.comp_currency_code);
                                $('#comp_currency_exg_rate').val(response.data
                                    .comp_currency_exg_rate);

                                $('#company_currency_code').val(response.data.comp_currency_code);
                                $('#company_exchange_rate').val(response.data
                                    .comp_currency_exg_rate);

                                $('#group_currency_id').val(response.data.group_currency_id);
                                $('#group_currency_code').val(response.data.group_currency_code);
                                $('#group_currency_exg_rate').val(response.data
                                    .group_currency_exg_rate);
                                $('#grp_currency_code').val(response.data.group_currency_code);
                                $('#grp_exchange_rate').val(response.data
                                    .group_currency_exg_rate);

                                calculate_cr_dr();

                            } else {
                                resetCurrencies();
                                $('#orgExchangeRate').val('');
                                showToast("error", response.message);
                            }
                        }
                    });

                } else {
                    resetCurrencies();
                }
            } else {
                showToast("error", 'Organization currency is not set!!');
            }
        }

        function resetCurrencies() {
            $('#org_currency_id').val('');
            $('#org_currency_code').val('');
            $('#org_currency_exg_rate').val('');

            $('#comp_currency_id').val('');
            $('#comp_currency_code').val('');
            $('#comp_currency_exg_rate').val('');

            $('#group_currency_id').val('');
            $('#group_currency_code').val('');
            $('#group_currency_exg_rate').val('');

            $('#base_currency_code').val('');

            $('#company_currency_code').val('');
            $('#company_exchange_rate').val('');

            $('#grp_currency_code').val('');
            $('#grp_exchange_rate').val('');


            $('#orgExchangeRate').val('');
        }


        function submitForm(status) {
            var dateInput = document.getElementById('date');
            if (dateInput && !isDateAuthorized(dateInput.value)) {
                dateInput.value = '';
                dateInput.focus();
                return false; // Prevent form submission
            }
            $('#status').val(status);
            $('#submitButton').click();
        }


        var costcenters = <?php echo json_encode($cost_centers); ?>;
        var bookTypes = <?php echo json_encode($bookTypes); ?>;
        var lastVoucher = <?php echo json_encode($lastVoucher); ?>;

        $(function() {
            $(".ledgerselect").autocomplete({

                source: function(request, response) {


                    // get all pre selected ledgers
                    var preLedgers = [];
                    $('.ledgers').each(function() {
                        if ($(this).val() != "") {
                            preLedgers.push($(this).val());
                        }
                    });
                    if ($('#book_type_id').val() != null) {

                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                    'content')
                            },
                            url: '<?php echo e(route('ledgers.search')); ?>',
                            type: "POST",
                            dataType: "json",
                            data: {
                                keyword: request.term,
                                series: $('#book_type_id').val(),
                                ids: preLedgers,
                                '_token': '<?php echo csrf_token(); ?>'
                            },
                            success: function(data) {
                                response(
                                    data);
                                // Pass the data to the response callback
                            },
                            error: function() {
                                response(
                                    []);
                                // Respond with an empty array in case of error
                            }
                        });
                    }
                },
                minLength: 0,
                select: function(event, ui) {

                    $(this).val(ui.item.label);

                    // This function is called when an item is selected from the list
                    console.log("Selected: " + ui.item.label + " with ID: " + ui.item
                        .value);
                    let ledgerId = ui.item.value; // The value of the selected ledger
                    let rowId = $(this).data('id'); // The unique ID for the row

                    console.log(`Selected Ledger ID: ${ledgerId}, Row ID: ${rowId}`);

                    // Use rowId to target the corresponding group dropdown
                    let groupDropdown = $(`#groupSelect${rowId}`);
                    var preGroups = [];
                    $('.ledgerGroup').each(function(index) {
                        let ledgerGroup = $(this).val(); // Get the value of the select/input
                        let ledger_id = $(this).data(
                            'ledger'); // Get the ledger ID from data attribute

                        if (ledgerGroup !== "") {
                            preGroups.push({
                                ledger_id: ledger_id, // Ledger ID from data attribute
                                ledgerGroup: ledgerGroup // Selected value
                            });
                        }
                    });


                    if (ledgerId) {
                        $.ajax({
                            url: '<?php echo e(route('voucher.getLedgerGroups')); ?>',
                            method: 'GET',
                            data: {
                                ledger_id: ledgerId,
                                ids: preGroups,
                                _token: $('meta[name="csrf-token"]').attr(
                                    'content') // CSRF token
                            },
                            success: function(response) {
                                groupDropdown.empty(); // Clear previous options

                                response.forEach(item => {
                                    groupDropdown.append(
                                        `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
                                    );
                                });
                                groupDropdown.data('ledger', ledgerId);
                                handleRowClick(rowId);

                            },
                            error: function(xhr) {
                                let errorMessage =
                                    'Error fetching group items.'; // Default message

                                if (xhr.responseJSON && xhr.responseJSON.error) {
                                    errorMessage = xhr.responseJSON
                                        .error; // Use API error message if available
                                }
                                showToast("error", errorMessage);


                            }
                        });
                    }



                    // console.log(ui.item);

                    // You can also perform other actions here
                    const id = $(this).attr("data-id");
                    $('#ledger_id' + id).val(ui.item.value);
                    // if (ui.item.cost_center_id != "") {
                    //     console.log(ui.item.cost_center_id);
                    //     $.each(costcenters, function(ckey, cvalue) {
                    //         if (ui.item.cost_center_id == cvalue['value']) {
                    //             $("#cost_center_name" + id).val(cvalue['label']);
                    //             $("#cost_center_id" + id).val(cvalue['value']);
                    //         }
                    //     });
                    // }

                    return false;
                },
                change: function(event, ui) {
                    // If the selected item is invalid (i.e., user has not selected from the list)
                    if (!ui.item) {
                        // Clear the input field
                        $(this).val("");

                        // You can also perform other actions here
                        const id = $(this).attr("data-id");
                        $('#ledger_id' + id).val('');
                    }
                },
                focus: function(event, ui) {
                    // Prevent value from being inserted on focus
                    return false; // Prevents default behavior
                },
            }).focus(function() {
                if (this.value == "") {
                    $(this).autocomplete("search");
                }
                return false; // Prevents default behavior
            });

            // Monitor input field for empty state
            $(".ledgerselect").on('input', function() {
                const id = $(this).attr("data-id");
                let grp = $(`#groupSelect${id}`).empty();
                var inputValue = $(this).val();
                if (inputValue.trim() === '') {
                    const id = $(this).attr("data-id");
                    $('#ledger_id' + id).val('');
                    $(`#groupSelect${id}`).empty();

                }
            });

            //     $(".centerselecct").autocomplete({
            //         source: costcenters,
            //         minLength: 0,
            //         select: function(event, ui) {
            //             $(this).val(ui.item.label);

            //             // This function is called when an item is selected from the list
            //             console.log("Selected: " + ui.item.label + " with ID: " + ui.item
            //                 .value);
            //             console.log(ui.item);
            //             let ledgerId = ui.item.value;
            //             console.log(ledgerId);

            //             let groupDropdown = $(`#groupSelect${rowId}`);

            //             var preGroups = [];
            //             $('.ledgerGroup').each(function(index) {
            //                 let ledgerGroup = $(this).val(); // Get the value of the select/input
            //                 let ledger_id = $(this).data(
            //                     'ledger'); // Get the ledger ID from data attribute

            //                 if (ledgerGroup !== "") {
            //                     preGroups.push({
            //                         ledger_id: ledger_id, // Ledger ID from data attribute
            //                         ledgerGroup: ledgerGroup // Selected value
            //                     });
            //                 }
            //             });



            //             if (ledgerId) {
            //                 $.ajax({
            //                     url: '<?php echo e(route('voucher.getLedgerGroups')); ?>',
            //                     method: 'GET',
            //                     data: {
            //                         ledger_id: ledgerId,
            //                         ids: preGroups,
            //                         _token: $('meta[name="csrf-token"]').attr(
            //                             'content') // CSRF token
            //                     },
            //                     success: function(response) {
            //                         groupDropdown.empty(); // Clear previous options

            //                         response.forEach(item => {
            //                             groupDropdown.append(
            //                                 `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
            //                             );
            //                         });
            //                         groupDropdown.data('ledger', ledgerId);
            //                         handleRowClick(rowId);

            //                     },
            //                     error: function(xhr) {
            // let errorMessage = 'Error fetching group items.'; // Default message

            // if (xhr.responseJSON && xhr.responseJSON.error) {
            //     errorMessage = xhr.responseJSON.error; // Use API error message if available
            // }
            // showToast("error", errorMessage);
            // }
            //                 });
            //             }

            //             // You can also perform other actions here
            //             const id = $(this).attr("data-id");
            //             $('#cost_center_id' + id).val(ui.item.value);

            //             return false;
            //         },
            //         change: function(event, ui) {
            //             // If the selected item is invalid (i.e., user has not selected from the list)
            //             if (!ui.item) {
            //                 // Clear the input field
            //                 $(this).val("");

            //                 // You can also perform other actions here
            //                 const id = $(this).attr("data-id");
            //                 $('#cost_center_id' + id).val('');
            //             }
            //         }
            //     }).focus(function() {
            //         if (this.value == "") {
            //             $(this).autocomplete("search");
            //         }
            //     });
        });

        $(document).bind('ctrl+n', function() {
            document.getElementById('addnew').click();
        });

        function check_amount() {

            $('#draft').attr('disabled', true);
            $('#submitted').attr('disabled', true);
            $('.preloader').show();

            let seen = new Set(); // Create a Set to track unique combinations
            let duplicateFound = false; // Flag to track duplicates

            $('.ledgerGroup').each(function(index) {
                let ledgerGroup = $(this).val(); // Get the selected value
                let ledger_id = $(this).data('ledger'); // Get ledger ID from data attribute

                let key = ledger_id + '-' + ledgerGroup; // Create a unique key for comparison

                if (seen.has(key)) {
                    duplicateFound = true; // Set flag if duplicate found
                    return false; // Break out of .each loop early
                }

                seen.add(key); // Add key to Set
            });

            if (duplicateFound) {
                $('.preloader').hide();
                showToast("error", "Duplicate ledger groups found. Please correct and try again.");
                return false;
            }
            let stop = false;


            let rowCount = document.querySelectorAll('#item-details-body tr').length;
            $('#item-details-body tr').each(function() {
                let debAmount = parseFloat(removeCommas($(this).find('.dbt_amt').val())) || 0;
                let crdAmount = parseFloat(removeCommas($(this).find('.crd_amt').val())) || 0;

                // Check if both the credit and debit amounts are 0
                if (debAmount == 0 && crdAmount == 0) {
                    $('.preloader').hide();
                    $('#draft').attr('disabled', false);
                    $('#submitted').attr('disabled', false);
                    showToast('error', 'Can not save ledgers with Credit and Debit amount both being 0');


                    stop = true;
                    return false; // Stop the loop and return false
                }
            });
            if (stop)
                return false;


            if (parseFloat(removeCommas($('#crd_total').text())) == 0 || parseFloat(removeCommas($('#dbt_total').text())) ==
                0) {
                    $('.preloader').hide();
                $('#draft').attr('disabled', false);
                $('#submitted').attr('disabled', false);
                showToast("error", 'Debit and credit amount should be greater than 0');

                return false;
            }
            if (parseFloat(removeCommas($('#crd_total').text())) == parseFloat(removeCommas($('#dbt_total').text()))) {
                return true;
            } else {
                $('.preloader').hide();
                $('#draft').attr('disabled', false);
                $('#submitted').attr('disabled', false);
                showToast("error", 'Debit and credit amount total should be same!!');

                return false;
            }
        }

        $(document).on('input', '.dbt_amt, .crd_amt, .dbt_amt_inr, .crd_amt_inr,.remarks_', function() {
            const inVal = parseFloat(removeCommas($(this).val())) || 0;
            const rowId = $(this).closest('tr').attr('id'); // Get the row ID
            const $row = $(this).closest('tr'); // Find the row of the current input

            if ($(this).hasClass('dbt_amt')) {
                $row.find('.crd_amt').val(0);
            } else if ($(this).hasClass('crd_amt')) {
                $row.find('.dbt_amt').val(0);
            }

            handleRowClick(rowId);
            calculate_cr_dr();
        });

        // Moving between input fields on pressing ENTER
        $(document).on('keydown', function(event) {
            if (event.keyCode === 13) {
                var activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA') {
                    // Check if the input is not hidden
                    if (activeElement.type !== 'hidden') {
                        event.preventDefault(); // Prevent default enter key behavior

                        // Get the next sibling in the current row
                        var nextField = activeElement.nextElementSibling;
                        while (nextField && nextField.type === 'hidden') {
                            nextField = nextField.nextElementSibling;
                        }

                        // If there's a next field in the row, focus on it
                        if (nextField) {
                            nextField.focus();
                            return; // Stop further navigation within the row
                        }

                        // Otherwise, find the first input in the next column
                        var nextColumn = activeElement.closest('td').nextElementSibling;
                        if (nextColumn) {
                            nextField = nextColumn.querySelector('input, textarea');
                            if (nextField) {
                                nextField.focus();
                                return; // Stop further navigation within the row
                            }
                        }

                        // Otherwise, find the first input in the next row
                        var nextRow = activeElement.closest('tr').nextElementSibling;
                        if (nextRow) {
                            nextField = nextRow.querySelector('input, textarea');
                            if (nextField) {
                                nextField.focus();
                            }
                        }
                    }
                }
            }
        });

        // Remove item row
        $(document).on('click', '.remove-item', function() {
            $(this).closest('tr').remove(); // Remove the entire row
            updateRowNumbers();
            calculate_cr_dr(); // Call your custom function
        });

        function rate_change() {
            $('.voucher_details').hide();

        }

        function populateCostCenterDropdowns() {
            let selectedLocationIds = $('#locations').val();

            const costCenterSet = locationCostCentersMap.filter(center => {
                if (!center.location) return false;
                const locationArray = Array.isArray(center.location) ?
                    center.location.flatMap(loc => loc.split(',')) :
                    [];
                return locationArray.includes(String(selectedLocationIds));
            });

            // Update all .costCenter selects
            $('.costCenter').each(function() {
                let $dropdown = $(this);
                $dropdown.empty();
                costCenterSet.forEach((center) => {
                    $dropdown.append(`<option value="${center.id}">${center.name}</option>`);
                });
            });
        }

        function populateSingleCostCenterDropdown($dropdown,val) {
            let selectedLocationIds = $('#locations').val();

            const costCenterSet = locationCostCentersMap.filter(center => {
                if (!center.location) return false;
                const locationArray = Array.isArray(center.location)
                    ? center.location.flatMap(loc => loc.split(','))
                    : [];
                return locationArray.includes(String(selectedLocationIds));
            });

            $dropdown.empty();

            costCenterSet.forEach((center) => {
                const isSelected = String(center.id) === String(val) ? 'selected' : '';
                $dropdown.append(`<option value="${center.id}" ${isSelected}>${center.name}</option>`);
            });
            console.log(`Cost center dropdown populated with value: ${val}`);
        }

        function calculate_cr_dr() {
            $('#org_currency_exg_rate').val($('#orgExchangeRate').val());
            const exchangeRate = parseFloat($('#orgExchangeRate').val()) ||
                1; // Assume an input for exchange rate with id 'exchange_rate'

            const exchangeRateComp = parseFloat($('#comp_currency_exg_rate').val()) ||
                1; // Assume an input for exchange rate with id 'exchange_rate'

            const exchangeRateGroup = parseFloat($('#group_currency_exg_rate').val()) ||
                1; // Assume an input for exchange rate with id 'exchange_rate'

            $('#item-details-body tr').each(function() {
                const rowId = $(this).attr('id'); // Get the row ID

                // Get the debit and credit values for the current row
                const debitAmt = parseFloat(removeCommas($(`#dept_${rowId}`).val())) || 0;
                const creditAmt = parseFloat(removeCommas($(`#crd_${rowId}`).val())) || 0;

                // Organization Rate
                $(`#dept_inr_${rowId}`).val((debitAmt * exchangeRateComp).toFixed(2));
                $(`#crd_inr_${rowId}`).val((creditAmt * exchangeRateComp).toFixed(2));

                //Company Rate
                $(`#comp_debit_amt_${rowId}`).val((debitAmt * exchangeRateComp).toFixed(2));
                $(`#comp_credit_amt_${rowId}`).val((creditAmt * exchangeRateComp).toFixed(2));


                //Group Rate
                $(`#group_debit_amt_${rowId}`).val((debitAmt * exchangeRateGroup).toFixed(2));
                $(`#group_credit_amt_${rowId}`).val((creditAmt * exchangeRateGroup).toFixed(2));
            });

            let cr_sum = 0;
            let cr_sum_inr = 0;
            let dr_sum = 0;
            let dr_sum_inr = 0;
            $('.crd_amt').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                cr_sum += value;
            });

            // Iterate over credit INR amount fields
            $('.crd_amt_inr').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                cr_sum_inr += value;
            });

            // Iterate over debit amount fields
            $('.dbt_amt').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                dr_sum += value;
            });

            // Iterate over debit INR amount fields
            $('.dbt_amt_inr').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                dr_sum_inr += value;
            });
            $('#crd_total_inr').text(formatIndianNumber(cr_sum_inr.toFixed(2)));
            $('#crd_total').text(formatIndianNumber(cr_sum.toFixed(2)));
            $('#dbt_total').text(formatIndianNumber(dr_sum.toFixed(2)));
            $('#dbt_total_inr').text(formatIndianNumber(dr_sum_inr.toFixed(2)));

            $('#amount').val(dr_sum);

        }

        var books = [];
        document.addEventListener('DOMContentLoaded', function() {
            // Add new item row
            document.querySelector('.add-item-row').addEventListener('click', function(e) {
                e.preventDefault();

                var cr_amount = 0;
                var dr_amount = 0;

                $('.dbt_amt').each(function() {
                    const value = parseFloat(removeCommas($(this).val())) || 0;
                    $(this).val(value.toFixed(2));

                });
                $('.crd_amt').each(function() {
                    const value = parseFloat(removeCommas($(this).val())) || 0;
                    $(this).val(value.toFixed(2));

                });
                if (parseFloat(removeCommas($('#crd_total').text())) == parseFloat(removeCommas($(
                            '#dbt_total')
                        .text()))) {} else if (
                    parseFloat(removeCommas($('#crd_total').text())) > parseFloat(removeCommas($(
                        '#dbt_total').text()))) {
                    dr_amount = parseFloat(removeCommas($('#crd_total').text())) - parseFloat(removeCommas(
                        $('#dbt_total')
                        .text()));
                } else {
                    cr_amount = parseFloat(removeCommas($('#dbt_total').text())) - parseFloat(removeCommas(
                        $('#crd_total')
                        .text()));
                }


                let rowCount = document.querySelectorAll('#item-details-body tr').length;
                rowCount = Number($('#item-details-body tr:last').attr('id')) ||
                    0; // Fallback if no rows exist
                let totalDebit = parseFloat(removeCommas($('#dbt_total').text()));
                let totalCredit = parseFloat(removeCommas($('#crd_total').text()));
                let balanceDebit = totalDebit - totalCredit; // Calculate the balance for debit
                let balanceCredit = totalCredit - totalDebit; // Calculate the balance for credit
                balanceDebit = balanceDebit.toFixed(2);
                balanceCredit = balanceCredit.toFixed(2);

                let newRow = `
                <tr id="${rowCount + 1}">
                    <td class="number">${rowCount + 1}</td>
                    <td class="poprod-decpt">
                        <input type="text"
                            class="form-control mw-100 ledgerselect"
                            placeholder="Select Ledger" name="ledger_name${rowCount + 1}"
                            required id="ledger_name${rowCount + 1}"
                            data-id="${rowCount + 1}" />
                        <input type="hidden" name="ledger_id[]" type="hidden" id="ledger_id${rowCount + 1}" class="ledgers" />
                    </td>
                    <td>
                        <select required id="groupSelect${rowCount + 1}" name="parent_ledger_id[]" class="ledgerGroup form-select mw-100">
                        </select>
                    </td>
                    <input type="hidden" name="group_debit_amt[]" id="group_debit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" name="comp_debit_amt[]" id="comp_debit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" name="group_credit_amt[]" id="group_credit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" name="comp_credit_amt[]" id="comp_credit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" class="dbt_amt_inr debt_inr_${rowCount + 1}" name="org_debit_amt[]" id="dept_inr_${rowCount + 1}" />
                    <input type="hidden" class="crd_amt_inr crd_inr_${rowCount + 1}" name="org_credit_amt[]" id="crd_inr_${rowCount + 1}" />

                    <td>
                        <input type="number" class="form-control mw-100 dbt_amt debt_${rowCount + 1} text-end" onfocus="focusInput(this)"
                            name="debit_amt[]" id="dept_${rowCount + 1}" min="0" step="0.01"
                            value="${balanceCredit > 0 ? balanceCredit : 0}"/>
                    </td>
                    <td>
                        <input type="number" class="form-control mw-100 crd_amt crd_${rowCount + 1} text-end" onfocus="focusInput(this)"
                            name="credit_amt[]" id="crd_${rowCount + 1}" min="0" step="0.01"
                            value="${balanceDebit > 0 ? balanceDebit : 0}"/>
                    </td>
                    <td>
                        <select class="costCenter form-select mw-100" name="cost_center_id[]" id="cost_center_id${rowCount + 1}">
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control mw-100 remarks_" placeholder="Enter Remarks"
                            id="hiddenRemarks_${rowCount + 1}" name="item_remarks[]" value="">
                    </td>
                    <td>
                        <div class="d-flex">
                            <div hidden class="me-50 cursor-pointer remark-btn" data-row-id="${rowCount + 1}" data-bs-toggle="modal"
                                data-bs-target="#remarksModal"><span data-bs-toggle="tooltip" data-bs-placement="top" title="Remarks" class="text-primary"><i data-feather="file-text"></i></span></div>
                            <div class="me-50 cursor-pointer"><span data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" class="text-danger remove-item"><i data-feather="trash-2"></i></span></div>
                        </div>
                    </td>
                </tr>
                `;

                updateRowNumbers();
                document.querySelector('#item-details-body').insertAdjacentHTML('beforeend', newRow);
                // Populate cost centers for the new row's dropdown
                let selected = $(`#cost_center_id${rowCount}`).val();
                populateSingleCostCenterDropdown($(`#cost_center_id${rowCount + 1}`),selected);
                console.log(`Cost center for row ${rowCount + 1} populated with value: ${$(`#cost_center_id${rowCount}`).val()}`);
                calculate_cr_dr();


                feather.replace({
                    width: 14,
                    height: 14
                });
                $(".ledgerselect").autocomplete({

                    source: function(request, response) {



                        // get all pre selected ledgers
                        var preLedgers = [];
                        $('.ledgers').each(function() {
                            if ($(this).val() != "") {
                                preLedgers.push($(this).val());
                            }
                        });

                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                    'content')
                            },
                            url: '<?php echo e(route('ledgers.search')); ?>',
                            type: "POST",
                            dataType: "json",
                            data: {
                                keyword: request.term,
                                series: $('#book_type_id').val(),
                                ids: preLedgers,
                                '_token': '<?php echo csrf_token(); ?>'
                            },
                            success: function(data) {
                                response(
                                    data); // Pass the data to the response callback
                            },
                            error: function() {
                                response(
                                    []
                                ); // Respond with an empty array in case of error
                            }
                        });
                    },
                    minLength: 0,
                    select: function(event, ui) {
                        $(this).val(ui.item.label);

                        // This function is called when an item is selected from the list
                        console.log("Selected: " + ui.item.label + " with ID: " + ui.item
                            .value);
                        let ledgerId = ui.item.value; // The value of the selected ledger
                        let rowId = $(this).data('id'); // The unique ID for the row

                        console.log(`Selected Ledger ID: ${ledgerId}, Row ID: ${rowId}`);

                        // Use rowId to target the corresponding group dropdown
                        let groupDropdown = $(`#groupSelect${rowId}`);
                        var preGroups = [];
                        $('.ledgerGroup').each(function(index) {
                            let ledgerGroup = $(this)
                                .val(); // Get the value of the select/input
                            let ledger_id = $(this).data('ledger') ||
                                0; // Get the ledger ID from data attribute

                            if (ledgerGroup !== "") {
                                preGroups.push({
                                    ledger_id: ledger_id, // Ledger ID from data attribute
                                    ledgerGroup: ledgerGroup // Selected value
                                });
                            }
                        });




                        if (ledgerId) {
                            $.ajax({
                                url: '<?php echo e(route('voucher.getLedgerGroups')); ?>',
                                method: 'GET',
                                data: {
                                    ledger_id: ledgerId,
                                    ids: preGroups,
                                    _token: $('meta[name="csrf-token"]').attr(
                                        'content') // CSRF token
                                },
                                success: function(response) {
                                    groupDropdown.empty(); // Clear previous options

                                    response.forEach(item => {
                                        groupDropdown.append(
                                            `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
                                        );
                                    });
                                    groupDropdown.data('ledger', ledgerId);
                                    handleRowClick(rowId);


                                },
                                error: function(xhr) {
                                    let errorMessage =
                                        'Error fetching group items.'; // Default message

                                    if (xhr.responseJSON && xhr.responseJSON
                                        .error) {
                                        errorMessage = xhr.responseJSON
                                            .error; // Use API error message if available
                                    }
                                    showToast("error", errorMessage);
                                }
                            });
                        }



                        // console.log(ui.item);

                        // You can also perform other actions here
                        const id = $(this).attr("data-id");
                        $('#ledger_id' + id).val(ui.item.value);
                        // if (ui.item.cost_center_id != "") {
                        //     console.log(ui.item.cost_center_id);
                        //     $.each(costcenters, function(ckey, cvalue) {
                        //         if (ui.item.cost_center_id == cvalue['value']) {
                        //             $("#cost_center_name" + id).val(cvalue['label']);
                        //             $("#cost_center_id" + id).val(cvalue['value']);
                        //         }
                        //     });
                        // }

                        return false;
                    },
                    change: function(event, ui) {
                        // If the selected item is invalid (i.e., user has not selected from the list)
                        if (!ui.item) {
                            // Clear the input field
                            $(this).val("");

                            // You can also perform other actions here
                            const id = $(this).attr("data-id");
                            $('#ledger_id' + id).val('');
                        }
                    },
                    focus: function(event, ui) {
                        // Prevent value from being inserted on focus
                        return false; // Prevents default behavior
                    },
                }).focus(function() {
                    if (this.value == "") {
                        $(this).autocomplete("search");
                    }
                    return false; // Prevents default behavior
                });

                // Monitor input field for empty state
                $(".ledgerselect").on('input', function() {
                    const id = $(this).attr("data-id");
                    let grp = $(`#groupSelect${id}`).empty();

                    var inputValue = $(this).val();
                    if (inputValue.trim() === '') {
                        $('#ledger_id' + id).val('');
                    }
                });

                //         $(".centerselecct").autocomplete({
                //             source: costcenters,
                //             minLength: 0,
                //             select: function(event, ui) {
                //                 $(this).val(ui.item.label);

                //                 // This function is called when an item is selected from the list
                //                 console.log("Selected: " + ui.item.label + " with ID: " + ui.item
                //                     .value);
                //                 console.log(ui.item);
                //                 let ledgerId = ui.item.value;
                //                 console.log(ledgerId);

                //                 let groupDropdown = $(`#groupSelect${rowId}`);
                //                 var preGroups = [];
                //                 $('.ledgerGroup').each(function(index) {
                //                     let ledgerGroup = $(this)
                //                         .val(); // Get the value of the select/input
                //                     let ledger_id = $(this).data(
                //                         'ledger'); // Get the ledger ID from data attribute

                //                     if (ledgerGroup !== "") {
                //                         preGroups.push({
                //                             ledger_id: ledger_id, // Ledger ID from data attribute
                //                             ledgerGroup: ledgerGroup // Selected value
                //                         });
                //                     }
                //                 });


                //                 if (ledgerId) {
                //                     $.ajax({
                //                         url: '<?php echo e(route('voucher.getLedgerGroups')); ?>',
                //                         method: 'GET',
                //                         data: {
                //                             ids: preGroups,
                //                             ledger_id: ledgerId,
                //                             _token: $('meta[name="csrf-token"]').attr(
                //                                 'content') // CSRF token
                //                         },
                //                         success: function(response) {
                //                             groupDropdown.empty(); // Clear previous options

                //                             response.forEach(item => {
                //                                 groupDropdown.append(
                //                                     `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
                //                                 );
                //                             });
                //                             groupDropdown.data('ledger', ledgerId);
                //                             handleRowClick(rowId);

                //                         },
                //                         error: function(xhr) {
                // let errorMessage = 'Error fetching group items.'; // Default message

                // if (xhr.responseJSON && xhr.responseJSON.error) {
                //     errorMessage = xhr.responseJSON.error; // Use API error message if available
                // }

                // showToast("error", errorMessage); }
                //                     });
                //                 }

                //                 // You can also perform other actions here
                //                 const id = $(this).attr("data-id");
                //                 $('#cost_center_id' + id).val(ui.item.value);

                //                 return false;
                //             },
                //             change: function(event, ui) {
                //                 // If the selected item is invalid (i.e., user has not selected from the list)
                //                 if (!ui.item) {
                //                     // Clear the input field
                //                     $(this).val("");

                //                     // You can also perform other actions here
                //                     const id = $(this).attr("data-id");
                //                     $('#cost_center_id' + id).val('');
                //                 }
                //             }
                //         }).focus(function() {
                //             if (this.value == "") {
                //                 $(this).autocomplete("search");
                //             }
                //         });

            });
        });

        function getBooks() {
            $('#book_id').empty();
            $('#voucher_name').val('');
            $('#voucher_no').val('');
            //$('#book_id').prepend('<option disabled selected value="">Select Series</option>');
            $.ajax({
                url: '<?php echo e(route('get_voucher_series', ['placeholder'])); ?>'.replace('placeholder', $('#book_type_id')
                    .val()),
                type: 'GET',
                success: function(books) {
                    $.each(books, function(key, value) {
                        $("#book_id").append("<option value ='" + value['id'] + "'>" +
                            value['book_code'] + " </option>");
                    });
                     $('#book_id').trigger('change'); 

                }
            });
            let selectedOption = $('#book_type_id').find('option:selected');
            let cv = <?php echo json_encode(ConstantHelper::CONTRA_VOUCHER, 15, 512) ?>;
            let allowedNames = <?php echo json_encode($allowedCVGroups, 15, 512) ?>;
            let jv = <?php echo json_encode(ConstantHelper::JOURNAL_VOUCHER, 15, 512) ?>;
            let excludeNames = <?php echo json_encode($exlucdeJVGroups, 15, 512) ?>;

            // Check if selected option's data-alias is equal to contra_alias (e.g., 'cv')
            if (selectedOption.data('alias') === cv) {
                $('.ledgerGroup').each(function() {
                    let text = $(this).text().trim();
                    console.log("allowed " + allowedNames, text);
                    // get the visible text of each ledger group
                    if (!allowedNames.includes(text) && (text != "")) {
                        let id = $(this).closest('tr').attr('id');
                        $('#ledger_name' + id).val('');
                        $('#ledger_id' + id).val('');
                        $('#groupSelect' + id).val('');
                    }
                });
            } else if (selectedOption.data('alias') === jv) {
                $('.ledgerGroup').each(function() {
                    let text = $(this).text().trim();
                    console.log("exclude " + excludeNames, text);
                    if (excludeNames.includes(text) && (text != "")) {
                        let id = $(this).closest('tr').attr('id');
                        console.log(excludeNames, text, id);

                        $('#ledger_name' + id).val('');
                        $('#ledger_id' + id).val('');
                        $('#groupSelect' + id).val('');

                    }
                });
            }

           

        }



        function get_voucher_details() {
            $.each(books, function(key, value) {
                if (value['id'] == $('#book_id').val()) {
                    $('#voucher_name').val(value['book_name']);
                }
            });

            $.ajax({
                url: '<?php echo e(url('get_voucher_no')); ?>/' + $('#book_id').val(),
                type: 'GET',
                success: function(data) {
                    if (data.type == "Auto") {
                        $("#voucher_no").attr("readonly", true);
                        $('#voucher_no').val(data.voucher_no);
                    } else {
                        $("#voucher_no").attr("readonly", false);
                    }
                }
            });
        }

        function resetParametersDependentElements(data) {
            let backDateAllowed = false;
            let futureDateAllowed = false;

            if (data != null) {
                if (Array.isArray(data?.parameters?.back_date_allowed)) {
                    for (let i = 0; i < data.parameters.back_date_allowed.length; i++) {
                        if (data.parameters.back_date_allowed[i].trim().toLowerCase() === "yes") {
                            backDateAllowed = true;
                            break; // Exit the loop once we find "yes"
                        }
                    }
                }
                if (Array.isArray(data?.parameters?.future_date_allowed)) {
                    for (let i = 0; i < data.parameters.future_date_allowed.length; i++) {
                        if (data.parameters.future_date_allowed[i].trim().toLowerCase() === "yes") {
                            futureDateAllowed = true;
                            break; // Exit the loop once we find "yes"
                        }
                    }
                }
                //console.log(backDateAllowed, futureDateAllowed);

            }

            const dateInput = document.getElementById("date");

            // Determine the max and min values for the date input
            const today = moment().format("YYYY-MM-DD");
            const fyearStartDate = "<?php echo e($fyear['start_date']); ?>";
            const fyearEndDate = "<?php echo e($fyear['end_date']); ?>";
            // console.log('here',1,fyearStartDate, fyearEndDate);

            if (backDateAllowed && futureDateAllowed) {
                // dateInput.removeAttribute("min");
                // dateInput.removeAttribute("max");
                 console.log('here',1,fyearStartDate, fyearEndDate);
                dateInput.setAttribute("min", fyearStartDate);
                dateInput.setAttribute("max", fyearEndDate);
            } else if (backDateAllowed) {
                dateInput.setAttribute("max", today);
                dateInput.setAttribute("min", fyearStartDate);
                console.log('here',2);
            } else if (futureDateAllowed) {
                dateInput.setAttribute("min", today);
                dateInput.setAttribute("max", fyearEndDate);
                console.log('here',3);
            } else {
                dateInput.setAttribute("min", today);
                dateInput.setAttribute("max", today);
                // console.log('here',4);
            }
        }

        function getDocNumberByBookId() {
            resetParametersDependentElements(null);
            let currentDate = new Date().toISOString().split('T')[0];
            let bookId = $('#book_id').val();
            let document_date = $('#date').val();
            let actionUrl = '<?php echo e(route('book.get.doc_no_and_parameters')); ?>' + '?book_id=' + bookId +
                "&document_date=" +
                document_date;
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        resetParametersDependentElements(data.data);
                        $("#book_code_input").val(data.data.book_code);
                        $("#voucher_name").val($("#book_id option:selected").text());
                        if (!data.data.doc.document_number) {
                            $("#voucher_no").val('');
                            $('#doc_number_type').val('');
                            $('#doc_reset_pattern').val('');
                            $('#doc_prefix').val('');
                            $('#doc_suffix').val('');
                            $('#doc_no').val('');
                        } else {
                            $("#voucher_no").val(data.data.doc.document_number);
                            $('#doc_number_type').val(data.data.doc.type);
                            $('#doc_reset_pattern').val(data.data.doc.reset_pattern);
                            $('#doc_prefix').val(data.data.doc.prefix);
                            $('#doc_suffix').val(data.data.doc.suffix);
                            $('#doc_no').val(data.data.doc.doc_no);
                        }
                        if (data.data.doc.type == 'Manually') {
                            $("#voucher_no").attr('readonly', false);
                        } else {
                            $("#voucher_no").attr('readonly', true);
                        }

                    }
                    if (data.status == 404) {
                        $("#voucher_no").val('');
                        $('#doc_number_type').val('');
                        $('#doc_reset_pattern').val('');
                        $('#doc_prefix').val('');
                        $('#doc_suffix').val('');
                        $('#doc_no').val('');
                        showToast("error", data.message);
                    }
                });
            });
        }

        function handleRowClick(rowId) {
            $('.voucher_details').show();

            const row = $(`#item-details-body tr#${rowId}`);
            const ledgerName = row.find('td').eq(1).find('input[name^="ledger_name"]').val();
            const debitAmount = row.find('td').eq(3).find('input').val();
            // const debitAmountINR = row.find('td').eq(4).find('input').val();
            const creditAmount = row.find('td').eq(4).find('input').val();
            //const creditAmountINR = row.find('td').eq(6).find('input').val();
            const compCurrency = $('#comp_currency_code').val() || ''; // If #curre is a <select> dropdown
            const groupCurrency = $('#group_currency_code').val() || ''; // If #curre is a <select> dropdown
            const baseCurrency = $('#org_currency_code').val() || ''; // If #curre is a <select> dropdown
            const companyDebit = (debitAmount) * (parseFloat($('#comp_currency_exg_rate').val() || 1));
            const companyCredit = (creditAmount) * (parseFloat($('#comp_currency_exg_rate').val() || 1));
            const groupCredit = (creditAmount) * (parseFloat($('#group_currency_exg_rate').val() || 1));
            const groupDebit = (debitAmount) * (parseFloat($('#group_currency_exg_rate').val() || 1));
            const baseCredit = (creditAmount) * (parseFloat($('#org_currency_exg_rate').val() || 1));
            const baseDebit = (debitAmount) * (parseFloat($('#org_currency_exg_rate').val() || 1));



            const remark = $(`#hiddenRemarks_${rowId}`).val() ||
                'No remarks available'; // Fetch the remark, default to 'No remarks available'

            $('#ledger_name_details').text(ledgerName || '-'); // Update ledger name
            $('#company-currency').text(compCurrency); // Set company currency
            $('#company-debit').text(formatIndianNumber(companyDebit.toFixed(2))); // Set company debit amount
            $('#company-credit').text(formatIndianNumber(companyCredit.toFixed(2))); // Set company credit amount
            $('#group-currency').text(groupCurrency); // Set group currency
            $('#base-currency').text(baseCurrency); // Set group currency
            $('#group-debit').text(formatIndianNumber(groupDebit.toFixed(2))); // Set group debit amount
            $('#group-credit').text(formatIndianNumber(groupCredit.toFixed(2))); // Set group credit amount
            $('#base-debit').text(formatIndianNumber(baseDebit.toFixed(2))); // Set group debit amount
            $('#base-credit').text(formatIndianNumber(baseCredit.toFixed(2))); // Set group credit amount
            $('#remarks').text(remark); // Set remarks in the voucher details section
            $('#voucher-details-row').data('row-id', rowId); // Set row ID for the voucher details

        }

        function focusInput(inputElement) {
            // Check if the input value is "0"
            if (inputElement.value === "0" || inputElement.value === "0.00") {
                // Clear the input field
                inputElement.value = "";
            }
        }

        function checkFileTypeandSize(event) {
            $('#preview').empty();
            const file = event.target.files[0];

            if (file) {
                const maxSizeMB = 5;
                const fileSizeMB = file.size / (1024 * 1024);

                const videoExtensions = /(\.mp4|\.avi|\.mov|\.wmv|\.mkv)$/i;
                if (videoExtensions.exec(file.name)) {
                    showToast("error", "Video files are not allowed.");
                    event.target.value = "";
                    return;
                }

                if (fileSizeMB > maxSizeMB) {
                    showToast("error", "File size should not exceed 5MB.");
                    event.target.value = "";
                    return;
                }

                handleFileUpload(event, `#preview`);

            }
        }

        function handleFileUpload(event, previewElement) {
            var files = event.target.files;
            var previewContainer = $(previewElement); // The container where previews will appear
            previewContainer.empty(); // Clear previous previews

            if (files.length > 0) {
                // Loop through each selected file
                for (var i = 0; i < files.length; i++) {
                    // Get the file extension
                    var fileName = files[i].name;
                    var fileExtension = fileName.split('.').pop().toLowerCase(); // Get file extension

                    // Set default icon
                    var fileIconType = 'file-text'; // Default icon for unknown types

                    // Map file extension to specific Feather icons
                    switch (fileExtension) {
                        case 'pdf':
                            fileIconType = 'file'; // Icon for PDF files
                            break;
                        case 'doc':
                        case 'docx':
                            fileIconType = 'file'; // Icon for Word documents
                            break;
                        case 'xls':
                        case 'xlsx':
                            fileIconType = 'file'; // Icon for Excel files
                            break;
                        case 'png':
                        case 'jpg':
                        case 'jpeg':
                        case 'gif':
                            fileIconType = 'image'; // Icon for image files
                            break;
                        case 'zip':
                        case 'rar':
                            fileIconType = 'archive'; // Icon for compressed files
                            break;
                        default:
                            fileIconType = 'file'; // Default icon
                            break;
                    }

                    // Generate the file preview div dynamically
                    var fileIcon = `
                        <div class="image-uplodasection expenseadd-sign" data-file-index="${i}">
                            <i data-feather="${fileIconType}" class="fileuploadicon"></i>
                            <div class="delete-img text-danger" data-file-index="${i}">
                                <i data-feather="x"></i>
                            </div>
                        </div>
                    `;

                    // Append the generated fileIcon div to the preview container
                    previewContainer.append(fileIcon);
                }
                // Replace icons with Feather icons after appending the new elements
                feather.replace();
            }


            // Add event listener to delete the file preview when clicked
            previewContainer.find('.delete-img').click(function() {
                var fileIndex = $(this).parent().data('file-index'); // Get the correct index from parent
                removeFilePreview(fileIndex, previewContainer, event.target);
            });
        }

        // Function to remove a single file from the FileList
        function removeFilePreview(fileIndex, previewContainer, inputElement) {
            var dt = new DataTransfer(); // Create a new DataTransfer object to hold the remaining files
            var files = inputElement.files;

            // Loop through the files and add them to the DataTransfer object, except the one to delete
            for (var i = 0; i < files.length; i++) {
                if (i !== fileIndex) {
                    dt.items.add(files[i]); // Add file to DataTransfer if it's not the one being deleted
                }
            }

            // Update the input element with the new file list
            inputElement.files = dt.files;

            // Remove the preview of the deleted file
            previewContainer.children(`[data-file-index="${fileIndex}"]`).remove();

            // Now re-index the remaining file previews
            var remainingPreviews = previewContainer.children();
            remainingPreviews.each(function(index) {
                $(this).attr('data-file-index', index); // Update data-file-index correctly
                $(this).find('.delete-img').attr('data-file-index', index); // Also update delete button index
            });

            // Debugging logs
            console.log(`Remaining files after deletion: ${dt.files.length}`);
            console.log(`Remaining preview elements: ${remainingPreviews.length}`);

            // If no files are left after deleting, reset the file input
            if (dt.files.length === 0) { // Check the updated DataTransfer's files length
                inputElement.value = ""; // Clear the input value to reset it
            }
        }

        function updateRowNumbers() {
            $('#item-details-body tr').each(function(index) {
                // Update the number column (index starts at 0, so add 1)
                $(this).find('.number').text(index + 1);
            });
        }

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
        $(document).on('change', '.costCenter', function() {
            var selectedValue = $(this).val(); // Get the selected cost center value
            $('.costCenter').val(selectedValue); // Set the same value for all dropdowns


        });
        // $(document).on('change', '.costCenter', function() {
        //     var selectedValue = $(this).val(); // Get the selected cost center value
        //     $('.costCenter').val(selectedValue); // Set the same value for all dropdowns
        // });

        $('#locations').on('change', function() {
            populateCostCenterDropdowns();
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/erp_presence360/resources/views/voucher/create_voucher.blade.php ENDPATH**/ ?>