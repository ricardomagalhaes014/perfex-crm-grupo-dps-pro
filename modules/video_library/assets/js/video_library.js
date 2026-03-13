"use-strict";
var video_id = '';
function init_add_video()
{
	requestGet('video_library/get_video_data').done(function (response) {
    $('#modal-wrapper').html(response);
    $('#add_video').modal({
     show: true,
     backdrop: 'static',
     keyboard: false
   });
    $('.selectpicker').selectpicker();
  })
} 

function init_add_categeory()
{
 requestGet('video_library/add_video_categeory').done(function (response) {
  $('#wrapper-modal').html(response);
  $('#add_library_categeory').modal({
   show: true,
   backdrop: 'static',
   keyboard: false
 });
  $('.selectpicker').selectpicker();

})
}

function add_categeory_form()
{
 var form = $('#category_form');
 var validationObject = {
   add_category: 'required',

 };
 appValidateForm(form, validationObject);
 if(form.valid()){
   $.ajax({
     type: "POST",
     url: admin_url+'video_library/add_category_data',
     data:  $('#category_form').serialize(),
     success: function(resp){
       if(resp)
       {
         alert_float('success','Category Added');
         $("#add_library_categeory").modal('hide');
       }
       else
       {
         alert_float('danger','Category Addtion Failed');
         $("#add_library_categeory").modal('hide');
       }
       $('.table-video_library').DataTable().ajax.reload();
     },

   });

 }
}
function update_categeory_form()
{
 var form = $('#update-category-form');
 var validationObject = {
   update_category: 'required',

 };
 appValidateForm(form, validationObject);
 if(form.valid()){
  var formData = form.serialize();
   $.ajax({
     type: "POST",
     url: admin_url+'video_library/update_category_data',
     data:  formData,
     headers: {
      'X-CSRF-Token': csrfData.formatted.csrf_token_name
    },
     success: function(resp){
       if(resp)
       {
         alert_float('success','Category Updated!');
         $("#edit_category_data").modal('hide');
       }
       else
       {
         alert_float('danger','Category Updation Failed');
         $("#edit_category_data").modal('hide');
       }
       $('.table-video_library').DataTable().ajax.reload();
     },

   });

 }
}
function add_video_data()
{
 var form = $('#add_video_form');
 var validationObject = {
   video_title: 'required',
   category: 'required',
   upload_video: 'required',    

 };
 appValidateForm(form, validationObject);
 if(form.valid()){
   $.ajax({
     type: "POST",
     url: admin_url+'video_library/add_video_data',
     data:{
      data:new FormData($("#add_video_form")[0])
    },
    headers: {
      'X-CSRF-Token': csrfData.formatted.csrf_token_name
    },
    processData:false,
    contentType:false,

    success: function(resp){
     console.log(resp);
     if(resp)
     {
       alert_float('success','Category Added');
       $("#add_library_categeory .close").click()
     }
     else
     {
       alert_float('danger','Category Addtion Failed');
     }

   },

 });

 }

}

$("#category").change(function(){
  var cats = $(this).val();
  var title = $('#search').val();
  var url = admin_url+'video_library/search_category_data';
  $.post(url, {categories:cats, title:title}, function(res){
    $('#video_div').html(res);
  });
});

