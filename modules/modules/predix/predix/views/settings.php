<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <?php echo form_open(admin_url('predix/settings'), ['id' => 'quicksharesettings-form']); ?>
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <?php echo _l('predix_settings_notification'); ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <?php echo render_input('settings[predix_openai_secret_key]', 'predix_settings_open_ai_key', get_option('predix_openai_secret_key'), 'password'); ?>
                        </div>

                        <div class="col-md-4">
                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
                               data-title="<?php echo _l('predix_settings_text_limit_question'); ?>"></i>
                            <?php echo render_input('settings[predix_text_limit]', 'predix_settings_text_limit', get_option('predix_text_limit'), 'number', ['min' => 0, 'max' => 2048]); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_select('settings[predix_chat_model]', predix_supported_chat_models(), ['value', 'name'], 'predix_settings_chat_model', get_option('predix_chat_model'),[],[],'','',false); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_yes_no_option('predix_use_streams_for_chat', 'predix_settings_use_streams', 'predix_settings_use_streams_tooltip'); ?>
                        </div>

                        <div class="col-md-6">
                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
                               data-title="<?php echo _l('predix_generate_image_number_helper'); ?>"></i>
                            <?php echo render_input('settings[predix_image_generator_maximum_images_generate]', 'predix_generate_image_number', get_option('predix_image_generator_maximum_images_generate'), 'number'); ?>
                        </div>
                        <div class="col-md-6">
                            <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
                               data-title="<?php echo _l('predix_generated_images_question'); ?>"></i>
                            <?php echo render_select('settings[predix_image_generator_allowed_image_sizes][]', predixAISizeOfImages(), ['value', 'name'], 'predix_generate_image_size', explode(',', get_option('predix_image_generator_allowed_image_sizes')), ['multiple' => true]); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_input('settings[predix_audio_translation_model]', 'predix_settings_predix_audio_translation_model', get_option('predix_audio_translation_model'), 'text', ['disabled' => 'disabled']); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_input('settings[predix_audio_translation_max_size]', 'predix_settings_predix_audio_translation_model_max_filesize', get_option('predix_audio_translation_max_size'), 'number'); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_input('settings[predix_audio_translation_allowed_extensions]', 'predix_settings_predix_audio_translation_model_allowed_extensions', get_option('predix_audio_translation_allowed_extensions')); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_input('settings[predix_audio_transcription_model]', 'predix_settings_predix_audio_transcription_model', get_option('predix_audio_transcription_model'), 'text', ['disabled' => 'disabled']); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_input('settings[predix_audio_transcription_max_size]', 'predix_settings_predix_audio_transcription_model_max_filesize', get_option('predix_audio_transcription_max_size'), 'number'); ?>
                        </div>

                        <div class="col-md-4">
                            <?php echo render_input('settings[predix_audio_transcription_allowed_extensions]', 'predix_settings_predix_audio_transcription_model_allowed_extensions', get_option('predix_audio_transcription_allowed_extensions')); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_input('settings[predix_ai_reply_agent_name]', 'predix_settings_reply_agent_name', get_option('predix_ai_reply_agent_name')); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_input('settings[predix_ai_reply_agent_title]', 'predix_settings_reply_agent_title', get_option('predix_ai_reply_agent_title')); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_select('settings[predix_ai_reply_agent_staff_id]', $members, array('staffid', array('firstname', 'lastname')), 'predix_settings_ticket_assigned_staff', get_staff_user_id(), array('data-current-staff' => get_staff_user_id())); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_yes_no_option('predix_ai_autoreply_on_opening_ticket', 'predix_settings_ticket_reply_agent', 'predix_settings_ticket_reply_agent_tooltip'); ?>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>

