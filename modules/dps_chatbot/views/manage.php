<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('dps_chatbot_groups'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <ul class="list-group">
                            <?php foreach ($groups as $group) { ?>
                                <li class="list-group-item">
                                    <a href="#" class="group-chat-link" data-group-id="<?php echo $group['id']; ?>">
                                        <?php echo html_escape($group['name']); ?>
                                        <?php if ($group['is_public']) { ?>
                                            <span class="label label-info pull-right"><?php echo _l('dps_chatbot_public'); ?></span>
                                        <?php } ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                        <hr />
                        <h4 class="no-margin"><?php echo _l('dps_chatbot_staff'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <ul class="list-group">
                            <?php foreach ($staff as $member) { ?>
                                <?php if ($member['staffid'] != get_staff_user_id()) { ?>
                                    <li class="list-group-item">
                                        <a href="#" class="staff-chat-link" data-staff-id="<?php echo $member['staffid']; ?>">
                                            <?php echo html_escape($member['firstname'] . ' ' . $member['lastname']); ?>
                                        </a>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                        <?php if (is_admin() || staff_can('manage_groups', 'dps_chatbot')) { ?>
                            <hr />
                            <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#new_group_modal"><?php echo _l('dps_chatbot_new_group'); ?></button>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin" id="chat-title"><?php echo _l('dps_chatbot_select_chat'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <div id="chat-messages" style="height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                            <!-- Mensagens carregadas via AJAX -->
                        </div>
                        <div id="chat-input-area" style="display: none;">
                            <textarea id="chat-message-input" class="form-control" rows="3" placeholder="<?php echo _l('dps_chatbot_type_message'); ?>"></textarea>
                            <button id="send-chat-message" class="btn btn-primary pull-right" style="margin-top: 10px;"><?php echo _l('dps_chatbot_send'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Grupo -->
<div class="modal fade" id="new_group_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('dps_chatbot_new_group'); ?></h4>
            </div>
            <?php echo form_open(admin_url('dps_chatbot/create_group')); ?>
            <div class="modal-body">
                <?php echo render_input('name', 'dps_chatbot_group_name'); ?>
                <?php echo render_textarea('description', 'dps_chatbot_group_description'); ?>
                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="is_public" id="is_public" value="1">
                    <label for="is_public"><?php echo _l('dps_chatbot_is_public'); ?></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    var current_receiver_id = null;
    var current_group_id = null;

    $(document).on('click', '.staff-chat-link', function(e) {
        e.preventDefault();
        current_receiver_id = $(this).data('staff-id');
        current_group_id = null;
        $('#chat-title').text($(this).text());
        $('#chat-input-area').show();
        loadMessages();
    });

    $(document).on('click', '.group-chat-link', function(e) {
        e.preventDefault();
        current_group_id = $(this).data('group-id');
        current_receiver_id = null;
        $('#chat-title').text($(this).text());
        $('#chat-input-area').show();
        loadMessages();
    });

    function loadMessages() {
        $.get(admin_url + 'dps_chatbot/get_messages', {
            receiver_id: current_receiver_id,
            group_id: current_group_id
        }, function(data) {
            var messages = JSON.parse(data);
            var html = '';
            messages.forEach(function(msg) {
                html += '<div style="margin-bottom: 10px;"><strong>' + msg.sender_name + ':</strong> ' + msg.message + ' <small class="text-muted">(' + msg.created_at + ')</small></div>';
            });
            $('#chat-messages').html(html);
            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
        });
    }

    $('#send-chat-message').on('click', function() {
        var message = $('#chat-message-input').val();
        if (message.trim() === '') return;

        $.post(admin_url + 'dps_chatbot/send_message', {
            message: message,
            receiver_id: current_receiver_id,
            group_id: current_group_id
        }, function(data) {
            $('#chat-message-input').val('');
            loadMessages();
        });
    });

    // Atualizar mensagens a cada 5 segundos
    setInterval(function() {
        if (current_receiver_id || current_group_id) {
            loadMessages();
        }
    }, 5000);
</script>
</body>
</html>
