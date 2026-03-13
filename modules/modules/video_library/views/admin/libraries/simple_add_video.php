<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<style type="text/css">
.vl_video_link {
    position: relative;
    display: initial;
}
.vl_video_link a {
    border: 1px dotted #b3b3b3;
    display: inline-block;
    padding: 18px 54px;
    border-radius: 6px;
    position: relative;
    padding-bottom: 35px;
}
.d_l_btn {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    text-align: center;
    margin-bottom: 0;
    border-top: 1px solid #e1e1e1;
    padding-top: 5px;
    background-color: #e3e8ee;
    font-size: 11px;
}
.d_l_btn i {}
.vl_video_link h5 {
    font-size: 20px;
}
.vl_video_link h5 i {}

.vl_video_link span {
    position: absolute;
    right: 0;
    z-index: 999999;
    padding: 5px 9px;
    font-size: 10px;
    color: red;
    cursor: pointer;
}
</style>
<div id="wrapper">
<div class="content">
<div class="row">
<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">
<h4 class="no-margin"><?php echo $title; ?></h4>
<hr class="hr-panel-heading" />
<?php
echo form_open_multipart($this->uri->uri_string(), array('id' => 'upload_video_form'));
$value = isset($video) ? $video->title : '';
echo render_input('title', _l('vl_video_title'), $value);
$selected = isset($video) ? $video->category : '';
$data_category = isset($data_category) && !empty($data_category) ? $data_category : [];
echo render_select('category', $data_category, array('id', 'category'), _l('vl_video_cate'), $selected);
$selected = isset($video->project_id) && !empty($video->project_id) ? $video->project_id : '';
echo render_select('project_id', $projects, array('id', 'name'), _l('vl_projects'), $selected);
$valuee = isset($video) ? $video->upload_type : '';
?>
<div class="form-group">
<label for="upload_type" class="control-label clearfix">
<?php echo _l('vl_ask_for_upload_file'); ?> </label>
<div class="radio radio-primary radio-inline">
<input type="radio" class="upload_type" id="upload-type-file" name="upload_type" value="file" <?php if ($valuee == 'file') : ?>checked<?php endif; ?> checked>
    <label for="upload-type-file">
    <?php echo _l('vl_input_option1'); ?>  </label>
    </div>
    <div class="radio radio-primary radio-inline">
    <input type="radio" id="upload-type-link" class="upload_type" name="upload_type" value="link" <?php if ($valuee == 'link') : ?>checked <?php endif; ?>>
        <label for="upload-type-link">
        <?php echo _l('vl_input_option2'); ?> </label>
        </div>
        </div>
        <?php
        echo render_input('link', _l('vl_link_url'), '', '',  ['placeholder' => _l('vl_link_url_placeholder')], [], 'hidden showl');
        
        echo render_input('upload_video', _l('vl_video_file'), '', 'file', [], [], 'showf');
        if (isset($video) && !empty($video->upload_video)) {
            echo "<div class='form-group vl_video_link'><a href='" . base_url() . 'uploads/video_library/' . $video->upload_video . "' download>
            <h5><i class='fa fa-video-camera'></i></h5>
            <p class='d_l_btn'><i class='fa fa-download'></i> Download</p>
            </a>
            <span class='_delete' data-id='" . $video->id . "'><i class='fa fa-times' data-id='" . $video->id . "'></i></span>
            </div>";
        }
        $value = isset($video) ? $video->description : '';
        echo render_textarea('description', _l('vl_video_description'), $value); ?>
        <button type="submit" class="btn btn-info pull-right save_vl_btn" data-><?php echo _l('submit'); ?></button>
        <?php echo form_close(); ?>
        </div>
        </div>
        </div>
        </div>
        </div>
        </div>
        <?php init_tail(); ?>
        <script>
        function validate_form() {
            <?php if (!isset($video) && empty($video)) { ?>
                appValidateForm($('#upload_video_form'), {
                    title: 'required',
                    category: 'required',
                    description: 'required',
                    upload_video: {
                        required: {
                            depends: function(element) {
                                if ($('.upload_type') == 'file') {
                                    return true;
                                } else {
                                    return false;
                                }
                            },
                            extension: "mpeg|mp4|flv|wmv|avi|mkv",
                        }
                    },
                    link: {
                        required: {
                            depends: function(element) {
                                if ($('.upload_type') == 'link') {
                                    return true;
                                } else {
                                    return false;
                                }
                            },
                            extension: "mpeg|mp4|flv|wmv|avi|mkv",
                        }
                    }
                });
                <?php } else { ?>
                    appValidateForm($('#upload_video_form'), {
                        title: 'required',
                        category: 'required',
                        description: 'required',
                    });
                    <?php } ?>
                }
                $(function() {
                    $('body').on('click', 'button.save_vl_btn', function() {
                        validate_form();
                        $('form#upload_video_form').submit();
                    });
                    
                })
                $(document).on('click', '.vl_video_link span', function(event) {
                    var video_id = $(event.currentTarget).data('id');
                    $.post(admin_url + "video_library/delete_video/" + video_id, function(resp) {
                        resp = JSON.parse(resp);
                        if (resp.status == 'success') {
                            location.reload();
                        }
                        alert_float(resp.status, resp.message);
                    });
                });
                var jFoo = <?php echo json_encode($valuee); ?>;
                if (jFoo == 'link') {
                    $('.showf').hide();
                    $('.showl').removeClass("hidden");
                }
                $(document).on('change', '.upload_type', function() {
                    if (this.value == 'link') {
                        $('.showf').hide();
                        $('.showl').removeClass("hidden");
                        $(".showl").css('display', 'block');
                    }
                    if (this.value == 'file') {
                        $('.showl').hide();
                        $('.showf').show();
                    }
                });
                </script>
                </body>
                
                </html>