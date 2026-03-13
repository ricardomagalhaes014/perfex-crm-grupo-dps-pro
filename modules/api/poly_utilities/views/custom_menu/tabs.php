<div class="horizontal-scrollable-tabs panel-full-width-tabs">
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal">
            <li class="<?php echo ($active == 'sidebar') ? 'active' : '' ?>"><a href="<?php echo admin_url('poly_utilities/custom_menu'); ?>">
                    <?php echo _l('poly_utilities_custom_sidebar_menu_extend'); ?>
                </a></li>
            <li class="<?php echo ($active == 'setup') ? 'active' : '' ?>"><a href="<?php echo admin_url('poly_utilities/custom_menu?menu=setup'); ?>">
                    <?php echo _l('poly_utilities_custom_setup_menu_extend'); ?>
                </a></li>
            <li class="<?php echo ($active == 'clients') ? 'active' : '' ?>"><a href="<?php echo admin_url('poly_utilities/custom_menu?menu=clients'); ?>">
                    <?php echo _l('poly_utilities_custom_clients_menu_extend'); ?>
                </a></li>
        </ul>
    </div>
</div>
<div class="tw-items-center tw-mb-2">
    <div>
        <span class="cursor btn-poly-reset-menu"><i class="fa-solid fa-rotate-right fa-fw"></i>&nbsp;<span @click.stop="handleResetCustomMenu('sidebar')">Reset sidebar menu</span></span>&nbsp;
        <span class="cursor btn-poly-reset-menu"><i class="fa-solid fa-rotate-right fa-fw"></i>&nbsp;<span @click.stop="handleResetCustomMenu('setup')">Reset setup menu</span></span>&nbsp;
        <span class="cursor btn-poly-reset-menu"><i class="fa-solid fa-rotate-right fa-fw"></i>&nbsp;<span @click.stop="handleResetCustomMenu('clients')">Reset client menu</span></span>&nbsp;
        <span class="cursor btn-poly-reset-menu"><i class="fa-solid fa-rotate-right fa-fw"></i>&nbsp;<span @click.stop="handleResetCustomMenu('all')">Reset all menus</span></span></div>

    <div><span class="cursor btn-poly-delete-menu"><i class="fa-solid fa-circle-xmark fa-fw"></i>&nbsp;<span @click.stop="handleDeleteCustomMenu('sidebar')">Delete custom sidebar menu</span></span>&nbsp;<span class="cursor btn-poly-delete-menu"><i class="fa-solid fa-circle-xmark fa-fw"></i>&nbsp;<span @click.stop="handleDeleteCustomMenu('setup')">Delete custom setup menu</span></span>&nbsp;<span class="cursor btn-poly-delete-menu"><i class="fa-solid fa-circle-xmark fa-fw"></i>&nbsp;<span @click.stop="handleDeleteCustomMenu('clients')">Delete custom client menu</span></span>&nbsp;<span class="cursor btn-poly-delete-menu"><i class="fa-solid fa-circle-xmark fa-fw"></i>&nbsp;<span @click.stop="handleDeleteCustomMenu('all')">Delete all custom menus</span></span></div>
</div>

<div class="tw-mb-2 tw-mt-2"><i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1"></i>Please click "<span class="cursor btn-poly-flush-rewrite-url"><i class="fa-solid fa-rotate-right fa-fw"></i>&nbsp;<span @click.stop="handleReactivationModule()">Flush rewrite rules</span></span>" if the routes (friendly URLs) on the custom client menus are not working.</div>
<hr>