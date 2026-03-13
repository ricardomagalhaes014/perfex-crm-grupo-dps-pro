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
			<div class="col-md-12" >
				<div class="panel_s">
					<div class="panel-body">

						<div class="row">
							<div class="col-md-3 hide">
								
								<div class="form-group" id="report-time">
									<label for="real_months-report"><?php echo _l('period_datepicker'); ?></label><br />
									<select class="selectpicker" name="real_months-report" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
										<option value=""><?php echo _l('report_sales_months_all_time'); ?></option>
										<option value="this_month"><?php echo _l('this_month'); ?></option>
										<option value="1"><?php echo _l('last_month'); ?></option>
										<option value="this_year"><?php echo _l('this_year'); ?></option>
										<option value="last_year"><?php echo _l('last_year'); ?></option>
										<option value="3" data-subtext="<?php echo _d(date('Y-m-01', strtotime("-2 MONTH"))); ?> - <?php echo _d(date('Y-m-t')); ?>"><?php echo _l('report_sales_months_three_months'); ?></option>
										<option value="6" data-subtext="<?php echo _d(date('Y-m-01', strtotime("-5 MONTH"))); ?> - <?php echo _d(date('Y-m-t')); ?>"><?php echo _l('report_sales_months_six_months'); ?></option>
										<option value="12" data-subtext="<?php echo _d(date('Y-m-01', strtotime("-11 MONTH"))); ?> - <?php echo _d(date('Y-m-t')); ?>"><?php echo _l('report_sales_months_twelve_months'); ?></option>
										<option value="custom"><?php echo _l('period_datepicker'); ?></option>
									</select>
								</div>
							</div>
							<div id="mo_date-range" class="hide ">
								<div class="row">
									<div class="col-md-3">
										<label for="real_report-from" class="control-label"><?php echo _l('report_sales_from_date'); ?></label>
										<div class="input-group date">
											<input type="text" class="form-control datepicker" id="real_report-from" autocomplete="off" name="real_report-from">
											<div class="input-group-addon">
												<i class="fa fa-calendar calendar-icon"></i>
											</div>
										</div>
									</div>
									<div class="col-md-3">
										<label for="real_report-to" class="control-label"><?php echo _l('report_sales_to_date'); ?></label>
										<div class="input-group date">
											<input type="text" class="form-control datepicker" disabled="disabled" autocomplete="off" id="real_report-to" name="real_report-to">
											<div class="input-group-addon">
												<i class="fa fa-calendar calendar-icon"></i>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div>
						<?php if($get_remaining_property['show_on_dashboard']){ ?>
							<div class="row">
									<div class="col-md-6 list-status projects-status">
										<a href="javascript:void(0)" class="  cs-portal-a-renewal" >
											<h5 class="bold text-uppercase text-success cs-portal-h-cancelled" ><?php echo _l('real_remaining_property'); ?></h5>
											<span class="bold cs-portal-h-cancelled tw-text-lg" ><?php echo new_html_entity_decode($get_remaining_property['remaining_quantity']. ' ( '.$get_remaining_property['current_listing'].' / '.$get_remaining_property['plan_listing'].' ) '. _l('real_properties')); ?></span>
										</a>
									</div>

									<div class="col-md-6 list-status projects-status" data-original-title="<?php echo _l('rel_view_plans'); ?>">
										<a href="javascript:void(0)" class=" cs-portal-a-your-plan" data-toggle="tooltip" >
											<h5 class="bold text-uppercase text-success cs-portal-h-cancelled" ><?php echo _l('real_your_plan'); ?></h5>
											<span class="bold cs-portal-h-cancelled tw-text-lg" ><?php echo new_html_entity_decode($get_remaining_property['plan_name']); ?></span>
										</a>
									</div>
							</div>
							<br>
						<?php } ?>

						<div class="row">
							<div class="col-md-3 list-status projects-status">
								<a href="#" class=" background-color-2196f3">
									<h5 class="bold text-uppercase text-success cs-portal-h-cancelled "><?php echo _l('real_sale_income'); ?></h5>
									<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo app_format_money($dashboard_sale_performance['income_sold'], $base_currency_id); ?></span>
								</a>
							</div>
							<div class="col-md-3 list-status projects-status">
								<a href="#" class=" background-color-2563eb">
									<h5 class="bold text-uppercase text-success cs-portal-h-cancelled"><?php echo _l('real_for_sale'); ?></h5>
									<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo html_entity_decode(app_format_number($dashboard_sale_performance['properties_for_sale'])) ?></span>
								</a>
							</div>
							<div class="col-md-3 list-status projects-status">
								<a href="#" class=" background-color-16a34a">
									<h5 class="bold text-uppercase text-success cs-portal-h-cancelled"><?php echo _l('real_sold'); ?></h5>
									<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo html_entity_decode(app_format_number($dashboard_sale_performance['properties_sold'])) ?></span>
								</a>
							</div>
							<div class="col-md-3 list-status projects-status">
								<a href="#" class=" background-color-f97316">
									<h5 class="bold text-uppercase text-success cs-portal-h-cancelled"><?php echo _l('real_selling'); ?></h5>
									<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo html_entity_decode(app_format_number($dashboard_sale_performance['properties_selling'])) ?></span>
								</a>
							</div>


						</div>
						<br>

						<!--report by sale  -->
						<div class="row">
							<div class="col-md-4">
								<div id="property_by_status">
								</div>
							</div>
							<div class="col-md-4">
								<div id="sale_request_by_status"></div>
							</div>
							<div class="col-md-4">
								<div id="sale_percent_by_property_type"></div>
							</div>
							
							
						</div>
						<?php if(is_staff_logged_in()){ ?>
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="col-md-3 list-status projects-status">
										<a href="#" class=" background-color-2196f3">
											<h5 class="bold text-uppercase text-success cs-portal-h-cancelled "><?php echo _l('real_rental_income'); ?></h5>
											<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo app_format_money($dashboard_sale_performance['income_leased'], $base_currency_id); ?></span>
										</a>
									</div>
									<div class="col-md-3 list-status projects-status">
										<a href="#" class=" background-color-2563eb">
											<h5 class="bold text-uppercase text-success cs-portal-h-cancelled"><?php echo _l('real_for_lease'); ?></h5>
											<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo html_entity_decode(app_format_number($dashboard_sale_performance['properties_for_lease'])); ?></span>
										</a>
									</div>
									<div class="col-md-3 list-status projects-status">
										<a href="#" class=" background-color-16a34a">
											<h5 class="bold text-uppercase text-success cs-portal-h-cancelled"><?php echo _l('real_rented'); ?></h5>
											<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo html_entity_decode(app_format_number($dashboard_sale_performance['properties_leased'])); ?></span>
										</a>
									</div>
									<div class="col-md-3 list-status projects-status">
										<a href="#" class=" background-color-f97316">
											<h5 class="bold text-uppercase text-success cs-portal-h-cancelled"><?php echo _l('real_available'); ?></h5>
											<span class="bold cs-portal-h-cancelled tw-text-lg"><?php echo html_entity_decode(app_format_number($dashboard_sale_performance['properties_rent_available'])); ?></span>
										</a>
									</div>
								</div>
							</div>

						</div>
						<br>
					<?php } ?>


						<!-- report by rent -->
						<div class="row">
						<?php if(is_staff_logged_in()){ ?>

							<div class="col-md-4">
								<div id="rent_request_by_status"></div>
							</div>
							<div class="col-md-4">
								<div id="rent_percent_by_property_type"></div>
							</div>
						<?php } ?>

						<div class="col-md-4">
							<div id="report_by_top_city_listing"></div>
						</div>
							
							
						</div>

						<br>

						
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php 
if(is_broker_logged_in()){
	broker_init_tail();
}else{
	init_tail();
}
?>
<?php require('modules/realestate/assets/js/companies/dashboards/dashboard_js.php'); ?>
</body>
</html>
