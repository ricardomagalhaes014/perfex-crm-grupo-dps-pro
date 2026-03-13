<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php 
if(is_broker_logged_in()){
	broker_init_head();
}else{
	init_head();
}
?>

<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">

				<div class="panel_s">
					<div class="panel-body accounting-template">               
						<div class="clearfix"></div>
						<div class="row">
							<div class="col-md-6">

								<div class="tw-flex tw-flex-wrap tw-items-center">
									<div id="project_view_name" class="tw-mr-3 tw-max-w-[350px]">
										<div class="tw-w-full">
											<h4 class="tw-font-semibold">
												<?php echo _l('real_Property_Detail'); ?>
											</h4>
											<div class="row tw-font-semibold">
												<?php if($property_listing->status == 'pending'){ ?>
													<div class="col-md-12"><?php echo _l('rel_status').': '.render_real_property_listing_status_html($property_listing->id, 'property_listing', $property_listing->status, false, $property_listing->related_id, $property_listing->related_type); ?></div>
												<?php }else{ ?>
													<div class="col-md-12"><?php echo _l('rel_status').': '.render_real_property_listing_status_html($property_listing->id, 'property_listing', $property_listing->status, true, $property_listing->related_type); ?></div>
												<?php } ?>
											</div>
										</div>
									</div>
									<div class="visible-xs">
										<div class="clearfix"></div>
									</div>


								</div>
							</div>
							<?php
							$checked = '';
							$favourite_text = _l('rel_add_listing_to_favorites');
							if (isset($favorite_listings[$property_listing->id])) {
								$checked = 'checked';
								$favourite_text = _l('rel_remove_listing_to_favorites');
							}

							?>
							<div class="col-md-4 hide">
								<div class="pull-right">
									<div class="onoffswitch">
										<input type="checkbox" data-switch-url="<?php echo html_entity_decode($site_url) . ('change_favorite') ?>" name="onoffswitch" class="onoffswitch-checkbox" id="c_<?php echo html_entity_decode($property_listing->id) ?>" data-id="<?php echo html_entity_decode($property_listing->id) ?>" <?php echo new_html_entity_decode($checked); ?>>
										<label class="onoffswitch-label" for="c_<?php echo html_entity_decode($property_listing->id) ?>"></label>
										</div><?php echo new_html_entity_decode($favourite_text); ?>
									</div>
								</div>

								<div class="col-md-2 hide">
									<div class="pull-right">
										<div class="onoffswitch">
											<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox add_to_compare_list" id="compare_list_<?php echo html_entity_decode($property_listing->id) ?>" data-id="<?php echo html_entity_decode($property_listing->id) ?>" data-type="add_to_compare" >
											<label class="onoffswitch-label" for="compare_list_<?php echo html_entity_decode($property_listing->id) ?>"></label>
											</div><?php echo _l('rel_add_to_compare_list'); ?>
										</div>
									</div>

									<div class="col-md-6">
									</div>
									
								</div>

								
								<hr class="hr-panel-heading" /> 

								<?php $this->load->view('companies/property_listings/utilities/property_template'); ?>
								
							</div>
						</div>

						<div class="btn-bottom-toolbar btn-toolbar-container-out text-right">
							<a href="<?php echo html_entity_decode($site_url) . ('properties'); ?>"class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>

							<a href="<?php echo html_entity_decode($site_url) . ('listing_pdf/'.$property_listing->id.'?output_type=I'); ?>" class="btn btn-primary text-right hide" target="_blank"><?php echo _l('rel_print'); ?></a>
							<a href="#" class="btn btn-primary text-right save_template hide"><?php echo _l('rel_print'); ?></a>
							<a href="#" onclick="send_request_quotation1(<?php echo new_html_entity_decode($property_listing->id); ?>)" class="btn btn-primary text-right hide"><?php echo _l('rel_contact'); ?></a>

							<a href="#" onclick="send_request_quotation(<?php echo new_html_entity_decode($property_listing->id); ?>)" class="btn btn-primary text-right hide"><?php echo _l('rel_send_to_email'); ?></a>
							<a href="<?php echo html_entity_decode($site_url) . ('add_edit_property_listing/'.$property_listing->id); ?>" class="btn btn-primary text-right"><?php echo _l('edit'); ?></a>

						</div>

					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="request_quotation" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<?php echo form_open_multipart(html_entity_decode($site_url) . ('send_listing'),array('id'=>'send_listing-form')); ?>
				<div class="modal-content modal_withd">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">
							<span><?php echo _l('send_a_property_listing'); ?></span>
						</h4>
					</div>
					<div class="modal-body">
						<div id="additional_rqquo"></div>
						<div class="row">
							<div class="col-md-12 form-group">
								<?php echo render_input('subject', 'rel_subject', ''); ?>
							</div>  
							<div class="col-md-12 form-group">
								<?php echo render_input('fromname', 'rel_fromname', ''); ?>
							</div>  

							<div class="col-md-12 form-group" data-toggle="tooltip" title="" data-original-title="<?php echo _l('rel_send_to_tooltip'); ?>">
								<span class="pull-left fa fa-question-circle" ></span>
								<?php echo render_input('send_to', 'rel_send_to', ''); ?>
							</div>  
							<div class="col-md-12">
								<div class="checkbox checkbox-primary">
									<input type="checkbox" name="attach_pdf" id="attach_pdf" checked>
									<label for="attach_pdf"><?php echo _l('attach_listing_pdf'); ?></label>
								</div>
							</div>

							<div class="col-md-12">
								<?php echo render_textarea('content','rel_additional_content','',array('rows'=>6,'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($routing) || isset($routing) && $routing->description == '' ? 'routing_init_editor(\'.tinymce-task\', {height:200, auto_focus: true});' : '')),array(),'no-mbot','tinymce-task'); ?> 
							</div>     
							<div id="type_care">

							</div>        
						</div>
					</div>
					<div class="modal-footer">
						<button type=""class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
						<button id="sm_btn" type="button" onclick="send_listing(); return false;" class="btn btn-primary"><?php echo _l('rel_send'); ?></button>
					</div>
				</div><!-- /.modal-content -->
				<?php echo form_close(); ?>
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->

		<div class="modal fade" id="request_quotation1" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<?php echo form_open_multipart(html_entity_decode($site_url) . ('send_listing_contact'),array('id'=>'send_listing_contact-form')); ?>
				<div class="modal-content modal_withd">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">
							<span><?php echo _l('rel_send_message_to_publisher'); ?></span>
						</h4>
					</div>
					<div class="modal-body">
						<div id="additional_rqquo1"></div>
						<div class="row">
							<div class="col-md-12 form-group">
								<?php 
								$fromname = rel_get_staff_code();
								$subject = get_option('companyname').' '. _l('rel_mls').': '.$property_listing->description;
								?>
								<?php echo render_input('fromname', 'rel_name', $fromname, '', ['readonly' => true]); ?>
							</div>  

							<div class="col-md-12 form-group">
								<?php echo render_input('subject', 'rel_subject', $subject, '', ['readonly' => true]); ?>
							</div>  

							<div class="col-md-12">
								<?php echo render_textarea('content','rel_message','',array('rows'=>6,'data-task-ae-editor'=>true, !is_mobile() ? 'onclick' : 'onfocus'=>(!isset($routing) || isset($routing) && $routing->description == '' ? 'routing_init_editor(\'.tinymce-task\', {height:200, auto_focus: true});' : '')),array(),'no-mbot','tinymce-task'); ?> 
							</div>     
							<h5 class="text-center mtop5"><?php echo _l('rel_you_can_also_contact_to_the_publisher_via_profile_page'); ?></h5>

						</div>
					</div>

					<div class="modal-footer">
						<button type=""class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
						<button id="sm_btn" type="button" onclick="send_listing_contact(); return false;" class="btn btn-primary"><?php echo _l('rel_send'); ?></button>
					</div>
				</div><!-- /.modal-content -->
				<?php echo form_close(); ?>
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->

		<div id="search_modal_wrapper"></div>
		<div id="add_to_compare_modal_wrapper"></div>

		<?php 
		if(is_broker_logged_in()){
			broker_init_tail();
		}else{
			init_tail();
		}
		?>
		
		<?php 
		require('modules/realestate/assets/js/companies/property_listings/property_listing_detail_js.php');
		require('modules/realestate/assets/js/companies/property_listings/preview_property_file_js.php');

		?>
	</body>
	</html>
