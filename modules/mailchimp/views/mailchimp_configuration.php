<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row testingupload">
         <div class="col-md-12 left-column">
            <div class="panel_s">
               <div class="panel-body">
                  <?php echo form_open(admin_url('mailchimp/save_configuration'),array('id'=>'meeting-submit-form')); ?>
                  <div class="row mailchimp-configure-row">
                     <div class="col-md-12">
                        <h3>
                           <p class="bottom40"><?=_l('mailchimp_configuration_details');?></p>
                        </h3>
                     </div>
                     <div class="row mailchimp-configure-row">
                        <div class="col-md-12">
                           <div class="flex_rows">
                              <div class="row mailchimp-configure-row">
                                 <p class="bottom15"><?=_l('mailchimp_package_requirements:');?></p>
                                 <p>
                                    <div>
                                       <span class="right5">Recommended:</span> 
                                       Standard (Data-driven automation and optimization tools for businesses that want to grow faster)
                                    </div>
                                    <div>
                                       <span class="right38">Minimum: </span>
                                       Essentials (Great for email-only senders who want around-the-clock support)
                                    </div>                                            
                                 </p>
                                 <p class="bottom25">                                            
                                    <a href="https://mailchimp.com/en-gb/pricing/marketing/">https://mailchimp.com/en-gb/pricing/marketing/</a>
                                 </p>
                              </div>
                           </div>
                        </div>
                        
                     </div>
                     
                     <div class="row mailchimp-configure-row" id="sage_details">
                        <div class="col-md-12">
                           <br>
                           <p class="mt_7"><?=_l('to_connect_to_MAILCHIMP_please_follow_these_instructions:');?></p>
                           <p>1. <?php echo _l('step_label_1'); ?>: <a href="https://mailchimp.com/en-gb/">https://mailchimp.com/en-gb/</a></p>
                           <p>2. <?php echo _l('step_label_2'); ?></p>
                           <p>3. <?php echo _l('step_label_3'); ?>.</p>
                           <p>4. <?php echo _l('step_label_4'); ?>.</p>
                           <p>5. <?php echo _l('step_label_5'); ?>.</p>
                           <p>6. <?php echo _l('step_label_6'); ?>.</p>
                           <p>7. <?php echo _l('step_label_7'); ?>.</p>
                        </div>
                        <div class="col-md-12">
                           <p>8. <?php echo _l('step_label_8'); ?> <?=strtoupper(SYNC_COMPANY_NAME);?>…</p>
                        </div>
                        <div class="col-md-12 indented_sage_config">
                           <div class="form-group open-ticket-subject-group">
                              <?php echo render_input('api_key','mailchimp_api_key_label', !empty($api_key) ? $api_key : '' ) ; ?>
                              <?php echo form_error('api_key'); ?>
                           </div>
                        </div>
                        <div class="col-md-12">
                           <p>9. <?php echo _l('step_label_9'); ?></p>
                        </div>
                        <div class="col-md-12 indented_sage_config">
                           <div class="form-group open-ticket-subject-group">
                              <?php echo render_input('limit_for_sync_jm_to_mc','limit_for_sync_jm_to_mc', !empty($limit_for_sync_jm_to_mc) ? $limit_for_sync_jm_to_mc : 0,'number' ) ; ?>
                              <?php echo form_error('limit_for_sync_jm_to_mc'); ?>
                           </div>
                        </div>
                        <div class="col-md-12">
                           <div class="mailchimp-configure-row sage_ledger_buttons">
                              <div class="form-group open-ticket-subject-group">
                                 <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                              </div>
                           </div>
                        </div>
                        <?php echo form_close(); ?>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
</div>
</div>
</div>
<?php init_tail(); ?>
<script>
   $(function(){
        $('.onoffmailchimp').on('click', function(){
            $('#sage_details').toggle('slow');
        });
      
   });
   
</script>
</body>
</html>