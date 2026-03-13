<div class="row">
	<div class="col-md-9">
		<h4 class="h4-color no-margin"><?php echo _l('real_plan_details'); ?></h4>
	</div>
	<?php if(has_permission('real_permission', '', 'create')){ ?>
		<div class="col-md-3">
			<div class="_buttons">
				<a href="<?php echo admin_url('realestate/role'); ?>" class="btn btn-primary pull-right">
					<i class="fa-regular fa-plus tw-mr-1"></i>
					<?php echo _l('real_new_plan_detail'); ?>
				</a>
			</div>
		</div>
	<?php } ?>
</div>
<br>

<?php render_datatable([
	_l('real_name_label'),
	_l('options'),
], 'roles'); ?>
