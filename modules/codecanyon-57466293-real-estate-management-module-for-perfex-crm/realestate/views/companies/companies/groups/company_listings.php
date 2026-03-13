<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
	<div class="col-md-6">
		<h4 class=""><?php echo _l('real_real_estate_agent_listings'); ?></h4>
	</div>
</div>

<ul class="nav nav-tabs display-flex justify-content-center" role="tablist" id="filters_types">
	<li role="presentation" class="active"><a href="#company_plan" aria-controls="company_plan" role="tab" data-toggle="tab"><i class="fa-regular fa-grid" aria-hidden="true"></i> <?php echo _l('real_view_grid'); ?></a></li>
	<li role="presentation"><a href="#freelance_plan" aria-controls="freelance_plan" role="tab" data-toggle="tab"><i class="fa-solid fa-map" aria-hidden="true"></i> <?php echo _l('real_view_map'); ?></a></li>
</ul>

<div class="tab-content">
	<div role="tabpanel" class="tab-pane active" id="company_plan">
		<?php $this->load->view('companies/property_listings/utilities/table_html', ['table_name_custom' => 'c_property_listing_table']); ?>
	</div>
	<div role="tabpanel" class="tab-pane" id="freelance_plan">
		<?php if(count($map_property_listing) > 0){ ?>
			<div id="map_area" class="listing_map"></div>
		<?php }else{ ?>
			<h4><?php echo _l('real_No_entries_found'); ?></h4>
		<?php } ?>
	</div>
</div>

<div id="modal_wrapper"></div>
