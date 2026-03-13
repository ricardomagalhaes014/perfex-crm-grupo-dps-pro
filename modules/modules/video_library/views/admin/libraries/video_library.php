<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('modules/video_library/assets/css/jquery.fancybox.min.css')?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url('modules/video_library/assets/css/video_library.css')?>">
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body mbot10">
                <div class="row">
              <div class="col-md-4">
               <?php
               $data_category = isset($data_category) && !empty($data_category) ? $data_category : [];
               echo render_select('category',$data_category,array('id','category'),'','',array('multiple'=>true,'data-none-selected-text' => _l('vl_categories_submenu')),[],'','',false);
               ?>
           </div>
           <div class="col-lg-4">
               <?php echo render_input('search','','','',['onkeyup'=>'video_search_by_title(); return false;', 'placeholder'=>_l('cf_translate_input_link_title')]); ?>
           </div>
           <div class="col-md-4">
                   <a href="<?php echo admin_url('video_library/add_video')?>" class="btn btn-info" >
                      <?php echo _l('vl_add_video'); ?>
                  </a>
              </div>
       </div>
   </div>
   <div class="panel-body">
       <div class="video_list_wrap" id="video_div"></div>
   </div>
</div>
</div>
</div>
</div>
</div>
</div>
<div id="modal-wrapper">
    <div class="modal fade" id="comments-modal" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal">&times;</button>
              <h4 class="modal-title"><?php echo _l('discussion'); ?></h4>
          </div>
          <div class="modal-body">
              <div id="video-comments"></div>
          </div>
      </div>
  </div>
</div>
</div>
<?php init_tail(); ?>
<script src="<?php echo base_url('modules/video_library/assets/js/jquery.fancybox.min.js')?>"></script>
<script type="text/javascript">
    "use strict";
    var _lnth = 6;
    $(function(){
        loadVideosGridView();
    });
    $(document).on('click','a.paginate',function(e){
        e.preventDefault();
        var pageno = $(this).data('ci-pagination-page');
        var formData = {
            start: (pageno-1),
            length: _lnth,
            draw: 1,
            cat_ids_arr: $('input[name="cat_ids_arr"]').val(),
            sortBy: 'desc',
            order: [{
                column: 0,
                dir: 'desc'
            }]
        }
        videosGridViewDataCall(formData, function (resposne) {
            $('div#video_div').html(resposne)
        });
    });
</script>
