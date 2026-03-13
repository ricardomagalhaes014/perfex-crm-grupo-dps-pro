<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
$transaction_types = real_transaction_type();
$property_status = rel_property_listing_status();

$beds = rel_bed_filter();
$baths = rel_baths_filter();
$is_mobile_hide = '' ;
?>
<?php if(is_mobile()){
	$is_mobile_hide = ' hide' ;

} ?>
<div id="property_search" class="search-box tw-flex tw-justify-between">
	<div class="input-form">
		<?php echo render_input('search_input', '', '', 'text', ['placeholder' => _l('real_search_input')]); ?>
	</div>
	<div class="select-form <?php  echo new_html_entity_decode($is_mobile_hide); ?>">
		<select name="transaction_type_search" data-live-search="true" id="transaction_type_search"
		class="form-control selectpicker "
		data-none-selected-text="<?php echo _l('real_transaction_type'); ?>" multiple="true">
		<?php foreach ($transaction_types as $transaction_type) {
			?>
			<option value="<?php echo html_entity_decode($transaction_type['name']); ?>">
				<?php echo ucfirst($transaction_type['label']); ?></option>
				<?php
			} ?>
		</select>
	</div>

	<div class="select-form  <?php  echo new_html_entity_decode($is_mobile_hide); ?>">
		<select name="status" data-live-search="true" id="status"
		class="form-control selectpicker "
		data-none-selected-text="<?php echo _l('real_status'); ?>">
		<option value="<?php echo html_entity_decode('0'); ?>">
				<?php echo _l('real_status'); ?></option>
		<?php foreach ($property_status as $status) {
			?>
			<option value="<?php echo html_entity_decode($status['id']); ?>">
				<?php echo ucfirst($status['name']); ?></option>
				<?php
			} ?>
		</select>
	</div>

	<div class="input-form price  <?php  echo new_html_entity_decode($is_mobile_hide); ?>">
		<?php echo render_input('min_price_search', '', '', 'number', ['step' => 'any', 'min' => 0, 'placeholder' => _l('real_min_price')]); ?>
	</div>
	<div class="input-form price  <?php  echo new_html_entity_decode($is_mobile_hide); ?>">
		<?php echo render_input('max_price_search', '', '', 'number', ['step' => 'any', 'min' => 0, 'placeholder' => _l('real_max_price')]); ?>
	</div>


	<div class="select-form  <?php  echo new_html_entity_decode($is_mobile_hide); ?>">
		<select name="beds_search" data-live-search="true" id="beds_search"
		class="form-control selectpicker "
		data-none-selected-text="<?php echo _l('real_Beds'); ?>">
		<?php foreach ($beds as $bed) {
			?>
			<option value="<?php echo html_entity_decode($bed['name']); ?>">
				<?php echo ucfirst($bed['label']); ?></option>
				<?php
			} ?>
		</select>
	</div>
	<div class="select-form  <?php  echo new_html_entity_decode($is_mobile_hide); ?>">
		<select name="baths_search" data-live-search="true" id="baths_search"
		class="form-control selectpicker "
		data-none-selected-text="<?php echo _l('real_baths'); ?>">
		<?php foreach ($baths as $bath) {
			?>
			<option value="<?php echo html_entity_decode($bath['name']); ?>">
				<?php echo ucfirst($bath['label']); ?></option>
				<?php
			} ?>
		</select>
	</div>
	<div class="filter-form">
		<button class="btn btn-primary" onclick="filter_form()"><?php echo _l('real_filters'); ?><i class="fa-solid fa-sliders mleft15"></i></button>
	</div>

	<div class="_buttons">
		<?php 
		if(isset($is_client)){
			$switch_map_url = site_url('realestate/client/switch_map/' . $switch_map);
		}elseif(isset($is_broker)){
			$switch_map_url = site_url('realestate/broker/switch_map/' . $switch_map);
		}else{
			$switch_map_url = $site_url . ('switch_map/' . $switch_map);
		}
		?>
		<a href="<?php echo html_entity_decode($switch_map_url); ?>"
			class="btn btn-secondary" data-toggle="tooltip" data-placement="top"
			data-title="<?php echo html_entity_decode($switch_map) == 1 ? _l('listings_switch_to_map') : _l('switch_to_list_view'); ?>">
			<?php if ($switch_map == 1) { ?>
				<i class="fa-regular fa-map"></i>
				<?php echo _l('real_map'); ?>
			<?php } else { ?>
				<i class="fa-solid fa-list"></i>
				<?php echo _l('real_list'); ?>

			<?php }; ?>
		</a>
	</div>
</div>