<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="request_broker_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<?php echo form_open_multipart(admin_url('realestate/request_broker'), array('id'=>'add_edit_request_broker')); ?>
		<?php 
		$staff_in_company = rel_check_staff_in_company();
		?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">
					<span class="add-title"><?php echo _l('real_add_request_broker'); ?></span>
					<span class="edit-title"><?php echo _l('real_edit_request_broker'); ?></span>
				</h4>
			</div>

			<div class="modal-body">
				<div id="request_broker_additional"></div>
				<input type="hidden" name="item_id" value="<?php echo html_entity_decode($property_listing->id); ?>">

				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<div class="radio radio-primary radio-inline" >
								<input type="radio" id="my_staff" name="broker_type" value="staffs">
								<label for="my_staff"><?php echo _l('real_my_staffs'); ?></label>
							</div>
						</div>
					</div>
					<?php if(!$staff_in_company){ ?>
						<div class="col-md-4">
							<div class="form-group">
								<div class="radio radio-primary radio-inline" >
									<input type="radio" id="real_agent" name="broker_type" value="agents">
									<label for="real_agent"><?php echo _l('real_real_estate_agents'); ?></label>
								</div>
							</div>
						</div>
					<?php } ?>
					<?php if($property_listing->transaction_type == 'Sale'){ ?>
						<div class="col-md-4">
							<div class="form-group">
								<div class="radio radio-primary radio-inline" >
									<input type="radio" id="real_broker" name="broker_type" value="business_brokers">
									<label for="real_broker"><?php echo _l('real_business_brokers'); ?></label>
								</div>
							</div>
						</div>
					<?php } ?>
					<div class="_real_agent hide">
						<?php if(!$staff_in_company){ ?>
							<div class="col-md-12">
								<div class="agent_select">
									<?php echo render_select('company_id[]', $property_agents, ['id', 'name'], 'real_construction_company', '', ['multiple' => true, 'data-actions-box' => true, 'data-live-search' => true], [], '', '', false); ?>
								</div>
							</div>
						<?php } ?>
					</div>
					<div class="_my_staff hide">
						<div class="col-md-12">
							<?php echo render_select('staff_id[]', [], ['id', 'name'], 'real_staffs', '', ['multiple' => true, 'data-actions-box' => true, 'data-live-search' => true], [], '', '', false); ?>
						</div>
					</div>
					<div class="_real_broker hide">
						<div class="col-md-12">
							<div class="broker_select">
								<?php echo render_select('broker_id[]', $brokers, ['id', 'name'], 'real_business_broker', '', ['multiple' => true, 'data-actions-box' => true, 'data-live-search' => true], [], '', '', false); ?>
							</div>
						</div>
					</div>

					<div class="col-md-12">
						<?php echo render_input('commission', 'real_commission', '', 'number', ['min' => 0, 'max' => 100]); ?>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
			</div>
		</div><!-- /.modal-content -->
		<?php echo form_close(); ?>
	</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->