
<!-- BEGIN: Main Menu-->
<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow erpnewsidemenu" data-scroll-to-active="true">

    <div class="shadow-bottom"></div>
    <div class="main-menu-content newmodulleftmenu">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
           
            <?php $__currentLoopData = $iamMenu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($iamUser->user_type !== 'IAM-SUPER' && ! in_array('menu.' . $menu['alias'], $iamPermissions)): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <li class="nav-item  <?php if(!empty($menu['childMenus'])): ?> has-sub <?php endif; ?>">
                    <a class="d-flex align-items-center dashboard-icon"
                        <?php if(empty($menu['childMenus'])): ?> href="<?php echo e($menu['url']); ?>" <?php else: ?> href="#" <?php endif; ?>>
                        <i data-feather="<?php echo e($menu['icon'] ?? 'file-text'); ?>"></i>
                        <span class="menu-title text-truncate"><?php echo e($menu['name']); ?></span></a>

                    <?php if(!empty($menu['childMenus'])): ?>
                        <ul class="menu-content">
                            <?php $__currentLoopData = $menu['childMenus']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $childMenu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($iamUser->user_type !== 'IAM-SUPER' && ! in_array('menu.' . $childMenu['alias'], $iamPermissions)): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <?php echo $__env->make('p360::layouts.partials.menu-item', [
                                    'menu' => $childMenu,
                                    'iamUser' => $iamUser,
                                    'iamPermissions' => $iamPermissions,
                                    'iamAppUrls' => $iamAppUrls
                                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            
        </ul>
    </div>

</div>
<!-- END: Main Menu-->
<?php /**PATH /var/www/html/erp_presence360/resources/views/layouts/partials/v2/left-sidebar.blade.php ENDPATH**/ ?>