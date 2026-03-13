<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style type="text/css">fieldset, label { margin: 0; padding: 0; }.rating {   border: none;  float: left;}.rating > input { display: none; } .rating > label:before {   margin: 5px;  font-size: 1.25em;  font-family: FontAwesome;  display: inline-block;  content: "\f005";}.rating > .half:before {   content: "\f089";  position: absolute;}.rating > label {   color: #ddd;  float: right; }/***** CSS Magic to Highlight Stars on Hover *****/.rating > input:checked ~ label, /* show gold star when clicked */.rating:not(:checked) > label:hover, /* hover current star */.rating:not(:checked) > label:hover ~ label { color: #FFD700;  } /* hover previous stars in list */.rating > input:checked + label:hover, /* hover current star when changing rating */.rating > input:checked ~ label:hover,.rating > label:hover ~ input:checked ~ label, /* lighten current selection */.rating > input:checked ~ label:hover ~ label { color: #FFED85;  }#rating_box{    display: inline-block;margin-left: 49px;} 
.jquery-comments ul.main li.comment.edit > .comment-wrapper > *:not(.commenting-field, .rating) {
    display: none !important;
}
#comment-list .rating > input:checked ~ label, .rating:not(:checked) > label:hover, .rating:not(:checked) > label:hover ~ label {
    /* color: #FFD700; */
}
.rating{
  display: block !important;
}

</style>
<link rel="stylesheet" type="text/css" id="jquery-comments-css" href="<?php echo base_url();?>/assets/plugins/jquery-comments/css/jquery-comments.css">
<link href="<?php echo base_url();?>modules/whiteboard/_assets/literallycanvas.css" rel="stylesheet">
<link href="<?php echo base_url();?>modules/whiteboard/assets/css/style.css" rel="stylesheet">
 <script src="<?php echo base_url();?>modules/whiteboard/_js_libs/react-0.14.3.js"></script>
