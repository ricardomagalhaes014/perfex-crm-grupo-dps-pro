<div class="col-md-12">
    <h4 class="bold well email-template-heading">
        <?php echo _l('meetlink_manager'); ?>
        <?php if ($hasPermissionEdit) { ?>
          <a href="<?php echo admin_url('emails/disable_by_type/order'); ?>" class="pull-right mleft5 mright25"><small><?php echo _l('disable_all'); ?></small></a>
          <a href="<?php echo admin_url('emails/enable_by_type/order'); ?>" class="pull-right"><small><?php echo _l('enable_all'); ?></small></a>
      <?php } ?>
    </h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr><th><?php echo _l('email_templates_table_heading_name'); ?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($meetlink as $meetlink_template) { ?>
                    <tr>
                        <td class="<?php if (0 == $meetlink_template['active']) {
    echo 'text-throught';
} ?>">
                            <a href="<?php echo admin_url('emails/email_template/'.$meetlink_template['emailtemplateid']); ?>">
                                <?php echo htmlspecialchars($meetlink_template['name']); ?>                                
                            </a>
                            <?php if (ENVIRONMENT !== 'production') { ?>
                                <br/>
                                <small><?php echo htmlspecialchars($meetlink_template['slug']); ?></small>
                            <?php } ?>
                            <?php if ($hasPermissionEdit) { ?>
                                <a href="<?php echo admin_url('emails/'.('1' == $meetlink_template['active'] ? 'disable/' : 'enable/').$meetlink_template['emailtemplateid']); ?>" class="pull-right">
                                    <small>
                                        <?php echo _l(1 == $meetlink_template['active'] ? 'disable' : 'enable'); ?>
                                    </small>
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>