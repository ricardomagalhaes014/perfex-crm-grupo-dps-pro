<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						<h4 class="pull-left"><?php echo ucfirst($relto)." "._l('si_custom_statuses'); ?></h4>
						<div class="_buttons pull-right">
							<?php if(is_admin() || has_permission('si_custom_status', '', 'create')){ ?>
							<a href="#" onclick="si_new_status(); return false;" class="btn btn-info pull-left display-block">
								<?php echo _l('si_custom_status_new_status'); ?>
							</a>
							<?php } ?>
						</div>
						<div class="clearfix"></div>
						<hr class="hr-panel-heading" />
						<p><?php echo _l('si_custom_status_message',ucfirst($relto));?></p>
						<table class="table dt-table scroll-responsive" data-order-col="0" data-order-type="asc">
							<thead>
								<th>#</th>
								<th><?php echo _l('si_custom_status_status_add_edit_name'); ?></th>
								<th><?php echo _l('si_custom_status_status_table_total',ucfirst($relto)); ?></th>
								<th><?php echo _l('si_custom_status_status_add_edit_order'); ?></th>
								<th><?php echo _l('si_custom_status_status_filter_default'); ?></th>
								<?php if(is_admin() || has_permission('si_custom_status', '', 'edit') || has_permission('si_custom_status', '', 'delete')){ ?>
								<th><?php echo _l('options'); ?></th>
								<?php }?>
							</thead>
							<tbody>
								<!--start default statuses-->
								<?php 
								$no=1;
								foreach($default_statuses as $status){ if(isset($status['relto'])) continue; 
								?>
								<tr class="alert-info">
									<td>
										<?php echo htmlspecialchars($no++); ?>
									</td>
									<td>
										<?php echo si_cs_format_statuses($status); ?></td>
									<td>	
										<?php echo total_rows(db_prefix().$relto,array('status'=>$status['id'])); ?>
									</td>
									<td>
										<?php echo htmlspecialchars($status['order']);?>
									</td>
									<td>
										<?php echo htmlspecialchars($status['filter_default']==1?'Yes':'No');?>
									</td>
									<?php if(is_admin() || has_permission('si_custom_status', '', 'edit') || has_permission('si_custom_status', '', 'delete')){ ?>
									<td>
											<?php echo _l('si_custom_status_default_status'); ?>
									</td>
									<?php } ?>
								</tr>
								<?php } ?>
								<!--start custom statuses-->
								<?php foreach($statuses as $status){ ?>
								<tr>
									<td>
										<?php echo htmlspecialchars($no++); ?>
									</td>
									<td>
										<?php if(is_admin() || has_permission('si_custom_status', '', 'edit')){ ?>
										<a href="#" onclick="si_edit_status(this,<?php echo htmlspecialchars($status['id']); ?>);return false;" data-color="<?php echo htmlspecialchars($status['color']); ?>" data-name="<?php echo htmlspecialchars($status['name']); ?>" data-order="<?php echo htmlspecialchars($status['order']); ?>"  data-filter_default="<?php echo htmlspecialchars($status['filter_default']); ?>"><?php echo si_cs_format_statuses($status); ?></a>	
										<?php } else {echo si_cs_format_statuses($status);}?>
									</td>
									<td>	
										<?php echo total_rows(db_prefix().$relto,array('status'=>$status['id'])); ?>
									</td>
									<td>
										<?php echo htmlspecialchars($status['order']);?>
									</td>
									<td>
										<?php echo ($status['filter_default']==1?'Yes':'No');?>
									</td>
									<?php if(is_admin() || has_permission('si_custom_status', '', 'edit') || has_permission('si_custom_status', '', 'delete')){ ?>
									<td>
											<?php if(is_admin() || has_permission('si_custom_status', '', 'edit')){ ?>
											<a href="#" onclick="si_edit_status(this,<?php echo htmlspecialchars($status['id']); ?>);return false;" data-color="<?php echo htmlspecialchars($status['color']); ?>" data-name="<?php echo htmlspecialchars($status['name']); ?>" data-order="<?php echo htmlspecialchars($status['order']); ?>"  data-filter_default="<?php echo htmlspecialchars($status['filter_default']); ?>" class="btn btn-info btn-icon" title="<?php echo _l('edit')?>"><i class="fa fa-pencil-square-o"></i></a>
											<?php }
											if(is_admin() || has_permission('si_custom_status', '', 'delete')){ ?>
											<a href="<?php echo admin_url('si_custom_status/delete_status/'.$status['id']); ?>" class="btn btn-danger btn-icon _delete" title="<?php echo _l('delete')?>"><i class="fa fa-remove"></i></a>
											<?php } ?>
									</td>
									<?php } ?>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php $this->load->view('statuses/status'); ?>
<?php init_tail(); ?>
<script src="<?php echo module_dir_url('si_custom_status','assets/js/si_custom_status_manage_status.js'); ?>"></script>
</body>
</html>