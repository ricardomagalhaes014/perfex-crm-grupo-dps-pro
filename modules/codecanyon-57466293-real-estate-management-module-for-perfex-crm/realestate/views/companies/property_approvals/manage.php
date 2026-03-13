<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
init_head();
?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12" id="small-table">
				<div class="panel_s">
					<div class="panel-body">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="col-md-9">
										<h4 class="h4-color no-margin"><i class="fa-solid fa-building-user" aria-hidden="true"></i> <?php echo _l('real_property_approvals'); ?></h4>
									</div>

									<div class="col-md-3">

									</div>
								</div>
								<hr class="hr-panel-heading" />
								<?php if(has_permission('real_property_approval', '', 'create') || $is_approval_mamanger ){ ?>
									<a href="#"  onclick="change_status_in_bulk(); return false;" data-toggle="modal" data-table=".table-pending_property_table" data-target="#leads_bulk_actions" class=" hide bulk-actions-btn table-btn"><?php echo _l('real_change_status_in_bulk'); ?></a>
								<?php } ?>

								<?php $this->load->view('companies/property_approvals/table_html', ['table_name_custom' => 'pending_property_table']); ?>

							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<?php 
init_tail();
?>

</body>
<?php 
require('modules/realestate/assets/js/companies/property_approvals/manage_js.php');
?>

</html>
