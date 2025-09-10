<!-- BEGIN: Header-->
<nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav d-block container-xxl erpnewheader">
    <div class="d-flex justify-content-between align-items-center">
        <div class="w-100">
            <div class="header-navbar navbar-light navbar-shadow new-navbarfloating">
                <div class="navbar-container d-flex content">
                    <div class="bookmark-wrapper d-flex align-items-center">
                        <ul class="nav navbar-nav headerlogo">
                            <?php if(isset($orgLogo)): ?>
                                <li>
                                    <img src="<?php echo e($orgLogo ? $orgLogo : url('/img/thepresence360_logo.svg')); ?>" alt="Logo"/>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <ul class="nav navbar-nav left-baricontop">
                            <li class="nav-item">
                                <a class="nav-link menu-toggle" href="#">
                                    <i></i>
                                </a>
                            </li>
                        </ul>
                        
                    </div>

                    <ul class="nav navbar-nav align-items-center ms-auto">

                        <?php if(isset($iamOrganizations) && count($iamOrganizations)): ?>                        <li class="nav-item d-none d-lg-block select-organization-menu">
                            <form action="<?php echo e(route('update-organization')); ?>" method="POST">
                                <?php echo csrf_field(); ?>

                                <select class="form-select" name="organization_id" id="organization" onchange="this.form.submit()">
                                    <option value="">-- Select Organization --</option>
                                    <?php $__currentLoopData = $iamOrganizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($org->id); ?>"
                                            <?php echo e($org->id == $organization_id ? 'selected' : ''); ?>>
                                            <?php echo e($org->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </form>
                        </li>

                        <li class="nav-item d-none d-lg-block select-organization-menu">
                            <form action="<?php echo e(route('update-organization')); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <select class="form-select" name="financial_year" id="financial_year" >
                                    <option value="">-- Select F.Y --</option>
                                    <?php if(isset($fyears) && is_iterable($fyears)): ?>
                                    <?php $__currentLoopData = $fyears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($year['id']); ?>"
                                        data-start="<?php echo e($year['start_date']); ?>"
                                        data-end="<?php echo e($year['end_date']); ?>"
                                        <?php echo e(isset($c_fyear) && $c_fyear == $year['range'] ? 'selected' : ''); ?>>
                                        FY <?php echo e($year['range']); ?>

                                    </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </select>
                            </form>
                        </li>
                        <?php endif; ?>


                        

                        
                        
                        <li class="nav-item dropdown dropdown-user">
                            <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="#"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="avatar">
                                            <?php echo e($logedinUser->getInitials()); ?>

                                            
                                    </span>
                                </a>
                            <div class="dropdown-menu drop-newmenu dropdown-menu-end" aria-labelledby="dropdown-user">
                                
                                <a class="dropdown-item" href="<?php echo e(env("AUTH_URL", "")); ?>logout" ><i
                                        class="me-50" data-feather="power"></i> Logout</a>


                            </div>
                        </li>
                        
                    </ul>
                </div>
            </div>
        </div>

    </div>
</nav>
<ul class="main-search-list-defaultlist d-none">
    <li class="d-flex align-items-center"><a href="#">
            <h6 class="section-label mt-75 mb-0">Files</h6>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html">
            <div class="d-flex">
                <div class="me-75"><img src="<?php echo e(url('/app-assets/images/icons/xls.png')); ?>" alt="png"
                        height="32">
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Two new item submitted</p><small class="text-muted">Marketing
                        Manager</small>
                </div>
            </div><small class="search-data-size me-50 text-muted">&apos;17kb</small>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html">
            <div class="d-flex">
                <div class="me-75"><img src="<?php echo e(url('/app-assets/images/icons/jpg.png')); ?>" alt="png"
                        height="32">
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">52 JPG file Generated</p><small class="text-muted">FontEnd
                        Developer</small>
                </div>
            </div><small class="search-data-size me-50 text-muted">&apos;11kb</small>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html">
            <div class="d-flex">
                <div class="me-75"><img src="<?php echo e(url('/app-assets/images/icons/pdf.png')); ?>" alt="png"
                        height="32">
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">25 PDF File Uploaded</p><small class="text-muted">Digital
                        Marketing Manager</small>
                </div>
            </div><small class="search-data-size me-50 text-muted">&apos;150kb</small>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between w-100"
            href="app-file-manager.html">
            <div class="d-flex">
                <div class="me-75"><img src="<?php echo e(url('/app-assets/images/icons/doc.png')); ?>" alt="png"
                        height="32">
                </div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Anna_Strong.doc</p><small class="text-muted">Web
                        Designer</small>
                </div>
            </div><small class="search-data-size me-50 text-muted">&apos;256kb</small>
        </a></li>
    <li class="d-flex align-items-center"><a href="#">
            <h6 class="section-label mt-75 mb-0">Members</h6>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view-account.html">
            <div class="d-flex align-items-center">
                <div class="avatar me-75"><img src="<?php echo e(url('/app-assets/images/portrait/small/avatar-s-8.jpg')); ?>"
                        alt="png" height="32"></div>
                <div class="search-data">
                    <p class="search-data-title mb-0">John Doe</p><small class="text-muted">UI designer</small>
                </div>
            </div>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view-account.html">
            <div class="d-flex align-items-center">
                <div class="avatar me-75"><img src="<?php echo e(url('/app-assets/images/portrait/small/avatar-s-1.jpg')); ?>"
                        alt="png" height="32"></div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Michal Clark</p><small class="text-muted">FontEnd
                        Developer</small>
                </div>
            </div>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view-account.html">
            <div class="d-flex align-items-center">
                <div class="avatar me-75"><img src="<?php echo e(url('/app-assets/images/portrait/small/avatar-s-14.jpg')); ?>"
                        alt="png" height="32"></div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Milena Gibson</p><small class="text-muted">Digital Marketing
                        Manager</small>
                </div>
            </div>
        </a></li>
    <li class="auto-suggestion"><a class="d-flex align-items-center justify-content-between py-50 w-100"
            href="app-user-view-account.html">
            <div class="d-flex align-items-center">
                <div class="avatar me-75"><img src="<?php echo e(url('/app-assets/images/portrait/small/avatar-s-6.jpg')); ?>"
                        alt="png" height="32"></div>
                <div class="search-data">
                    <p class="search-data-title mb-0">Anna Strong</p><small class="text-muted">Web Designer</small>
                </div>
            </div>
        </a></li>
</ul>
<ul class="main-search-list-defaultlist-other-list d-none">
    <li class="auto-suggestion justify-content-between"><a
            class="d-flex align-items-center justify-content-between w-100 py-50">
            <div class="d-flex justify-content-start"><span class="me-75"
                    data-feather="alert-circle"></span><span>No
                    results found.</span></div>
        </a></li>
</ul>
<!-- END: Header-->
<script>
    function sendFySession(startDate, endDate, id) {
    fetch("<?php echo e(route('store.fy.session')); ?>", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            start_date: startDate,
            end_date: endDate,
            fyearId: id,
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Session updated:', data.message);
        location.reload();
    })
    .catch(error => {
        console.error('Error setting session:', error);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('financial_year');
    let previousIndex = select.selectedIndex;

    select.addEventListener('change', function (event) {
        const newIndex = this.selectedIndex;
        const selected = this.options[newIndex];
        const previousFY = this.options[previousIndex].textContent.trim().replace(/^FY\s*/, '');
        const newFY = selected?.textContent.trim().replace(/^FY\s*/, '');

        const id = selected.value;
        const start = selected.getAttribute('data-start');
        const end = selected.getAttribute('data-end');

        // Immediately revert selection before async dialog
        this.selectedIndex = previousIndex;

        if (id.trim() !== "") {
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to switch F.Y. from "${previousFY}" to "${newFY}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, switch it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, update index and trigger form submission or action
                    previousIndex = newIndex;
                    select.selectedIndex = newIndex;

                    if (start && end && id !== "") {
                        sendFySession(start, end, id);
                    }
                }
            });
        }
    });
});


</script>


<?php /**PATH /var/www/html/erp_presence360/resources/views/layouts/partials/header.blade.php ENDPATH**/ ?>