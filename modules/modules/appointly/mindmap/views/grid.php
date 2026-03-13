<?php defined('BASEPATH') or exit('No direct script access allowed');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$CI = & get_instance();

$start = intval($CI->input->post('start'));
$length = intval($CI->input->post('length'));
$draw = intval($CI->input->post('draw'));

$CI->db->query("SET sql_mode = ''");

$aColumns = [
    'title',
    db_prefix() . 'mindmap.description',
    'staffid',
    db_prefix() . 'mindmap_groups.name',
];


$sIndexColumn = 'id';
$sTable       = db_prefix() . 'mindmap';
$join = [
    'LEFT JOIN ' . db_prefix() . 'mindmap_groups ON ' . db_prefix() . 'mindmap_groups.id = ' . db_prefix() . 'mindmap.mindmap_group_id',
];
$where        = [];
$filter = [];

$join = hooks()->apply_filters('mindmap_grid_sql_join', $join);

$result = get_cl_lists_query($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'mindmap.id', db_prefix() . 'mindmap.mindmap_content']);


$output  = $result['output'];
$rResult = $result['rResult'];

$prevPage = (($draw - 1) < 0)?0:($draw-1);
$nextPage = $draw + 1;
$nxtStart = ($start +1 ) * $length; //($draw <= 2)?$length:($draw - 1) * $length;
$prevStart = ($start -1 ) * $length; //(($draw - 1) >= 0)?(($draw - 1) * $length):0;
$this->load->library('pagination');

$config['base_url'] = '';
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

$CI->load->model('staff_model');
$CI->load->model('mindmap_model');
?>
<div class="row">
    <div id="cl-grid-view" class="container-fluid">
<?php
if($output['iTotalDisplayRecords'] > 0){
foreach ($rResult as $aRow) {
    $oStaff = $CI->staff_model->get($aRow['staffid']);
?>
    <div class="col-md-3">
        <div class="cardbox text-center">
            <textarea style="display: none;" id="m_map_<?php echo $aRow['id'];?>"><?php echo json_decode($aRow['mindmap_content']);?></textarea>
            <div class="map_grid" id="map_<?php echo $aRow['id'];?>"><img src="/modules/mindmap/assets/images/loader.gif"></div>
            <h4><a href="<?php echo admin_url('mindmap/preview/' . $aRow['id']);?>"><?php echo $aRow['title'];?></a></h4>
            <p><?php echo $aRow[db_prefix() . 'mindmap_groups.name']; ?></p>
            <?php if($oStaff) {?>
            <p style="font-size: 13px;">Created by: <a href="<?php echo admin_url('profile/'.$oStaff->staffid);?>"><?php echo $oStaff->firstname.' '. $oStaff->lastname; ?></a></p>
        <?php } ?>
        </div>
    </div>
<?php } }else{?>
    <div class="col-md-12">
        <div class="cardbox text-center dataTables_empty" style="border: none">
            <p>No entries found</p>
        </div>
    </div>
<?php } ?>
</div></div>
<div class="row">
    <div style='margin-top: 10px;' id='pagination'>
        <?php echo $this->pagination->create_links(); ?>
    </div>
</div>
<link href="<?php echo module_dir_url('mindmap', 'assets/css/cl.css'); ?>" rel="stylesheet">