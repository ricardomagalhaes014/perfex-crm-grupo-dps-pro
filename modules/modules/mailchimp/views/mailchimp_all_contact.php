<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
          <a href="javascript:void(0)" data-toggle="tooltip" title="<?php echo _l('SYNC MAILCHIMP TO '.strtoupper(SYNC_COMPANY_NAME)); ?>"  data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-info mailchimp-sync-btn" id="all_mc_to_jm_contact_sync_btn" data-id="0"><?php echo _l(' SYNC '); ?><img src="<?=site_url()?>modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" /> TO
        <?=strtoupper(SYNC_COMPANY_NAME) ;?>
        </a>
        <a href="javascript:void(0)" data-toggle="tooltip" title="SYNC <?=strtoupper(SYNC_COMPANY_NAME) ;?> TO MAILCHIMP" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-info mailchimp-sync-btn" id="all_jm_to_mc_contact_sync_btn" data-id="0"> <?php echo _l('SYNC'); ?> <?=strtoupper(SYNC_COMPANY_NAME) ;?> TO 
        <img src="<?=site_url()?>modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" />
        </a>
      </div>
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <?php if(isset($consent_purposes)) { ?>
            <div class="row mbot15">
              <div class="col-md-3 contacts-filter-column">
               <div class="select-placeholder">
                <select name="custom_view" title="<?php echo _l('gdpr_consent'); ?>" id="custom_view" class="selectpicker" data-width="100%">
                 <option value=""></option>
                 <?php foreach($consent_purposes as $purpose) { ?>
                 <option value="consent_<?php echo $purpose['id']; ?>">
                  <?php echo $purpose['name']; ?>
                </option>
                <?php } ?>
              </select>
            </div>
          </div>
        </div>
        <?php } ?>
        <div class="clearfix"></div>
        <?php
        $table_data = array(_l('client_firstname'),_l('client_lastname'));
        if(is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1'){
         array_push($table_data, array(
          'name'=>_l('gdpr_consent') .' ('._l('gdpr_short').')',
          'th_attrs'=>array('id'=>'th-consent', 'class'=>'not-export')
        ));
       }
       $table_data = array_merge($table_data, array(
        _l('client_email'),
        _l('clients_list_company'),
        _l('client_phonenumber'),
        _l('contact_position'),
      ));
      //Mailchimp Integration
      if(isExistMailChimp()){
        $table_data = array_merge($table_data, array(_l('contact_mailchimp_status_col_title')));
        $table_data = array_merge($table_data, array(_l('mailchimp_linked')));
      }
       $custom_fields = get_custom_fields('contacts',array('show_on_table'=>1));
       foreach($custom_fields as $field){
        array_push($table_data,$field['name']);
      }
      render_datatable($table_data,'all-contacts');
      ?>
    </div>
  </div>
</div>
</div>
</div>
</div>
<?php init_tail(); ?>
<?php $this->load->view('admin/clients/client_js'); ?>
<div id="contact_data"></div>
<div id="consent_data"></div>

<?php 
  if(isExistMailChimp()){
    $this->load->view('modals/mailchimp_status_show'); 
  }
