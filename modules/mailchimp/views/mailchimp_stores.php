<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
                  <div class="_buttons">
                     <a href="#" class="btn btn-info pull-left" onclick="open_store_model()"><?php echo _l('new_store'); ?></a>
                     <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-info mailchimp-sync-btn" id="store_sync_btn"><?php echo _l('SYNC '); ?><img src="<?=site_url()?>modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" />TO <?=strtoupper(SYNC_COMPANY_NAME);?></a>
                  </div>
                  <div class="clearfix"></div>
                  <hr class="hr-panel-heading" />
                  <div class="clearfix"></div>
                  <table class="table table-store">
                     <thead>
                        <tr>
                           <th>#SN</th>
                           <th><?=_l('store_name_col_title');?></th>
                           <th><?=_l('store_audience_col_title');?></th>
                           <th><?=_l('store_mailchimp_col_title');?></th>
                           <th><?=_l('store_action_col_title');?></th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php
                           if($list && !empty($list)){
                              foreach($list as $key=>$value){
                                 if($key/2 == 0){
                                    $sub_even_class = 'even';
                                 }else{
                                    $sub_even_class = 'odd';
                                 }
                                 if (!empty($value["mailchimp_store_id"]) && $value["mailchimp_store_id"] != '') {
                                    $mailchimp_link = '<a href="#" class="linked-mailchimp-btn mailchimp-linked-txt-color"><img src="'.site_url().'modules/mailchimp/assets/logo.svg" />' . _l(' Linked') . '</a>';
                                } else {
                                     $mailchimp_link = '<a href="'.admin_url().'mailchimp/store_link_to_mailchimp/'.$value["id"].'" class="btn btn-info link-to-mailchimp-btn">' . _l('Add to ') . '<img src="'.site_url().'modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" /></a>';
                                }
                                if($value['is_default'] == 1){
                                    $is_default = '';
                                }else{
                                    $is_default = '<a href="#" class="btn btn-primary btn-icon active-mailchimp-store-icon" data-id="'.$value["id"].'" data-is-referenced-expenses="0" data-toggle="tooltip" title="Make Default Store">
                                       <i class="fa fa-star"></i>
                                    </a>';
                                }
                                 echo'<tr role="row" class="'.$sub_even_class.'">
                                    <td tabindex="0">'.++$key.'</td>
                                    <td>'.$value["name"].'</td>
                                    <td>'.get_audience_name_by_mailchimp_id($value["mailchimp_audience_id"]).'</td>
                                    <td>'. $mailchimp_link.'</td>
                                    <td>
                                    <a href="" class="btn btn-default btn-icon edit-mailchimp-store-icon" data-id="'.$value["id"].'" data-is-referenced-expenses="0" data-is-referenced-subscriptions="" data-toggle="tooltip" title="Edit Store">
                                       <i class="fa fa-pencil-square-o"></i>
                                    </a>
                                     '.$is_default.'
                                    <a href="" class="btn btn-danger delete-mailchimp-store-icon" data-id="'.$value["id"].'" data-toggle="tooltip" title="If you delete it.It will delete all customers and orders from mailchimp">
                                       <i class="fa fa-remove"></i>
                                    </a>
                                    </td>
                                    </tr>';
                              }
                           } 
                        ?>  
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
</div>
</div>
</div>
<?php $this->load->view('modals/stores_add') ?>
<?php init_tail(); ?>
<script type="text/javascript">
   $(function() {
      //datatable 
      initDataTableInline('.table-store');
      //Form validation and submit
      appValidateForm($('#store-model-form'), {
        store_name: 'required',
        store_audience: 'required',
        },submit_store_form
      );

      //open model for edit store
      $('body').on('click','.edit-mailchimp-store-icon', function(event){
       event.preventDefault();
       var id = $(this).attr('data-id');
       if(id != '') {
         $.ajax({
             type: 'POST',
             data: { id: id},
             url: admin_url+'mailchimp/get_store_data',
             success: function(res){
               data = JSON.parse(res);
                 if(data.status == 'success'){
                   $('#add_store_model #id').val(data.store.id);
                   $('#add_store_model #mailchimp_store_id').val(data.store.mailchimp_store_id);
                   $('#add_store_model #store_name').val(data.store.name);
                   $('#add_store_model #store_audience').val(data.store.mailchimp_audience_id);
                   
                   $('#add_store_model #myModalLabel').text(data.title);
                   $('#add_store_model #store_audience').prop('disabled', true);
                   $('#add_store_model .selectpicker').selectpicker('refresh');
                   $('#add_store_model').modal('show');
                 }
                 
             }
         });
       }
      });
   });
   //Delete store
   $('body').on('click','.delete-mailchimp-store-icon', function(event){
      event.preventDefault();
      var confirm = ConfirmDelete();
      var id = $(this).attr('data-id');
      if(id != '' && confirm) {
         $.ajax({
             type: 'POST',
             data: { id: id},
             url: admin_url+'mailchimp/delete_store',
             success: function(res){
               data = JSON.parse(res);
                 if(data.status == 'success'){
                   alert_float('success', data.message);
                   setTimeout(function(){
                     window.location.reload(true);
                   }, 2000);
                 }
                 
             }
         });
      }
   });
   //store Sync 
   $('body').on('click','#store_sync_btn', function(event){
      event.preventDefault();
      $.ajax({
          type: 'POST',
          data: {},
          url: admin_url+'mailchimp/store_sync',
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
   //Make default store active
   $('body').on('click','.active-mailchimp-store-icon', function(event){
      event.preventDefault();
      var confirm = ConfirmMessage('Are you sure you want to make default it');
      var id = $(this).attr('data-id');
      if(id != '' && confirm) {
         $.ajax({
             type: 'POST',
             data: { id: id},
             url: admin_url+'mailchimp/make_default_store',
             success: function(res){
               data = JSON.parse(res);
                 if(data.status == 'success'){
                   alert_float('success', data.message);
                   setTimeout(function(){
                     window.location.reload(true);
                   }, 2000);
                 }
                 
             }
         });
      }
   });
   function ConfirmMessage(message){
      return confirm(message);
   }
   //Delete confirmation
   function ConfirmDelete(){
    return confirm("<?=_l('Are_you_sure_you_want_to_delete?');?>");
   }

   //Open add store model
   function open_store_model() {
      $('#add_store_model #myModalLabel').text("<?=_l('add_store')?>");
      $('#add_store_model').find("input[type=text]").val("");
      $('#add_store_model #id').val('');
      $('#add_store_model #store_audience').prop('disabled', false);
      $('#add_store_model .selectpicker').selectpicker('refresh');
      if($('#add_store_model').is(':hidden')) {
         $('#add_store_model').modal({
         backdrop: 'static',
         show: true
         });
      }
   }
   //form submit handler
   function submit_store_form(){
     var form = $('#store-model-form')[0];
     var formData = new FormData(form);
      $.ajax({
          type: 'POST',
          data: formData,
          url: admin_url+'mailchimp/add_stores',
          processData: false,
          contentType: false,
         cache: false,
          success: function(res){
            data = JSON.parse(res);
              if(data.status == 'success'){
                $('#add_store_model').modal('hide');
                alert_float('success', data.message);
                setTimeout(function(){
                  window.location.reload(true);
                }, 2500);
              }
              
          }
      });
   }
</script>
</body>
</html>