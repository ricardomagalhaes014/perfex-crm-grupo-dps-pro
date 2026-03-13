<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
                  <div class="_buttons">
                     <a href="#" class="btn btn-info pull-left" onclick="open_audience_model()"><?php echo _l('new_audience'); ?></a>
                     <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-info mailchimp-sync-btn" id="audience_sync_btn"><?php echo _l('SYNC '); ?><img src="<?=site_url()?>modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" />TO <?= strtoupper(SYNC_COMPANY_NAME);?></a>
                  </div>
                  <div class="clearfix"></div>
                  <hr class="hr-panel-heading" />
                  <div class="clearfix"></div>
                  <table class="table table-audience">
                     <thead>
                        <tr>
                           <th>#SN</th>
                           <th><?=_l('audience_name_col_title');?></th>
                           <th><?=_l('audience_company_col_title');?></th>
                           <th><?=_l('audience_created_at_col_title');?></th>
                           <th><?=_l('audience_mailchimp_col_title');?></th>
                           <th><?=_l('audience_action_col_title');?></th>
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
                                 if (!empty($value["mailchimp_id"]) && $value["mailchimp_id"] != '') {
                                    $mailchimp_link = '<a href="#" class="linked-mailchimp-btn mailchimp-linked-txt-color"><img src="'.site_url().'modules/mailchimp/assets/logo.svg" />' . _l(' Linked') . '</a>';
                                } else {
                                     $mailchimp_link = '<a href="'.admin_url().'mailchimp/audience_link_to_mailchimp/'.$value["id"].'" class="btn btn-info link-to-mailchimp-btn">' . _l('Add to ') . '<img src="'.site_url().'modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" /></a>';
                                }
                                if($value['is_default'] == 1){
                                    $is_default = '';
                                }else{
                                    $is_default = '<a href="#" class="btn btn-primary btn-icon active-mailchimp-audience-icon" data-id="'.$value["id"].'" data-is-referenced-expenses="0" data-toggle="tooltip" title="Make Default Audience">
                                       <i class="fa fa-star"></i>
                                    </a>';
                                }
                                 echo'<tr role="row" class="'.$sub_even_class.'">
                                    <td tabindex="0">'.++$key.'</td>
                                    <td>'.$value["name"].'</td>
                                    <td>'.$value["company"].'</td>
                                    <td>'.$value["created_at"].'</td>
                                    <td>'. $mailchimp_link.'</td>
                                    <td>
                                    <a href="" class="btn btn-default btn-icon edit-mailchimp-audience-icon" data-id="'.$value["id"].'" data-is-referenced-expenses="0" data-toggle="tooltip" title="Edit Audience">
                                       <i class="fa fa-pencil-square-o"></i>
                                    </a>
                                     '.$is_default.'
                                    <a href="" class="btn btn-danger delete-mailchimp-audience-icon" data-id="'.$value["id"].'" data-toggle="tooltip" title="If you delete it.It will delete all customers,contacts and orders from mailchimp">
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
<?php $this->load->view('modals/audience_add') ?>
<?php init_tail(); ?>
<script type="text/javascript">
   $(function() {
      //datatable 
      initDataTableInline('.table-audience');
      //Form validation and submit
      appValidateForm($('#audience-model-form'), {
        name: 'required',
        company: 'required',
        address: 'required',
        country: 'required',
        city: 'required',
        zip: 'required',
        state: 'required',
        from_email: 'required',
        from_name: 'required',
        },submit_audience_form
      );

      //open model for edit audience
      $('body').on('click','.edit-mailchimp-audience-icon', function(event){
       event.preventDefault();
       var id = $(this).attr('data-id');
       if(id != '') {
         $.ajax({
             type: 'POST',
             data: { id: id},
             url: admin_url+'mailchimp/get_audience_data',
             success: function(res){
               data = JSON.parse(res);
                 if(data.status == 'success'){
                   $('#add_audience_model #id').val(data.audience.id);
                   $('#add_audience_model #name').val(data.audience.name);
                   $('#add_audience_model #company').val(data.audience.company);
                   $('#add_audience_model #address').val(data.audience.address);
                   $('#add_audience_model #city').val(data.audience.city);
                   $('#add_audience_model #country').val(data.audience.country);
                   $('#add_audience_model #state').val(data.audience.state);
                   $('#add_audience_model #zip').val(data.audience.zip);
                   $('#add_audience_model #from_name').val(data.audience.from_name);
                   $('#add_audience_model #from_email').val(data.audience.from_email);
                   $('#add_audience_model #phone').val(data.audience.phone);
                   $('#add_audience_model #myModalLabel').text(data.title);
                   $('#add_audience_model .selectpicker').selectpicker('refresh');
                   $('#add_audience_model').modal('show');
                 }
                 
             }
         });
       }
      });
   });
   //Delete audience
   $('body').on('click','.delete-mailchimp-audience-icon', function(event){
      event.preventDefault();
      var confirm = ConfirmDelete();
      var id = $(this).attr('data-id');
      if(id != '' && confirm) {
         $.ajax({
             type: 'POST',
             data: { id: id},
             url: admin_url+'mailchimp/delete_audience',
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
   //Audience Sync 
   $('body').on('click','#audience_sync_btn', function(event){
      event.preventDefault();
      $.ajax({
          type: 'POST',
          data: {},
          url: admin_url+'mailchimp/audience_sync',
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
   //Make default audince active
   $('body').on('click','.active-mailchimp-audience-icon', function(event){
      event.preventDefault();
      var confirm = ConfirmMessage('Are you sure you want to make default it');
      var id = $(this).attr('data-id');
      if(id != '' && confirm) {
         $.ajax({
             type: 'POST',
             data: { id: id},
             url: admin_url+'mailchimp/make_default_audience',
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
   //Delete confirmation
   function ConfirmDelete(){
    return confirm("<?=_l('Are you sure you want to delete?');?>");
   }
   function ConfirmMessage(message){
      return confirm(message);
   }
   //Open add audience model
   function open_audience_model() {
      $('#add_audience_model #myModalLabel').text("<?=_l('add_audience')?>");
      $('#add_audience_model').find("input[type=text],input[type=email]").val("");
      $('#add_audience_model #id').val('');
      if($('#add_audience_model').is(':hidden')) {
         $('#add_audience_model').modal({
         backdrop: 'static',
         show: true
         });
      }
   }
   //form submit handler
   function submit_audience_form(){
     var form = $('#audience-model-form')[0];
     var formData = new FormData(form);
      $.ajax({
          type: 'POST',
          data: formData,
          url: admin_url+'mailchimp/add_audience',
          processData: false,
          contentType: false,
         cache: false,
          success: function(res){
            data = JSON.parse(res);
              if(data.status == 'success'){
                $('#add_audience_model').modal('hide');
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