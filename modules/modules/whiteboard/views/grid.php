<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI = & get_instance();

$start = intval($CI->input->post('start'));
$length = intval($CI->input->post('length'));
$draw = intval($CI->input->post('draw'));

$CI->db->query("SET sql_mode = ''");

$aColumns = [
    'title',
    db_prefix() . 'whiteboard.description',
    'staffid',
    db_prefix() . 'whiteboard_groups.name',
];


$sIndexColumn = 'id';
$sTable       = db_prefix() . 'whiteboard';
$join = [
    'LEFT JOIN ' . db_prefix() . 'whiteboard_groups ON ' . db_prefix() . 'whiteboard_groups.id = ' . db_prefix() . 'whiteboard.whiteboard_group_id',
];
$where        = [];
$filter = [];

$join = hooks()->apply_filters('whiteboard_grid_sql_join', $join);

$result = get_cl_list_query_whiteboard($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'whiteboard.id', db_prefix() . 'whiteboard.whiteboard_content']);


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
$CI->load->model('whiteboard_model');
?>
<div class="row">
    <div id="cl-grid-view" class="container-fluid">
<?php
if($output['iTotalDisplayRecords'] > 0){
foreach ($rResult as $aRow) {
    $oStaff = $CI->staff_model->get($aRow['staffid']);
?>
    <div class="col-md-4">
        <div class="cardbox text-center">
            <textarea class="map-textarea" id="m_map_<?php echo $aRow['id'];?>"><?php echo $aRow['whiteboard_content'];?></textarea>
            <div class="map_grid" id="map_<?php echo $aRow['id'];?>">
              <?php
                $jsondata =''.$aRow['whiteboard_content'].'';
                ?>
                <iframe src="<?php echo base_url();?>whiteboard/checkiframe?filename=<?php echo $aRow['id'];?>" height="250" width="320" title="Iframe Example" scrolling="no"></iframe>
            </div>
            
            <h4><a href="<?php echo admin_url('whiteboard/preview/' . $aRow['id']);?>"><?php echo $aRow['title'];?></a></h4>
            <p><?php echo $aRow[db_prefix() . 'whiteboard_groups.name']; ?></p>
            <?php if($oStaff) {?>
            <p class="created-by">Created by: <a href="<?php echo admin_url('profile/'.$oStaff->staffid);?>"><?php echo $oStaff->firstname.' '. $oStaff->lastname; ?></a></p>
        <?php } ?>
        </div>
    </div>
<?php } }else{?>
    <div class="col-md-12">
        <div class="cardbox text-center dataTables_empty">
            <p>No entries found</p>
        </div>
    </div>
<?php } ?>
</div></div>
<div class="row">
    <div id='pagination'>
        <?php echo $this->pagination->create_links(); ?>
    </div>
</div>
<link href="<?php echo base_url();?>modules/whiteboard/_assets/literallycanvas.css" rel="stylesheet">
<link href="<?php echo base_url();?>modules/whiteboard/assets/css/grid.css" rel="stylesheet">
<link href="<?php echo module_dir_url('whiteboard', 'assets/css/cl.css'); ?>" rel="stylesheet">
<script src="<?php echo base_url();?>modules/whiteboard/_js_libs/react-0.14.3.js"></script>
<script src="<?php echo base_url();?>modules/whiteboard/_js_libs/literallycanvas.js"></script>