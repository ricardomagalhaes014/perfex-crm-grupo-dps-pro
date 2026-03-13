<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';
	Dropzone.autoDiscover = false;
	
	$(function() {
		'use strict';

	  // On document read check and init for client ajax-search
		real_init_ajax_search("customer", "#clientid.ajax-search");
		init_ajax_project_search_by_customer_id();
		if ($('#contract-attachments-form').length > 0) {
			new Dropzone("#contract-attachments-form", appCreateDropzoneOptions({
				success: function(file) {
					if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length ===
						0) {
						var location = window.location.href;
					window.location.href = location.split('?')[0] + '?tab=attachments';
				}
			}
		}));
		}

		if (typeof(Dropbox) != 'undefined' && $('#dropbox-chooser').length > 0) {
			document.getElementById("dropbox-chooser").appendChild(Dropbox.createChooseButton({
				success: function(files) {
					$.post(site_url + 'realestate/broker/add_external_attachment', {
						files: files,
						contract_id: contract_id,
						external: 'dropbox'
					}).done(function() {
						var location = window.location.href;
						window.location.href = location.split('?')[0] + '?tab=attachments';
					});
				},
				linkType: "preview",
				extensions: app.options.allowed_files.split(','),
			}));
		}

		appValidateForm($('#contract-form'), {
			client: 'required',
			datestart: 'required',
			subject: 'required'
		});

		appValidateForm($('#renew-contract-form'), {
			new_start_date: 'required'
		});

		init_tinymce_inline_editor({
			saveUsing: save_contract_content,
			onSetup: function(editor) {
				editor.on('MouseDown ContextMenu', function() {
					if (!is_mobile() && !$('.left-column').hasClass('hide')) {
						contract_full_view();
					}
				});
			}
		})
	});

	function save_contract_content(manual) {
		'use strict';

		var editor = tinyMCE.activeEditor;
		var data = {};
		data.contract_id = contract_id;
		data.content = editor.getContent();
		$.post(site_url + 'realestate/broker/save_contract_data', data).done(function(response) {
			response = JSON.parse(response);
			if (typeof(manual) != 'undefined') {
			// Show some message to the user if saved via CTRL + S
				alert_float('success', response.message);
			}
		// Invokes to set dirty to false
			editor.save();
		}).fail(function(error) {
			var response = JSON.parse(error.responseText);
			alert_float('danger', response.message);
		});
	}

	function delete_contract_attachment(wrapper, id) {
		'use strict';

		if (confirm_delete()) {
			$.get(site_url + 'realestate/broker/delete_contract_attachment/' + id, function(response) {
				if (response.success == true) {
					$(wrapper).parents('.contract-attachment-wrapper').remove();

					var totalAttachmentsIndicator = $('.attachments-indicator');
					var totalAttachments = totalAttachmentsIndicator.text().trim();
					if (totalAttachments == 1) {
						totalAttachmentsIndicator.remove();
					} else {
						totalAttachmentsIndicator.text(totalAttachments - 1);
					}
				} else {
					alert_float('danger', response.message);
				}
			}, 'json');
		}
		return false;
	}

	function insert_merge_field(field) {
		'use strict';

		var key = $(field).text();
		tinymce.activeEditor.execCommand('mceInsertContent', false, key);
	}

	function contract_full_view() {
		'use strict';

		$('.left-column').toggleClass('hide');
		$('.right-column').toggleClass('col-md-7');
		$('.right-column').toggleClass('col-md-12');
		$(window).trigger('resize');
	}

	function add_contract_comment() {
		'use strict';

		var comment = $('#comment').val();
		if (comment == '') {
			return;
		}
		var data = {};
		data.content = comment;
		data.contract_id = contract_id;
		$('body').append('<div class="dt-loader"></div>');
		$.post(site_url + 'realestate/broker/add_comment', data).done(function(response) {
			response = JSON.parse(response);
			$('body').find('.dt-loader').remove();
			if (response.success == true) {
				$('#comment').val('');
				get_contract_comments();
			}
		});
	}

	function get_contract_comments() {
		'use strict';

		if (typeof(contract_id) == 'undefined') {
			return;
		}
		requestGet('realestate/broker/get_contract_comments/' + contract_id).done(function(response) {
			$('#contract-comments').html(response);
			var totalComments = $('[data-commentid]').length;
			var commentsIndicator = $('.comments-indicator');
			if (totalComments == 0) {
				commentsIndicator.addClass('hide');
			} else {
				commentsIndicator.removeClass('hide');
				commentsIndicator.text(totalComments);
			}
		});
	}

	function remove_contract_comment(commentid) {
		'use strict';

		if (confirm_delete()) {
			requestGetJSON('realestate/broker/remove_contract_comment/' + commentid).done(function(response) {
				if (response.success == true) {

					var totalComments = $('[data-commentid]').length;

					$('[data-commentid="' + commentid + '"]').remove();

					var commentsIndicator = $('.comments-indicator');
					if (totalComments - 1 == 0) {
						commentsIndicator.addClass('hide');
					} else {
						commentsIndicator.removeClass('hide');
						commentsIndicator.text(totalComments - 1);
					}
				}
			});
		}
	}

	function edit_contract_comment(id) {
		'use strict';

		var content = $('body').find('[data-contract-comment-edit-textarea="' + id + '"] textarea').val();
		if (content != '') {
			$.post(site_url + 'realestate/broker/edit_comment/' + id, {
				content: content
			}).done(function(response) {
				response = JSON.parse(response);
				if (response.success == true) {
					alert_float('success', response.message);
					$('body').find('[data-contract-comment="' + id + '"]').html(nl2br(content));
				}
			});
			toggle_contract_comment_edit(id);
		}
	}

	function toggle_contract_comment_edit(id) {
		'use strict';

		$('body').find('[data-contract-comment="' + id + '"]').toggleClass('hide');
		$('body').find('[data-contract-comment-edit-textarea="' + id + '"]').toggleClass('hide');
	}

	function contractGoogleDriveSave(pickData) {
		'use strict';
		
		var data = {};
		data.contract_id = contract_id;
		data.external = 'gdrive';
		data.files = pickData;
		$.post(site_url + 'realestate/broker/add_external_attachment', data).done(function() {
			var location = window.location.href;
			window.location.href = location.split('?')[0] + '?tab=attachments';
		});
	}
</script>