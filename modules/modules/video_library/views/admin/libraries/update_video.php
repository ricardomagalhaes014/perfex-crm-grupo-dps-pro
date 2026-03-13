<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
 <div class="content">
     <div class="panel_s">
         <div class="panel-body">
            <div class="wrap_form_new_cl " style=" max-width: 60%; margin: 0 auto; border: 1px solid #e3e8ee;padding: 24px;  border-radius: 10px; background-color: #e3e8ee;margin-top: 25px;"> 
             <?php echo form_open('admin/video_library/update_video',array('id'=>'update_owner_operator_form','enctype'=>'multipart/form-data')); ?>  
             <div class="row">
                <div class="form-group col-lg-12">  
                  <?php if ($this->session->flashdata('msg')) { ?>
                     <div class="alert alert-success"> <?= $this->session->flashdata('msg') ?> </div>
                 <?php } ?>
                 <?php echo form_hidden('video_id', $data_video->id); ?>
                 <label for="title" class="form-label ">Title</label>  
                 <input type="text" class="form-control " id="title" name="title" placeholder="Enter Ttile" value="<?php echo $data_video->video_title ?>" autocomplete required >  
                 <span style="color:red;">  <?php echo form_error('title'); ?></span>
             </div> 
             <div class="form-group col-md-12">  
                <div class="form-group">
                    <?php
                    $selected = '';
                    if(isset($data_video) && !empty($data_video)){
                      $selected = $data_video->id;
                  }
                  echo render_select( 'category',$data_category,array('id','category'),'Category',$selected);
                  ?>
                  
              </div>
              <span style="color:red;">  <?php echo form_error('category'); ?></span>
          </div>  
          <div class="form-group col-md-12">  
             <label for="upload_video" class="form-label">Upload Video</label>  
             <input type="file" class="form-control" id="upload_video" name="upload_video">
             <video controls style="height:80px;">
                <source src="<?= base_url().'uploads/upload_video/'. $data_video->upload_video;?>" type="video/mp4" >
                </video>
                <span style="color:red;">  <?php echo form_error('upload_video'); ?></span>  
            </div> 
            <div class="form-group col-lg-12">  
             <label for="desc" class="form-label ">Description</label>  
             <textarea name="desc" class="form-control"><?php echo $data_video->description ?></textarea>  
             <span style="color:red;">  <?php echo form_error('title'); ?></span>
         </div> 
         <div class="form-group col-lg-4">  
            <button type="submit" class=" btn btn-primary padding-top">Submit</button>    
        </div>
        <?php echo form_close(); ?>

    </div>
</div>  


</div>


</div>
</div>
<?php init_tail(); ?>

