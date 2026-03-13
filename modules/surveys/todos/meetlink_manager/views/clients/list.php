<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tickets-summary-heading">
    <?php echo _l('meetlink_manager'); ?>
</h4>
<div class="panel_s">
    <div class="panel-body">
        <table class="table dt-table table-projects" data-order-col="2" data-order-type="desc">
            <thead>
                <tr>
                    <th class="th-project-name"><?php echo _l('title'); ?></th>
                    <th class="th-project-start-date"><?php echo _l('meeting_start_time'); ?></th>
                    <th class="th-project-start-date"><?php echo _l('service_type'); ?></th>
                    <th class="th-project-start-date"><?php echo _l('meeting_url'); ?></th>
                    <th class="th-project-deadline"><?php echo _l('created_by'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meetings as $meeting) { ?>
                <tr>
                    <td><?= $meeting['title']?></td>
                    <td><?= _dt($meeting['meeting_date'] . ' ' . $meeting['meeting_time']) ?></td>
                    <td><?= get_service_name_by_id($meeting['service_id']) ?></td>
                    <td>
                    <a href="<?= $meeting['meeting_url']?>" target="_blank" data-toggle="tooltip" data-original-title="<?=_l('Join via the link')?>"> <?= _l('join')?>
                        </a>

                        
                    </td>
                    <td><?= get_staff_full_name($meeting['created_by']) ?></td>
                  
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>