<script src="<?php echo base_url();?>modules/whiteboard/_js_libs/literallycanvas.js"></script>
<div id="wrapper">
    <div class="content">
        <div class="row">
        	<?php
            if(isset($whiteboard)){
                echo form_hidden('is_edit','true');
            }
            ?>
            <?php //echo form_open_multipart($this->uri->uri_string(),array('id'=>'whiteboard-form')) ;?>
            <?php echo render_input('staffid','', get_staff_user_id(), 'hidden'); ?>
            <?php $value = (isset($whiteboard) ? $whiteboard->whiteboard_content : ''); ?>
            <input type="hidden" name="whiteboard_id" value="<?php echo $whiteboard->id;?>">
            <textarea  id="whiteboard_content" name="whiteboard_content"><?php echo $value;?></textarea>
            <div class="col-lg-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $whiteboard->title; ?>
                        
                        <div class="btn-group " style="float: right;margin-right:2px;">

                          <button type="button" class="btn btn-primary dropdown-toggle waves-effect waves-effect waves-light waves-ripple" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><font style="vertical-align: inherit;"><font style="vertical-align: inherit;">
                           Action </font></font><span class="caret"></span>
                           </button>
                                   <!-- <a  class="btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                   Action <span class="caret"></span> -->
                                   <!-- </a> -->
                                   <ul class="dropdown-menu dropdown-menu-right width200 project-actions">
                                    <li>
                                     <a href="<?php echo base_url('whiteboard/whiteboard_create/'.$whiteboard->id);?>" type="button">Edit</a>
                                   </li>
                                   <li>
                                    <a  id="delete-button" type="button" href="<?php echo base_url('whiteboard/delete/'.$whiteboard->id); ?>">Delete</a>
                                   </li>
                                   <!-- <li>
                                      <a onclick="generatePDF()" href="javascript:void(0)">Download PDF</a>
                                   </li> -->
                                   <li>
                                      <!-- <a id="print"  href="javascript:void(0)">Print</a> -->
                                   </li>
                                   <li>
                                     <a  href="javascript:void(0)" onclick="send_email_modal();return false;">Share via email</a>
                                   </li>
                                   <li>
                                      <a href="javascript:void(0)" onclick="new_discussion();return false;" >Help</a>
                                   </li>
                                 
                                 </ul>
                                </div>
                        <span><a href="#" onclick="new_whiteboard();return false;" class="btn btn-success" style="float: right;margin-right:2px;">Properties</a></span>
                            <!--  <span><button id="expand-button" type="button" class="collapsible btn btn-success">Properties</button></span> -->
                        </h4>
                        <hr class="hr-panel-heading" />
                        <div class="row">
                            <div class="col-md-12">
                            <div class="literally localstorage"></div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="btn-bottom-toolbar text-right">
                    <button type="button" class="btn btn-info whiteboard-btns"><?php echo _l('submit'); ?></button>
                </div>
            </div>
            <?php //echo form_close(); ?>
        </div>
        <div class="panel_s">
            <div class="panel-body">                 
                <div id="rating_box">
                       <label style="float: left;padding: 10px 0px;">Rating: </label>                   
                       <fieldset class="rating">
                       <input type="radio" id="star5" name="rating" value="5" />
                       <label class = "full" for="star5" title="Awesome - 5 stars"></label>
                       <input type="radio" id="star4half" name="rating" value="4.5" />
                       <label class="half" for="star4half" title="Pretty good - 4.5 stars"></label>
                       <input type="radio" id="star4" name="rating" value="4" />
                       <label class = "full" for="star4" title="Pretty good - 4 stars"></label>
                       <input type="radio" id="star3half" name="rating" value="3.5" />
                       <label class="half" for="star3half" title="Meh - 3.5 stars"></label>
                       <input type="radio" id="star3" name="rating" value="3" />
                       <label class = "full" for="star3" title="Meh - 3 stars"></label> 
                       <input type="radio" id="star2half" name="rating" value="2.5" />
                       <label class="half" for="star2half" title="Kinda bad - 2.5 stars"></label>
                       <input type="radio" id="star2" name="rating" value="2" />
                       <label class = "full" for="star2" title="Kinda bad - 2 stars"></label>
                       <input type="radio" id="star1half" name="rating" value="1.5" />
                       <label class="half" for="star1half" title="Meh - 1.5 stars"></label> 
                       <input type="radio" id="star1" name="rating" value="1" />
                       <label class = "full" for="star1" title="Sucks big time - 1 star"></label>
                       <input type="radio" id="starhalf" name="rating" value="0.5" />
                       <label class="half" for="starhalf" title="Sucks big time - 0.5 stars"></label>
                       </fieldset>
                       <label id="rating_value" style="padding: 10px;"></label>                                                 
                </div>
                <div id="whiteboard-comments"></div>                 
            </div>
                       <input type="hidden" name="whiteboard_id" value="<?php echo $whiteboard->id;?>">
        </div>
        <div class="btn-bottom-pusher"></div>
    </div>
</div>

<div class="modal fade whiteboard-modal" id="whiteboard_edit" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-lg">
        <?php echo form_open_multipart($this->uri->uri_string(),array('id'=>'whiteboard-form')) ;?>
            <?php echo render_input('staffid','', get_staff_user_id(), 'hidden'); ?>
        <div class="modal-content data">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">
                    <span class="edit-title hide"><?php echo _l('whiteboard_create_new'); ?></span>
                    <span class="add-title"><?php echo 'Edit'; ?></span>
                </h4>
            </div>
                <div class="panel-body">
                       
                        <hr>
                         <?php $value = (isset($whiteboard) ? $whiteboard->title : ''); ?>
                        <?php echo render_input('title','Title',$value); ?>

                        <?php
                        $selected = (isset($whiteboard) ? $whiteboard->whiteboard_group_id : '');
                        if(is_admin() || get_option('staff_members_create_inline_whiteboard_group') == '1'){
                            echo render_select_with_input_group('whiteboard_group_id',$whiteboard_groups,array('id','name'),'whiteboard_group',$selected,'<a href="#" onclick="new_group();return false;"><i class="fa fa-plus"></i></a>');
                        } else {
                            echo render_select('whiteboard_group_id',$whiteboard_groups,array('id','name'),'whiteboard_group',$selected);
                        }
                        ?>

                        <?php $value = (isset($whiteboard) ? $whiteboard->description : ''); ?>
                        <?php echo render_textarea('description','Description',$value,array('rows'=>4),array()); 
                         $selected = (isset($whiteboard) ? $whiteboard->project_id : '');
                        echo render_select('project_id',$projects,array('id','name'),'project_group',$selected);?>

                    </div>
                    <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info whiteboard-btn" data-loading-text="Please wait..." data-autocomplete="off" data-form="#whiteboard-form">Save</button>
            </div>
        </div>
        

       <?php echo form_close();?>
    </div>
