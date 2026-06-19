<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
                    <h4 class="tw-text-xl tw-font-semibold tw-text-neutral-700">
                        <i class="fa fa-phone tw-mr-2" style="color:#c9a96e;"></i> Sofia Calls
                    </h4>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalNovaCampanha">
                        <i class="fa fa-plus"></i> Nova Campanha
                    </button>
                </div>

                <div class="row" id="campaigns-container">
                    <?php if (empty($campaigns)): ?>
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Nenhuma campanha criada ainda. Clique em <strong>Nova Campanha</strong> para começar.
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($campaigns as $c): ?>
                    <div class="col-md-6 col-lg-4 campaign-card" id="campaign-<?php echo $c['id']; ?>">
                        <div class="panel panel-default" style="border-left: 4px solid <?php echo $c['status'] === 'active' ? '#27ae60' : ($c['status'] === 'paused' ? '#f39c12' : '#e74c3c'); ?>;">
                            <div class="panel-body">
                                <div class="tw-flex tw-justify-between tw-items-start tw-mb-2">
                                    <h5 class="tw-font-semibold tw-text-neutral-700 tw-mb-0"><?php echo htmlspecialchars($c['name']); ?></h5>
                                    <div class="tw-flex tw-items-center tw-gap-1">
                                        <span class="label label-<?php echo $c['status'] === 'active' ? 'success' : ($c['status'] === 'paused' ? 'warning' : 'danger'); ?>">
                                            <?php echo $c['status'] === 'active' ? 'Ativa' : ($c['status'] === 'paused' ? 'Pausada' : ($c['status'] === 'completed' ? 'Concluída' : 'Parada')); ?>
                                        </span>
                                        <button class="btn btn-xs btn-default btn-campaign-edit"
                                            data-id="<?php echo $c['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($c['name'], ENT_QUOTES); ?>"
                                            data-focus="<?php echo htmlspecialchars($c['focus_text'] ?? '', ENT_QUOTES); ?>"
                                            data-status="<?php echo $c['lead_status_id']; ?>"
                                            data-staff="<?php echo $c['staff_id']; ?>"
                                            data-agent="<?php echo htmlspecialchars($c['agent_id'] ?? '', ENT_QUOTES); ?>"
                                            title="Editar campanha">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                    </div>
                                </div>

                                <?php if ($c['focus_text']): ?>
                                <p class="tw-text-sm tw-text-neutral-500 tw-mb-2" style="font-style:italic;">
                                    <i class="fa fa-bullseye"></i> <?php echo htmlspecialchars(substr($c['focus_text'], 0, 80)) . (strlen($c['focus_text']) > 80 ? '...' : ''); ?>
                                </p>
                                <?php endif; ?>

                                <?php
                                $total = max(1, (int)$c['total_leads']);
                                $made  = (int)$c['calls_made'];
                                $pct   = min(100, round($made / $total * 100));
                                ?>
                                <div class="tw-mb-2">
                                    <div class="tw-flex tw-justify-between tw-text-xs tw-text-neutral-500 tw-mb-1">
                                        <span><?php echo $made; ?> / <?php echo $total; ?> chamadas</span>
                                        <span><?php echo $pct; ?>%</span>
                                    </div>
                                    <div class="progress tw-mb-0" style="height:8px;">
                                        <div class="progress-bar progress-bar-success" style="width:<?php echo $pct; ?>%"></div>
                                    </div>
                                </div>

                                <div class="tw-flex tw-gap-2 tw-text-xs tw-mb-3">
                                    <span class="tw-text-green-600"><i class="fa fa-check"></i> <?php echo $c['calls_answered']; ?> atendidas</span>
                                    <span class="tw-text-red-500"><i class="fa fa-times"></i> <?php echo $c['calls_failed']; ?> falhadas</span>
                                </div>

                                <div class="btn-group btn-group-sm tw-w-full">
                                    <?php if ($c['status'] === 'paused'): ?>
                                    <button class="btn btn-success btn-campaign-action" data-id="<?php echo $c['id']; ?>" data-action="active" title="Iniciar campanha">
                                        <i class="fa fa-play"></i> Iniciar
                                    </button>
                                    <?php elseif ($c['status'] === 'active'): ?>
                                    <button class="btn btn-warning btn-campaign-action" data-id="<?php echo $c['id']; ?>" data-action="paused" title="Pausar">
                                        <i class="fa fa-pause"></i> Pausar
                                    </button>
                                    <button class="btn btn-success btn-make-call" data-id="<?php echo $c['id']; ?>" title="Ligar agora para o próximo lead">
                                        <i class="fa fa-phone"></i> Ligar Agora
                                    </button>
                                    <?php endif; ?>
                                    <?php if (in_array($c['status'], ['active', 'paused'])): ?>
                                    <button class="btn btn-danger btn-campaign-action" data-id="<?php echo $c['id']; ?>" data-action="stopped" title="Parar definitivamente">
                                        <i class="fa fa-stop"></i> Parar
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-default btn-campaign-detail" data-id="<?php echo $c['id']; ?>" title="Ver detalhes">
                                        <i class="fa fa-list"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NOVA CAMPANHA -->
