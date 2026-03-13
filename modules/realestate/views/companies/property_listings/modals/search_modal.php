<div class="modal fade z-index-none" id="searchModal">
	<div class="modal-dialog rel_modal-90">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo new_html_entity_decode($title); ?></h4>
			</div>
			<?php echo form_open(admin_url('realestate/property_listings'), array('id' => 'property_listings', 'autocomplete'=>'off')); ?>
			<div class="modal-body">
				<?php echo form_hidden('search_template_id', $search_template_id); ?>
				<?php echo form_hidden('show_criteria', $show_criteria); ?>
				<div class="row">
					<?php if(isset($search_template->search_template_details) && count($search_template->search_template_details) > 0){ ?>
						<?php foreach ($search_template->search_template_details as $key => $value) { ?>
							<?php 
							$type = isset($search_template_fields[$value['field_name']]) ? $search_template_fields[$value['field_name']]['type'] : 'TEXT';

							$type_data = [];
							$multiple = false;
							$input_type = 'input';

							if(isset($search_template_fields[$value['field_name']]) && isset($search_template_fields[$value['field_name']]['data']) && $search_template_fields[$value['field_name']]['data'] != ''){
								$type_data = $search_template_fields[$value['field_name']]['data'];
								$type_data = $type_data();
							}

							if(isset($search_template_fields[$value['field_name']]) && isset($search_template_fields[$value['field_name']]['multiple'])){
								$multiple = $search_template_fields[$value['field_name']]['multiple'];
							}

							if(isset($search_template_fields[$value['field_name']]) && isset($search_template_fields[$value['field_name']]['input_type'])){
								$input_type = $search_template_fields[$value['field_name']]['input_type'];
							}

							?>

							<div class="col-md-<?php echo new_html_entity_decode($value['bs_column']); ?>">

								<?php if($type == 'date'){ ?>

									<?php echo render_date_input( $value['field_name'], 'rel_'.$value['field_name'], ''); ?>

								<?php }elseif($type == 'TEXT'){ ?>
									<?php if($multiple){ ?>

										<?php echo render_select($value['field_name'], $type_data, ['name', 'label'], 'rel_'.$value['field_name'], '', ['multiple' => true], [], '', '', false); ?>

									<?php }elseif($input_type == 'select'){ ?>

										<?php echo render_select($value['field_name'], $type_data, ['name', 'label'], 'rel_'.$value['field_name'], '', [], [], '', ''); ?>

									<?php }else{ ?>
										<?php echo render_input( $value['field_name'], 'rel_'.$value['field_name'], '', 'text'); ?>

									<?php } ?>

								<?php }elseif($type == 'DECIMAL'){ ?>
									<?php echo render_input( $value['field_name'], 'rel_'.$value['field_name'], '', 'number', ['step' => 'any', 'min' => 0]); ?>

								<?php }elseif($type == 'INT'){ ?>
									<?php echo render_input( $value['field_name'], 'rel_'.$value['field_name'], '', 'number', ['step' => 'any', 'min' => 0]); ?>
								<?php } ?>
							</div>

						<?php } ?>
					<?php }else{ ?>
						<h4 class="text-center"><?php echo _l('rel_reset_filter'); ?></h4>
					<?php } ?>
				</div>

			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary only-save submit_button"><?php echo _l( 'submit'); ?></button>
			</div>

		</div>

		<?php echo form_close(); ?>
	</div>
</div>
<?php 
require 'modules/realestate/assets/js/construction_companies/property_listings/modals/search_modal_js.php';
?>