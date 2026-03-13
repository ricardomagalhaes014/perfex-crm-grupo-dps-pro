<div class="col-md-12">
	<div class="table-responsive">
		<table class="table table-bordered">
			<thead>
				<th align="center">#</th>
				<th align="left"><?php echo _l('client') ?></th>
				<th align="left"><?php echo _l('real_property_name') ?></th>
				<th align="left"><?php echo _l('real_listing_type') ?></th>
				<th align="left"><?php echo _l('real_unit_number') ?></th>
				<th align="center"><?php echo _l('real_Beds') ?></th>
				<th align="center"><?php echo _l('real_baths') ?></th>
				<th align="center"><?php echo _l('real_lot_size') ?></th>
				<th align="center"><?php echo _l('real_lease_start') ?></th>
				<th align="center"><?php echo _l('real_lease_end') ?></th>
				<th align="right"><?php echo _l('real_total_amount') ?></th>
			</thead>
			<tbody>
				<?php 
				$clientid = '';
				$total_amount = 0;
				$index = 0;
				?>
				<?php foreach ($leases_endings as $key => $value) { ?>
					<?php if($key == 0 || $clientid != $value['clientid']){
						$index = 0;
						$clientid = $value['clientid'];
						?>
						<tr class="tw-font-semibold">
							<td></td>
							<td colspan="10"><?php echo new_html_entity_decode($value['company']); ?></td>
						</tr>
					<?php } ?>
					<?php 
					$index ++;
					$total_amount += (float)$value['contract_total'];
					?>
					<tr>
						<td><?php echo new_html_entity_decode($index); ?></td>
						<td></td>
						<td><?php echo new_html_entity_decode($value['commodity_code'].' '.$value['description']); ?>
						<br>
						<?php echo new_html_entity_decode($value['street_number'].' '.$value['street_dir_pre'].' '.$value['street_name'].' '.$value['city'].' '.real_remove_underscore($value['state']).' '.get_country_name($value['country'])); ?>
					</td>
					<td ><?php echo new_html_entity_decode($value['listing_type']); ?></td>
					<td><?php echo new_html_entity_decode($value['use_code']); ?></td>
					<td align="center"><?php echo new_html_entity_decode($value['beds']); ?></td>
					<td align="center"><?php echo new_html_entity_decode($value['full_baths']); ?></td>
					<td align="center"><?php echo new_html_entity_decode($value['lot_size_acres']); ?></td>
					<td align="center"><?php echo new_html_entity_decode($value['date']); ?></td>
					<td align="center"><?php echo new_html_entity_decode($value['duedate']); ?></td>
					<td align="right"><?php echo app_format_money($value['contract_total'], $base_currency_id); ?></td>
				</tr>

				<?php if( (isset($leases_endings[$key+1]) && $clientid != $leases_endings[$key+1]['clientid']) || (count($leases_endings) == $key+1)){

					?>
					<tr>
						<th colspan="9" class="text-right tw-font-semibold"><?php echo _l('real_sub_total') ?> : </th>
						<th colspan="3" align="right"><?php echo app_format_money($total_amount, $base_currency_id); ?></th>
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