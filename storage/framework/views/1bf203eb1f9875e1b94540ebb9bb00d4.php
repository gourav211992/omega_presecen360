<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<!-- BEGIN: Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <meta name='robots' content='noindex, nofollow' />
    <meta name="google-site-verification" content="N5En2PPju8H1whZHL5CNKb4sG-LFoZkUgpp3H1AwNzY" />

    <title><?php echo $__env->yieldContent('title', 'Presence 360'); ?></title>

    
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(url('/assets/css/favicon.png')); ?>">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600;700"
        rel="stylesheet">

    <!-- <script src = "<?php echo e(asset('app-assets/js/common-script.js')); ?>"> </script> -->
    <?php echo $__env->make('layouts.partials.css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php echo $__env->yieldContent('styles'); ?>

</head>
<!-- END: Head-->

<!-- BEGIN: Body-->

<div class="overlay-loader" id = "erp-overlay-loader">
        <div class="spinner"></div>
    </div>

<script>
    document.getElementById('erp-overlay-loader').style.display = "flex";
</script>


<body class="vertical-layout vertical-menu-modern  navbar-floating footer-static  menu-collapsed" data-open="click"
    data-menu="vertical-menu-modern" data-col="">
    <div class="preloader" style="display: none;">
        <div class="loadingBox">
            <p>Loading...</p>
            <div class="loadingbar">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
    <?php echo $__env->make('layouts.partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <!-- if ($organization_id != 8) -->
        <!-- ('layouts.partials.side-menu-permission-wise')   -->
        <?php echo $__env->make('layouts.partials.v2.left-sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- else  -->
        <!-- include('layouts.partials.side-menu') -->
    <!-- endif -->
    <?php echo $__env->yieldContent('content'); ?>

    <?php echo $__env->make('layouts.partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php echo $__env->yieldContent('modals'); ?>

    <?php echo $__env->make('layouts.partials.js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php echo $__env->yieldContent('scripts'); ?>

</body>
<!-- END: Body-->

</html>
<?php /**PATH /var/www/html/erp_presence360/resources/views/layouts/app.blade.php ENDPATH**/ ?>