</div>
 <div class="modal fade in" id="discussion" tabindex="-1" role="dialog">
  <div class="modal-dialog">                                                           
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          <h4 class="modal-title">
            <span class="edit-title hide">Help</span>
            <span class="add-title">Help</span>
          </h4>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12">

             <p> 1. To create a whiteboard start be using the tools in the tool bar </p>
            <p> 2. Click on Properties buttons to add a title, Description, group, assign to a group or project.</p>
            <p> 3. To email the whiteboard via email, click on “Action” button email pdf</p>
            <p> 4. To assign a whiteboard to a project click on “Properties” and select</p>

              <br />
            </div>
          </div>
        </div>
      </div><!-- /.modal-content -->
     </div><!-- /.modal-dialog -->
  </div>
<?php $this->load->view('whiteboard/whiteboard_group'); ?>
<!--- enter email modal ---------->
  <div class="modal fade in" id="email_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog">                                                           
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          <h4 class="modal-title">
            <span class="edit-title hide">Add Email</span>
            <span class="add-title">Add Email</span>
          </h4>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12">
                <!-- <div class="form-group" app-field-wrapper="from">
                <label for="from" class="control-label"> 
                <small class="req text-danger">* </small>from</label><input type="text" id="from" name="from" class="form-control" value="">
                </div> -->
                
           <?php
             $i = 0;
             $selected = '';
             foreach($staff as $member){
              if(isset($proposal)){
                if($proposal->assigned == $member['staffid']) {
                  $selected = $member['staffid'];
                }
              }
              $i++;
             }
             echo render_select('from',$staff,array('staffid',array('firstname','lastname')),'from',$selected);
             ?>
                         
                <input type="hidden" id="from" name="from" class="form-control" value="<?php echo !empty(get_option('companyname')) ? get_option('companyname') : 'Perfex'; ?>">
                <div class="form-group" app-field-wrapper="subject">
                <label for="subject" class="control-label"> 
                <!-- <small class="req text-danger">* </small> -->
                Subject</label><input type="text" id="subject" name="subject" class="form-control" value="">
                </div>
                 <?php
             
             $selectede = '';
             foreach($contacts as $contact){
            
             }
             
              echo render_select('sent_to', $contacts, array('id','email','firstname,lastname'), 'Email to',$selectede);
             ?>
                <div class="form-group" app-field-wrapper="content">
                <label for="content" class="control-label"> 
                Content</label>
               <textarea id="content" name="content" class="form-control" rows="4"><p>Hi, </p><p>You’ve been invited to view a whiteboard with {crmcompanyname}, you can view the whiteboard by clicking on the link below.</p><p>Association of Enterpreneurs</p><p>Many Thanks<br data-mce-bogus="1"></p><p>
                </textarea>
                </div>
                
                <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" data-loading-text="Please wait..." data-autocomplete="off" id="sendEmail">Save</button>
            </div>
            
            </div>
          </div>
        </div>
      </div><!-- /.modal-content -->
     </div><!-- /.modal-dialog -->
  </div>
<?php init_tail(); ?>

