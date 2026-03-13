<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                    <?php if (has_permission('meetlink_manager', '', 'create')) { ?>
                     <div class="_buttons">
                   
            <a href="<?php echo admin_url('meetlink_manager/add'); ?>" class="btn btn-info pull-left display-block">
              <?php echo _l('new_meetlink_manager'); ?>
            </a>
        
                    </div>

                    <div class="clearfix"></div>
                    <hr class="hr-panel-heading" />
                    <?php } ?>
                    <div class="clearfix"></div>
                    <?php
                $table_data = [
                    _l('title'),
                    _l('meeting_start_time'),
                    _l('service_type'),
                    _l('meeting_url'),
                    _l('created_by'),
                    _l('created_datetime'),
                    _l('options'),
                  ];
                  render_datatable($table_data, ($class ?? 'meeting')); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('meetlink_manager/meetings/meeting_modal'); ?>

  <?php init_tail(); ?>
<script type="text/javascript">
  $(function(){
    initDataTable('.table-meeting', window.location.href,'undefined','undefined','');
  });     


  $(document).on('click','.copy_url',function(){
    if($(this).attr('data-url')){
      copyToClipboard($(this).attr('data-url'));
    }
  });

  function copyToClipboard(text) {
      const tempInput = document.createElement('input');
      tempInput.style.position = 'absolute';
      tempInput.style.left = '-9999px';
      tempInput.value = text;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand('copy');
      document.body.removeChild(tempInput);
      alert_float('success', 'Meeting URL copied to clipboard.');
  }

  $(document).on('click','.view_details',function(e){
      e.preventDefault();
      var id = $(this).attr('data-id');
      var url = admin_url+'meetlink_manager/view/'+id;
      
      $.get(url).done(function(response) {
              $("#meeting_details_modal .modal-body").html(response); 
              $("#meeting_details_modal").modal('show');
      }).fail(function() {
          console.log("Error retrieving data.");
      });
      
      return false;
  });
</script>
