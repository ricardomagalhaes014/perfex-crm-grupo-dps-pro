<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $meeting ? _l('dps_meeting_edit') : _l('dps_meeting_new'); ?></h4>
                        <hr class="hr-panel-heading" />

                        <?php echo form_open(admin_url('dps_meetings/save')); ?>
                        <?php echo form_hidden('id', $meeting ? $meeting->id : ''); ?>

                        <?php echo render_input('title', 'dps_meeting_title', $meeting ? $meeting->title : ''); ?>

                        <?php
                        $providers = [
                            ['id' => 'jitsi', 'name' => 'Jitsi'],
                            ['id' => 'meet', 'name' => 'Google Meet'],
                            ['id' => 'zoom', 'name' => 'Zoom'],
                            ['id' => 'custom', 'name' => 'Link personalizado'],
                        ];
                        echo render_select('provider', $providers, ['id', 'name'], 'dps_meeting_provider', $meeting ? $meeting->provider : 'jitsi');
                        ?>

                        <?php echo render_input('room_name', 'dps_meeting_room_name', $meeting ? $meeting->room_name : ''); ?>
                        <?php echo render_input('meeting_url', 'dps_meeting_url', $meeting ? $meeting->meeting_url : ''); ?>

                        <?php
                        $relatedTypes = [
                            ['id' => '', 'name' => '-'],
                            ['id' => 'lead', 'name' => 'Lead'],
                            ['id' => 'customer', 'name' => 'Cliente'],
                            ['id' => 'other', 'name' => 'Outro'],
                        ];
                        echo render_select('related_type', $relatedTypes, ['id', 'name'], 'dps_meeting_related_type', $meeting ? $meeting->related_type : '');
                        ?>

                        <?php echo render_input('related_id', 'dps_meeting_related_id', $meeting ? $meeting->related_id : ''); ?>
                        <?php echo render_input('contact_name', 'dps_meeting_contact_name', $meeting ? $meeting->contact_name : ''); ?>
                        <?php echo render_input('contact_email', 'dps_meeting_contact_email', $meeting ? $meeting->contact_email : '', 'email'); ?>
                        <?php echo render_select('host_staff_id', $staff, ['id', 'name'], 'dps_meeting_host', $meeting ? $meeting->host_staff_id : get_staff_user_id()); ?>
                        <?php echo render_datetime_input('start_at', 'dps_meeting_start_at', $meeting ? _dt($meeting->start_at) : ''); ?>
                        <?php echo render_datetime_input('end_at', 'dps_meeting_end_at', $meeting ? _dt($meeting->end_at) : ''); ?>

                        <?php
                        $statuses = [
                            ['id' => 'scheduled', 'name' => _l('dps_meeting_status_scheduled')],
                            ['id' => 'completed', 'name' => _l('dps_meeting_status_completed')],
                            ['id' => 'cancelled', 'name' => _l('dps_meeting_status_cancelled')],
                            ['id' => 'no_show', 'name' => _l('dps_meeting_status_no_show')],
                        ];
                        echo render_select('status', $statuses, ['id', 'name'], 'dps_meeting_status', $meeting ? $meeting->status : 'scheduled');
                        ?>

                        <?php echo render_textarea('description', 'dps_meeting_description', $meeting ? $meeting->description : ''); ?>
                        <?php echo render_textarea('result_notes', 'dps_meeting_result_notes', $meeting ? $meeting->result_notes : ''); ?>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('dps_meetings'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <div class="table-responsive">
                            <table class="table dt-table" data-order-col="5" data-order-type="desc">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('dps_meeting_title'); ?></th>
                                        <th><?php echo _l('dps_meeting_provider'); ?></th>
                                        <th><?php echo _l('dps_meeting_related'); ?></th>
                                        <th><?php echo _l('dps_meeting_host'); ?></th>
                                        <th><?php echo _l('dps_meeting_status'); ?></th>
                                        <th><?php echo _l('dps_meeting_start_at'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($meetings as $item) { ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo html_escape($item['title']); ?></strong><br>
                                            <?php if (!empty($item['meeting_url'])) { ?>
                                                <a href="<?php echo html_escape($item['meeting_url']); ?>" target="_blank"><?php echo _l('dps_meeting_join'); ?></a>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo html_escape(ucfirst($item['provider'])); ?></td>
                                        <td><?php echo html_escape(trim(($item['related_type'] ?: '-') . ' #' . ($item['related_id'] ?: ''))); ?></td>
                                        <td><?php echo html_escape($item['host_name']); ?></td>
                                        <td><?php echo dps_meetings_status_badge($item['status']); ?></td>
                                        <td><?php echo _dt($item['start_at']); ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('dps_meetings/edit/' . $item['id']); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil"></i></a>
                                            <a href="<?php echo admin_url('dps_meetings/delete/' . $item['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