<script src="<?php echo base_url();?>modules/whiteboard/assets/js/style.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $('#print').click(function(){
        window.print();
     });
    });
     var whiteboard_id = $('input[name="whiteboard_id"]').val();
    tinymce.init({
            selector: '#content',
            branding: false
        });
    function new_discussion() {
         $('#discussion').modal('show');
      }
    function new_whiteboard(){
        $('#whiteboard_edit').modal('show');
    }
    $("button.whiteboard-btn").on('click', function (e) {

         if($('#title').val() == '' || $('#whiteboard_group_id').val() == '' || $('#description').val() == ''){
                validate_whiteboard_form();
            }else{
                $('#whiteboard-form').submit();
            }
    });
    function validate_whiteboard_form(){
    appValidateForm($('#whiteboard-form'), {
        title: 'required',
        whiteboard_group_id: 'required',
        description : 'required',
    });
    $('#whiteboard-form').submit();
  }

 $('#sendEmail').click(function(){
     var eml = $('#sent_to').val();
     var from = $('#from').val();
     var subject = $('#subject').val();
     var content = tinymce.get("content").getContent();
     if(eml == '' || from == '' || subject =='' || content == ''){
        $('.btn-info').attr('disabled', false);
        $('.btn-info').removeClass('disabled');
        $('.btn-info').text('Save');
        return false; 
     }
    send_Email();
 });
  function send_email_modal(){
      $('#email_modal').modal('show');
      
  }
 function send_Email(){
    var eml = $('#sent_to').val();
     var from = $('#from').val();
     var subject = $('#subject').val();
     var content = tinymce.get("content").getContent();
     console.log($('#email').val());
     
          let data = {
            'whiteboard_id':whiteboard_id,'eml':eml,'subject':subject,'from':from,'content':content,
          };
          $.post( admin_url + 'whiteboard/sendEmail', data);
          //console.log( data );
          $('#email_modal').modal('hide');
          $('#email').val('');
          $('.disabled').removeClass('disabled');
       
       
     }