function video_search_by_title()
{
 var cats= $('#category').val();
 var title = $('#search').val();
 var url = admin_url+'video_library/search_category_data';
 $.post(url, {categories:cats, title:title}, function(res){
  $('#video_div').html(res);
});
}
function edit_category(e){
  requestGet('video_library/edit_category_data/'+$(e).data('id')).done(function (response) {
   $('#wrapper-modal').html(response);
   $('#edit_category_data').modal({
     show: true,
     backdrop: 'static',
     keyboard: false
   });
   $('.selectpicker').selectpicker();
 })
}
function loadVideosGridView() {
  var a = {
    search: $("input#search").val(),
    start: 0,
    length: _lnth,
    draw: 1,
    video_id: $("input#video_id").val(),
    order: [{
      column: 1,
      dir: 'desc'
    }]
  };
  videosGridViewDataCall(a, function(b) {
    $("div#video_div").html(b)
  });
}
function videosGridViewDataCall(a, b, c) {
  $.ajax({
    url: admin_url + "video_library/video_grid/" + (a.start + 1),
    method: "POST",
    data: a,
    async: true,
    error: function(d, m, h) {
      console.log("error API", h)
    },
    beforeSend: function() {},
    complete: function() {},
    success: function(d) {
      $.isFunction(b) && b.call(this, d)
    }
  });
}
var discussion_user_profile_image_url = '';
var current_user_is_admin = '';
var get_project_discussions_language_array = '{"discussion_add_comment":"Add comment","discussion_newest":"Newest","discussion_oldest":"Oldest","discussion_attachments":"Attachments","discussion_send":"Send","discussion_reply":"Answer","discussion_edit":"Edit","discussion_edited":"Modified","discussion_you":"You","discussion_save":"Save","discussion_delete":"Delete","discussion_view_all_replies":"Show all replies","discussion_hide_replies":"Hide replies","discussion_no_comments":"No comments","discussion_no_attachments":"No attachments","discussion_attachments_drop":"Drag and drop to upload file"}';
function video_discussion_comments(selector,video_id,discussion_type){
  var defaults = _get_jquery_comments_default_config({"discussion_add_comment":"Add comment","discussion_newest":"Newest","discussion_oldest":"Oldest","discussion_attachments":"Attachments","discussion_send":"Send","discussion_reply":"Answer","discussion_edit":"Edit","discussion_edited":"Modified","discussion_you":"You","discussion_save":"Save","discussion_delete":"Delete","discussion_view_all_replies":"Show all replies","discussion_hide_replies":"Hide replies","discussion_no_comments":"No comments","discussion_no_attachments":"No attachments","discussion_attachments_drop":"Drag and drop to upload file"});
  var n = 1 + Math.floor(Math.random() * 6);
  var options = {
    wysiwyg_editor: {
      opts: {
        enable: true,
        is_html: true,
        container_id: 'editor-container',
        comment_index: n,
      },
      init: function (textarea, content) {
        console.log('adsd'+options.wysiwyg_editor.opts.enable);
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
          });
        }
        var editor = init_editor('#'+ this.get_container_id(comment_index), editorConfig)
        console.log('#'+ this.get_container_id(comment_index));
        
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
    $.get(admin_url + 'video_library/get_video_comments/'+video_id+'/'+discussion_type,function(response){
      success(response);
    },'json');
  },
  postComment: function(commentJSON, success, error) {
    $.ajax({
      type: 'post',
      url: admin_url + 'video_library/add_discussion_comment/'+video_id+'/'+discussion_type,
      data: commentJSON,
      success: function(comment) {
        comment = JSON.parse(comment);
        success(comment)
      },
      error: error
    });
  },
  putComment: function(commentJSON, success, error) {
    $.ajax({
      type: 'post',
      url: admin_url + 'video_library/update_discussion_comment',
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
      url: admin_url + 'video_library/delete_discussion_comment/'+commentJSON.id,
      success: success,
      error: error
    });
  },
  uploadAttachments: function(commentArray, success, error) {
    var responses = 0;
    var successfulUploads = [];
    var serverResponded = function() {
      responses++;
      if(responses == commentArray.length) {
        if(successfulUploads.length == 0) {
          error();
        } else {
          successfulUploads = JSON.parse(successfulUploads);
          success(successfulUploads)
        }
      }
    }
    $(commentArray).each(function(index, commentJSON) {
      var formData = new FormData();
      if(commentJSON.file.size && commentJSON.file.size > app.max_php_ini_upload_size_bytes){
       alert_float('danger',"The uploaded file exceeds the upload_max_filesize directive in php.ini");
       serverResponded();
     }else{
      $(Object.keys(commentJSON)).each(function(index, key) {
        var value = commentJSON[key];
        if(value) formData.append(key, value);
      });
      if(typeof(csrfData) !== 'undefined') {
        formData.append(csrfData['token_name'], csrfData['hash']);
      }
      $.ajax({
        url: admin_url + 'video_library/add_discussion_comment/'+video_id+'/'+discussion_type,
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
$(document).on('click','a.discussion_link',function(e){
  video_id = $(this).data('id');
  $("#comments-modal").modal('show');
  video_discussion_comments('#video-comments',video_id,'regular');
});