<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(is_invoice_overdue($invoice)){ ?>

<div class="row">
  <div class="col-md-12">
    <div class="text-center text-white danger-bg">
      <h5><?php echo _l('overdue_by_days', get_total_days_overdue($invoice->duedate)) ?></h5>
    </div>
  </div>
</div>
<?php } ?>
<style>
/* Just to play with animations */
.copiedtext {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	text-align: center;
	opacity: 0;
	transform: translateY(-1em);
	color: #000;
	transition: all .500s;
}
.copied .copiedtext {
	opacity: 1;
	transform: translateY(-2em);
}
</style>
<div class="mtop15 preview-top-wrapper">
  <div class="row">
    <div class="col-md-3">
      <div class="mbot30">
        <div class="invoice-html-logo"> <?php echo get_dark_company_logo(); ?> </div>
      </div>
    </div>
    <div class="clearfix"></div>
  </div>
  <div class="top" data-sticky data-sticky-class="preview-sticky-header">
    <div class="container preview-sticky-container">
      <div class="row">
        <div class="col-md-12">
          <div class="pull-left">
            <h3 class="bold no-mtop invoice-html-number no-mbot"> <span class="sticky-visible hide"> <?php echo format_invoice_number($invoice->id); ?> </span> </h3>
            <h4 class="invoice-html-status mtop7"> <?php echo format_invoice_status($invoice->status, '', true); ?> </h4>
          </div>
          <div class="visible-xs">
            <div class="clearfix"></div>
          </div>
          <a href="<?php echo site_url('invoice/' . $invoice->id .'/' . $invoice->hash); ?>" class="btn btn-default pull-right mtop5 mright5 action-button go-to-portal"> Voltar a fatura </a>
          <?php if (is_client_logged_in() && has_contact_permission('invoices')) { ?>
          <a href="<?php echo site_url('clients/invoices/'); ?>" class="btn btn-default pull-right mtop5 mright5 action-button go-to-portal"> <?php echo _l('client_go_to_dashboard'); ?> </a>
          <?php } ?>
          <div class="clearfix"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
  <div class="panel-body">
    <div class="col-md-10 col-md-offset-1">
      <div class="row mtop20">
        <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
          <h4 class="bold invoice-html-number"><?php echo format_invoice_number($invoice->id); ?></h4>
          <address class="invoice-html-company-info">
          <?php echo format_organization_info(); ?>
          </address>
        </div>
        <div class="col-sm-6 text-right transaction-html-info-col-right"> <span class="bold invoice-html-bill-to"><?php echo _l('invoice_bill_to'); ?>:</span>
          <address class="invoice-html-customer-billing-info">
          <?php echo format_customer_info($invoice, 'invoice', 'billing'); ?>
          </address>
          <!-- shipping details -->
          <?php if ($invoice->include_shipping == 1 && $invoice->show_shipping_on_invoice == 1) { ?>
          <span class="bold invoice-html-ship-to"><?php echo _l('ship_to'); ?>:</span>
          <address class="invoice-html-customer-shipping-info">
          <?php echo format_customer_info($invoice, 'invoice', 'shipping'); ?>
          </address>
          <?php } ?>
          <p class="no-mbot invoice-html-date"> <span class="bold"> <?php echo _l('invoice_data_date'); ?> </span> <?php echo _d($invoice->date); ?> </p>
          <?php if (!empty($invoice->duedate)) { ?>
          <p class="no-mbot invoice-html-duedate"> <span class="bold"><?php echo _l('invoice_data_duedate'); ?></span> <?php echo _d($invoice->duedate); ?> </p>
          <?php } ?>
          <?php if ($invoice->sale_agent != 0 && get_option('show_sale_agent_on_invoices') == 1) { ?>
          <p class="no-mbot invoice-html-sale-agent"> <span class="bold"><?php echo _l('sale_agent_string'); ?>:</span> <?php echo get_staff_full_name($invoice->sale_agent); ?> </p>
          <?php } ?>
          <?php if ($invoice->project_id != 0 && get_option('show_project_on_invoice') == 1) { ?>
          <p class="no-mbot invoice-html-project"> <span class="bold"><?php echo _l('project'); ?>:</span> <?php echo get_project_name_by_id($invoice->project_id); ?> </p>
          <?php } ?>
          <?php $pdf_custom_fields = get_custom_fields('invoice', array('show_on_pdf' => 1, 'show_on_client_portal' => 1));
               foreach ($pdf_custom_fields as $field) {
                  $value = get_custom_field_value($invoice->id, $field['id'], 'invoice');
                  if ($value == '') {
                     continue;
                  } ?>
          <p class="no-mbot"> <span class="bold"><?php echo $field['name']; ?>: </span> <?php echo $value; ?> </p>
          <?php } ?>
        </div>
      </div>
      <div class="col-md-6 col-md-offset-6">
        <table class="table text-right">
          <tbody>
            <tr id="subtotal">
              <td><span class="bold"><?php echo _l('invoice_subtotal'); ?></span></td>
              <td class="subtotal"><?php echo app_format_money($invoice->subtotal, $invoice->currency_name); ?></td>
            </tr>
            <?php if (is_sale_discount_applied($invoice)) { ?>
            <tr>
              <td><span class="bold"><?php echo _l('invoice_discount'); ?>
                <?php if (is_sale_discount($invoice, 'percent')) { ?>
                (<?php echo app_format_number($invoice->discount_percent, true); ?>%)
                <?php } ?>
                </span></td>
              <td class="discount"><?php echo '-' . app_format_money($invoice->discount_total, $invoice->currency_name); ?></td>
            </tr>
            <?php } ?>
            <?php
               
                     ?>
            <?php if ((int)$invoice->adjustment != 0) { ?>
            <tr>
              <td><span class="bold"><?php echo _l('invoice_adjustment'); ?></span></td>
              <td class="adjustment"><?php echo app_format_money($invoice->adjustment, $invoice->currency_name); ?></td>
            </tr>
            <?php } ?>
            <tr>
              <td><span class="bold"><?php echo _l('invoice_total'); ?></span></td>
              <td class="total"><?php echo app_format_money($invoice->total, $invoice->currency_name); ?></td>
            </tr>
            <?php if (count($invoice->payments) > 0 && get_option('show_total_paid_on_invoice') == 1) { ?>
            <tr>
              <td><span class="bold"><?php echo _l('invoice_total_paid'); ?></span></td>
              <td><?php echo '-' . app_format_money(sum_from_table(db_prefix() . 'invoicepaymentrecords', array('field' => 'amount', 'where' => array('invoiceid' => $invoice->id))), $invoice->currency_name); ?></td>
            </tr>
            <?php } ?>
            <?php if (get_option('show_credits_applied_on_invoice') == 1 && $credits_applied = total_credits_applied_to_invoice($invoice->id)) { ?>
            <tr>
              <td><span class="bold"><?php echo _l('applied_credits'); ?></span></td>
              <td><?php echo '-' . app_format_money($credits_applied, $invoice->currency_name); ?></td>
            </tr>
            <?php } ?>
            <?php if (get_option('show_amount_due_on_invoice') == 1 && $invoice->status != Invoices_model::STATUS_CANCELLED) { ?>
            <tr>
              <td><span class="<?php if ($invoice->total_left_to_pay > 0) {
                                                echo 'text-danger ';
                                             } ?>bold"><?php echo _l('invoice_amount_due'); ?></span></td>
              <td><span class="<?php if ($invoice->total_left_to_pay > 0) {
                                                echo 'text-danger';
                                             } ?>"> <?php echo app_format_money($invoice->total_left_to_pay, $invoice->currency_name); ?> </span></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if($invoice->status !== '2') {  ?>
    <div class="clearfix"></div>
    <hr>
    <div class="col-md-4 col-md-offset-4 text-center"> <img class="img-responsive  center-block" src="<?php echo site_url('modules/asaas/assets/img/spinner.gif') ?>" style="height:80px" />
      <h4 > Aguardando pagamento</h4>
    </div>
    <div class="clearfix"></div>
    <hr>
    <div class="row mtop50 qrcode"> 
      <!--       <div class="col-md-8 col-md-offset-2 qrcode">  -->
      <div class="col-md-6 ">
        <ul>
          <li class="sc-bWNSNh ijsSky how-to pixx"> <span><strong>1. Abra o app do seu banco e acesse o ambiente Pix.</strong></span> </li>
          <li class="sc-bWNSNh ijsSky how-to pixx"> <span><strong>2. escolha a opção pagar com qr code e escaneie o código ao
            lado</strong></span> </li>
          <li class="sc-bWNSNh ijsSky how-to pixx"> <span><strong>3. Confirme as informações e finalize o pagamento no seu
            APP.</strong></span> </li>
        </ul>
        <div class="form-group">
          <textarea  rows="6" class="form-control text-center" id="payload" readonly><?php echo $response->payload; ?></textarea>
        </div>
        <div class="text-center">
          <button class="btn btn-success " data-clipboard-action="copy" data-clipboard-target="#payload" type="button">Copiar a chave<span class="btn btn-success copiedtext" aria-hidden="true">Copiado!</span></button>
          <!--   
             <button style="margin-top:10px; width: 100%; " class="btn btn-info btn-copy-qrcode">Copiar
                    Código</button>
         --> 
        </div>
      </div>
      <!--    <hr>     <div class="row">
       <div class="col-md-4 col-md-offset-4"> -->
      <div class="col-md-6">
        <div class="form-group"><img class="img-responsive" src="data:image/gif;base64,<?php echo $response->encodedImage ?>" style="height:auto" /> </div>
      </div>
    </div>
    <!--      </div>
    </div> -->
    <?php }  ?>
  </div>
