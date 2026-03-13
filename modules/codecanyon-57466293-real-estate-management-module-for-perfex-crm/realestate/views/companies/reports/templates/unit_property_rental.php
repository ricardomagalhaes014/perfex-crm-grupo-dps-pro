<div class="col-md-12">
	<div class="table-responsive">
		<table class="table table-bordered">
			<thead>
				<th align="center">#</th>
				<th align="left"><?php echo _l('real_listing_type') ?></th>
				<th align="left"><?php echo _l('client') ?></th>
				<th align="left"><?php echo _l('real_property_name') ?></th>
				<th align="left"><?php echo _l('real_unit_number') ?></th>
				<th align="center"><?php echo _l('real_lease_start') ?></th>
				<th align="center"><?php echo _l('real_lease_end') ?></th>
				<th align="center"><?php echo _l('real_proj_completion_date') ?></th>
				<th align="right"><?php echo _l('real_total_amount') ?></th>
			</thead>
			<tbody>
				<?php 
				$listing_type = '';
				$total_amount = 0;
				$index = 0;
				?>
				<?php foreach ($property_rentals as $key => $value) { ?>
					<?php if($key == 0 || $listing_type != $value['listing_type']){
						$index = 0;
						$listing_type = $value['listing_type'];
						?>
						<tr class="tw-font-semibold">
							<td></td>
							<td colspan="8"><?php echo new_html_entity_decode($value['listing_type']); ?></td>
						</tr>
					<?php } ?>
					<?php 
					$index ++;
					$total_amount += (float)$value['contract_total'];
					?>
					<tr>
						<td><?php echo new_html_entity_decode($index); ?></td>
						<td></td>
						<td ><?php echo new_html_entity_decode($value['company']); ?></td>
						<td><?php echo new_html_entity_decode($value['commodity_code'].' '.$value['description']); ?></td>
						<td><?php echo new_html_entity_decode($value['use_code']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['date']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['duedate']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['proj_completion_date']); ?></td>
						<td align="right"><?php echo app_format_money($value['contract_total'], $base_currency_id); ?></td>
					</tr>

					<?php if( (isset($property_rentals[$key+1]) && $listing_type != $property_rentals[$key+1]['listing_type']) || (count($property_rentals) == $key+1)){
						
						?>
						<tr>
							<th colspan="7" class="text-right tw-font-semibold"><?php echo _l('real_sub_total') ?> : </th>
							<th colspan="2" align="right"><?php echo app_format_money($total_amount, $base_currency_id); ?></th>
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