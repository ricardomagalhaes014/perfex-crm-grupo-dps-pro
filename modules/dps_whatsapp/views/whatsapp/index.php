<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php $this->load->view('admin/includes/alerts'); ?>

            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-mb-4">
                    <i class="fa fa-whatsapp tw-text-green-500 tw-mr-2"></i>
                    WhatsApp Business — Ligação Pessoal
                </h4>
            </div>

            <!-- Painel de Ligação -->
            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="tw-font-semibold tw-mb-3">Estado da Ligação</h5>

                        <!-- Estado actual -->
                        <div id="wa-status-box" class="tw-mb-4 tw-p-3 tw-rounded-lg tw-border">
                            <div id="wa-status-loading" class="tw-text-gray-500">
                                <i class="fa fa-spinner fa-spin tw-mr-2"></i> A verificar estado...
                            </div>
                            <div id="wa-status-connected" class="tw-hidden">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-w-3 tw-h-3 tw-rounded-full tw-bg-green-500 tw-inline-block"></span>
                                    <span class="tw-font-semibold tw-text-green-700">Ligado</span>
                                </div>
                                <p class="tw-text-sm tw-text-gray-600 tw-mt-1 tw-mb-0">
                                    Número: <strong id="wa-phone-display"></strong>
                                </p>
                                <button id="btn-disconnect" class="btn btn-danger btn-sm tw-mt-3">
                                    <i class="fa fa-times tw-mr-1"></i> Desligar
                                </button>
                            </div>
                            <div id="wa-status-disconnected" class="tw-hidden">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <span class="tw-w-3 tw-h-3 tw-rounded-full tw-bg-red-500 tw-inline-block"></span>
                                    <span class="tw-font-semibold tw-text-red-700">Desligado</span>
                                </div>
                                <p class="tw-text-sm tw-text-gray-500 tw-mt-1 tw-mb-0">
                                    Ligue o seu WhatsApp Business para activar os follow-ups automáticos.
                                </p>
                                <button id="btn-connect" class="btn btn-success btn-sm tw-mt-3">
                                    <i class="fa fa-qrcode tw-mr-1"></i> Ligar WhatsApp
                                </button>
                            </div>
                        </div>

                        <!-- QR Code -->
                        <div id="wa-qr-box" class="tw-hidden tw-text-center tw-p-4 tw-border tw-rounded-lg tw-bg-gray-50">
                            <p class="tw-text-sm tw-font-semibold tw-mb-3">
                                Abra o WhatsApp no telemóvel → <strong>Dispositivos Ligados</strong> → <strong>Ligar um dispositivo</strong> → Aponte a câmara para o QR code
                            </p>
                            <div id="qr-image-container" class="tw-flex tw-justify-center tw-mb-3">
                                <div class="tw-text-gray-400">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p class="tw-text-sm tw-mt-2">A gerar QR code...</p>
                                </div>
                            </div>
                            <p class="tw-text-xs tw-text-gray-500">O QR code expira em 60 segundos. Se expirar, clique em "Ligar WhatsApp" novamente.</p>
                        </div>

                    </div>
                </div>

                <!-- Informação sobre os follow-ups -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="tw-font-semibold tw-mb-3">
                            <i class="fa fa-clock-o tw-mr-1 tw-text-blue-500"></i>
                            Follow-ups Automáticos
                        </h5>
                        <p class="tw-text-sm tw-text-gray-600">
                            Quando o estado de uma lead é alterado para <strong>Novos</strong>, <strong>Quente</strong> ou <strong>VIP</strong>, é agendado automaticamente um follow-up para <strong>7 dias depois</strong>.
                        </p>
                        <p class="tw-text-sm tw-text-gray-600 tw-mb-0">
                            Se ao fim de 7 dias a lead ainda estiver nesse estado, a mensagem é enviada automaticamente para o número de telefone da lead.
                        </p>
                        <?php if (is_admin()): ?>
                        <hr>
                        <button id="btn-process-followups" class="btn btn-default btn-sm">
                            <i class="fa fa-refresh tw-mr-1"></i> Processar Follow-ups Agora
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabela de Follow-ups -->
            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="tw-font-semibold tw-mb-3">
                            <i class="fa fa-list tw-mr-1"></i>
                            Follow-ups Agendados
                        </h5>
                        <table class="table table-striped" id="followups-table">
                            <thead>
                                <tr>
                                    <th>Lead</th>
                                    <th>Telefone</th>
                                    <th>Estado</th>
                                    <?php if (is_admin()): ?>
                                    <th>Comercial</th>
                                    <?php endif; ?>
                                    <th>Agendado Para</th>
                                    <th>Situação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($followups as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['lead_name'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($f['phonenumber'] ?? '—'); ?></td>
                                    <td>
                                        <span class="label label-default">
                                            <?php echo htmlspecialchars($f['status_name'] ?? '—'); ?>
                                        </span>
                                    </td>
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
                                <tr><td colspan="6" class="tw-text-center tw-text-gray-400 tw-py-4">Nenhum follow-up registado.</td></tr>
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
var WA_STATUS_URL     = '<?php echo admin_url("dps_whatsapp/ajax_status"); ?>';
var WA_CONNECT_URL    = '<?php echo admin_url("dps_whatsapp/ajax_connect"); ?>';
var WA_QR_URL         = '<?php echo admin_url("dps_whatsapp/ajax_qr"); ?>';
var WA_DISCONNECT_URL = '<?php echo admin_url("dps_whatsapp/ajax_disconnect"); ?>';
var WA_PROCESS_URL    = '<?php echo admin_url("dps_whatsapp/ajax_process_followups"); ?>';

var qrPollInterval = null;
var statusPollInterval = null;

function showStatus(connected, phone) {
    $('#wa-status-loading').addClass('tw-hidden');
    if (connected) {
        $('#wa-status-connected').removeClass('tw-hidden');
        $('#wa-status-disconnected').addClass('tw-hidden');
        $('#wa-qr-box').addClass('tw-hidden');
        $('#wa-phone-display').text(phone || 'Ligado');
        clearInterval(qrPollInterval);
    } else {
        $('#wa-status-connected').addClass('tw-hidden');
        $('#wa-status-disconnected').removeClass('tw-hidden');
    }
}

function checkStatus() {
    $.get(WA_STATUS_URL, function(data) {
        showStatus(data.connected, data.phone);
        if (data.connected) {
            clearInterval(statusPollInterval);
            clearInterval(qrPollInterval);
        }
    });
}

function pollQR() {
    $.get(WA_QR_URL, function(data) {
        if (data.connected) {
            clearInterval(qrPollInterval);
            checkStatus();
            return;
        }
        if (data.qr) {
            $('#qr-image-container').html('<img src="' + data.qr + '" style="max-width:220px;border-radius:8px;">');
        }
    });
}

$(document).ready(function() {
    // Verificar estado inicial
    checkStatus();
    statusPollInterval = setInterval(checkStatus, 5000);

    // Botão ligar
    $('#btn-connect').on('click', function() {
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A ligar...');
        $.post(WA_CONNECT_URL, function() {
            $('#wa-qr-box').removeClass('tw-hidden');
            $('#wa-status-disconnected').addClass('tw-hidden');
            // Começar a fazer polling do QR
            clearInterval(qrPollInterval);
            qrPollInterval = setInterval(pollQR, 3000);
            pollQR();
        }).always(function() {
            $('#btn-connect').prop('disabled', false).html('<i class="fa fa-qrcode tw-mr-1"></i> Ligar WhatsApp');
        });
    });

    // Botão desligar
    $('#btn-disconnect').on('click', function() {
        if (!confirm('Tem a certeza que quer desligar o WhatsApp?')) return;
        $(this).prop('disabled', true);
        $.post(WA_DISCONNECT_URL, function() {
            location.reload();
        });
    });

    // Botão processar follow-ups (admin)
    $('#btn-process-followups').on('click', function() {
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A processar...');
        $.post(WA_PROCESS_URL, function(data) {
            alert(data.message || 'Concluído');
            location.reload();
        }).always(function() {
            $('#btn-process-followups').prop('disabled', false).html('<i class="fa fa-refresh tw-mr-1"></i> Processar Follow-ups Agora');
        });
    });
});
</script>

<?php init_tail(); ?>
</body>
</html>
