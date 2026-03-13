<div class="col-md-12">
	<div class="table-responsive">
		<table class="table table-bordered">
			<thead>
				<th align="center">#</th>
				<th align="left"><?php echo _l('client') ?></th>
				<th align="left"><?php echo _l('real_property_name') ?></th>
				<th align="left"><?php echo _l('real_listing_type') ?></th>
				<th align="left"><?php echo _l('real_unit_number') ?></th>
				<th align="center"><?php echo _l('real_lease_start') ?></th>
				<th align="center"><?php echo _l('real_lease_end') ?></th>
				<th align="center"><?php echo _l('invoice') ?></th>
				<th align="right"><?php echo _l('real_total_amount') ?></th>
			</thead>
			<tbody>
				<?php 
				$clientid = '';
				$total_amount = 0;
				$index = 0;
				?>
				<?php foreach ($delinquent_tenants as $key => $value) { ?>
					<?php if($key == 0 || $clientid != $value['clientid']){
						$index = 0;
						$clientid = $value['clientid'];
						?>
						<tr class="tw-font-semibold">
							<td></td>
							<td colspan="8"><?php echo new_html_entity_decode($value['company']); ?></td>
						</tr>
					<?php } ?>
					<?php 
					$index ++;
					$total_amount += (float)$value['contract_total'];
					?>
					<tr>
						<td><?php echo new_html_entity_decode($index); ?></td>
						<td></td>
						<td><?php echo new_html_entity_decode($value['commodity_code'].' '.$value['description']); ?></td>
						<td ><?php echo new_html_entity_decode($value['listing_type']); ?></td>
						<td><?php echo new_html_entity_decode($value['use_code']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['date']); ?></td>
						<td align="center"><?php echo new_html_entity_decode($value['duedate']); ?></td>
						<td align="center"><?php echo format_invoice_number($value['invoice_id']); ?></td>
						<td align="right"><?php echo app_format_money($value['contract_total'], $base_currency_id); ?></td>
					</tr>

					<?php if( (isset($delinquent_tenants[$key+1]) && $clientid != $delinquent_tenants[$key+1]['clientid']) || (count($delinquent_tenants) == $key+1)){
						
						?>
						<tr>
							<th colspan="6" class="text-right tw-font-semibold"><?php echo _l('real_sub_total') ?> : </th>
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