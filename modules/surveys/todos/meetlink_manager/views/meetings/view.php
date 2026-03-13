<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('title')?>
    <span class="pull-right"><?=$meeting->title?></span>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('meeting_start_time')?>
    <span class="pull-right"><?=_dt($meeting->meeting_date . ' ' . $meeting->meeting_time) ?></span>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('service_type')?>
    <span class="pull-right"><?=get_service_name_by_id($meeting->service_id)?></span>
</li>

<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('created_by')?>
    <span class="pull-right"><?=get_staff_full_name($meeting->created_by)?></span>
</li>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('meeting_url')?>
    <span class="pull-right">
        <a href="<?=$meeting->meeting_url?>" target="_blank" data-toggle="tooltip" data-original-title="<?=_l('Join via the link')?>"> <?=_l('join')?></a>
    </span>
</li>
<?php if(!empty($lead)){?>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('lead_name')?>
    <span class="pull-right">
    <a href="<?=admin_url('leads/index/' . $lead->id)?>" onclick="init_lead(<?=$lead->id?>);return false;"><?=$lead->name?></a>    
    </span>
</li>
<?php }  if(!empty($client)){ 
    
    ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('client')?>
    <span class="pull-right">
        <a href="<?=admin_url('clients/client/' . $client->userid)?>">    
            <?=$client->company?>
        </a>
    </span>
</li>
<?php }if(!empty($staffs)){ ?>

<li class="list-group-item d-flex justify-content-between align-items-center">
    <?=_l('staff')?>
    <span class="pull-right">

        <?php 
        $membersOutput = '';
        foreach ($staffs as $key => $member) { 
             if ($member != '') {
                $member_id   = $member['staffid'];
                $membersOutput .= '<a href="' . admin_url('profile/' . $member_id) . '">' .
                staff_profile_image($member_id, [
                    'tw-inline-block tw-h-7 tw-w-7 tw-rounded-full tw-ring-2 tw-ring-white',
                    ], 'small', [
                    'data-toggle' => 'tooltip',
                    'data-title'  => get_staff_full_name($member_id),
                    ]) . '</a>';
            }
         }?>

         <?= $membersOutput?>
    </span>
</li>
<?php } ?>