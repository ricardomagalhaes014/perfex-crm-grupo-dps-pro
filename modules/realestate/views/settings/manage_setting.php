<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-3">
				<ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked">
					<?php if(is_admin()){ ?>
						<?php if(has_permission('real_permission','','view')){ ?>
							<li	<?php if($tab == 'general'){echo " class='active'"; } ?>>
								<a href="<?php echo admin_url('realestate/settings?tab=general'); ?>">
									<i class="fa-solid fa-house-medical"></i> <?php echo _l('real_general'); ?>
								</a>
							</li>
						<?php } ?>
						<?php if(has_permission('real_permission','','view')){ ?>
							<li	<?php if($tab == 'prefix_setting'){echo " class='active'"; } ?>>
								<a href="<?php echo admin_url('realestate/settings?tab=prefix_setting'); ?>">
									<i class="fa fa-bars"></i> <?php echo _l('real_prefix_settings'); ?>
								</a>
							</li>
						<?php } ?>
						
						<?php if(has_permission('real_permission','','view')){ ?>
							<li	<?php if($tab == 'plan'){echo " class='active'"; } ?>>
								<a href="<?php echo admin_url('realestate/settings?tab=plan'); ?>">
									<i class="fa-solid fa-chart-simple"></i> <?php echo _l('real_plans'); ?>
								</a>
							</li>
						<?php } ?>

						
						<?php if(has_permission('real_permission','','view')){ ?>
							<li	<?php if($tab == 'role'){echo " class='active'"; } ?>>
								<a href="<?php echo admin_url('realestate/settings?tab=role'); ?>">
									<i class="fa-solid fa-drum-steelpan"></i> <?php echo _l('real_plan_details'); ?>
								</a>
							</li>
						<?php } ?>
					<?php } ?>
					
					<?php if(has_permission('real_permission','','view')){ ?>
						<li	<?php if($tab == 'permissions'){echo " class='active'"; } ?>>
							<a href="<?php echo admin_url('realestate/settings?tab=permissions'); ?>">
								<i class="fa fa-unlock-alt"></i> <?php echo _l('real_permissions'); ?>
							</a>
						</li>
					<?php } ?>
				</ul>
			</div>

			<div class="col-md-9">
				<div class="panel_s">
					<div class="panel-body">
						<?php $this->load->view('settings/tabs/'.$tab); ?>  
					</div>
				</div>
			</div>


			<div class="clearfix"></div>
		</div>
		<div class="btn-bottom-pusher"></div>
	</div>
</div>
<div id="new_version"></div>
<div id="search_modal_wrapper"></div>

<?php init_tail(); ?>

<?php 
$viewuri = $_SERVER['REQUEST_URI'];
?>
<?php if(!(strpos($viewuri,'admin/realestate/settings?tab=general') === false)){
	require('modules/realestate/assets/js/settings/general_js.php');
}elseif(!(strpos($viewuri,'admin/realestate/settings?tab=role') === false)){
	require('modules/realestate/assets/js/settings/roles/manage_js.php');
}elseif(!(strpos($viewuri,'admin/realestate/settings?tab=plan') === false)){
	require('modules/realestate/assets/js/settings/plans/manage_js.php');
}elseif(!(strpos($viewuri,'admin/realestate/settings?tab=permissions') === false)){
	require('modules/realestate/assets/js/settings/permissions/permissions_js.php');
}
?>

</body>
</html>

