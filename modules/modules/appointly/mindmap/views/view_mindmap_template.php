<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header task-single-header" data-task-single-id="<?php echo $mindmap->id; ?>" >
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php echo $mindmap->title; ?></h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-8 task-single-col-left" style="min-height: 926px;">
            <div class="tc-content">
                <div id="mindmap_draw">
                    <?php $value = (isset($mindmap) ? $mindmap->mindmap_content : ''); ?>
                    <textarea style="display: none" id="mindmap_content" name="mindmap_content"><?php echo $value;?></textarea>
                    <div id="map"></div>
                    <style>
                        #map {
                            height: 500px;
                            width: 100%;
                        }
                    </style>
                </div>
            </div>
            <div class="clearfix"></div>

        </div>
        <div class="col-md-4 task-single-col-right">
            <h4 class="task-info-heading"><?php echo _l('mindmap_info'); ?></h4>
            <div class="clearfix"></div>
            <h5 class="no-mtop task-info-created">
                <small class="text-dark"><?php echo _l('task_created_at','<span class="text-dark">'._dt($mindmap->dateadded).'</span>'); ?></small>
            </h5>

            <hr class="task-info-separator">

            <div class="task-info task-info-billable">
                <h5><i class="fa task-info-icon fa-fw fa-lg pull-left fa fa-user-o"></i>
                    <?php echo _l('mindmap_filter_staff'); ?>: <?php echo ($staff)?$staff->firstname.' '.$staff->lastname:'';?>
                </h5>
            </div>

            <div class="task-info task-info-billable">
                <h5><i class="fa task-info-icon fa-fw fa-lg pull-left fa fa-cog"></i>
                    <?php echo _l('mindmap_filter_group'); ?>: <?php echo ($group)?$group->name:'';?>
                </h5>
            </div>

        </div>
    </div>
</div>
<style type="text/css">
    .lt{width: 40px !important;}
    nmenu { border: 1px solid blue !important;}
</style>
