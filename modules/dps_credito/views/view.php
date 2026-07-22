<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    Processo #<?php echo $processo['id']; ?>
                                    <span class="label <?php echo dps_credito_cor_estado($processo['estado']); ?>">
                                        <?php echo dps_credito_nome_estado($processo['estado']); ?>
                                    </span>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('dps_credito'); ?>" class="btn btn-default btn-sm">Voltar</a>
                            </div>
                        </div>
                        <hr>

                        <table class="table table-borderless">
                            <tr><td width="35%"><strong>Cliente</strong></td><td><?php echo html_escape($processo['cliente']); ?></td></tr>
                            <tr>
                                <td><strong>Lead</strong></td>
                                <td>
                                    <?php if (!empty($processo['lead_id'])) { ?>
                                        <a href="<?php echo admin_url('leads/index/' . $processo['lead_id']); ?>" target="_blank">
                                            #<?php echo $processo['lead_id']; ?> <?php echo html_escape($processo['lead_nome']); ?>
                                        </a>
                                    <?php } else { echo '—'; } ?>
                                </td>
                            </tr>
                            <tr><td><strong>Contacto</strong></td><td><?php echo html_escape($processo['lead_telefone'] ?: '—'); ?> <?php echo !empty($processo['lead_email']) ? '· ' . html_escape($processo['lead_email']) : ''; ?></td></tr>
                            <tr><td><strong>Comercial</strong></td><td><?php echo html_escape($processo['staff_nome']); ?></td></tr>
                            <tr><td><strong>Situação</strong></td><td><?php echo dps_credito_nome_situacao($processo['situacao']); ?></td></tr>
                            <tr><td><strong>Banco</strong></td><td><?php echo html_escape($processo['banco'] ?: '—'); ?></td></tr>
                            <tr><td><strong>Montante pedido</strong></td><td><?php echo $processo['montante'] !== null ? app_format_money($processo['montante'], get_base_currency()) : '—'; ?></td></tr>
                        </table>

                        <?php if ($processo['estado'] === 'documentos_em_falta' && !empty($processo['docs_em_falta'])) { ?>
                            <div class="alert alert-warning">
                                <strong>Documentos pedidos ao comercial:</strong><br>
                                <?php echo nl2br(html_escape($processo['docs_em_falta'])); ?>
                            </div>
                        <?php } ?>

                        <h5>Documentos</h5>
                        <?php if (empty($docs)) { ?>
                            <p class="text-muted">Sem documentos.</p>
                        <?php } else { ?>
                            <table class="table">
                                <tbody>
                                    <?php foreach ($docs as $doc) { ?>
                                        <tr>
                                            <td><?php echo html_escape($doc['original_name']); ?><br><small class="text-muted"><?php echo _dt($doc['dateadded']); ?></small></td>
                                            <td class="text-right">
                                                <?php if ($pode_download || (int) $processo['staff_id'] === (int) get_staff_user_id()) { ?>
                                                    <a href="<?php echo admin_url('dps_credito/download_doc/' . $doc['id']); ?>" class="btn btn-default btn-xs"><i class="fa fa-download"></i></a>
                                                <?php } ?>
                                                <a href="<?php echo admin_url('dps_credito/delete_doc/' . $doc['id']); ?>" class="btn btn-danger btn-xs _delete"><i class="fa fa-remove"></i></a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                        <?php echo form_open_multipart(admin_url('dps_credito/update/' . $processo['id'])); ?>
                        <div class="form-group">
                            <label class="control-label">Anexar mais documentos</label>
                            <input type="file" name="documentos[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Observações internas</label>
                            <textarea name="observacoes" class="form-control" rows="2"><?php echo html_escape($processo['observacoes'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-default btn-sm">Guardar documentos / notas</button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <?php if (is_admin() || staff_can('edit', 'dps_credito')) { ?>

                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="no-margin">Seguimento</h5>
                            <hr>

                            <?php if ($processo['estado'] !== 'sucesso') { ?>
                                <!-- Pedir documentos em falta -->
                                <?php echo form_open(admin_url('dps_credito/documentos_em_falta/' . $processo['id'])); ?>
                                <div class="form-group">
                                    <label class="control-label">Faltam documentos? Diga ao comercial o quê:</label>
                                    <textarea name="nota" class="form-control" rows="2" placeholder="Ex.: falta o comprovativo de rendimentos e o IRS."></textarea>
                                </div>
                                <button type="submit" class="btn btn-warning btn-block btn-sm">Pedir documentos ao comercial</button>
                                <?php echo form_close(); ?>

                                <hr>

                                <!-- Mudar estado -->
                                <?php echo form_open(admin_url('dps_credito/estado/' . $processo['id'])); ?>
                                <div class="form-group">
                                    <label class="control-label">Estado</label>
                                    <select name="estado" class="form-control" id="dps-credito-estado-sel">
                                        <?php foreach (dps_credito_estados_processo() as $e) { ?>
                                            <option value="<?php echo $e; ?>" <?php echo $processo['estado'] === $e ? 'selected' : ''; ?>>
                                                <?php echo dps_credito_nome_estado($e); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group" id="dps-credito-valor-grupo" style="display:none;">
                                    <label class="control-label">Valor do crédito recebido (€)</label>
                                    <input type="text" name="valor_credito" class="form-control" value="<?php echo $processo['valor_credito'] ?? ''; ?>">
                                    <small class="text-muted">A comissão do comercial (<?php echo rtrim(rtrim(number_format(dps_credito_taxa_comissao(), 2, ',', ''), '0'), ','); ?>%) é calculada sobre este valor.</small>
                                </div>
                                <button type="submit" class="btn btn-info btn-block btn-sm">Actualizar estado</button>
                                <?php echo form_close(); ?>
                            <?php } else { ?>
                                <div class="alert alert-success no-margin">
                                    <strong>Concluído com sucesso.</strong><br>
                                    Valor do crédito: <?php echo app_format_money($processo['valor_credito'] ?? 0, get_base_currency()); ?><br>
                                    Comissão do comercial: <strong><?php echo app_format_money($processo['comissao_total'] ?? 0, get_base_currency()); ?></strong>
                                    (<?php echo rtrim(rtrim(number_format((float) ($processo['taxa'] ?? dps_credito_taxa_comissao()), 2, ',', ''), '0'), ','); ?>%)
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                <?php } ?>

                <?php if ($processo['estado'] === 'sucesso') { ?>
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="no-margin">Comissão</h5>
                            <hr>
                            <table class="table table-borderless no-margin">
                                <tr><td><strong>Valor do crédito</strong></td><td><?php echo app_format_money($processo['valor_credito'] ?? 0, get_base_currency()); ?></td></tr>
                                <tr><td><strong>Taxa</strong></td><td><?php echo rtrim(rtrim(number_format((float) ($processo['taxa'] ?? 0), 2, ',', ''), '0'), ','); ?>%</td></tr>
                                <tr><td><strong>Comissão</strong></td><td><strong><?php echo app_format_money($processo['comissao_total'] ?? 0, get_base_currency()); ?></strong></td></tr>
                            </table>
                        </div>
                    </div>
                <?php } ?>
            </div>

        </div>
    </div>
</div>
<?php
// Build titular lookup: [num_titular => data]
$_titulares_map = [];
foreach ($titulares as $_t) { $_titulares_map[(int)$_t['num_titular']] = $_t; }

// Build typed-docs lookup: [num_titular][tipo_doc] => [docs]
$_docs_tipados_map = [];
foreach ($docs_tipados as $_d) {
    $_k = (int)$_d['num_titular'];
    $_docs_tipados_map[$_k][$_d['tipo_doc']][] = $_d;
}

$_pode_editar = is_admin() || staff_can('edit', 'dps_credito') || ((int)$processo['staff_id'] === (int)get_staff_user_id());

$_tipos_titular = [
    'cartao_cidadao'   => 'Cartão Cidadão',
    'mapa_bpt'         => 'Mapa Responsabilidades BdP',
    'extratos'         => 'Últimos 3 extratos bancários',
    'contrato_trabalho'=> 'Contrato de trabalho / efectividade',
    'recibos'          => 'Últimos 3 recibos de vencimento',
    'irs'              => 'Último IRS + Nota de liquidação',
    'declaracao_irs'   => 'Declaração IRS (formato digital)',
    'outros_rendimentos' => 'Outros rendimentos',
];

$_tipos_imovel = [
    'caderneta_predial'  => 'Caderneta predial',
    'certidao_predial'   => 'Certidão permanente predial',
    'cpcv'               => 'CPCV',
    'licenca_utilizacao' => 'Licença de utilização',
    'planta'             => 'Planta do imóvel',
    'declaracao_condo'   => 'Declaração de condomínio',
    'ficha_tecnica'      => 'Ficha técnica habitacional',
];
?>
<div style="margin-top:24px;">
  <div class="row">
    <div class="col-md-12">

      <!-- Titulares Section -->
      <div class="panel_s" id="dps-titulares-panel">
        <div class="panel-body">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
            <h5 class="no-margin"><i class="fa fa-users"></i> Titulares do Pedido de Crédito</h5>
            <?php if ($_pode_editar): ?>
            <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin:0;cursor:pointer;">
              <input type="checkbox" id="dps-t2-toggle" <?= isset($_titulares_map[2]) ? 'checked' : '' ?>>
              <span style="font-size:13px;">2.º titular</span>
            </label>
            <?php endif ?>
          </div>

          <div class="row">
            <!-- 1.º Titular -->
            <div class="col-md-6" id="dps-t1-col">
              <h6 style="color:#1a56db;border-bottom:2px solid #1a56db;padding-bottom:6px;margin-bottom:14px;">1.º Titular</h6>
              <?php echo _dps_titular_form($processo['id'], 1, $_titulares_map[1] ?? [], $_pode_editar); ?>
            </div>
            <!-- 2.º Titular -->
            <div class="col-md-6" id="dps-t2-col" style="<?= isset($_titulares_map[2]) ? '' : 'display:none' ?>">
              <h6 style="color:#1a56db;border-bottom:2px solid #1a56db;padding-bottom:6px;margin-bottom:14px;">2.º Titular</h6>
              <?php echo _dps_titular_form($processo['id'], 2, $_titulares_map[2] ?? [], $_pode_editar); ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Docs por Titular -->
      <?php foreach ([1 => '1.º Titular', 2 => '2.º Titular'] as $_nt => $_nt_label): ?>
      <div class="panel_s dps-docs-titular-panel" id="dps-docs-t<?= $_nt ?>-panel"
           style="<?= $_nt === 2 && !isset($_titulares_map[2]) ? 'display:none' : '' ?>">
        <div class="panel-body">
          <h5 class="no-margin" style="margin-bottom:14px;">
            <i class="fa fa-folder-open-o"></i> Documentos — <?= $_nt_label ?>
          </h5>
          <div class="row">
            <?php foreach ($_tipos_titular as $_tipo => $_label): ?>
            <div class="col-md-6 col-lg-4" style="margin-bottom:16px;">
              <?php echo _dps_doc_slot($processo['id'], $_nt, $_tipo, $_label, $_docs_tipados_map[$_nt][$_tipo] ?? [], $_pode_editar, $pode_download); ?>
            </div>
            <?php endforeach ?>
          </div>
        </div>
      </div>
      <?php endforeach ?>

      <!-- Docs Imóvel -->
      <div class="panel_s">
        <div class="panel-body">
          <h5 class="no-margin" style="margin-bottom:14px;">
            <i class="fa fa-home"></i> Documentos — Imóvel
          </h5>
          <div class="row">
            <?php foreach ($_tipos_imovel as $_tipo => $_label): ?>
            <div class="col-md-6 col-lg-4" style="margin-bottom:16px;">
              <?php echo _dps_doc_slot($processo['id'], 0, $_tipo, $_label, $_docs_tipados_map[0][$_tipo] ?? [], $_pode_editar, $pode_download); ?>
            </div>
            <?php endforeach ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
.dps-titular-field { margin-bottom:10px; }
.dps-titular-field label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;margin-bottom:3px;display:block; }
.dps-titular-field input,.dps-titular-field select,.dps-titular-field textarea { width:100%;border:1px solid #d1d5db;border-radius:5px;padding:7px 10px;font-size:13px; }
.dps-doc-slot { border:1px solid #e2e8f0;border-radius:8px;padding:12px;background:#fafafa; }
.dps-doc-slot-title { font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px; }
.dps-doc-file-row { display:flex;align-items:center;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f1f5f9;gap:6px; }
.dps-doc-file-row:last-child { border-bottom:none; }
.dps-doc-file-row a.name { font-size:12px;color:#1a56db;text-decoration:none;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.dps-doc-file-row .del-btn { flex-shrink:0;background:none;border:none;color:#ef4444;cursor:pointer;padding:2px 6px;font-size:13px; }
.dps-upload-lbl { display:inline-block;margin-top:6px;cursor:pointer;font-size:11px;color:#1a56db;border:1px dashed #1a56db;border-radius:4px;padding:3px 10px; }
.dps-upload-lbl:hover { background:#e8f0fe; }
.dps-upload-input { display:none; }
.dps-save-msg { font-size:12px;margin-top:4px; }
.dps-save-msg.ok { color:#15803d; }
.dps-save-msg.err { color:#b91c1c; }
</style>

<?php
function _dps_titular_form($processo_id, $num, $t, $pode_editar) {
    $regimes = ['Comunhão de adquiridos','Comunhão geral de bens','Separação de bens','Participação nos adquiridos','União de facto'];
    $id_prefix = 'dps-t' . $num;
    $ob = '<form class="dps-titular-form" data-processo="' . $processo_id . '" data-num="' . $num . '">';
    $fields = [
        'nome'              => ['Nome completo', 'text'],
        'nif'               => ['NIF', 'text'],
        'data_nascimento'   => ['Data de nascimento', 'date'],
        'morada'            => ['Morada', 'text'],
        'regime_casamento'  => ['Regime de casamento', 'select'],
        'profissao'         => ['Profissão', 'text'],
        'rendimento_mensal' => ['Rendimento mensal (€)', 'text'],
        'telefone'          => ['Telefone', 'text'],
        'email'             => ['Email', 'email'],
    ];
    foreach ($fields as $name => $info) {
        [$label, $type] = $info;
        $val = htmlspecialchars($t[$name] ?? '', ENT_QUOTES);
        $ob .= '<div class="dps-titular-field">';
        $ob .= '<label>' . $label . '</label>';
        if ($type === 'select') {
            $ob .= '<select name="' . $name . '"' . ($pode_editar ? '' : ' disabled') . '>';
            $ob .= '<option value="">— Selecionar —</option>';
            foreach ($regimes as $r) {
                $sel = ($val === $r) ? ' selected' : '';
                $ob .= '<option value="' . htmlspecialchars($r, ENT_QUOTES) . '"' . $sel . '>' . htmlspecialchars($r) . '</option>';
            }
            $ob .= '</select>';
        } else {
            $ob .= '<input type="' . $type . '" name="' . $name . '" value="' . $val . '"' . ($pode_editar ? '' : ' readonly') . '>';
        }
        $ob .= '</div>';
    }
    if ($pode_editar) {
        $ob .= '<button type="submit" class="btn btn-primary btn-sm btn-block" style="margin-top:8px;">'
             . '<i class="fa fa-save"></i> Guardar</button>';
        $ob .= '<div class="dps-save-msg" id="dps-tmsg-' . $num . '"></div>';
    }
    $ob .= '</form>';
    return $ob;
}

function _dps_doc_slot($processo_id, $num_titular, $tipo, $label, $docs, $pode_editar, $pode_download) {
    $container_id = 'dps-docs-' . $num_titular . '-' . $tipo;
    $ob  = '<div class="dps-doc-slot">';
    $ob .= '<div class="dps-doc-slot-title">' . htmlspecialchars($label) . '</div>';
    $ob .= '<div id="' . $container_id . '">';
    foreach ($docs as $d) {
        $name  = htmlspecialchars($d['original_name'] ?: $d['filename']);
        $short = mb_strlen($name) > 28 ? mb_substr($name, 0, 26) . '…' : $name;
        $ob .= '<div class="dps-doc-file-row" id="dps-drow-' . $d['id'] . '">';
        if ($pode_download) {
            $ob .= '<a href="' . admin_url('dps_credito/download_doc/' . $d['id']) . '" class="name" title="' . $name . '" target="_blank">'
                 . '<i class="fa fa-paperclip"></i> ' . $short . '</a>';
        } else {
            $ob .= '<span class="name" title="' . $name . '">' . $short . '</span>';
        }
        if ($pode_editar) {
            $ob .= '<button type="button" class="del-btn" title="Remover" onclick="dpsCreditoDelDoc(' . $d['id'] . ',this)">'
                 . '<i class="fa fa-trash"></i></button>';
        }
        $ob .= '</div>';
    }
    $ob .= '</div>';
    if ($pode_editar) {
        $uid = 'dps-upl-' . $num_titular . '-' . $tipo;
        $ob .= '<label class="dps-upload-lbl" for="' . $uid . '">'
             . '<i class="fa fa-plus"></i> Adicionar</label>';
        $ob .= '<input type="file" class="dps-upload-input" id="' . $uid . '" multiple'
             . ' data-processo="' . $processo_id . '"'
             . ' data-titular="' . $num_titular . '"'
             . ' data-tipo="' . htmlspecialchars($tipo, ENT_QUOTES) . '">';
    }
    $ob .= '</div>';
    return $ob;
}
?>

<?php init_tail(); ?>
<script>
(function () {
    // Estado toggle
    function toggleValor() {
        var sel = document.getElementById('dps-credito-estado-sel');
        var grp = document.getElementById('dps-credito-valor-grupo');
        if (sel && grp) { grp.style.display = sel.value === 'sucesso' ? '' : 'none'; }
    }
    var sel = document.getElementById('dps-credito-estado-sel');
    if (sel) { sel.addEventListener('change', toggleValor); toggleValor(); }

    // 2.º titular toggle
    var t2Toggle = document.getElementById('dps-t2-toggle');
    if (t2Toggle) {
        t2Toggle.addEventListener('change', function () {
            var on = this.checked;
            var t2col   = document.getElementById('dps-t2-col');
            var t2panel = document.getElementById('dps-docs-t2-panel');
            if (t2col)   t2col.style.display   = on ? '' : 'none';
            if (t2panel) t2panel.style.display  = on ? '' : 'none';
        });
    }

    // Titular form submit
    document.addEventListener('submit', function (e) {
        if (!e.target.classList.contains('dps-titular-form')) return;
        e.preventDefault();
        var form    = e.target;
        var processo = form.dataset.processo;
        var num     = form.dataset.num;
        var btn     = form.querySelector('button[type="submit"]');
        var msgEl   = document.getElementById('dps-tmsg-' + num);
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; }

        var fd = new FormData(form);
        fd.append('num_titular', num);

        fetch(window.admin_url + 'dps_credito/guardar_titular/' + processo, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Guardar'; }
            if (msgEl) {
                msgEl.textContent = res.success ? '✓ Guardado' : '✗ Erro';
                msgEl.className = 'dps-save-msg ' + (res.success ? 'ok' : 'err');
                setTimeout(function () { msgEl.textContent = ''; msgEl.className = 'dps-save-msg'; }, 3000);
            }
        })
        .catch(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Guardar'; }
        });
    });

    // File upload inputs
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('dps-upload-input')) return;
        var inp     = e.target;
        var processo = inp.dataset.processo;
        var titular  = inp.dataset.titular;
        var tipo     = inp.dataset.tipo;
        var files    = inp.files;
        if (!files || !files.length) return;
        Array.from(files).forEach(function (f) { dpsCreditoUpload(processo, titular, tipo, f); });
        inp.value = '';
    });

    function dpsCreditoUpload(processo, titular, tipo, file) {
        var fd = new FormData();
        fd.append('doc', file);
        fd.append('num_titular', titular);
        fd.append('tipo_doc', tipo);

        fetch(window.admin_url + 'dps_credito/upload_doc_tipado/' + processo, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                var container = document.getElementById('dps-docs-' + titular + '-' + tipo);
                if (!container) return;
                var row = document.createElement('div');
                row.className = 'dps-doc-file-row';
                row.id = 'dps-drow-' + res.doc_id;
                var name  = res.original_name || '';
                var short = name.length > 28 ? name.slice(0, 26) + '…' : name;
                row.innerHTML = '<a href="' + window.admin_url + 'dps_credito/download_doc/' + res.doc_id + '" class="name" target="_blank"><i class="fa fa-paperclip"></i> ' + short + '</a>'
                              + '<button type="button" class="del-btn" title="Remover" onclick="dpsCreditoDelDoc(' + res.doc_id + ',this)"><i class="fa fa-trash"></i></button>';
                container.appendChild(row);
            } else {
                alert('Erro: ' + (res.message || 'Erro ao carregar ficheiro'));
            }
        })
        .catch(function () { alert('Erro de ligação.'); });
    }

    // Delete typed doc
    window.dpsCreditoDelDoc = function (docId, btn) {
        if (!confirm('Remover este documento?')) return;
        fetch(window.admin_url + 'dps_credito/delete_doc_ajax/' + docId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                var row = document.getElementById('dps-drow-' + docId);
                if (row) row.remove();
            } else {
                alert('Erro ao remover.');
            }
        });
    };
})();
</script>
