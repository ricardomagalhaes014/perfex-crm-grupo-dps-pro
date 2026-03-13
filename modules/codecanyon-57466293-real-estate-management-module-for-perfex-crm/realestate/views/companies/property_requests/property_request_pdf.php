<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';
$title_font_size = 'font-size:'.($font_size + 15).'px;';

$info_right_column .= '<span style="'.$title_font_size.'"><b>' . _l('real_request') . '</b></span><br />';
$info_right_column .= '<b># ' . $property_request_number . '</b>';


// Add logo
if(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id == 0 ){
	$info_left_column .= pdf_logo_url();
}elseif(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id > 0){
	$info_left_column .= company_profile_image($property_request->company_id, ['img', 'img-responsive']);
}elseif($property_request->broker_id > 0){ 
	$info_left_column .= company_profile_image($property_request->broker_id, ['img', 'img-responsive']);
}

// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$organization_info = '<div >';
if(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id == 0 ){
	$organization_info .= format_organization_info();
}elseif(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id > 0){
	$organization_info .= real_get_company_name($property_request->company_id, true, false, true);
}elseif($property_request->broker_id > 0){ 
	$organization_info .= real_get_company_name($property_request->broker_id, true, false, true);
}

$organization_info .= '</div>';

// Bill to
$invoice_info = '<b>' . _l('invoice_bill_to') . ':</b>';
$invoice_info .= '<div>';
$invoice_info .= format_customer_info($property_request, 'invoice', 'billing');
$invoice_info .= '</div>';

// ship to to
$invoice_info .= '<br /><b>' . _l('ship_to') . ':</b>';
$invoice_info .= '<div>';
$invoice_info .= format_customer_info($property_request, 'invoice', 'shipping');
$invoice_info .= '</div>';

$invoice_info .= '<br />' . _l('real_created_date') . ' ' . _d($property_request->datecreated) . '<br />';


$left_info  = $swap == '1' ? $invoice_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $invoice_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$item_description_width = 20;


$table_font_size = 'font-size:'.($font_size + 4).'px;';
$items = '';
$items .='<table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop" cellpadding="6">
<thead>';
$items .= '<tr height="40" bgcolor="' . get_option('pdf_table_heading_color') . '" style="color:' . get_option('pdf_table_heading_text_color') . '; ">';
$items.='<th width="5%" align="center">#</th>
<th width="20%" align="left" >'. _l('real_property_name').'</th>';

if($property_request->request_type == 'buy'){
	$items.='<th width="40%" align="right">'. _l('real_expected_buy_date').'</th>';
}else{
	$items.='<th width="15%" align="right">'. _l('real_preferred_lease_start_date').'</th>';
	$items .='<th width="10%" align="right">'. _l('real_term').'</th>';
	$items .='<th width="15%" align="right">'. _l('real_end_date').'</th>';
}
$items .='<th width="10%" align="right">'. _l('real_property_price').'</th>';
$items .='<th width="10%" align="right">'. _l('real_contract_amount').'</th>';
$items .='<th width="15%" align="right">'. _l('real_inspection_date').'</th>';

$items .='</tr>
</thead>
<tbody class="tbody-main" style="'.$table_font_size.'">';

$rental_type = get_property_name($property_request->item_id, false, true);
$rental_type_s = '';
$_rental_type = '';
if($rental_type != '' && $property_request->term_month > 1){
	$rental_type_s = $rental_type.'s';
	$_rental_type = ' per '.$rental_type;
}
// render item table start

$itemHTML = '';

// Open table row
$itemHTML .= '<tr style="'.$table_font_size.'">';

// Table data number
$itemHTML .= '<td align="center" width="5%">1</td>';

$itemHTML .= '<td class="description" align="left;" width="'.$item_description_width.'%">';

/**
 * Item long description
 */
$itemHTML .= '<span>' . get_property_name($property_request->item_id) . '</span>';
$itemHTML .= '</td>';

/**
 * Item quantity
 */

if($property_request->request_type == 'buy'){
	$itemHTML .= '<td align="right" width="40%">' . _d($property_request->date). '</td>';
}else{
	$itemHTML .= '<td align="right" width="15%">' . _d($property_request->date). '</td>';
	$itemHTML .= '<td align="right" width="10%">' . ($property_request->term_month). $rental_type_s . '</td>';
	$itemHTML .= '<td align="right" width="15%">' . _d($property_request->duedate) . '</td>';
}

// sub total
$itemHTML .= '<td class="amount" align="right" width="10%">' . app_format_money((float)$property_request->property_price, $property_request->currency).$_rental_type . '</td>';
$itemHTML .= '<td class="amount" align="right" width="10%">' . app_format_money((float)$property_request->contract_total, $property_request->currency) . '</td>';

$inspect_property_date = _l('real_inspected_answer_yes');
if($property_request->inspect_property == 1 && $property_request->inspection_date != null ){
	$inspect_property_date = _d($property_request->inspection_date);
}elseif($property_request->inspect_property == 0){
	$inspect_property_date = _l('real_inspected_answer_no');
}

$itemHTML .= '<td class="amount" align="right" width="15%">' . $inspect_property_date . '</td>';


// Close table row
$itemHTML .= '</tr>';

$items .= $itemHTML;

// render item table end

$items.= '</tbody>
</table>';

$tblhtml = $items;
$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(4);

if (!empty($property_request->clientnote)) {
	$pdf->Ln(4);
	$pdf->SetFont($font_name, 'B', $font_size);
	$pdf->Cell(0, 0, _l('real_client_note'), 0, 1, 'L', 0, '', 0);
	$pdf->SetFont($font_name, '', $font_size);
	$pdf->Ln(2);
	$pdf->writeHTMLCell('', '', '', '', $property_request->clientnote, 0, 1, false, true, 'L', true);
}

