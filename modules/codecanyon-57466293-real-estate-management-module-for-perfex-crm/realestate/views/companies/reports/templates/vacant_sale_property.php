
<div class="col-md-12">
	<div class="table-responsive">
		<table class="table table-bordered">
			<thead>
				<th align="center">#</th>
				<th align="left"><?php echo _l('real_listing_type') ?></th>
				<th align="left"><?php echo _l('real_property_code') ?></th>
				<th align="left"><?php echo _l('real_property_name') ?></th>
				<th align="left"><?php echo _l('real_unit_number') ?></th>
				<th align="center"><?php echo _l('real_proj_completion_date') ?></th>
				<th align="center"><?php echo _l('real_Beds') ?></th>
				<th align="center"><?php echo _l('real_baths') ?></th>
				<th align="center"><?php echo _l('real_lot_size') ?></th>
				<th align="right"><?php echo _l('real_list_price') ?></th>
			</thead>
			<tbody>
				<?php 
				$listing_type = '';
				$total_amount = 0;
				$index = 0;
				?>
				<?php foreach ($vacant_sales as $key => $value) { ?>
					<?php if($key == 0 || $listing_type != $value['listing_type']){
						$index = 0;
						$listing_type = $value['listing_type'];
						?>
						<tr class="tw-font-semibold">
							<td></td>
							<td colspan="9"><?php echo new_html_entity_decode($value['listing_type']); ?></td>
						</tr>
					<?php } ?>
					<?php 
					$index ++;
					$total_amount += (float)$value['rate'];

					?>
					<tr>
						<td><?php echo new_html_entity_decode($index); ?></td>
						<td></td>
						<td ><?php echo new_html_entity_decode($value['commodity_code']); ?></td>
						<td><?php echo new_html_entity_decode($value['description']); ?></td>
						<td><?php echo new_html_entity_decode($value['use_code']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['proj_completion_date']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['beds']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['full_baths']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['lot_size_acres']); ?></td>
						<td align="right"><?php echo app_format_money($value['rate'], $base_currency_id); ?></td>
					</tr>

					<?php if( (isset($vacant_sales[$key+1]) && $listing_type != $vacant_sales[$key+1]['listing_type']) || (count($vacant_sales) == $key+1)){
						
						?>
						<tr>
							<th colspan="6" class="text-right tw-font-semibold"><?php echo _l('real_sub_total') ?> : </th>
							<th colspan="4" align="right"><?php echo app_format_money($total_amount, $base_currency_id); ?></th>
						</tr>
						<?php 
						$index = 0;
						$total_amount = 0;
						?>
					<?php } ?>

				<?php } ?>
			</tbody>
		</table>
	</div>
</div>