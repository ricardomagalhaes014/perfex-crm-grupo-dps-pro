<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-12">
                <h2>
                    <?php echo $template_data->template_name; ?>
                </h2>
                <p><?php echo $template_data->template_description; ?></p>
            </div>

            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <?php echo render_input('language', 'predix_input_language_to_use', 'English'); ?>
                        </div>

                        <?php
                        $decodeInputs = json_decode($template_data->custom_inputs);

                        foreach ($decodeInputs as $input) {

                            if ($input->input_field_type === 'textarea') {
                                ?>
                                <div class="col-md-12">
                                    <?php
                                    echo render_textarea($input->input_name, $input->input_label)
                                    ?>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="col-md-12">
                                    <?php
                                    echo render_input($input->input_name, $input->input_label, '', $input->input_field_type);
                                    ?>
                                </div>
                                <?php
                            }
                        }
                        ?>

                        <div class="col-md-6">
                            <?php echo render_select('creativity', predix_template_priorities(), ['value', 'name'], 'predix_input_creativity', '', [], [], '', '', false); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_select('tone_of_voice', predix_template_tone_of_voices(), ['value', 'name'], 'predix_input_tone_of_voice', '', [], [], '', '', false); ?>
                        </div>

                        <div class="col-md-12">
                            <?php echo render_input('max_result_length', 'predix_input_max_result_length', '200', 'number'); ?>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" onclick="generateTemplateContent()"
                                    class="btn btn-primary generateTemplate center-block"><?php echo _l('predix_generate_template_text'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <?php echo render_input('document_name', 'predix_document_name', 'New Document'); ?>
                        </div>

                        <div class="col-md-12">
                            <?php echo render_textarea('document_description', '', '', ['rows' => 10], [], '', 'tinymce'); ?>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" onclick="saveDocument()"
                                    class="btn btn-primary saveDocument pull-right"><?php echo _l('predix_save_as_document'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
<?php init_tail(); ?>
</body>
<script>
    function generateTemplateContent() {
        $('.generateTemplate').prop('disabled', true);
        var lastInnerText = $('.generateTemplate').text();
        $('.generateTemplate').text('Generating Content....');

        let requestData = {
            language: $('#language').val(),
            creativity: $('#creativity').val(),
            tone_of_voice: $('#tone_of_voice').val(),
            max_result_length: $('#max_result_length').val()
        }

        <?php
        $decodeInputs = json_decode($template_data->custom_inputs);

        foreach ($decodeInputs as $input) {
        ?>
            requestData['<?php echo $input->input_name ?>'] = $('#<?php echo $input->input_name ?>').val();
        <?php
        }
        ?>

        $.ajax({
            url: '<?php echo admin_url('predix/handle_template_call/'. $template_data->id)?>',
            type: 'POST',
            data: requestData,
            success: function (data) {
                data = JSON.parse(data);

                if (data.status == 1) {
                    var editor = tinymce.get('document_description');

                    if (editor) {

                        let currentContent = editor.getContent();

                        editor.setContent(currentContent + data.message);
                    }
                    $('.generateTemplate').prop('disabled', false);
                    $('.generateTemplate').text(lastInnerText);
                }

            },
            error: function (error) {
                alert('Failed');
                console.error(error);
            }
        });
    }

    function saveDocument()
    {
        $('.saveDocument').prop('disabled', true);

        var editor = tinymce.get('document_description');

        var documentContent = '';
        if (editor) {
            documentContent = editor.getContent();
        }

        $.ajax({
            url: '<?php echo admin_url('predix/create_template_document/'. $template_data->id)?>',
            type: 'POST',
            data: {
                document_name: $('#document_name').val(),
                document_description: documentContent
            },
            success: function (data) {
                data = JSON.parse(data);

                if (data.status == 1) {
                    alert_float("success", data.message);
                    $('.saveDocument').prop('disabled', false);
                } else {
                    alert_float("danger", data.message);
                    return;
                }

            },
            error: function (error) {
                alert('Failed');
                console.error(error);
            }
        });
    }

</script>
</html>