</div>
<script src="<?php echo site_url('modules/asaas/assets/js/clipboard.min.js') ?>" type="text/javascript"> </script> 
<script>

    var clipboard = new ClipboardJS('.btn');

    clipboard.on('success', function(e) {
        console.log(e);
		alert_float("success", "Copiado!")
    });

    clipboard.on('error', function(e) {
        console.log(e);
		   alert_float("danger", "Erro ao copiar")
    });
    </script> 
<script>

var hash = '<?php echo $invoice->hash ?>';

var invoiceId = '<?php echo $invoice->id ?>';

/*jshint esversion: 6 */

    $('.btn-copy-qrcode').click(function () {
            const _this = $(this);
            _this.text('Copiado com sucesso.');
            setTimeout(() => {
                _this.text('COPIAR CHAVE');
        }, 1000);
        const editor = document.getElementsByClassName('input-get-qrcode');
        editor[0].select();
        editor[0].focus();
        editor[1].select();
       editor[1].focus();
        document.execCommand('copy');
	});

   $('.btn-copy-barcode').click(function () {
            const _this = $(this);
            _this.text('Copiado com sucesso.');
            setTimeout(() => {
                _this.text('COPIAR CHAVE');
        }, 1000);
        const editor = document.getElementsByClassName('input-get-barcode');
        editor[0].select();
        editor[0].focus();
        editor[1].select();
       editor[1].focus();
        document.execCommand('copy');
	});

setTimeout(() => {
                    setInterval(() => {
                        $.get(site_url+'asaas/get_invoice_data/'+ hash, function (response) {
							
							console.log(response);
							
                    if (response == '1') {
                        $('.btn-pagar').attr('disabled', true);
					/*	$('.qrcode').remove(); */           
					    $('.invoice-status-1').css({
                            color: "#84c529",
                            border: "1px solid #84c529",
                            background: "0 0"
                        }).text('Pagamento confirmado');
					
                        $('.invoice-html-status').find("span").removeClass('invoice-status-1 label-danger').addClass('invoice-status-2 label-success').html('PAGO');
				//			$('.qrcode').empty().append('<div class="text-center"><div class="btn btn-success"><i class="fa fa-check-circle fa-5x" aria-hidden="true"></i><h1 class="">Pagamento confirmado!</h1></div></div>');
						
					/*	 alert_float("danger", "danger"); */
					window.location.replace(site_url+'invoice/'+ invoiceId +'/'+hash);
                    }
                });
            }, 2000);
        }, 10000);
</script>