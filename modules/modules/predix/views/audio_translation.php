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
                if (has_permission('predix', '', 'create_translation')) {
                    ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo _l('predix_upload_audio_file'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="AudioToTranslate"
                                           class="form-label"><?php echo _l('predix_select_file_to_upload'); ?>:</label>
                                    <div class=" mb-3">
                                        <input type="file"
                                               filesize="<?php echo get_option('predix_audio_translation_max_size') ?>"
                                               accept="<?php echo get_option('predix_audio_translation_allowed_extensions') ?>"
                                               name="AudioToTranslate" class="form-control" id="AudioToTranslate">
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="button" onclick="translateAudio()"
                                            class="btn btn-primary translate-btn"><?php echo _l('predix_translate_button'); ?></button>
                                </div>
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
                    <?php echo _l('predix_generated_audio_translations'); ?>
                </h4>
                <div class="panel_s">
                    <div class="col-md-12 panel-body audio-translated-list">

                        <?php
                        foreach ($generatedAudioTranslations as $translation) {
                            $pathInfo = pathinfo($translation['audio_file_path']);
                            ?>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <audio controls class="mb-3">
                                            <source src="<?php echo substr(module_dir_url('predix/uploads/audio_translation/' . $translation['id'] . '/' . $pathInfo['basename']), 0, -1); ?>">
                                            Your browser does not support the audio tag.
                                        </audio>
                                        <div style="text-align: center;font-weight: bold"><?php echo $pathInfo['basename']; ?></div>
                                        <h5 class="card-title"><?php echo _l('predix_generated_translated_text'); ?>
                                            :</h5>
                                        <p class="card-text bg-light p-3 rounded"><?php echo nl2br($translation['translated_text']); ?></p>
                                        <?php
                                        if (has_permission('predix', '', 'delete_translation')) {
                                            ?>
                                            <a class="btn btn-danger btn-sm _delete"
                                               href="<?php echo admin_url('predix/deleteAudioTranslation/' . $translation['id']) ?>">
                                                <i class="bi bi-trash"></i> Delete
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

        <div class="btn-bottom-pusher"></div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    (function ($) {
        "use strict";

        function get(selector, root = document) {
            return root.querySelector(selector);
        }

        function translateAudio() {
            $('.translate-btn').prop('disabled', true);
            var lastInnerText = $('.translate-btn').text();
            $('.translate-btn').text('Translating....');

            var formData = new FormData();
            formData.append('AudioToTranslate', $('#AudioToTranslate')[0].files[0]);

            if (typeof csrfData !== "undefined") {
                formData.append(csrfData["token_name"], csrfData["hash"]);
            }

            $.ajax({
                url: '<?php echo admin_url('predix/audioTranslationWithAI') ?>',
                type: 'post',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    response = JSON.parse(response);

                    if (response.status == 0) {
                        alert_float("danger", response.message);
                        return;
                    }

                    const msgHTML = `
          <div class="col-md-4">
            <div class="card mb-4">
              <div class="card-body">
                <audio controls class="mb-3">
                  <source src="${response.audio_web_link}">
                  Your browser does not support the audio tag.
                </audio>
                <h5 class="card-title"><?php echo _l('predix_generated_translated_text'); ?>:</h5>
                <p class="card-text bg-light p-3 rounded">${response.translated_text}</p>
                <a class="btn btn-danger btn-sm _delete" href="<?php echo admin_url('predix/deleteAudioTranslation/') ?>${response.id}">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </div>
            </div>
          </div>
        `;

                    get(".audio-translated-list").insertAdjacentHTML("afterbegin", msgHTML);

                    $('.translate-btn').prop('disabled', false);
                    $('.translate-btn').text(lastInnerText);
                },
                error: function (response) {
                    $('.translate-btn').prop('disabled', false);
                    $('.translate-btn').text(lastInnerText);
                    alert_float("danger", 'Failed To Generate Audio Translation');
                }
            });

            return false;
        }

        $(document).ready(function () {
            $('.translate-btn').on('click', function () {
                translateAudio();
            });
        });
    })(jQuery);

</script>

