<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI     = & get_instance();
$start  = intval($CI->input->post('start'));
$length = intval($CI->input->post('length'));
$draw   = intval($CI->input->post('draw'));
$cat_ids_arr = $CI->input->post('cat_ids_arr');
$aColumns = [
  db_prefix().'upload_video.id as video_id',
  db_prefix().'upload_video.title as title',
  db_prefix().'upload_video.upload_video as upload_video',
  db_prefix().'video_category.category as video_category',
  db_prefix().'upload_video.description as description',
];
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'upload_video';
$join = [
  'LEFT JOIN ' . db_prefix() . 'video_category ON ' . db_prefix() . 'video_category.id = ' . db_prefix() . 'upload_video.category'
];
$where = [];
if(!empty($cat_ids_arr)) {
  array_push($where, 'AND '.db_prefix().'video_category.id IN ('.$cat_ids_arr.')');
}
if($CI->input->post('project_id')){
 array_push($where, 'AND '.db_prefix().'upload_video.project_id='.$CI->input->post('project_id')); 
}
$result = prepare_grid_query_for_video_library($aColumns, $sIndexColumn, $sTable, $join, $where);
$output  = $result['output'];
$rResult = $result['rResult'];
$prevPage = (($draw - 1) < 0) ? 0 : ($draw-1);
$nextPage = $draw + 1;
$nxtStart = ($start +1 ) * $length;
$prevStart = ($start -1 ) * $length;
$this->load->library('pagination');
$config['base_url'] = admin_url('video_library/');
$config['total_rows'] = $output['iTotalDisplayRecords'];
$config['per_page'] = $length;
$config['use_page_numbers'] = TRUE;
$config['full_tag_open'] = "<ul class='pagination pagination-sm pull-right' style='position:relative; top:-25px;'>";
$config['full_tag_close'] ="</ul>";
$config['num_tag_open'] = '<li>';
$config['num_tag_close'] = '</li>';
$config['cur_tag_open'] = "<li class='disabled'><li class='active'><a href='javascript:;'>";
$config['cur_tag_close'] = "<span class='sr-only'></span></a></li>";
$config['next_tag_open'] = "<li>";
$config['next_tagl_close'] = "</li>";
$config['prev_tag_open'] = "<li>";
$config['prev_tagl_close'] = "</li>";
$config['first_tag_open'] = "<li>";
$config['first_tagl_close'] = "</li>";
$config['last_tag_open'] = "<li>";
$config['last_tagl_close'] = "</li>";
$config['attributes'] = array('class' => 'paginate');
$config["uri_segment"] = 4;
$this->pagination->initialize($config);
?>
<style type="text/css">
  .idea_ra span{padding: 6px 12px;color: white;font-weight: 400;font-size: 15px; border-radius: 2px;}
</style>
<div id="vl-grid-view" class="container-fluid">
  <div class="row">
    <?php
    if($output['iTotalDisplayRecords'] > 0){
      foreach ($rResult as $aRow) {
        $hrefAttr = admin_url('video_library/add_video/' . $aRow['video_id']);

        ?>
        <div class="col-md-4">
          <div class="v_o_wr">
            <div class="wrap_video_cl">
                 <h1><?= $aRow['title']?></h1>

                 <a class="player_btn" data-fancybox href="#myVideo_<?php echo $aRow['video_id'] ?>">
                  <span>
                    <img src="<?php echo base_url('modules/video_library/assets/image/youtube_thumb.png'); ?>" alt="img not found"/>
                  </span>
                </a>
                <div class="card">
                  <video width="640" height="320" controls id="myVideo_<?php echo $aRow['video_id'] ?>" style="display:none;">
                    <source src="<?= base_url().'uploads/video_library/'. $aRow['upload_video'];?>" type="video/mp4">
                    </video>
                  </div>
                </div>
                <div class="video_cat">
                  <p><?php 
                  $discussion_count = video_discussion_count($aRow['video_id']);
                  echo  $aRow['description']
                ?></p>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
        <?php 
      } else { ?>
        <div class="col-md-12">
          <div class="cardbox text-center dataTables_empty" style="border: none">
            <p><?= _l('no_entries_found');?></p>
          </div>
        </div>
      <?php } ?>
    </div>
    <div class="row">
      <div style='margin-top: 10px;' id='pagination'>
        <?php echo $this->pagination->create_links(); ?>
      </div>
    </div>