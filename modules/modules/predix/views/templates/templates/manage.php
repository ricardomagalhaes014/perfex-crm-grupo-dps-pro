<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .template_card {
        font-family: "Lato", sans-serif;

        background-color: #fff;
        margin-bottom: 1.5rem;
        width: 100%;
        border: 1px solid #dbe2eb;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-radius: 8px;
        padding: 1rem 1.5rem;
    }

    .template_card .card-body {
        min-height: 155px;
    }

    .template_card .template-icon {
        font-size: 16px;
        padding: 7px;
        background: #E1F0FF;
        color: #007bff;
        border-radius: 5px;
    }

    .number-font {
        font-family: "Poppins", sans-serif;
        font-weight: 700;
    }

    .fs-13 {
        font-size: 13px;
    }

    .text-muted {
        color: #728096 !important;
    }
</style>
<div id="wrapper">
    <div class="content">

        <div class="row">
            <div class="col-md-12">

                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <?php if (has_permission('predix', '', 'create_templates')) { ?>
                    <div class="tw-mb-2 sm:tw-mb-4">
                        <a href="<?php echo admin_url('predix/create_template'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('predix_create_template'); ?>
                        </a>
                    </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="col">
                            <button type="button" class="btn btn-primary category-btn active" data-category="all">All
                            </button>
                            <?php
                            foreach ($template_categories as $category) {
                                ?>
                                <button type="button" class="btn btn-primary category-btn"
                                        data-category="<?php echo $category['id']; ?>"><?php echo $category['category_name']; ?></button>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
            <div class="col-md-12">
            <?php
            foreach ($templates as $template) {
                ?>
                <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 templates-item <?php echo $template['template_category_id']; ?>">
                    <div class="card template_card">
                        <div class="card-body">
                            <i class="<?php echo $template['template_icon'] ?? 'fa-solid fa-file-text' ?> menu-icon template-icon"></i>
                            <div class="template-title">
                                <h4 class="mb-2 fs-15 number-font"><?php echo $template['template_name'] ?></h4>
                            </div>
                            <p class="card-text fs-13 text-muted mb-2"><?php echo $template['template_description'] ?></p>

                            <?php
                            if (has_permission('predix', '', 'view_templates')) {
                                ?>
                                <a href="<?php echo admin_url('predix/use_template/' . $template['id']) ?>"
                                   class="btn btn-primary"><?php echo _l('predix_use_template'); ?></a>
                                <?php
                            }
                            ?>

                            <?php
                            if (has_permission('predix', '', 'create_templates')) {
                                ?>
                                <a href="<?php echo admin_url('predix/create_template/' . $template['id']) ?>"
                                   class="btn btn-info tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                    <i style="color: white" class="fa-regular fa-pen-to-square fa-lg"></i>
                                </a>
                                <?php
                            }
                            ?>

                            <?php
                            if (has_permission('predix', '', 'delete_templates')) {
                                ?>
                                <a href="<?php echo admin_url('predix/delete_template/' . $template['id']) ?>"
                                   class="btn btn-danger tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                    <i style="color: white" class="fa-regular fa-trash-can fa-lg"></i>
                                </a>
                                <?php
                            }
                            ?>

                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
            </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
<script>
    $(document).ready(function () {
        $('.category-btn').click(function () {
            var category = $(this).data('category');
            if (category === 'all') {
                $('.templates-item').show();
            } else {
                $('.templates-item').hide();
                $('.templates-item.' + category).show();
            }
            $('.category-btn').removeClass('active');
            $(this).addClass('active');
        });
    });
</script>
</html>