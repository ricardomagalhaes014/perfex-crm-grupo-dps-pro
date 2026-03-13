<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade poly_utilities_ext_modal" id="poly_utilities_ext_modal" data-backdrop="static" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('poly_utilities/banners_announcements_add'), ['id' => 'poly_utilities_ext_form', '@submit.prevent' => 'manageSubmit']); ?>
        <div class="modal-content">
            <!-- Loading -->
            <div class="poly-loader" :class="{'hide': !isUpdateProccessing }">
                <div :class="{'poly-loading': isUpdateProccessing }">&nbsp;</div>
            </div>
            <!-- Loading -->
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span v-if="!is_edit" class="add-title"><i class="fa-regular fa-plus tw-mr-1"></i>&nbsp;<?php echo _l('poly_utilities_banner_media_announcements_create_new')?></span>
                    <span v-if="is_edit" class="edit-title"><i class="fa-regular fa-plus tw-mr-1"></i>&nbsp;<?php echo _l('poly_utilities_banner_media_announcements_edit')?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <input type="hidden" id="id" name="id" :value="handle_item.id" />
                        <?php echo poly_utilities_common_helper::render_input_vuejs('title', _l('poly_utilities_banner_media_title'), '', 'text', array('placeholder' => 'Title'), [], '', '', 'handle_item.title', 'validation_fields.title'); ?>
                        <div v-if="widgets_area && widgets_area.length" :class="{'has-error' : validation_fields.area, 'form-group':true}">
                            <label for="widgets_area"><?php echo _l('poly_utilities_banner_media_widgets_area')?>
                                <select id="widgets_area" class="select2 area form-control" name="area[]" multiple="multiple">
                                    <option v-for="widget_area in widgets_area" :key="widget_area.id" :value="widget_area.id">{{widget_area.name}}</option>
                                </select></label>
                            <p v-if="validation_fields.area" class="poly-help-message-small error red">{{validation_fields.area}}</p>
                        </div>

                        <div class="row tw-mb-3">
                            <?php echo poly_utilities_common_helper::render_textarea_vuejs('content', _l('poly_utilities_banner_media_announcements_content'), '', array('placeholder' => _l('poly_utilities_banner_media_announcements_content_message_help')), [], 'col-md-12', 'media-content', 'handle_item.content');
                            ?>
                            <div v-if="handle_item.content" v-html="handle_item.content" class="text-center media-block-content"></div>
                        </div>

                        <div class="row" :class="{'has-error': !rangeDateValid}">
                            <?php echo render_input('date_from', _l('poly_utilities_banner_media_date_from'), '', 'date', array('v-model' => 'handle_item.date_from'), null, 'col-md-6 col-sm-6 col-xs-6 tw-mb-1'); ?>
                            <?php echo render_input('date_to', _l('poly_utilities_banner_media_date_to'), '', 'date', array('v-model' => 'handle_item.date_to'), null, 'col-md-6 col-sm-6 col-xs-6 tw-mb-1'); ?>
                            <div v-if="validation_fields.date" class="col-md-12 poly-help-message-block">
                                <p class="poly-help-message-small error red">{{validation_fields.date}}</p>
                            </div>
                        </div>

                        <div class="row">
                            <?php echo poly_utilities_common_helper::render_input_vuejs('url',  _l('poly_utilities_banner_media_url'), '', 'url', array('placeholder' => 'Url'), [], 'col-md-6 col-xs-12', 'media-url', 'handle_item.url', ''); ?>
                            <?php echo poly_utilities_common_helper::render_select('target', poly_utilities_common_helper::$targets, "_self", _l('poly_utilities_banner_media_target'), 'col-md-3 col-xs-6', '', array('v-model' => 'handle_item.target')); ?>
                            <?php echo poly_utilities_common_helper::render_select('rel', poly_utilities_common_helper::$rels, 'nofollow', _l('poly_utilities_banner_media_rel'), 'col-md-3 col-xs-6', '', array('v-model' => 'handle_item.rel')); ?>
                        </div>

                        <div class="row col-lg-12 tw-mt-2">
                            <?php echo poly_utilities_common_helper::render_toggle_vuejs('active', _l('poly_utilities_banner_media_is_active'), '', [], [], '', '', 'handle_item.active'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" databs--dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>