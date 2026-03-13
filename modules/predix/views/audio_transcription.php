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
                if (has_permission('predix', '', 'create_transcription')) {
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
                                    <button type="button"
                                            class="btn btn-primary transcript-btn"><?php echo _l('predix_transcript_button'); ?></button>
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
                    <?php echo _l('predix_generated_audio_transcriptions'); ?>
                </h4>
                <div class="panel_s">
                    <div class="col-md-12 panel-body audio-transcription-list">

                        <?php
                        foreach ($generatedAudioTranscripts as $transcription) {
                            $pathInfo = pathinfo($transcription['audio_file_path']);
                            ?>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <audio controls class="mb-3">
                                            <source src="<?php echo substr(module_dir_url('predix/uploads/audio_transcription/' . $transcription['id'] . '/' . $pathInfo['basename']), 0, -1); ?>">
                                            Your browser does not support the audio tag.
                                        </audio>
                                        <div style="text-align: center;font-weight: bold"><?php echo $pathInfo['basename']; ?></div>
                                        <h5 class="card-title"><?php echo _l('predix_generated_transcribed_text'); ?>
                                            :</h5>
                                        <p class="card-text bg-light p-3 rounded"><?php echo nl2br($transcription['transcription_text']); ?></p>
                                        <?php
                                        if (has_permission('predix', '', 'delete_transcription')) {
                                        ?>
                                        <a class="btn btn-danger btn-sm _delete"
                                           href="<?php echo admin_url('predix/deleteAudioTranscription/' . $transcription['id']) ?>">
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

        function transcriptAudio() {
            $('.transcript-btn').prop('disabled', true);
            var lastInnerText = $('.transcript-btn').text();
            $('.transcript-btn').text('Transcribing....');

            var formData = new FormData();
            formData.append('AudioToTranslate', $('#AudioToTranslate')[0].files[0]);

            if (typeof csrfData !== "undefined") {
                formData.append(csrfData["token_name"], csrfData["hash"]);
            }

            $.ajax({
                url: '<?php echo admin_url('predix/audioTranscriptionWithAi') ?>',
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
                <p class="card-text bg-light p-3 rounded">${response.transcription_text}</p>
                <a class="btn btn-danger btn-sm _delete" href="<?php echo admin_url('predix/deleteAudioTranscription/') ?>${response.id}">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </div>
            </div>
          </div>
        `;

                    get(".audio-transcription-list").insertAdjacentHTML("afterbegin", msgHTML);

                    $('.transcript-btn').prop('disabled', false);
                    $('.transcript-btn').text(lastInnerText);

                },
                error: function (response) {
                    $('.transcript-btn').prop('disabled', false);
                    $('.transcript-btn').text(lastInnerText);
                    alert_float("danger", 'Failed To Generate Audio Transcription' + response);
                }
            });

            return false;
        }

        $(document).ready(function () {
            $('.transcript-btn').on('click', function () {
                transcriptAudio();
            });
        });
    })(jQuery);

</script>

