<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('app_customers_portal_head'); ?>

<div class="col-md-12">
	<h4 class="tw-mt-0 tw-font-semibold tw-text-neutral-700 section-heading section-heading-invoices">
		<?php echo e($title); ?>
	</h4>
	<div class="panel_s">
		<div class="panel-body">
			<hr />
			<table class="table dt-table table-requests" data-order-col="1" data-order-type="desc">
				<thead>
					<tr>
						<th class="th-invoice-number"><?php echo _l('real_request_number'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_property_name'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_property_price'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_contract_amount'); ?></th>
						<th class="th-invoice-number hide"><?php echo _l('real_term'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_created_date'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_expected_buy_date'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_end_date'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_status'); ?></th>
						<th class="th-invoice-number"><?php echo _l('contract'); ?></th>
						<th class="th-invoice-number"><?php echo _l('invoice'); ?></th>
						<th class="th-invoice-number"><?php echo _l('real_options'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($requests as $request){ ?>
						<?php 
						$rental_type = get_property_name($request['item_id'], false, true);
						$rental_type_s = '';

						$_rental_type = '';
						$rental_type_s = $rental_type;
						
						if($rental_type != '' && $request['term_month'] > 1){
							$rental_type_s = $rental_type.'s';
							$_rental_type = ' per '.$rental_type;
						}
						?>
						<tr>
							<td data-order="<?php echo new_html_entity_decode($request['id']); ?>"><a href="<?php echo site_url('realestate/client/request_detail/'.$request['id']) ?>"><?php echo html_entity_decode($request['code']); ?></a></td>
							<td data-order="<?php echo new_html_entity_decode($request['item_id']); ?>"><a href="<?php echo site_url('realestate/client/property_listing_detail/'.$request['item_id']) ?>"><?php echo get_property_name($request['item_id']); ?></a></td>

							<td data-order="<?php echo new_html_entity_decode($request['total']); ?>"><?php echo  app_format_money($request['total'], $base_currency_id); ?></td>
							<td data-order="<?php echo new_html_entity_decode($request['contract_total']); ?>"><?php echo app_format_money($request['contract_total'], $base_currency_id); ?></td>

							<td data-order="<?php echo new_html_entity_decode($request['term_month']); ?>" class="hide"><?php echo html_entity_decode(($request['term_month']).$rental_type_s); ?></td>
							<td data-order="<?php echo new_html_entity_decode($request['datecreated']); ?>"><?php echo html_entity_decode(_dt($request['datecreated'])); ?></td>
							<td data-order="<?php echo new_html_entity_decode($request['date']); ?>"><?php echo html_entity_decode(_d($request['date'])); ?></td>
							<td data-order="<?php echo new_html_entity_decode($request['duedate']); ?>"><?php echo html_entity_decode(_d($request['duedate'])); ?></td>
							
							<td data-order="<?php echo new_html_entity_decode($request['status']); ?>"><?php echo render_property_request_status_html($request['id'], 'order', $request['status'] ); ?></td>

							<?php if($request['contract_id'] != 0){ ?>
								<td data-order="<?php echo new_html_entity_decode($request['contract_id']); ?>"><a href="<?php echo site_url('contract/'.$request['contract_id'].'/'.real_get_contract_hash($request['contract_id'])) ?>"><?php echo get_contract_name($request['contract_id']); ?></a></td>
							<?php }else{ ?>
								<td data-order="<?php echo new_html_entity_decode($request['contract_id']); ?>"></td>
							<?php } ?>

							<?php if($request['invoice_id'] != 0){ ?>
								<td data-order="<?php echo new_html_entity_decode($request['invoice_id']); ?>"><a href="<?php echo site_url('invoice/'.$request['invoice_id'].'/'.real_get_invoice_hash($request['invoice_id'])) ?>"><?php echo format_invoice_number($request['invoice_id']); ?></a></td>
							<?php }else{ ?>
								<td data-order="<?php echo new_html_entity_decode($request['invoice_id']); ?>"></td>
							<?php } ?>

							<?php 
							$option = '';
							$option .='<a href="'. site_url('realestate/client/request_detail/'.$request['id']).'"class="btn btn-default btn-icon mright5" data-toggle="tooltip" data-title="'._l('view').'" data-placement="bottom"><i class="fa-regular fa-eye"></i></a>';

							if($request['status'] == '1'){
								if(is_primary_contact()){
									$option .='<a href="'. site_url('realestate/client/property_request/'.$request['id']).'"class="btn btn-default btn-icon mright5" data-toggle="tooltip" data-title="'._l('edit').'" data-placement="bottom"><i class="fa-regular fa-pen-to-square"></i></a>';
									$option .='<a href="'. site_url('realestate/client/delete_property_request/'.$request['id']).'"class="btn btn-danger _delete btn-icon" data-toggle="tooltip" data-placement="bottom" title="'._l('delete').'"><i class="fa fa-remove"></i></a>';
								}
							}

							$_data = $option;
							?>
							<td data-order="<?php echo new_html_entity_decode($request['id']); ?>"><?php echo new_html_entity_decode($option); ?></td>

						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php real_client_init_tail(); ?>