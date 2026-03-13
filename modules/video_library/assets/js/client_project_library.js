"use strict";
var _lnth = 6;
var video_id = '';
$(function(){
    var baseUrl = (window.location).href; // You can also use document.URL
    var project_id = baseUrl.substring(baseUrl.lastIndexOf('=') + 1);
    var node = '<li role="presentation" class="project_tab_video_library"><a data-group="project_video_library" href="'+baseUrl+'" role="tab"><i class="fa fa-video-camera" aria-hidden="true"></i> Video library </a></li>';
    $('.nav-tabs').append(node);
    loadVideosGridView();
});
function loadVideosGridView() {
    $(".header-user-profile").on("click", function() {
        $(".header-user-profile").toggleClass("open")
    });
    var a = {
        search: $("input#search").val(),
        start: 0,
        length: _lnth,
        draw: 1,
        video_id: $("input#video_id").val(),
        project_id: $("input[name='project_id']").val(),
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
        url: site_url + "video_library/client/video_grid/" + (a.start + 1),
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
$(document).on('click','a.paginate',function(e){
    e.preventDefault();
    var pageno = $(this).data('ci-pagination-page');
    var formData = {
        start: (pageno-1),
        length: _lnth,
        draw: 1,
        cat_ids_arr: $('input[name="cat_ids_arr"]').val(),
        sortBy: 'desc',
        order: [{
            column: 0,
            dir: 'desc'
        }]
    }
    videosGridViewDataCall(formData, function (resposne) {
        $('div#video_div').html(resposne)
    });
});
function video_search_by_title()
{
 var cats= $('#category').val();
 var title = $('#search').val();
 var url = site_url + 'video_library/client/search_category_data';
 $.post(url, {categories:cats, title:title}, function(res){
  $('#video_div').html(res);
});
}
$("#category").change(function(){
  var cats = $(this).val();
  var title = $('#search').val();
  var url = site_url + 'video_library/client/search_category_data';
  $.post(url, {categories:cats, title:title}, function(res){
    $('#video_div').html(res);
});
});
var discussion_user_profile_image_url = '';
var current_user_is_admin = '';
var get_project_discussions_language_array = '{"discussion_add_comment":"Add comment","discussion_newest":"Newest","discussion_oldest":"Oldest","discussion_attachments":"Attachments","discussion_send":"Send","discussion_reply":"Answer","discussion_edit":"Edit","discussion_edited":"Modified","discussion_you":"You","discussion_save":"Save","discussion_delete":"Delete","discussion_view_all_replies":"Show all replies","discussion_hide_replies":"Hide replies","discussion_no_comments":"No comments","discussion_no_attachments":"No attachments","discussion_attachments_drop":"Drag and drop to upload file"}';
function discussion_comments(selector,video_id,discussion_type){
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
    $.get(site_url + 'video_library/client/get_video_comments/'+video_id+'/'+discussion_type,function(response){
      success(response);
    },'json');
  },
  postComment: function(commentJSON, success, error) {
    $.ajax({
      type: 'post',
      url: site_url + 'video_library/client/add_discussion_comment/'+video_id+'/'+discussion_type,
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
      url: site_url + 'video_library/client/update_discussion_comment',
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
      url: site_url + 'video_library/client/delete_discussion_comment/'+commentJSON.id,
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
        url: site_url + 'video_library/client/add_discussion_comment/'+video_id+'/'+discussion_type,
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
  discussion_comments('#video-comments',video_id,'regular');
});