<?php defined('BASEPATH') or exit('No direct script access allowed');?>
<?php init_head();?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						<div class="alert alert-info">Before Creating or updating a Tax Agency, kindy make sure you are connected to <?=_l('quickbooks')?>. Note: Updating an agency creates a new Tax Agency in <?=_l('quickbooks')?>, Deleting only works locally it does not work remotely so you have to delete the same manually in <?=_l('quickbooks')?>.</div>
						<div class="_buttons">
							<a href="#" class="btn btn-info pull-left" data-toggle="modal" data-target="#tax_agency_modal"><?php echo _l('qb_new_tax_agency'); ?></a>
						</div>
						<div class="clearfix"></div>
						<hr class="hr-panel-heading" />
						<div class="clearfix"></div>
						<?php render_datatable(array(
	_l('#'),
	_l('qb_tax_agency'),
	_l('options'),
), 'tax_agency');
?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="tax_agency_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">
						<span class="edit-title"><?php echo _l('tax_agency_edit_title'); ?></span>
						<span class="add-title"><?php echo _l('tax_agency_add_title'); ?></span>
					</h4>
				</div>
				<?php echo form_open('quickbooks/tax_agency/manage', array('id' => 'tax_agency_form')); ?>
				<?php echo form_hidden('tax_agencyid'); ?>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<?php echo render_input('agency_name', 'tax_add_edit_name'); ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
					<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
					<?php echo form_close(); ?>
				</div>
			</div>
		</div>
	</div>
	<?php init_tail();?>
</body>
</html>
