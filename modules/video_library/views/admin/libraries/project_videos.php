<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('modules/video_library/assets/css/jquery.fancybox.min.css')?>">
<link rel="stylesheet" type="text/css" href="<?php echo base_url('modules/video_library/assets/css/video_library.css')?>">
<div class="content">
  <div class="row">
   <div class="col-md-12">
     <div class="panel-body mbot10">
        <div class="row">
            <div class="col-md-6">
             <?php
             $categories = get_video_library_category();
             $data_category = isset($categories) && !empty($categories) ? $categories : [];
             echo render_select('category',$data_category,array('id','category'),'Category','',array('multiple'=>true));
             ?>
         </div>
         <div class="col-lg-6">
             <?php echo render_input('search',_l('Search Title'),'','',['onkeyup'=>'video_search_by_title(); return false;', 'placeholder'=>'search']); ?>
         </div>
     </div>
 </div>
 <div class="panel-body">
     <div class="video_list_wrap" id="video_div"></div>
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
</div>
</div>
</div>
</div>
<?php 
$this->app_scripts->add('library-js', module_dir_url('video_library', '/assets/js/jquery.fancybox.min.js')); 
$this->app_scripts->add('library-project-js', module_dir_url('video_library', '/assets/js/project_library.js')); 
?>