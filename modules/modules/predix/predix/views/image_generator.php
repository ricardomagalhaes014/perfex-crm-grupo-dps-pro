<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <?php
                if (has_permission('predix', '', 'create_image_gen')) {
                    ?>
                    <div class="panel_s">
                        <div class="panel-body">
                            <div class="col-md-12">
                                <?php echo render_input('image_input', 'predix_generate_image_label', ''); ?>
                            </div>
                            <div class="col-md-4">
                                <?php
                                $inputAttr = [];
                                if (!is_admin()) {
                                    $inputAttr['max'] = get_option('predix_image_generator_maximum_images_generate');
                                }
                                $inputAttr['min'] = '1';
                                ?>
                                <?php echo render_input('number_of_images', 'predix_generate_image_number', get_option('predix_image_generator_maximum_images_generate'), 'number', $inputAttr); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_select('size_of_image', predixAISizeOfImages(), ['value', 'name'], 'predix_generate_image_size', '',[],[],'','',false); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_select('image_model', predix_supported_image_models(), ['value', 'name'], 'Model', 'default',[],[],'','',false); ?>
                            </div>
                            <div class="col-md-12">
                                <button type="button"
                                        class="btn btn-primary generate-btn"><?php echo _l('predix_generate'); ?></button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('predix_generated_images'); ?>
                </h4>
                <div class="panel_s">
                    <div class="col-md-12 panel-body image-list">

                        <?php
                        foreach ($generatedImages as $image) {
                            ?>
                            <div class="col-md-4 mtop10">
                                <div class="card">
                                    <img class="card-img-top" style="max-height: 256px; max-width: 256px"
                                         src="<?php echo $image['image_url']; ?>" alt="Image">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mtop15">
                                            <a href="<?php echo $image['image_url']; ?>" target="_blank"
                                               class="btn btn-primary">View</a>
                                            <?php
                                            if (has_permission('predix', '', 'delete_image_gen')) {
                                                ?>
                                                <a href="<?php echo admin_url('predix/deleteImageGenerated/' . $image['id']) ?>"
                                                   class="btn btn-danger _delete">Delete</a>
                                                <?php
                                            }
                                            ?>
                                        </div>
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

        <div class="btn-bottom-pusher"></div>
    </div>
</div>
<?php init_tail(); ?>
</body>
<script>
    (function ($) {
        "use strict";

        function get(selector, root = document) {
            return root.querySelector(selector);
        }

        function generateImage() {
            $('.generate-btn').prop('disabled', true);
            var lastInnerText = $('.generate-btn').text();
            $('.generate-btn').text('Generating....');

            $.ajax({
                url: '<?php echo admin_url('predix/generateImageWithAI')?>',
                type: 'post',
                data: {
                    image_input: $('#image_input').val(),
                    number_of_images: $('#number_of_images').val(),
                    size_of_image: $('#size_of_image').val(),
                    image_model: $('#image_model').val()
                },
                success: function (data) {
                    data = JSON.parse(data)

                    if (data.status == 0) {
                        alert_float("danger", data.message);
                        $('.generate-btn').prop('disabled', false);
                        $('.generate-btn').text(lastInnerText);
                        return;
                    }

                    for (const row of data) {
                        const msgHTML = `
            <div class="col-md-4 mtop10">
              <div class="card">
                <img class="card-img-top" style="max-height: 256px; max-width: 256px" src="${row.file_url}" alt="Image">
                <div class="card-body">
                  <div class="d-flex justify-content-between mtop15">
                    <a href="${row.file_url}" target="_blank" class="btn btn-primary">View</a>
                    <a href="<?php echo admin_url('predix/deleteImageGenerated/') ?>${row.id}" class="btn btn-danger _delete">Delete</a>
                  </div>
                </div>
              </div>
            </div>
          `;

                        get(".image-list").insertAdjacentHTML("afterbegin", msgHTML);
                    }

                    $('.generate-btn').prop('disabled', false);
                    $('.generate-btn').text(lastInnerText);
                },
                error: function (error) {
                    $('.generate-btn').prop('disabled', false);
                    alert('Failed to generate!');
                }
            });
        }

        $(document).ready(function () {
            $('.generate-btn').on('click', function () {
                generateImage();
            });
        });
    })(jQuery);

</script>
</html>
