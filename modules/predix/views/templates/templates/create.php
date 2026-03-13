<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            $formUrl = 'predix/create_template';

            if (isset($template_data)) {
                $formUrl = 'predix/create_template/' . $template_data->id;
            }
            echo form_open(admin_url($formUrl));
            ?>
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo $title; ?>
                        </h4>
                        <div class="col-md-6">
                            <?php echo render_input('template_name', 'predix_input_template_name', $template_data->template_name ?? ''); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_input('template_description', 'predix_input_template_description', $template_data->template_description ?? ''); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_input('template_icon', 'predix_input_template_icon', $template_data->template_icon ?? ''); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_select('template_category_id', $template_categories, ['id', ['category_name']], 'predix_input_template_category', $template_data->template_category_id ?? '') ?>
                        </div>

                        <div class="col-md-12">
                            <?php echo render_textarea('custom_prompt', 'predix_input_template_custom_prompt', $template_data->custom_prompt ?? '', ['rows' => 10]); ?>
                            <?php
                            if (isset($template_data) && !is_null($template_data->custom_inputs)) {
                                $decodeInputs = json_decode($template_data->custom_inputs);

                                foreach ($decodeInputs as $input) {
                                    ?>
                                    <a onclick="insertText('<?php echo $input->input_name?>')">{{<?php echo $input->input_name?>}}</a>
                                    <?php
                                }
                            }
                            ?>
                            <p><?php echo _l('predix_custom_prompt_hint'); ?></p>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php
                        if (!isset($template_data) || (isset($template_data) && is_null($template_data->custom_inputs))) {
                            ?>
                            <div class="custom-inputs col-md-12">
                                <div class="col-md-3">
                                    <?php echo render_input('custom_input_name[]', 'predix_custom_input_name'); ?>
                                </div>
                                <div class="col-md-3">
                                    <?php echo render_input('custom_input_label[]', 'predix_custom_input_label'); ?>
                                </div>
                                <div class="col-md-4">
                                    <?php echo render_select('custom_input_field_type[]', predix_custom_inputs_allowed_types(), ['value', 'name'], 'predix_custom_input_field_type'); ?>
                                </div>
                                <div class="col-md-2">
                                 <span onclick="addField($(this), 1)" class="btn btn-primary mtop20">
												<i class="fa fa-btn fa-plus"></i>
											</span>
                                    <span onclick="removeField($(this))" class="btn btn-danger mtop20">
												<i class="fa fa-btn fa-minus"></i>
											</span>
                                </div>
                            </div>
                            <?php
                        } else {
                            $decodeInputs = json_decode($template_data->custom_inputs);

                            foreach ($decodeInputs as $input) {
                                ?>
                                <div class="col-md-12 custom-inputs">
                                    <div class="col-md-3">
                                        <?php echo render_input('custom_input_name[]', 'predix_custom_input_name', $input->input_name); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <?php echo render_input('custom_input_label[]', 'predix_custom_input_label', $input->input_label); ?>
                                    </div>
                                    <div class="col-md-4 customInputFieldType">
                                        <?php echo render_select('custom_input_field_type[]', predix_custom_inputs_allowed_types(), ['value', 'name'], 'predix_custom_input_field_type', $input->input_field_type); ?>
                                    </div>
                                    <div class="col-md-2">
                                 <span onclick="addField($(this), 1)" class="btn btn-primary mtop20">
												<i class="fa fa-btn fa-plus"></i>
											</span>
                                        <span onclick="removeField($(this))" class="btn btn-danger mtop20">
												<i class="fa fa-btn fa-minus"></i>
											</span>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                        <div class="field-container"></div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
        <div class="btn-bottom-pusher"></div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
<script type="text/javascript">
    $(function () {

        "use strict";

    });

    let i = 2;

    function addField(plusElement, k) {

        if (k == 2) {
            i = 3;
        }

        let new_field = '<div class="col-md-12">' +
            '<div class="col-md-3">' +
            '<div class="form-group" app-field-wrapper="custom_input_name[]"><label for="custom_input_name[]" class="control-label"><?php echo _l('predix_custom_input_name'); ?></label><input type="text" id="custom_input_name[]" name="custom_input_name[]" class="form-control" value=""></div>                            </div>' +
            '<div class="col-md-3">' +
            '<div class="form-group" app-field-wrapper="custom_input_label[]"><label for="custom_input_label[]" class="control-label"><?php echo _l('predix_custom_input_label'); ?></label><input type="text" id="custom_input_label[]" name="custom_input_label[]" class="form-control" value=""></div>                            </div>' +
            '<div class="col-md-4">' +
            '<div class="form-group">' +
            '<label for="exampleSelect">Select Option</label>' +
            '<select class="form-control" name="custom_input_field_type[]" id="exampleSelect">' +
            '<option value="text">Text</option>' +
            '<option value="number">Number</option>' +
            '<option value="textarea">Textarea</option>' +
            '</select>' +
            '</div>' +
            '</div>' +
            '<div class="col-md-2">' +
            '<span onclick="addField($(this), 1)" style="margin-right:3px" class="btn btn-primary mtop20">' +
            '<i class="fa fa-btn fa-plus"></i>' +
            '</span>' +
            '<span onclick="removeField($(this))" class="btn btn-danger mtop20">' +
            '<i class="fa fa-btn fa-minus"></i>' +
            '</span>' +
            '</div>' +
            '</div>';
        i++;
        $(".field-container").append(new_field);
    }

    function removeField(minusElement) {
        minusElement.parent().parent().remove();
    }

    function insertText(value) {
        insertToPrompt(" {{" + value + "}} ");
    }

    function insertToPrompt(text) {
        var curPos = document.getElementById("custom_prompt").selectionStart;
        let x = $("#custom_prompt").val();
        $("#custom_prompt").val(x.slice(0, curPos) + text + x.slice(curPos));
    }
</script>
