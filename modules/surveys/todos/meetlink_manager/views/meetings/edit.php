<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <?php echo htmlspecialchars($title);?>
                        </h4>
                        <hr class="hr-panel-heading" />
                        <?php echo form_open_multipart($this->uri->uri_string()); ?>
                        <div class="row">
                            <div class="col-md-6">
                                 <?php echo render_select('services_id', $services, ['id', 'service_name'], 'service', !empty(set_value('services_id')) ? set_value('services_id') : $meeting->service_id ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('title', 'meeting_title', $meeting->title ?? ''); ?>
                            </div>
                            <div class="col-md-12">
                                <?php echo render_input('meeting_url', 'meeting_url', $meeting->meeting_url ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                 <?php echo render_select('lead_id', $leads, ['id', 'name'], 'lead',!empty(set_value('services_id')) ? set_value('services_id') : $meeting_lead ?? '');?>
                            </div>
                            <div class="col-md-6">
                                    
                                 <?php echo render_select('client_id', $clients, ['userid', 'company'], 'client', !empty(set_value('client_id')) ? set_value('client_id') : $meeting_customers ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                    <?php echo render_select('staffid[]', $staff, ['staffid', ['firstname', 'lastname']], 'staff', !empty(set_value('staffid')) ? set_value('staffid') : $meeting_staffs ?? '', ['multiple' => true, 'data-actions-box' => true]); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($meeting) ? _dt($meeting->meeting_date .' '.$meeting->meeting_time):""); ?>
                                <?php echo render_datetime_input('start_time', 'meeting_start_time', $value,input_attrs: ['data-step' => 30]); ?>

                            </div>
                            
                        </div>
                        
                      
                    
                        <button type="submit" class="btn btn-info pull-right"><?php echo _l('submit'); ?></button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script type="text/javascript">
    var mode = '<?php echo $this->uri->segment(3, 0); ?>';
    (mode == 'add_product') ? $('input[type="file"]').prop('required',true) : $('input[type="file"]').prop('required',false);
    $(function () {
        if($('#is_digital').is(':checked')){
            $('#quantity_number').attr({readonly:true,value:1}); 
        }
        appValidateForm($('form'), {
          product_name        : "required",
          product_description : "required",
          product_category_id : "required",
          rate                : "required",
          quantity_number     : "required"
        });
        $('#is_digital').click(function(event) {
            if($('#is_digital').is(':checked')){
                $(this).attr({value:1});
                $('#quantity_number').attr({readonly:true,value:1});
            }else{
                $(this).attr({value:0});
                $('#quantity_number').attr({readonly:false,value:1});
            }
        });
        
        
    });
</script>