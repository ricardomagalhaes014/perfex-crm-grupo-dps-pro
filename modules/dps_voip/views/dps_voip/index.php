<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
          <h4 class="tw-font-semibold tw-text-lg">
            <i class="fa fa-phone-square tw-mr-2 text-success"></i> VoIP Central
          </h4>
          <?php if ($is_admin): ?>
          <div>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAddNumber">
              <i class="fa fa-plus"></i> Adicionar Número
            </button>
            <a href="<?= admin_url('dps_voip/settings') ?>" class="btn btn-default btn-sm">
              <i class="fa fa-cog"></i> Configurações
            </a>
          </div>
          <?php endif; ?>
        </div>

        <!-- Estatísticas -->
        <div class="row tw-mb-4">
          <div class="col-md-3">
            <div class="panel_s">
              <div class="panel-body tw-text-center">
                <div class="tw-text-3xl tw-font-bold text-success"><?= $stats['total'] ?></div>
                <div class="tw-text-sm text-muted">Total Chamadas</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="panel_s">
              <div class="panel-body tw-text-center">
                <div class="tw-text-3xl tw-font-bold text-info"><?= $stats['outbound'] ?></div>
                <div class="tw-text-sm text-muted">Saída</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="panel_s">
              <div class="panel-body tw-text-center">
                <div class="tw-text-3xl tw-font-bold text-warning"><?= $stats['inbound'] ?></div>
                <div class="tw-text-sm text-muted">Entrada</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="panel_s">
              <div class="panel-body tw-text-center">
                <div class="tw-text-3xl tw-font-bold text-primary"><?= gmdate('i:s', $stats['avg_duration']) ?></div>
                <div class="tw-text-sm text-muted">Duração Média</div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Softphone -->
          <div class="col-md-4">
            <div class="panel_s">
              <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-phone"></i> Softphone</h4>
              </div>
              <div class="panel-body">
                <?php if ($my_number): ?>
                <div class="tw-mb-3">
                  <span class="label label-success"><i class="fa fa-check"></i> Número atribuído</span>
                  <strong class="tw-ml-2"><?= htmlspecialchars($my_number['twilio_number']) ?></strong>
                  <?php if ($my_number['friendly_name']): ?>
                  <div class="text-muted tw-text-sm"><?= htmlspecialchars($my_number['friendly_name']) ?></div>
                  <?php endif; ?>
                </div>

                <!-- Marcador -->
                <div id="voip-softphone">
                  <div id="voip-status" class="alert alert-info tw-text-sm tw-py-2 tw-mb-3">
                    <i class="fa fa-circle-o-notch fa-spin"></i> A ligar ao servidor...
                  </div>

                  <div class="input-group tw-mb-3">
                    <input type="tel" id="voip-number-input" class="form-control" placeholder="+351912345678" />
                    <span class="input-group-btn">
                      <button id="voip-call-btn" class="btn btn-success" disabled>
                        <i class="fa fa-phone"></i>
                      </button>
                    </span>
                  </div>

                  <!-- Teclado numérico -->
                  <div id="voip-dialpad" class="tw-grid tw-grid-cols-3 tw-gap-1 tw-mb-3">
                    <?php foreach (['1','2','3','4','5','6','7','8','9','*','0','#'] as $k): ?>
                    <button class="btn btn-default btn-sm voip-key" data-key="<?= $k ?>"><?= $k ?></button>
                    <?php endforeach; ?>
                  </div>

                  <!-- Controles durante chamada -->
                  <div id="voip-call-controls" style="display:none;">
                    <div class="tw-text-center tw-mb-2">
                      <span id="voip-call-timer" class="tw-text-lg tw-font-mono">00:00</span>
                      <div class="text-muted tw-text-sm" id="voip-call-to"></div>
                    </div>
                    <div class="tw-flex tw-gap-2 tw-justify-center">
                      <button id="voip-mute-btn" class="btn btn-default btn-sm" title="Mudo">
                        <i class="fa fa-microphone"></i>
                      </button>
                      <button id="voip-hangup-btn" class="btn btn-danger btn-sm">
                        <i class="fa fa-phone"></i> Desligar
                      </button>
                    </div>
                  </div>
                </div>

                <?php else: ?>
                <div class="alert alert-warning">
                  <i class="fa fa-exclamation-triangle"></i>
                  Não tem um número Twilio atribuído. Contacte o administrador.
                </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Números atribuídos (admin) -->
            <?php if ($is_admin): ?>
            <div class="panel_s">
              <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-list"></i> Números Twilio</h4>
              </div>
              <div class="panel-body" style="padding:0;">
                <table class="table table-condensed tw-mb-0">
                  <thead>
                    <tr>
                      <th>Número</th>
                      <th>Comercial</th>
                      <th>Estado</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($numbers as $n): ?>
                    <tr>
                      <td>
                        <strong><?= htmlspecialchars($n['twilio_number']) ?></strong>
                        <?php if ($n['friendly_name']): ?>
                        <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($n['friendly_name']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($n['firstname']): ?>
                          <?= htmlspecialchars($n['firstname'] . ' ' . $n['lastname']) ?>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($n['is_active']): ?>
                          <span class="label label-success">Ativo</span>
                        <?php else: ?>
                          <span class="label label-default">Inativo</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <button class="btn btn-xs btn-default btn-edit-number"
                          data-id="<?= $n['id'] ?>"
                          data-number="<?= htmlspecialchars($n['twilio_number']) ?>"
                          data-name="<?= htmlspecialchars($n['friendly_name']) ?>"
                          data-staff="<?= $n['staff_id'] ?>"
                          data-active="<?= $n['is_active'] ?>"
                          title="Editar">
                          <i class="fa fa-pencil"></i>
                        </button>
                        <a href="<?= admin_url('dps_voip/delete_number/' . $n['id']) ?>"
                          class="btn btn-xs btn-danger"
                          onclick="return confirm('Remover este número?')"
                          title="Remover">
                          <i class="fa fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($numbers)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Nenhum número adicionado</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Histórico de chamadas -->
          <div class="col-md-8">
            <div class="panel_s">
              <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-history"></i> Histórico de Chamadas</h4>
              </div>
              <div class="panel-body">
                <!-- Filtros -->
                <div class="row tw-mb-3">
                  <?php if ($is_admin): ?>
                  <div class="col-md-3">
                    <select id="filter-staff" class="form-control input-sm">
                      <option value="">Todos os comerciais</option>
                      <?php foreach ($staff_list as $s): ?>
                      <option value="<?= $s['staffid'] ?>"><?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <?php endif; ?>
                  <div class="col-md-2">
                    <select id="filter-direction" class="form-control input-sm">
                      <option value="">Todas</option>
                      <option value="outbound">Saída</option>
                      <option value="inbound">Entrada</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <input type="date" id="filter-date-from" class="form-control input-sm" placeholder="De">
                  </div>
                  <div class="col-md-2">
                    <input type="date" id="filter-date-to" class="form-control input-sm" placeholder="Até">
                  </div>
                  <div class="col-md-2">
                    <button id="btn-filter" class="btn btn-default btn-sm btn-block">
                      <i class="fa fa-filter"></i> Filtrar
                    </button>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-hover" id="calls-table">
                    <thead>
                      <tr>
                        <th>Data/Hora</th>
                        <th>Direção</th>
                        <th>De</th>
                        <th>Para</th>
                        <th>Comercial</th>
                        <th>Estado</th>
                        <th>Duração</th>
                      </tr>
                    </thead>
                    <tbody id="calls-tbody">
                      <?php foreach ($calls as $c): ?>
                      <tr>
                        <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        <td>
                          <?php if ($c['direction'] === 'outbound'): ?>
                            <span class="label label-info"><i class="fa fa-arrow-up"></i> Saída</span>
                          <?php else: ?>
                            <span class="label label-warning"><i class="fa fa-arrow-down"></i> Entrada</span>
                          <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['from_number'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($c['to_number'] ?? '—') ?></td>
                        <td><?= $c['firstname'] ? htmlspecialchars($c['firstname'] . ' ' . $c['lastname']) : '—' ?></td>
                        <td><?php
                          $status_labels = [
                            'completed'   => 'success',
                            'in-progress' => 'info',
                            'ringing'     => 'warning',
                            'initiated'   => 'default',
                            'failed'      => 'danger',
                            'busy'        => 'warning',
                            'no-answer'   => 'default',
                            'canceled'    => 'default',
                          ];
                          $lbl = $status_labels[$c['status']] ?? 'default';
                          echo '<span class="label label-' . $lbl . '">' . htmlspecialchars($c['status']) . '</span>';
                        ?></td>
                        <td><?= $c['duration'] ? gmdate('i:s', $c['duration']) : '—' ?></td>
                      </tr>
                      <?php endforeach; ?>
                      <?php if (empty($calls)): ?>
                      <tr><td colspan="7" class="text-center text-muted">Nenhuma chamada registada</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Adicionar Número -->
<?php if ($is_admin): ?>
<div class="modal fade" id="modalAddNumber" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="modal-number-title">Adicionar Número Twilio</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-number-id" value="">
        <div class="form-group">
          <label>Número Twilio (formato E.164)</label>
          <input type="text" id="input-twilio-number" class="form-control" placeholder="+15551234567">
          <small class="text-muted">Exemplo: +15551234567 (EUA), +351912345678 (Portugal)</small>
        </div>
        <div class="form-group">
          <label>Nome descritivo</label>
          <input type="text" id="input-friendly-name" class="form-control" placeholder="ex: DPS Lisboa">
        </div>
        <div class="form-group">
          <label>Atribuir a comercial</label>
          <select id="input-staff-id" class="form-control">
            <option value="">— Não atribuído —</option>
            <?php foreach ($staff_list as $s): ?>
            <option value="<?= $s['staffid'] ?>"><?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" id="edit-active-group" style="display:none;">
          <label>
            <input type="checkbox" id="input-is-active" checked> Ativo
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-save-number">
          <i class="fa fa-save"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// ===================== GESTÃO DE NÚMEROS =====================
<?php if ($is_admin): ?>
$('#btn-save-number').on('click', function() {
  var id = $('#edit-number-id').val();
  var url = id ? '<?= admin_url('dps_voip/update_number') ?>' : '<?= admin_url('dps_voip/add_number') ?>';
  var data = {
    id: id,
    twilio_number: $('#input-twilio-number').val(),
    friendly_name: $('#input-friendly-name').val(),
    staff_id: $('#input-staff-id').val(),
    is_active: $('#input-is-active').is(':checked') ? 1 : 0,
  };
  $.post(url, data, function(r) {
    if (r.success) {
      alert_float('success', r.message);
      setTimeout(function(){ location.reload(); }, 800);
    } else {
      alert_float('danger', r.message);
    }
  }, 'json');
});

$(document).on('click', '.btn-edit-number', function() {
  var d = $(this).data();
  $('#edit-number-id').val(d.id);
  $('#input-twilio-number').val(d.number).prop('readonly', true);
  $('#input-friendly-name').val(d.name);
  $('#input-staff-id').val(d.staff);
  $('#input-is-active').prop('checked', d.active == 1);
  $('#edit-active-group').show();
  $('#modal-number-title').text('Editar Número');
  $('#modalAddNumber').modal('show');
});

$('#modalAddNumber').on('hidden.bs.modal', function() {
  $('#edit-number-id').val('');
  $('#input-twilio-number').prop('readonly', false).val('');
  $('#input-friendly-name').val('');
  $('#input-staff-id').val('');
  $('#input-is-active').prop('checked', true);
  $('#edit-active-group').hide();
  $('#modal-number-title').text('Adicionar Número Twilio');
});
<?php endif; ?>

// ===================== FILTROS HISTÓRICO =====================
$('#btn-filter').on('click', function() {
  var params = {
    staff_id: $('#filter-staff').val() || '',
    direction: $('#filter-direction').val() || '',
    date_from: $('#filter-date-from').val() || '',
    date_to: $('#filter-date-to').val() || '',
  };
  $.get('<?= admin_url('dps_voip/get_calls_ajax') ?>', params, function(r) {
    if (!r.success) return;
    var tbody = $('#calls-tbody');
    tbody.empty();
    if (!r.calls.length) {
      tbody.append('<tr><td colspan="7" class="text-center text-muted">Nenhuma chamada encontrada</td></tr>');
      return;
    }
    var statusLabels = {
      'completed': 'success', 'in-progress': 'info', 'ringing': 'warning',
      'initiated': 'default', 'failed': 'danger', 'busy': 'warning',
      'no-answer': 'default', 'canceled': 'default'
    };
    $.each(r.calls, function(i, c) {
      var dir = c.direction === 'outbound'
        ? '<span class="label label-info"><i class="fa fa-arrow-up"></i> Saída</span>'
        : '<span class="label label-warning"><i class="fa fa-arrow-down"></i> Entrada</span>';
      var lbl = statusLabels[c.status] || 'default';
      var dur = c.duration ? Math.floor(c.duration/60) + ':' + ('0'+c.duration%60).slice(-2) : '—';
      var staff = c.firstname ? c.firstname + ' ' + c.lastname : '—';
      var dt = c.created_at ? c.created_at.substr(0,16).replace('T',' ') : '—';
      tbody.append('<tr><td>'+dt+'</td><td>'+dir+'</td><td>'+(c.from_number||'—')+'</td><td>'+(c.to_number||'—')+'</td><td>'+staff+'</td><td><span class="label label-'+lbl+'">'+c.status+'</span></td><td>'+dur+'</td></tr>');
    });
  }, 'json');
});

// ===================== SOFTPHONE =====================
<?php if ($my_number): ?>
var voipDevice = null;
var voipConn   = null;
var voipTimer  = null;
var voipSecs   = 0;
var voipMuted  = false;

function voipUpdateStatus(msg, type) {
  var cls = {'info':'alert-info','success':'alert-success','danger':'alert-danger','warning':'alert-warning'};
  $('#voip-status').removeClass('alert-info alert-success alert-danger alert-warning')
    .addClass(cls[type]||'alert-info').html(msg);
}

function voipStartTimer() {
  voipSecs = 0;
  voipTimer = setInterval(function() {
    voipSecs++;
    var m = Math.floor(voipSecs/60), s = voipSecs%60;
    $('#voip-call-timer').text(('0'+m).slice(-2)+':'+('0'+s).slice(-2));
  }, 1000);
}

function voipStopTimer() {
  if (voipTimer) { clearInterval(voipTimer); voipTimer = null; }
}

// Inicializar Twilio Device
$.get('<?= admin_url('dps_voip/get_token') ?>', function(r) {
  if (!r.success) {
    voipUpdateStatus('<i class="fa fa-exclamation-triangle"></i> ' + r.message, 'warning');
    return;
  }
  voipDevice = new Twilio.Device(r.token, { debug: false, enableRingingState: true });

  voipDevice.on('ready', function() {
    voipUpdateStatus('<i class="fa fa-circle text-success"></i> Pronto para chamadas', 'success');
    $('#voip-call-btn').prop('disabled', false);
  });

  voipDevice.on('error', function(err) {
    voipUpdateStatus('<i class="fa fa-times-circle"></i> Erro: ' + err.message, 'danger');
  });

  voipDevice.on('incoming', function(conn) {
    voipConn = conn;
    var from = conn.parameters.From || 'Desconhecido';
    voipUpdateStatus('<i class="fa fa-phone fa-spin"></i> Chamada de <strong>' + from + '</strong>', 'warning');
    if (confirm('Chamada de ' + from + '. Atender?')) {
      conn.accept();
      voipStartTimer();
      $('#voip-call-controls').show();
      $('#voip-call-to').text('De: ' + from);
      voipUpdateStatus('<i class="fa fa-phone text-success"></i> Em chamada com ' + from, 'success');
    } else {
      conn.reject();
      voipUpdateStatus('<i class="fa fa-circle text-success"></i> Pronto para chamadas', 'success');
    }
  });

  voipDevice.on('disconnect', function() {
    voipStopTimer();
    voipConn = null;
    voipMuted = false;
    $('#voip-call-controls').hide();
    $('#voip-call-btn').prop('disabled', false).html('<i class="fa fa-phone"></i>');
    $('#voip-mute-btn').html('<i class="fa fa-microphone"></i>');
    voipUpdateStatus('<i class="fa fa-circle text-success"></i> Pronto para chamadas', 'success');
  });

}, 'json');

// Teclado numérico
$(document).on('click', '.voip-key', function() {
  var k = $(this).data('key');
  $('#voip-number-input').val($('#voip-number-input').val() + k);
  if (voipConn) voipConn.sendDigits(k);
});

// Botão ligar
$('#voip-call-btn').on('click', function() {
  var to = $('#voip-number-input').val().trim();
  if (!to) { alert('Introduza um número'); return; }
  if (!voipDevice) { alert('Dispositivo não inicializado'); return; }

  voipConn = voipDevice.connect({ To: to, staff_id: '<?= get_staff_user_id() ?>' });
  voipUpdateStatus('<i class="fa fa-phone fa-spin"></i> A ligar para <strong>' + to + '</strong>...', 'info');
  $('#voip-call-btn').prop('disabled', true);
  $('#voip-call-to').text('Para: ' + to);
  $('#voip-call-controls').show();

  voipConn.on('accept', function() {
    voipStartTimer();
    voipUpdateStatus('<i class="fa fa-phone text-success"></i> Em chamada com ' + to, 'success');
  });
});

// Desligar
$('#voip-hangup-btn').on('click', function() {
  if (voipConn) voipConn.disconnect();
  else if (voipDevice) voipDevice.disconnectAll();
});

// Mudo
$('#voip-mute-btn').on('click', function() {
  if (!voipConn) return;
  voipMuted = !voipMuted;
  voipConn.mute(voipMuted);
  $(this).html(voipMuted ? '<i class="fa fa-microphone-slash text-danger"></i>' : '<i class="fa fa-microphone"></i>');
});
<?php endif; ?>
</script>

<?php init_tail(); ?>
