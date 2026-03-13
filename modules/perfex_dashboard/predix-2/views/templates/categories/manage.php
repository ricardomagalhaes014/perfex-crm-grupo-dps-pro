<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (has_permission('predix', '', 'create_template_categories')) { ?>
                    <div class="tw-mb-2 sm:tw-mb-4">
                        <a href="<?php echo admin_url('predix/create_template_category'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('predix_add_template_category'); ?>
                        </a>
                    </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('predix_category_name'),
                            _l('predix_category_description'),
                            _l('predix_is_enabled'),
                            _l('created_at'),
                            _l('options'),
                        ], 'predix-template-categories'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-predix-template-categories', window.location.href, [3], [3], [], [3, 'desc']);
    });
</script>
</body>

</html>