<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.wa-status-box { padding: 15px; border-radius: 6px; border: 1px solid #ddd; margin-bottom: 15px; }
.wa-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 6px; vertical-align: middle; }
.wa-dot-green { background: #22c55e; }
.wa-dot-red   { background: #ef4444; }
.wa-dot-gray  { background: #9ca3af; }
.wa-qr-box    { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 6px; background: #f9f9f9; margin-top: 10px; }
.wa-qr-box img { max-width: 220px; border-radius: 8px; border: 1px solid #ccc; }
.wa-automation-row td { vertical-align: middle !important; }
.automation-msg-preview { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #666; font-size: 12px; }
.wa-send-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; }
</style>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php $this->load->view('admin/includes/alerts'); ?>

            <div class="col-md-12">
                <h4 class="page-title">
                    <i class="fa fa-whatsapp" style="color:#25D366;margin-right:6px;"></i>
                    WhatsApp Business
                </h4>
            </div>

            <!-- Coluna esquerda: Ligação + Envio Manual + Automações -->
            <div class="col-md-5">

                <!-- Painel de Ligação -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 style="font-weight:600;margin-bottom:15px;">Estado da Ligação</h5>

                        <div class="wa-status-box" id="wa-status-box">
                            <!-- Loading -->
                            <div id="wa-status-loading">
                                <span class="wa-dot wa-dot-gray"></span>
                                <span style="color:#666;"><i class="fa fa-spinner fa-spin"></i> A verificar estado...</span>
                            </div>
                            <!-- Ligado -->
                            <div id="wa-status-connected" style="display:none;">
                                <span class="wa-dot wa-dot-green"></span>
                                <strong style="color:#16a34a;">Ligado</strong>
                                <p style="margin:8px 0 0;color:#555;font-size:13px;">
                                    Número: <strong id="wa-phone-display">—</strong>
                                </p>
                                <button id="btn-disconnect" class="btn btn-danger btn-sm" style="margin-top:10px;">
                                    <i class="fa fa-times"></i> Desligar
                                </button>
                            </div>
                            <!-- Desligado -->
                            <div id="wa-status-disconnected" style="display:none;">
                                <span class="wa-dot wa-dot-red"></span>
                                <strong style="color:#dc2626;">Desligado</strong>
                                <p style="margin:8px 0 0;color:#666;font-size:13px;">
                                    Ligue o seu WhatsApp Business para activar as automações.
                                </p>
                                <button id="btn-connect" class="btn btn-success btn-sm" style="margin-top:10px;">
                                    <i class="fa fa-qrcode"></i> Ligar WhatsApp
                                </button>
                            </div>
                        </div>

                        <!-- QR Code -->
                        <div id="wa-qr-box" class="wa-qr-box" style="display:none;">
                            <p style="font-size:13px;font-weight:600;margin-bottom:12px;">
                                Abra o WhatsApp no telemóvel &rarr; <strong>Dispositivos Ligados</strong> &rarr; <strong>Ligar um dispositivo</strong>
                            </p>
                            <div id="qr-image-container">
                                <i class="fa fa-spinner fa-spin fa-3x" style="color:#ccc;"></i>
                                <p style="color:#999;font-size:12px;margin-top:8px;">A gerar QR code...</p>
                            </div>
                            <p style="font-size:11px;color:#999;margin-top:10px;margin-bottom:0;">
                                O QR code expira em 60 segundos. Se expirar, clique em "Ligar WhatsApp" novamente.
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Painel de Envio Manual -->
                <div class="panel_s" id="panel-send-manual">
                    <div class="panel-body">
                        <h5 style="font-weight:600;margin-bottom:15px;">
                            <i class="fa fa-paper-plane" style="color:#25D366;margin-right:5px;"></i>
                            Enviar Mensagem
                        </h5>
                        <div class="wa-send-box">
                            <div class="form-group" style="margin-bottom:10px;">
                                <label style="font-size:13px;font-weight:600;">Número de destino</label>
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                                    <input type="text" id="send-phone" class="form-control input-sm"
                                        placeholder="Ex: 351912345678 (sem + ou espaços)">
                                </div>
                                <p style="font-size:11px;color:#999;margin-top:3px;margin-bottom:0;">
                                    Inclua o código do país sem o sinal +. Ex: Portugal = 351912345678
                                </p>
                            </div>
                            <div class="form-group" style="margin-bottom:10px;">
                                <label style="font-size:13px;font-weight:600;">Mensagem</label>
                                <textarea id="send-message" class="form-control" rows="4"
                                    placeholder="Escreva aqui a mensagem a enviar..."></textarea>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <button id="btn-send-manual" class="btn btn-success btn-sm">
                                    <i class="fa fa-paper-plane"></i> Enviar Agora
                                </button>
                                <span id="send-result" style="font-size:13px;display:none;"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Painel de Automações -->
                <div class="panel_s">
                    <div class="panel-body">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                            <h5 style="font-weight:600;margin:0;">
                                <i class="fa fa-bolt" style="color:#f59e0b;margin-right:5px;"></i>
                                As Minhas Automações
                            </h5>
                            <button class="btn btn-success btn-sm" id="btn-nova-automacao">
                                <i class="fa fa-plus"></i> Nova Automação
                            </button>
                        </div>

                        <!-- Formulário de criação/edição (oculto por defeito) -->
                        <div id="automation-form-box" style="display:none;background:#f8f9fa;border:1px solid #e0e0e0;border-radius:6px;padding:15px;margin-bottom:15px;">
                            <h6 style="font-weight:600;margin-bottom:12px;" id="automation-form-title">Nova Automação</h6>
                            <input type="hidden" id="automation-id" value="">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Nome da automação</label>
                                <input type="text" id="automation-name" class="form-control input-sm" placeholder="Ex: Follow-up Quente 7 dias">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Leads no estado:</label>
                                <select id="automation-status" class="form-control input-sm">
                                    <?php foreach ($lead_statuses as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Ao fim de (dias):</label>
                                <input type="number" id="automation-days" class="form-control input-sm" value="7" min="1" max="365" style="width:100px;">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Mensagem a enviar:</label>
                                <textarea id="automation-message" class="form-control" rows="6" placeholder="Escreva aqui a mensagem que será enviada via WhatsApp..."></textarea>
                                <p style="font-size:11px;color:#999;margin-top:4px;">Pode usar <code>{{nome}}</code> para inserir o nome da lead.</p>
                            </div>
                            <div style="display:flex;gap:8px;">
                                <button class="btn btn-primary btn-sm" id="btn-save-automation">
                                    <i class="fa fa-save"></i> Guardar
                                </button>
                                <button class="btn btn-default btn-sm" id="btn-cancel-automation">
                                    Cancelar
                                </button>
                            </div>
                        </div>

                        <!-- Lista de automações -->
                        <div id="automations-list">
                            <?php if (empty($automations)): ?>
                            <p style="color:#999;text-align:center;padding:20px 0;margin:0;">
                                Ainda não tem automações. Crie a primeira!
                            </p>
                            <?php else: ?>
                            <table class="table table-striped table-condensed" style="font-size:13px;">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Estado</th>
                                        <th>Dias</th>
                                        <th>Activa</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($automations as $a): ?>
                                    <tr class="wa-automation-row" data-id="<?php echo $a['id']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($a['name']); ?></strong>
                                            <div class="automation-msg-preview"><?php echo htmlspecialchars($a['message']); ?></div>
                                        </td>
                                        <td><span class="label label-default"><?php echo htmlspecialchars($a['status_name'] ?? '—'); ?></span></td>
                                        <td><?php echo (int)$a['days_delay']; ?>d</td>
                                        <td>
                                            <input type="checkbox" class="automation-toggle"
                                                data-id="<?php echo $a['id']; ?>"
                                                <?php echo $a['is_active'] ? 'checked' : ''; ?>>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <button class="btn btn-xs btn-success btn-send-now"
                                                title="Enviar agora"
                                                data-id="<?php echo $a['id']; ?>"
                                                data-message="<?php echo htmlspecialchars($a['message'], ENT_QUOTES); ?>">
                                                <i class="fa fa-paper-plane"></i> Enviar
                                            </button>
                                            <button class="btn btn-xs btn-default btn-edit-automation"
                                                data-id="<?php echo $a['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($a['name'], ENT_QUOTES); ?>"
                                                data-status="<?php echo $a['lead_status_id']; ?>"
                                                data-days="<?php echo $a['days_delay']; ?>"
                                                data-message="<?php echo htmlspecialchars($a['message'], ENT_QUOTES); ?>">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button class="btn btn-xs btn-danger btn-delete-automation" data-id="<?php echo $a['id']; ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>

                        <?php if (is_admin()): ?>
                        <hr>
                        <button id="btn-process-followups" class="btn btn-default btn-sm">
                            <i class="fa fa-refresh"></i> Processar Follow-ups Agora
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Coluna direita: Follow-ups -->
            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 style="font-weight:600;margin-bottom:15px;">
                            <i class="fa fa-list" style="margin-right:5px;"></i>
                            Follow-ups Agendados
                        </h5>
                        <table class="table table-striped" id="followups-table" style="font-size:13px;">
                            <thead>
                                <tr>
                                    <th>Lead</th>
                                    <th>Telefone</th>
                                    <th>Estado</th>
                                    <?php if (is_admin()): ?><th>Comercial</th><?php endif; ?>
                                    <th>Agendado Para</th>
                                    <th>Situação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($followups as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['lead_name'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($f['phonenumber'] ?? '—'); ?></td>
                                    <td><span class="label label-default"><?php echo htmlspecialchars($f['status_name'] ?? '—'); ?></span></td>
                                    <?php if (is_admin()): ?>
                                    <td><?php echo htmlspecialchars(($f['firstname'] ?? '') . ' ' . ($f['lastname'] ?? '')); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $f['scheduled_at']; ?></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'pending'   => '<span class="label label-warning">Pendente</span>',
                                            'sent'      => '<span class="label label-success">Enviado</span>',
                                            'cancelled' => '<span class="label label-default">Cancelado</span>',
                                            'failed'    => '<span class="label label-danger">Falhou</span>',
                                        ];
                                        echo $badges[$f['status']] ?? $f['status'];
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($followups)): ?>
                                <tr><td colspan="6" style="text-align:center;color:#999;padding:20px;">Nenhum follow-up registado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
var WA_STATUS_URL      = '<?php echo admin_url("dps_whatsapp/ajax_status"); ?>';
var WA_CONNECT_URL     = '<?php echo admin_url("dps_whatsapp/ajax_connect"); ?>';
var WA_QR_URL          = '<?php echo admin_url("dps_whatsapp/ajax_qr"); ?>';
var WA_DISCONNECT_URL  = '<?php echo admin_url("dps_whatsapp/ajax_disconnect"); ?>';
var WA_PROCESS_URL     = '<?php echo admin_url("dps_whatsapp/ajax_process_followups"); ?>';
var WA_SAVE_AUTO_URL   = '<?php echo admin_url("dps_whatsapp/ajax_save_automation"); ?>';
var WA_DELETE_AUTO_URL = '<?php echo admin_url("dps_whatsapp/ajax_delete_automation"); ?>';
var WA_TOGGLE_AUTO_URL = '<?php echo admin_url("dps_whatsapp/ajax_toggle_automation"); ?>';
var WA_SEND_URL        = '<?php echo admin_url("dps_whatsapp/ajax_send_message"); ?>';
var WA_SEND_NOW_URL    = '<?php echo admin_url("dps_whatsapp/ajax_send_automation_now"); ?>';

var qrPollInterval     = null;
var statusPollInterval = null;
var waIsConnected      = false;

// ── Estado da ligação ──────────────────────────────────────────────────────
function showStatus(connected, phone) {
    $('#wa-status-loading').hide();
    waIsConnected = connected;
    if (connected) {
        $('#wa-status-connected').show();
        $('#wa-status-disconnected').hide();
        $('#wa-qr-box').hide();
        $('#wa-phone-display').text(phone || 'Ligado');
        $('#panel-send-manual').show();
        clearInterval(qrPollInterval);
        clearInterval(statusPollInterval);
    } else {
        $('#wa-status-connected').hide();
        $('#wa-status-disconnected').show();
        $('#panel-send-manual').hide();
    }
}

function checkStatus() {
    $.ajax({
        url: WA_STATUS_URL,
        type: 'GET',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        success: function(data) {
            showStatus(data.connected, data.phone);
        },
        error: function() {
            $('#wa-status-loading').hide();
            $('#wa-status-disconnected').show();
            $('#panel-send-manual').hide();
        }
    });
}

function pollQR() {
    $.ajax({
        url: WA_QR_URL,
        type: 'GET',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        success: function(data) {
            if (data.connected) {
                clearInterval(qrPollInterval);
                checkStatus();
                return;
            }
            if (data.qr) {
                $('#qr-image-container').html('<img src="' + data.qr + '">');
            }
        }
    });
}

function initWA() {
    // Ocultar painel de envio até confirmar ligação
    $('#panel-send-manual').hide();

    // Verificar estado inicial
    checkStatus();
    statusPollInterval = setInterval(checkStatus, 5000);

    // Ligar WhatsApp
    $('#btn-connect').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A ligar...');
        $.ajax({
            url: WA_CONNECT_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function() {
                $('#wa-qr-box').show();
                $('#wa-status-disconnected').hide();
                clearInterval(qrPollInterval);
                qrPollInterval = setInterval(pollQR, 3000);
                pollQR();
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-qrcode"></i> Ligar WhatsApp');
            }
        });
    });

    // Desligar
    $('#btn-disconnect').on('click', function() {
        if (!confirm('Tem a certeza que quer desligar o WhatsApp?')) return;
        $(this).prop('disabled', true);
        $.ajax({
            url: WA_DISCONNECT_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            complete: function() { location.reload(); }
        });
    });

    // ── Envio Manual ───────────────────────────────────────────────────────
    $('#btn-send-manual').on('click', function() {
        var phone   = $.trim($('#send-phone').val()).replace(/\D/g, '');
        var message = $.trim($('#send-message').val());
        var $result = $('#send-result');

        if (!phone) {
            $result.show().css('color','#dc2626').text('Por favor insira um número de telefone.');
            return;
        }
        if (!message) {
            $result.show().css('color','#dc2626').text('Por favor escreva uma mensagem.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A enviar...');
        $result.hide();

        $.ajax({
            url: WA_SEND_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: { phone: phone, message: message },
            success: function(data) {
                if (data.success) {
                    $result.show().css('color','#16a34a').html('<i class="fa fa-check"></i> Mensagem enviada com sucesso!');
                    $('#send-phone').val('');
                    $('#send-message').val('');
                } else {
                    $result.show().css('color','#dc2626').text(data.error || 'Erro ao enviar mensagem.');
                }
            },
            error: function() {
                $result.show().css('color','#dc2626').text('Erro de comunicação com o servidor.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar Agora');
            }
        });
    });

    // Processar follow-ups (admin)
    $('#btn-process-followups').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A processar...');
        $.ajax({
            url: WA_PROCESS_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(data) {
                alert(data.message || 'Concluído');
                location.reload();
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Processar Follow-ups Agora');
            }
        });
    });

    // ── Automações ─────────────────────────────────────────────────────────

    // Mostrar formulário de nova automação
    $('#btn-nova-automacao').on('click', function() {
        $('#automation-id').val('');
        $('#automation-name').val('');
        $('#automation-days').val(7);
        $('#automation-message').val('');
        $('#automation-form-title').text('Nova Automação');
        $('#automation-form-box').slideDown();
        $('html, body').animate({ scrollTop: $('#automation-form-box').offset().top - 80 }, 300);
    });

    // Cancelar formulário
    $('#btn-cancel-automation').on('click', function() {
        $('#automation-form-box').slideUp();
    });

    // Editar automação
    $(document).on('click', '.btn-edit-automation', function() {
        var $btn = $(this);
        $('#automation-id').val($btn.data('id'));
        $('#automation-name').val($btn.data('name'));
        $('#automation-status').val($btn.data('status'));
        $('#automation-days').val($btn.data('days'));
        $('#automation-message').val($btn.data('message'));
        $('#automation-form-title').text('Editar Automação');
        $('#automation-form-box').slideDown();
        $('html, body').animate({ scrollTop: $('#automation-form-box').offset().top - 80 }, 300);
    });

    // Guardar automação
    $('#btn-save-automation').on('click', function() {
        var name    = $.trim($('#automation-name').val());
        var status  = $('#automation-status').val();
        var days    = parseInt($('#automation-days').val());
        var message = $.trim($('#automation-message').val());
        var id      = $('#automation-id').val();

        if (!name)    { alert('Por favor insira um nome para a automação.'); return; }
        if (!message) { alert('Por favor escreva a mensagem a enviar.'); return; }
        if (!days || days < 1) { alert('Por favor insira um número de dias válido.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A guardar...');

        $.ajax({
            url: WA_SAVE_AUTO_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: { id: id, name: name, lead_status_id: status, days_delay: days, message: message },
            success: function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao guardar automação.');
                }
            },
            error: function() { alert('Erro de comunicação.'); },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
            }
        });
    });

    // Apagar automação
    $(document).on('click', '.btn-delete-automation', function() {
        if (!confirm('Tem a certeza que quer apagar esta automação?')) return;
        var id = $(this).data('id');
        $.ajax({
            url: WA_DELETE_AUTO_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: { id: id },
            success: function(data) {
                if (data.success) location.reload();
                else alert(data.error || 'Erro ao apagar.');
            }
        });
    });

    // Activar/desactivar automação
    $(document).on('change', '.automation-toggle', function() {
        var id      = $(this).data('id');
        var active  = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            url: WA_TOGGLE_AUTO_URL,
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: { id: id, is_active: active }
        });
    });
}

// Inicializar após 500ms para garantir que o DOM está completamente pronto
setTimeout(initWA, 500);
</script>


<script>
// ── Enviar Automação Agora (envio em massa a todos os leads do estado) ─────────────────
$(document).on('click', '.btn-send-now', function() {
    var $btn          = $(this);
    var automation_id = $btn.data('id');
    var auto_name     = $btn.closest('tr').find('strong').first().text();
    var status_name   = $btn.closest('tr').find('.label-default').first().text();

    if (!confirm('Enviar a mensagem da automação "' + auto_name + '" para TODOS os leads no estado "' + status_name + '"?\n\nEsta acção envia imediatamente para todos os leads nesse estado.')) {
        return;
    }

    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A enviar...');

    $.ajax({
        url: WA_SEND_NOW_URL,
        type: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        data: { automation_id: automation_id },
        success: function(data) {
            if (data.success) {
                alert('✅ ' + data.message);
            } else {
                alert('❌ Erro: ' + (data.error || 'Não foi possível enviar.'));
            }
        },
        error: function() {
            alert('❌ Erro de comunicação com o servidor.');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Enviar');
        }
    });
});
</script>

<?php init_tail(); ?>
</body>
</html>