<div class="modal fade" id="modalNovaCampanha" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-phone"></i> Nova Campanha Sofia</h4>
            </div>
            <div class="modal-body">
                <form id="formNovaCampanha">
                    <div class="form-group">
                        <label>Nome da Campanha <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Ex: Campanha VIP Junho" required>
                    </div>
                    <div class="form-group">
                        <label>Estado dos Leads <span class="text-danger">*</span></label>
                        <select name="lead_status_id" class="form-control" required>
                            <option value="">-- Selecionar estado --</option>
                            <?php foreach ($lead_statuses as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">A Sofia vai ligar aos leads com este estado que tenham número de telefone.</small>
                    </div>
                    <div class="form-group">
                        <label>Responsável (Staff)</label>
                        <select name="staff_id" class="form-control">
                            <option value="">-- Todos os responsáveis --</option>
                            <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo $staff['staffid']; ?>"><?php echo htmlspecialchars($staff['fullname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Filtrar leads por responsável. Deixe vazio para incluir todos.</small>
                    </div>
                    <input type="hidden" name="agent_id" value="agent_9901kv1pvewveh9s9ebs1rys274k">
                    <div class="form-group">
                        <label>Foco / Contexto para a Sofia</label>
                        <textarea name="focus_text" class="form-control" rows="4"
                            placeholder="Ex: Perguntar ao cliente se já analisou o conteúdo enviado. Recordar que as condições de pré-venda terminam em breve."></textarea>
                        <small class="text-muted">A Sofia vai usar este texto como contexto em todas as chamadas desta campanha.</small>
                    </div>
                    <div class="alert alert-info" style="margin-bottom:0;">
                        <i class="fa fa-info-circle"></i> A campanha será criada em estado <strong>pausado</strong>. Pode iniciar quando quiser.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnCriarCampanha">
                    <i class="fa fa-plus"></i> Criar Campanha
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR CAMPANHA -->
<div class="modal fade" id="modalEditarCampanha" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-pencil"></i> Editar Campanha</h4>
            </div>
            <div class="modal-body">
                <form id="formEditarCampanha">
                    <input type="hidden" name="id" id="editCampanhaId">
                    <div class="form-group">
                        <label>Nome da Campanha <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editCampanhaName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Estado dos Leads <span class="text-danger">*</span></label>
                        <select name="lead_status_id" id="editCampanhaStatus" class="form-control" required>
                            <option value="">-- Selecionar estado --</option>
                            <?php foreach ($lead_statuses as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Atenção: alterar o estado vai recalcular os leads da campanha.</small>
                    </div>
                    <div class="form-group">
                        <label>Responsável (Staff)</label>
                        <select name="staff_id" id="editCampanhaStaff" class="form-control">
                            <option value="">-- Todos os responsáveis --</option>
                            <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo $staff['staffid']; ?>"><?php echo htmlspecialchars($staff['fullname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Foco / Contexto para a Sofia</label>
                        <textarea name="focus_text" id="editCampanhaFocus" class="form-control" rows="5"
                            placeholder="Ex: Perguntar ao cliente se já analisou o conteúdo enviado..."></textarea>
                        <small class="text-muted">A Sofia vai usar este texto como contexto em todas as chamadas desta campanha.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCampanha">
                    <i class="fa fa-save"></i> Guardar Alterações
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETALHES -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detalhes da Campanha</h4>
            </div>
            <div class="modal-body" id="modalDetalhesBody">
                <div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<?php $csrf = get_csrf_for_ajax(); ?>
<script>
$(document).ready(function() {

    var BASE      = '<?php echo admin_url("dps_sofia_calls"); ?>';
    var CSRF_NAME = '<?php echo $csrf["token_name"]; ?>';
    var CSRF_HASH = '<?php echo $csrf["hash"]; ?>';

    // Criar campanha
    $('#btnCriarCampanha').on('click', function() {
        var name     = $('[name="name"]', '#formNovaCampanha').val().trim();
        var statusId = $('[name="lead_status_id"]', '#formNovaCampanha').val();
        var staffId  = $('[name="staff_id"]', '#formNovaCampanha').val();
        var focus    = $('[name="focus_text"]', '#formNovaCampanha').val().trim();
        var agentId  = $('[name="agent_id"]', '#formNovaCampanha').val();

        if (!name || !statusId) {
            alert('Preencha o nome e o estado dos leads.');
            return;
        }

        var btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A criar...');

        $.ajax({
            url: BASE + '/create_campaign',
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: (function(){ var d = { name: name, lead_status_id: statusId, staff_id: staffId, focus_text: focus, agent_id: agentId }; d[CSRF_NAME] = CSRF_HASH; return d; })(),
            success: function(r) {
                if (r.success) {
                    alert_float('success', 'Campanha criada! Clique em Iniciar quando quiser começar as chamadas.');
                    $('#modalNovaCampanha').modal('hide');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    alert_float('danger', r.message || 'Erro ao criar campanha');
                }
            },
            error: function(xhr) {
                alert_float('danger', 'Erro ' + xhr.status + ': ' + (xhr.responseText || 'Sem resposta'));
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-plus"></i> Criar Campanha');
            }
        });
    });

    // Abrir modal de editar
    $(document).on('click', '.btn-campaign-edit', function() {
        var btn = $(this);
        $('#editCampanhaId').val(btn.data('id'));
        $('#editCampanhaName').val(btn.data('name'));
        $('#editCampanhaFocus').val(btn.data('focus'));
        $('#editCampanhaStatus').val(btn.data('status'));
        $('#editCampanhaStaff').val(btn.data('staff') || '');
        $('#modalEditarCampanha').modal('show');
    });

    // Guardar edição
    $('#btnGuardarCampanha').on('click', function() {
        var id       = $('#editCampanhaId').val();
        var name     = $('#editCampanhaName').val().trim();
        var statusId = $('#editCampanhaStatus').val();
        var staffId  = $('#editCampanhaStaff').val();
        var focus    = $('#editCampanhaFocus').val().trim();

        if (!name || !statusId) {
            alert('Preencha o nome e o estado dos leads.');
            return;
        }

        var btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A guardar...');

        $.ajax({
            url: BASE + '/update_campaign',
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: (function(){ var d = { id: id, name: name, lead_status_id: statusId, staff_id: staffId, focus_text: focus }; d[CSRF_NAME] = CSRF_HASH; return d; })(),
            success: function(r) {
                if (r.success) {
                    alert_float('success', 'Campanha atualizada com sucesso!');
                    $('#modalEditarCampanha').modal('hide');
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    alert_float('danger', r.message || 'Erro ao atualizar campanha');
                }
            },
            error: function(xhr) {
                alert_float('danger', 'Erro ' + xhr.status + ': ' + (xhr.responseText || 'Sem resposta'));
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Alterações');
            }
        });
    });

    // Ação na campanha (iniciar/pausar/parar)
    $(document).on('click', '.btn-campaign-action', function() {
        var id     = $(this).data('id');
        var action = $(this).data('action');
        var labels = { active: 'iniciada', paused: 'pausada', stopped: 'parada' };

        $.ajax({
            url: BASE + '/campaign_action',
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: (function(){ var d = { id: id, action: action }; d[CSRF_NAME] = CSRF_HASH; return d; })(),
            success: function(r) {
                if (r.success) {
                    alert_float('success', 'Campanha ' + (labels[action] || action));
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    alert_float('danger', 'Erro ao atualizar campanha');
                }
            }
        });
    });

    // Ligar agora
    $(document).on('click', '.btn-make-call', function() {
        var campaign_id = $(this).data('id');
        var btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: BASE + '/make_call',
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: (function(){ var d = { campaign_id: campaign_id }; d[CSRF_NAME] = CSRF_HASH; return d; })(),
            success: function(r) {
                if (r.success) {
                    alert_float('success', 'A Sofia está a ligar para ' + (r.lead || 'o lead'));
                } else {
                    alert_float('danger', r.message || 'Erro ao iniciar chamada');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-phone"></i> Ligar Agora');
            }
        });
    });

    // Ver detalhes
    $(document).on('click', '.btn-campaign-detail', function() {
        var id = $(this).data('id');
        $('#modalDetalhesBody').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
        $('#modalDetalhes').modal('show');

        $.ajax({
            url: BASE + '/campaign_detail',
            type: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: (function(){ var d = { id: id }; d[CSRF_NAME] = CSRF_HASH; return d; })(),
            success: function(r) {
                var s = r.stats || {};
                var html = '<div class="row tw-mb-4">';
                html += '<div class="col-xs-4 text-center"><div class="tw-text-2xl tw-font-bold tw-text-green-600">' + (s.answered || 0) + '</div><div class="tw-text-xs tw-text-neutral-500">Atendidas</div></div>';
                html += '<div class="col-xs-4 text-center"><div class="tw-text-2xl tw-font-bold tw-text-orange-500">' + (s.no_answer || 0) + '</div><div class="tw-text-xs tw-text-neutral-500">Sem resposta</div></div>';
                html += '<div class="col-xs-4 text-center"><div class="tw-text-2xl tw-font-bold tw-text-red-500">' + (s.failed || 0) + '</div><div class="tw-text-xs tw-text-neutral-500">Falhadas</div></div>';
                html += '</div>';
                html += '<table class="table table-bordered table-striped"><thead><tr><th>Lead</th><th>Telefone</th><th>Estado</th><th>Data</th></tr></thead><tbody>';
                var logs = r.logs || [];
                if (logs.length > 0) {
                    var badge = {
                        pending:   '<span class="label label-default">Pendente</span>',
                        calling:   '<span class="label label-info">A ligar</span>',
                        answered:  '<span class="label label-success">Atendida</span>',
                        no_answer: '<span class="label label-warning">Sem resposta</span>',
                        failed:    '<span class="label label-danger">Falhada</span>',
                        busy:      '<span class="label label-warning">Ocupado</span>'
                    };
                    $.each(logs, function(i, log) {
                        html += '<tr>';
                        html += '<td>' + (log.lead_name || '-') + '</td>';
                        html += '<td>' + (log.phone_number || '-') + '</td>';
                        html += '<td>' + (badge[log.status] || log.status) + '</td>';
                        html += '<td>' + (log.started_at || '-') + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="4" class="text-center text-muted">Sem registos de chamadas</td></tr>';
                }
                html += '</tbody></table>';
                $('#modalDetalhesBody').html(html);
            },
            error: function() {
                $('#modalDetalhesBody').html('<div class="alert alert-danger">Erro ao carregar detalhes</div>');
            }
        });
    });
});
</script>
