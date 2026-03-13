
<div class="col-md-12">
	<div class="table-responsive">
		<table class="table table-bordered">
			<thead>
				<th align="center">#</th>
				<th align="left"><?php echo _l('real_listing_type') ?></th>
				<th align="left"><?php echo _l('client') ?></th>
				<th align="left"><?php echo _l('Name') ?></th>
				<th align="left"><?php echo _l('real_unit_number') ?></th>
				<th align="center"><?php echo _l('real_date_sold') ?></th>
				<th align="right"><?php echo _l('real_list_price') ?></th>
			</thead>
			<tbody>
				<?php 
				$listing_type = '';
				$total_amount = 0;
				$index = 0;
				?>
				<?php foreach ($unit_property_solds as $key => $value) { ?>
					<?php if($key == 0 || $listing_type != $value['listing_type']){
						$index = 0;
						$listing_type = $value['listing_type'];
						?>
						<tr class="tw-font-semibold">
							<td></td>
							<td><?php echo html_entity_decode($value['listing_type']); ?></td>
							<td colspan="5">
							</tr>
						<?php } ?>
						<?php 
						$index ++;
						$total_amount += (float)$value['contract_total'];

						?>
						<tr>
							<td><?php echo html_entity_decode($index); ?></td>
							<td></td>
							<td ><?php echo html_entity_decode($value['company']); ?></td>
							<td><?php echo html_entity_decode($value['commodity_code'].' '.$value['description']); ?></td>
							<td><?php echo html_entity_decode($value['use_code']); ?></td>
							<td align="center"><?php echo html_entity_decode($value['date_sold']); ?></td>
							<td align="right"><?php echo app_format_money($value['contract_total'], $base_currency_id); ?></td>
						</tr>

						<?php if( (isset($unit_property_solds[$key+1]) && $listing_type != $unit_property_solds[$key+1]['listing_type']) || (count($unit_property_solds) == $key+1)){
							
							?>
							<tr>
								<th colspan="6" class="text-right tw-font-semibold"><?php echo _l('real_sub_total') ?> : </th>
								<th colspan="1" align="right"><?php echo app_format_money($total_amount, $base_currency_id); ?></th>
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