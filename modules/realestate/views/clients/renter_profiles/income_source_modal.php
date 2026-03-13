<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="income_source_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<?php echo form_open_multipart(site_url('realestate/client/income_source'), array('id'=>'add_edit_income_source')); ?>

		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">
					<span class="add-title"><?php echo _l('real_add_income_source'); ?></span>
					<span class="edit-title"><?php echo _l('real_edit_income_source'); ?></span>
				</h4>
			</div>

			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<?php 
						$income_types = [
							[
								'name' => 'salary',
								'label' => _l('real_salary'),
							],
							[
								'name' => 'family_allowance',
								'label' => _l('real_family_allowance'),
							],
							[
								'name' => 'pensions',
								'label' => _l('real_pensions'),
							],
							[
								'name' => 'existing_rental_income',
								'label' => _l('real_existing_rental_income'),
							],
							[
								'name' => 'superannuation_income',
								'label' => _l('real_superannuation_income'),
							],
							[
								'name' => 'proposed_rental_income',
								'label' => _l('real_proposed_rental_income'),
							],
							[
								'name' => 'dividend_income',
								'label' => _l('real_dividend_income'),
							],
							[
								'name' => 'overtime',
								'label' => _l('real_overtime'),
							],
							[
								'name' => 'commission',
								'label' => _l('real_commission'),
							],
							[
								'name' => 'child_support',
								'label' => _l('real_child_support'),
							],
							[
								'name' => 'bonus',
								'label' => _l('real_bonus'),
							],
							[
								'name' => 'other',
								'label' => _l('real_other'),
							],							
						];

						$income_frequencies = [
							[
								'name' => 'annually',
								'label' => _l('real_annually'),
							],
							[
								'name' => 'monthly',
								'label' => _l('real_monthly'),
							],
							[
								'name' => 'fortnightly',
								'label' => _l('real_fortnightly'),
							],
							[
								'name' => 'weekly',
								'label' => _l('real_weekly'),
							],
						];

						?>
						<div id="income_source_additional"></div>
						<?php echo render_select('income_type', $income_types, ['name', 'label'], 'real_income_type'); ?>
						<?php echo render_select('income_frequency', $income_frequencies, ['name', 'label'], 'real_income_frequency'); ?>
						<?php echo render_input('amount', 'real_amount_after_taxt', '', 'number', ['min' => 0, 'step' => 'any']); ?>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
			</div>
		</div>
		<?php echo form_close(); ?>
	</div>
</div>
