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

		<div class="sm:tw-flex tw-space-y-3 sm:tw-space-y-0 tw-gap-6">
			<?php if(!is_broker_logged_in()){ ?>
				<div class="sm:tw-border-r sm:tw-border-solid sm:tw-border-neutral-200 tw-pr-10 tw-w-96">
					<h4
					class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-ml-2.5 tw-inline-flex tw-items-center">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
					stroke="currentColor" class="tw-w-5 tw-h-5 tw-mr-1.5 tw-ml-1">
					<path stroke-linecap="round" stroke-linejoin="round"
					d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
				</svg>

				<?php echo _l('real_rental_report'); ?>
			</h4>
			<ul class="reports tw-space-y-1">
				<li>
					<a href="#"
					class="group tw-font-medium tw-px-3 tw-py-3 tw-text-neutral-500 hover:tw-text-neutral-800 active:tw-text-neutral-800 focus:tw-text-neutral-800 hover:tw-bg-neutral-200 tw-w-full tw-inline-flex tw-items-center tw-rounded-md data-[active=true]:tw-bg-neutral-200 data-[active=true]:tw-text-neutral-800"
					onclick="init_report(this, 'rent-paid-report-data'); return false;">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
					stroke="currentColor"
					class="tw-w-5 tw-h-5 tw-mr-2.5 tw-text-neutral-500 group-hover:tw-text-neutral-800">
					<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
				</svg>
				<?php echo _l('rent_paid_report_data'); ?>
			</a>
		</li>
		<li>
			<a href="#"
			class="group tw-font-medium tw-px-3 tw-py-3 tw-text-neutral-500 hover:tw-text-neutral-800 active:tw-text-neutral-800 focus:tw-text-neutral-800 hover:tw-bg-neutral-200 tw-w-full tw-inline-flex tw-items-center tw-rounded-md data-[active=true]:tw-bg-neutral-200 data-[active=true]:tw-text-neutral-800"
			onclick="init_report(this,'vacant-rental-property-report-data'); return false;">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
			stroke="currentColor"
			class="tw-w-5 tw-h-5 tw-mr-2.5 tw-text-neutral-500 group-hover:tw-text-neutral-800">
			<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
		</svg>
		<?php echo _l('vacant_rental_property_report_data'); ?>
	</a>
</li>
<li>
	<a href="#"
	class="group tw-font-medium tw-px-3 tw-py-3 tw-text-neutral-500 hover:tw-text-neutral-800 active:tw-text-neutral-800 focus:tw-text-neutral-800 hover:tw-bg-neutral-200 tw-w-full tw-inline-flex tw-items-center tw-rounded-md data-[active=true]:tw-bg-neutral-200 data-[active=true]:tw-text-neutral-800"
	onclick="init_report(this,'unit-property-rental-report-data'); return false;">
	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
	stroke="currentColor"
	class="tw-w-5 tw-h-5 tw-mr-2.5 tw-text-neutral-500 group-hover:tw-text-neutral-800">
	<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
</svg>
<?php echo _l('unit_property_rental_report_data'); ?>
</a>
</li>
<li>
	<a href="#"
	class="group tw-font-medium tw-px-3 tw-py-3 tw-text-neutral-500 hover:tw-text-neutral-800 active:tw-text-neutral-800 focus:tw-text-neutral-800 hover:tw-bg-neutral-200 tw-w-full tw-inline-flex tw-items-center tw-rounded-md data-[active=true]:tw-bg-neutral-200 data-[active=true]:tw-text-neutral-800"
	onclick="init_report(this,'delinquent-tenants-report-data'); return false;">
	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
	stroke="currentColor"
	class="tw-w-5 tw-h-5 tw-mr-2.5 tw-text-neutral-500 group-hover:tw-text-neutral-800">
	<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
</svg>
<?php echo _l('delinquent_tenants_report_data'); ?>
</a>
</li>
<li>
	<a href="#"
	class="group tw-font-medium tw-px-3 tw-py-3 tw-text-neutral-500 hover:tw-text-neutral-800 active:tw-text-neutral-800 focus:tw-text-neutral-800 hover:tw-bg-neutral-200 tw-w-full tw-inline-flex tw-items-center tw-rounded-md data-[active=true]:tw-bg-neutral-200 data-[active=true]:tw-text-neutral-800"
	onclick="init_report(this,'leases-ending-repor-data'); return false;">
	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
	stroke="currentColor"
	class="tw-w-5 tw-h-5 tw-mr-2.5 tw-text-neutral-500 group-hover:tw-text-neutral-800">
	<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
</svg>
<?php echo _l('leases_ending_repor_data'); ?>
</a>
</li>

</ul>
</div>
<?php } ?>
<div class="sm:tw-border-r sm:tw-border-solid sm:tw-border-neutral-200 tw-pr-10 tw-w-96">
	<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-inline-flex tw-items-center">
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
		stroke="currentColor" class="tw-w-5 tw-h-5 tw-mr-1.5 tw-ml-1">
		<path stroke-linecap=" round" stroke-linejoin="round"
		d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
	</svg>

	<?php echo _l('real_sale_report'); ?>
