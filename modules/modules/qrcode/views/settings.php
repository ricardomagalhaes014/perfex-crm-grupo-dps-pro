<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?= form_hidden('qrcode_settings');
$otherMergeFields = $this->app_merge_fields->get_by_name('other');
$qrFeatureConfigs = [
    'invoices'     => [
        'key'   => 'invoice',
        'field' => ['available' => 'invoice', 'key' => '']
    ],
    'payments'     => [
        'key'   => 'payment',
        'field' => ['available' => 'invoice', 'key' => 'payment']
    ],
    'credit_notes' => [
        'key'   => 'credit_note',
        'field' => ['available' => 'credit_note', 'key' => '']
    ],
    'estimates'    => [
        'key'   => 'estimate',
        'field' => ['available' => 'estimate', 'key' => '']
    ],
    'proposals'    => [
        'key'   => 'proposal',
        'field' => ['available' => 'proposal', 'key' => '']
    ],
];
?>
<div class="horizontal-scrollable-tabs mbot15">
    <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <li role="presentation" class="active">
                <a href="#general" aria-controls="general" role="tab" data-toggle="tab"><?= _l('settings_sales_heading_general') ?></a>
            </li>
            <?php
            foreach ($qrFeatureConfigs as $feature => $config) {
                ?>
                <li role="presentation">
                    <a href="#<?= $feature ?>" aria-controls="<?= $feature ?>" role="tab" data-toggle="tab"><?= _l("$feature") ?></a>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>
</div>
<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="general">
        <?= render_input('settings[qr_code_width]', 'qr_code_width', get_option('qr_code_width'), 'number') ?>
        <hr/>
        <?= render_input('settings[qr_code_height]', 'qr_code_height', get_option('qr_code_height'), 'number') ?>
        <hr/>
    </div>
    <?php
    foreach ($qrFeatureConfigs as $feature => $config) {
        ?>
        <div role="tabpanel" class="tab-pane" id="<?= $feature ?>">
            <?php render_yes_no_option("show_{$config['key']}_qr_code", "qrcode_show_{$config['key']}_qr_code"); ?>
            <hr/>
            <?= render_input("settings[{$config['key']}_qr_code_x_position]", "qr_code_x_position", get_option("{$config['key']}_qr_code_x_position"),
                'number') ?>
            <hr/>
            <?= render_input("settings[{$config['key']}_qr_code_y_position]", "qr_code_y_position", get_option("{$config['key']}_qr_code_y_position"),
                'number') ?>
            <hr/>
            <?= render_textarea("settings[{$config['key']}_qr_code_info]", "qrcode_{$config['key']}_qr_code_info",
                clear_textarea_breaks(get_option("{$config['key']}_qr_code_info")), ['rows' => 8, 'style' => 'line-height:20px;']) ?>
            <div class="row">
                <div class="col-lg-12 ">
                    <?php
                    foreach (
                        array_merge($this->app_merge_fields->get_by_name($config['field']['available']), $this->app_merge_fields->get_by_name('client'),
                            $otherMergeFields) as $field
                    ) {
                        if (in_array($config['field']['available'], $field['available'])
                            || ($config['field']['key'] && str_starts_with($field['key'], $config['field']['key']))) {
                            ?>
                            <p class="col-lg-6">
                                <?= $field['name'] ?>
                                <a href="#" class="settings-textarea-merge-field mright5 pull-right"
                                   data-to="<?= $config['key'] ?>_qr_code_info"><?= $field['key'] ?></a>
                            </p>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>
