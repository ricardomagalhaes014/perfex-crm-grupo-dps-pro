<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<aside id="menu" class="sidebar sidebar">
    <ul class="nav metis-menu" id="side-menu">
        <li class="tw-mt-[63px] sm:tw-mt-0 -tw-mx-2 tw-overflow-hidden sm:tw-bg-neutral-900/50">
            <div id="logo" class="tw-py-2 tw-px-2 tw-h-[63px] tw-flex tw-items-center">
                <?php echo get_company_logo(get_admin_uri() . '/', '!tw-mt-0')?>
            </div>
        </li>
        <?php
        hooks()->do_action('before_render_broker_aside_menu');
        ?>
        <li class="menu-item-realestate">
            <a href="#" aria-expanded="true">
                <i class="fa-solid fa-house-circle-check menu-icon"></i>
                <span class="menu-text">
                Realestate                </span>
                <span class="fa arrow pleft5"></span>
            </a>
            <ul class="nav nav-second-level collapse in" aria-expanded="true">
                <li class="sub-menu-item-realestate_dashboard">
                    <a href="<?php echo site_url('realestate/broker/dashboard'); ?>">
                        <i class="fa fa-dashboard menu-icon"></i>
                        <span class="sub-menu-text"><?php echo _l('reale_dashboard'); ?></span>
                    </a>
                </li>
                <li class="sub-menu-item-realestate_companies">
                    <a href="<?php echo site_url('realestate/broker/properties'); ?>">
                        <i class="fa fa-regular fa-rectangle-list menu-icon"></i>
                        <span class="sub-menu-text">
                        <?php echo _l('real_properties'); ?></span>
                    </a>
                </li>
                <li class="sub-menu-item-property_owners">
                    <a href="<?php echo site_url('realestate/broker/property_owners'); ?>">
                        <i class="fa fa-solid fa-people-group menu-icon"></i>
                        <span class="sub-menu-text">
                            <?php echo _l('real_property_owners'); ?></span>
                        </a>
                    </li>


                <li class="sub-menu-item-realestate_property_agents">
                    <a href="<?php echo site_url('realestate/broker/requests'); ?>">
                        <i class="fa fa-solid fa-house-circle-exclamation menu-icon"></i>
                        <span class="sub-menu-text">
                        <?php echo _l('real_buy_requests'); ?></span>
                    </a>
                </li>

                <li class="sub-menu-item-realestate_list_invoices">
                    <a href="<?php echo site_url('realestate/broker/list_invoices'); ?>">
                        <i class="fa-solid fa-receipt menu-icon"></i>
                        <span class="sub-menu-text">
                        <?php echo _l('invoices'); ?></span>
                    </a>
                </li>
                <li class="sub-menu-item-realestate_contracts">
                    <a href="<?php echo site_url('realestate/broker/contracts'); ?>">
                        <i class="fa-solid fa-file-contract menu-icon"></i>
                        <span class="sub-menu-text">
                        <?php echo _l('contracts'); ?></span>
                    </a>
                </li>
                <li class="sub-menu-item-realestate_contracts">
                    <a href="<?php echo site_url('realestate/broker/reports'); ?>">
                        <i class="fa fa-list-alt menu-icon"></i>
                        <span class="sub-menu-text">
                        <?php echo _l('real_reports'); ?></span>
                    </a>
                </li>
                
            </ul>
        </li>

        <li class="menu-item-realestate-setting">
            <a href="#" aria-expanded="false">
                <i class="fa fa-cog menu-icon menu-icon"></i>
                <span class="menu-text"><?php echo _l('reale_settings'); ?></span>
                <span class="fa arrow pleft5"></span>
            </a>
            <ul class="nav nav-second-level " aria-expanded="false">
                <li class="sub-menu-item-realestate_dashboard">
                    <a href="<?php echo site_url('realestate/broker/contract_types'); ?>">
                        <i class="fa fa-contract_types menu-icon"></i>
                        <span class="sub-menu-text"><?php echo _l('contract_types'); ?></span>
                    </a>
                </li>
            </ul>
        </li>

    </li>
    <?php hooks()->do_action('after_render_broker_aside_menu'); ?>
</ul>
</aside>