</h4>
<ul class="reports tw-space-y-1">
	<li>
		<a href="#"
		class="group tw-font-medium tw-px-3 tw-py-3 tw-text-neutral-500 hover:tw-text-neutral-800 active:tw-text-neutral-800 focus:tw-text-neutral-800 hover:tw-bg-neutral-200 tw-w-full tw-inline-flex tw-items-center tw-rounded-md data-[active=true]:tw-bg-neutral-200 data-[active=true]:tw-text-neutral-800"
		onclick="init_report(this,'vacant-sale-property-report-data'); return false;">
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
		stroke="currentColor"
		class="tw-w-5 tw-h-5 tw-mr-2.5 tw-text-neutral-500 group-hover:tw-text-neutral-800">
		<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
	</svg>
	<?php echo _l('vacant_sale_property_report_data'); ?>
</a>
</li>

<li>
	<a href="#"
	class="group tw-font-medium tw-px-3 tw-py-3 tw-text-neutral-500 hover:tw-text-neutral-800 active:tw-text-neutral-800 focus:tw-text-neutral-800 hover:tw-bg-neutral-200 tw-w-full tw-inline-flex tw-items-center tw-rounded-md data-[active=true]:tw-bg-neutral-200 data-[active=true]:tw-text-neutral-800"
	onclick="init_report(this,'unit-property-sold-report-data'); return false;">
	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
	stroke="currentColor"
	class="tw-w-5 tw-h-5 tw-mr-2.5 tw-text-neutral-500 group-hover:tw-text-neutral-800">
	<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
</svg>
<?php echo _l('unit_property_sold_report_data'); ?>
</a>
</li>


</ul>
</div>
<div class="tw-flex-1 tw-max-w-md">
	
	<div id="income-years" class="hide mbot15">
		<label for="payments_years"><?php echo _l('year'); ?></label><br />
		<select class="selectpicker" name="payments_years" data-width="100%"
		onchange="total_income_bar_report();"
		data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
		<?php foreach ($payments_years as $year) { ?>
			<option value="<?php echo e($year['year']); ?>" <?php if ($year['year'] == date('Y')) {
				echo 'selected';
			} ?>>
			<?php echo e($year['year']); ?>
		</option>
	<?php } ?>
</select>
</div>
<div class="form-group hide" id="report-time">
	<label for="months-report"><?php echo _l('period_datepicker'); ?></label><br />
	<select class="selectpicker" name="months-report" data-width="100%"
	data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
	<option value=""><?php echo _l('report_sales_months_all_time'); ?>
</option>
<option value="this_month"><?php echo _l('this_month'); ?></option>
<option value="1"><?php echo _l('last_month'); ?></option>
<option value="this_year"><?php echo _l('this_year'); ?></option>
<option value="last_year"><?php echo _l('last_year'); ?></option>
<option value="3"
data-subtext="<?php echo _d(date('Y-m-01', strtotime('-2 MONTH'))); ?> - <?php echo _d(date('Y-m-t')); ?>">
<?php echo _l('report_sales_months_three_months'); ?></option>
<option value="6"
data-subtext="<?php echo _d(date('Y-m-01', strtotime('-5 MONTH'))); ?> - <?php echo _d(date('Y-m-t')); ?>">
<?php echo _l('report_sales_months_six_months'); ?></option>
<option value="12"
data-subtext="<?php echo _d(date('Y-m-01', strtotime('-11 MONTH'))); ?> - <?php echo _d(date('Y-m-t')); ?>">
<?php echo _l('report_sales_months_twelve_months'); ?></option>
<option value="custom"><?php echo _l('period_datepicker'); ?></option>
</select>
</div>
<div id="date-range" class="hide mbot15">
	<div class="row">
		<div class="col-md-6">
			<label for="report-from"
			class="control-label"><?php echo _l('report_sales_from_date'); ?></label>
			<div class="input-group date">
				<input type="text" class="form-control datepicker" id="report-from" name="report-from">
				<div class="input-group-addon">
					<i class="fa-regular fa-calendar calendar-icon"></i>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<label for="report-to"
			class="control-label"><?php echo _l('report_sales_to_date'); ?></label>
			<div class="input-group date">
				<input type="text" class="form-control datepicker" disabled="disabled" id="report-to"
				name="report-to">
				<div class="input-group-addon">
					<i class="fa-regular fa-calendar calendar-icon"></i>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>
<?php if (total_rows(db_prefix() . 'invoices', ['status' => 5]) > 0) { ?>
	<p class="text-danger tw-my-3">
		<i class="fa-solid fa-circle-exclamation tw-mr-1" aria-hidden="true"></i>
		<?php echo _l('sales_report_cancelled_invoices_not_included'); ?>
	</p>
<?php } ?>
<div class="row">
	<div class="col-md-12 hide" id="report">
		<h4 class="tw-mt-9 tw-font-semibold tw-text-lg tw-text-neutral-700">
			<?php echo _l('reports_sales_generated_report'); ?>
		</h4>
		<div class="panel_s">
			<div class="panel-body panel-table-full">
				<?php $this->load->view('companies/reports/includes/rent_paid'); ?>
				<?php $this->load->view('companies/reports/includes/delinquent_tenants'); ?>
				<?php $this->load->view('companies/reports/includes/leases_ending'); ?>
				<?php $this->load->view('companies/reports/includes/unit_property_rental'); ?>
				<?php $this->load->view('companies/reports/includes/unit_property_sold'); ?>
				<?php $this->load->view('companies/reports/includes/vacant_rental'); ?>
				<?php $this->load->view('companies/reports/includes/vacant_sale_property'); ?>

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
<?php require('modules/realestate/assets/js/companies/reports/manage_js.php'); ?>
</body>

</html>