?>
<script>
 $(function(){
  var optionsHeading = [];
  var allContactsServerParams = {
   "custom_view": "[name='custom_view']",
 }
 <?php if(is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1'){ ?>
  optionsHeading.push($('#th-consent').index());
  <?php } ?>
  _table_api = initDataTable('.table-all-contacts', window.location.href, optionsHeading, optionsHeading, allContactsServerParams, [0,'asc']);
  if(_table_api) {
   <?php if(is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1'){ ?>
    _table_api.on('draw', function () {
      var tableData = $('.table-all-contacts').find('tbody tr');
      $.each(tableData, function() {
        $(this).find('td:eq(2)').addClass('bg-light-gray');
      });
    });
    $('select[name="custom_view"]').on('change', function(){
      _table_api.ajax.reload()
      .columns.adjust()
      .responsive.recalc();
    });
    <?php } ?>
  }


  //Contact Sync Mailchimp to jm 02-08-2022
  $('body').on('click','#all_mc_to_jm_contact_sync_btn', function(event){
      event.preventDefault();
      var id = $(this).attr('data-id');
      $.ajax({
          type: 'POST',
          data: {customer_id:id},
          url: admin_url+'mailchimp/all_member_sync_mc_to_jm',
          success: function(res){
             data = JSON.parse(res);
              if(data.status == 'success'){
                 alert_float('success', data.message);
                 setTimeout(function(){
                   window.location.reload(true);
                 }, 2000);
              }else{

                alert_float('danger', data.message);
                setTimeout(function(){
                   window.location.reload(true);
                 }, 2000);
              }
          }
      });
  });
  //Contact Sync Mailchimp to jm 02-08-2022
  $('body').on('click','#all_jm_to_mc_contact_sync_btn', function(event){
      event.preventDefault();
      $.ajax({
          type: 'POST',
          data: {},
          url: admin_url+'mailchimp/all_member_sync_jm_to_mc',
          success: function(res){
             data = JSON.parse(res);
              if(data.status == 'success'){
                 alert_float('success', data.message);
                 setTimeout(function(){
                   window.location.reload(true);
                 }, 2000);
              }else{
                alert_float('danger', data.message);
                setTimeout(function(){
                   window.location.reload(true);
                 }, 2000);
              }
          }
      });
  });
  //Mailchimp subscribe/unsubscribe model open
  $("body").on('click', '.subscribed_unsubscribed_icon', function (event, state) {
      var id = $(this).data('id');
      status = 0;
      event.preventDefault();
      $.ajax({
          type: 'POST',
          data: {},
          url: admin_url + '/mailchimp/get_contact_audience_selected_options/' + id,
          success: function(res){
             data = JSON.parse(res);
              if(data.status == 'success'){
                $("#contact_subscribe_modal #contact_id").val(id);
                $("#subscribed_unsubscribed_list_div").html(data.content);
                $('#contact_subscribe_modal').modal('show');
              }else{
                alert_float('danger', data.message);
              }
          }
      });
  });
  //Mailchimp subscribe/unsubscribe action
  $("body").on('change', '#contact_subscribe_modal .contact_subscribe_btn', function (event, state) {

      var thisbutton = $(this);
      event.preventDefault();
      var subscription_status  = $("input[name='subscribed_status[]']:checked").map(function () {
          return this.value;
      }).get();
      var contact_id = $("#contact_subscribe_modal #contact_id").val();
      var mailchimp_aud_id = thisbutton.attr('id');

      console.log(mailchimp_aud_id);
      if(subscription_status.length) {
        //Trying to Subscribe
        if (confirm('Are you sure you want to subscribe this contact directly?')) {

            $.ajax({
                type: 'POST',
                data: {'contact_id':contact_id,'status':subscription_status, 'mailchimp_aud_id':mailchimp_aud_id},
                url: admin_url + '/mailchimp/member_subscribe_unsubscribe',
                success: function(res){
                   data = JSON.parse(res);
                    if(data.status == 'success'){
                      alert_float('success', data.message);
                      //$('#contact_subscribe_modal').modal('hide');
                    }else{
                      alert_float('danger', data.message);
                    }
                }
            });
        }
      }else{

        $.ajax({
          type: 'POST',
          data: {'contact_id':contact_id,'status':subscription_status, 'mailchimp_aud_id':mailchimp_aud_id},
          url: admin_url + '/mailchimp/member_subscribe_unsubscribe',
          success: function(res){
             data = JSON.parse(res);
              if(data.status == 'success'){
                alert_float('success', data.message);
                //$('#contact_subscribe_modal').modal('hide');
              }else{
                alert_float('danger', data.message);
              }
          }
        });

      }
      
  });  
});
</script>
</body>
</html>