function update_rating(id){
    var updated_rating = $('input[name="rating_'+id+'"]:checked').val();
   console.log(updated_rating);
   $.ajax({
          type: 'post',
          url: admin_url + 'whiteboard/update_discussion_comment_rating',
          data: {'id':id,'rating':updated_rating},
          success: function(comment) {
            console.log(updated_rating)
            $('#rt_val_'+id).text(updated_rating+'/5');
          },
          // error: error
        });
}
var gantt_data = {};
   <?php if(isset($gantt_data)){ ?>
   gantt_data = <?php echo json_encode($gantt_data); ?>;
   <?php } ?>
   var whiteboard_id = $('input[name="whiteboard_id"]').val();
   var discussion_user_profile_image_url = $('input[name="discussion_user_profile_image_url"]').val();
   var current_user_is_admin = $('input[name="current_user_is_admin"]').val();
   var project_id = $('input[name="project_id"]').val(1);
   if(typeof(whiteboard_id) != 'undefined'){
     discussion_comments('#whiteboard-comments',whiteboard_id,'regular');
   }
   $(function(){
    var project_progress_color = '<?php echo hooks()->apply_filters('admin_project_progress_color','#84c529'); ?>';
    var circle = $('.project-progress').circleProgress({fill: {
     gradient: [project_progress_color, project_progress_color]
   }}).on('circle-animation-progress', function(event, progress, stepValue) {
     $(this).find('strong.project-percent').html(parseInt(100 * stepValue) + '<i>%</i>');
   });
   });

   function discussion_comments(selector,whiteboard_id,discussion_type){
     var defaults = _get_jquery_comments_default_config(<?php echo json_encode(get_project_discussions_language_array()); ?>);
     var options = {
      // https://github.com/Viima/jquery-comments/pull/169
      wysiwyg_editor: {
            opts: {
                enable: true,
                is_html: true,
                container_id: 'editor-container',
                comment_index: 0,
            },
            init: function (textarea, content) {
                var comment_index = textarea.data('comment_index');
                 var editorConfig = _simple_editor_config();
                 editorConfig.setup = function(ed) {
                      textarea.data('wysiwyg_editor', ed);

                      ed.on('change', function() {
                          var value = ed.getContent();
                          if (value !== ed._lastChange) {
                            ed._lastChange = value;
                            textarea.trigger('change');
                          }
                      });

                      ed.on('keyup', function() {
                        var value = ed.getContent();
                          if (value !== ed._lastChange) {
                            ed._lastChange = value;
                            textarea.trigger('change');
                          }
                      });

                      ed.on('Focus', function (e) {
                        textarea.trigger('click');
                      });

                      ed.on('init', function() {
                        if (content) ed.setContent(content);
                      })
                  }

                var editor = init_editor('#'+ this.get_container_id(comment_index), editorConfig)
            },
            get_container: function (textarea) {
                if (!textarea.data('comment_index')) {
                    textarea.data('comment_index', ++this.opts.comment_index);
                }
                return $('<div/>', {
                    'id': this.get_container_id(this.opts.comment_index)
                });
            },
            get_contents: function(editor) {
              console.log('edit');
               return editor.getContent();
            },
            on_post_comment: function(editor, evt) {
               editor.setContent('');
            },
            get_container_id: function(comment_index) {
              var container_id = this.opts.container_id;
              if (comment_index) container_id = container_id + "-" + comment_index;
              return container_id;
            }
        },
      currentUserIsAdmin:current_user_is_admin,
      getComments: function(success, error) {
        $.get(admin_url + 'whiteboard/get_discussion_comments/'+whiteboard_id+'/'+discussion_type,function(response){
          success(response);
        },'json');
      },
      postComment: function(commentJSON, success, error) {

        commentJSON.rating = $('input[name="rating"]:checked').val();
        console.log(commentJSON);
        $.ajax({
          type: 'post',
          url: admin_url + 'whiteboard/add_discussion_comment/'+whiteboard_id+'/'+discussion_type,
          data: commentJSON,
          success: function(comment) {
            comment = JSON.parse(comment);
            success(comment)
          },
          error: error
        });
      },
      putComment: function(commentJSON, success, error) {
          commentJSON.updated_rating = $('input[name="rating_'+commentJSON.id+'"]:checked').val();
      console.log(commentJSON.updated_rating);
        $.ajax({
          type: 'post',
          url: admin_url + 'whiteboard/update_discussion_comment',
          data: commentJSON,
          success: function(comment) {
            comment = JSON.parse(comment);
            success(comment)
          },
          error: error
        });
      },
      deleteComment: function(commentJSON, success, error) {
        $.ajax({
          type: 'post',
          url: admin_url + 'whiteboard/delete_discussion_comment/'+commentJSON.id,
          success: success,
          error: error
        });
      },
      uploadAttachments: function(commentArray, success, error) {
        var responses = 0;
        var successfulUploads = [];
        var serverResponded = function() {
          responses++;
            // Check if all requests have finished
            if(responses == commentArray.length) {
                // Case: all failed
                if(successfulUploads.length == 0) {
                  error();
                // Case: some succeeded
              } else {
                successfulUploads = JSON.parse(successfulUploads);
                success(successfulUploads)
              }
            }
          }
          $(commentArray).each(function(index, commentJSON) {
            // Create form data
            var formData = new FormData();
            if(commentJSON.file.size && commentJSON.file.size > app.max_php_ini_upload_size_bytes){
             alert_float('danger',"<?php echo _l("file_exceeds_max_filesize"); ?>");
             serverResponded();
           } else {
            $(Object.keys(commentJSON)).each(function(index, key) {
              var value = commentJSON[key];
              if(value) formData.append(key, value);
            });

            if (typeof(csrfData) !== 'undefined') {
               formData.append(csrfData['token_name'], csrfData['hash']);
            }
            $.ajax({
              url: admin_url + 'whiteboard/add_discussion_comment/'+whiteboard_id+'/'+discussion_type,
              type: 'POST',
              data: formData,
              cache: false,
              contentType: false,
              processData: false,
              success: function(commentJSON) {
                successfulUploads.push(commentJSON);
                serverResponded();
              },
              error: function(data) {
               var error = JSON.parse(data.responseText);
               alert_float('danger',error.message);
               serverResponded();
             },
           });
          }
        });
        }
      }
      var settings = $.extend({}, defaults, options);
    $(selector).comments(settings);
   }
</script>
</body>
</html>