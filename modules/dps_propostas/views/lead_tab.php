<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div role="tabpanel" class="tab-pane" id="dps_propostas_tab">
    <div class="row">
        <div class="col-md-6">
            <div class="panel_s">
                <div class="panel-body">
                    <h4 class="no-margin"><i class="fa fa-paper-plane text-info"></i> Enviar informação</h4>
                    <p class="text-muted" style="font-size:12px;">Envia o dossier + unidades disponíveis pelo teu WhatsApp.</p>
                    <select id="dps_info_emp" class="form-control">
                        <?php foreach ($emps as $k => $e) { ?>
                        <option value="<?= e($k); ?>"><?= e($e['nome']); ?></option>
                        <?php } ?>
                    </select>
                    <button type="button" id="dps_info_btn" class="btn btn-info btn-block mtop10">
                        <i class="fa fa-whatsapp"></i> Enviar informação
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel_s">
                <div class="panel-body">
                    <h4 class="no-margin"><i class="fa fa-file-pdf-o text-danger"></i> Proposta</h4>
                    <p class="text-muted" style="font-size:12px;">Abre o simulador aqui dentro, gera a proposta da unidade e envia por WhatsApp.</p>
                    <select id="dps_prop_emp" class="form-control">
                        <?php foreach ($emps as $k => $e) { ?>
                        <option value="<?= e($k); ?>"><?= e($e['nome']); ?></option>
                        <?php } ?>
                    </select>
                    <button type="button" id="dps_prop_btn" class="btn btn-danger btn-block mtop10">
                        <i class="fa fa-calculator"></i> Abrir simulador aqui
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="dps_sim_wrap" style="display:none;margin-bottom:15px;">
        <div class="panel_s">
            <div class="panel-body" style="padding:8px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <strong id="dps_sim_title"><i class="fa fa-calculator"></i> Simulador</strong>
                    <span class="text-muted" style="font-size:12px;">Gera a proposta da unidade → depois clica em "Enviar proposta ao cliente".</span>
                    <button type="button" id="dps_send_prop" class="btn btn-success btn-sm" style="margin-left:auto;" disabled><i class="fa fa-whatsapp"></i> Enviar proposta ao cliente</button>
                    <button type="button" id="dps_sim_open_tab" class="btn btn-default btn-xs"><i class="fa fa-external-link"></i> Nova aba</button>
                    <button type="button" id="dps_sim_close" class="btn btn-default btn-xs">Fechar</button>
                </div>
                <iframe id="dps_sim_iframe" src="about:blank" style="width:100%;height:78vh;min-height:600px;border:1px solid #e3e7ec;border-radius:6px;background:#fff;"></iframe>
            </div>
        </div>
    </div>

    <?php
    $propostas = array_values(array_filter($rows, function ($r) { return $r->tipo === 'proposta'; }));
    $infos     = array_values(array_filter($rows, function ($r) { return $r->tipo === 'info'; }));
    ?>
    <div class="panel_s">
        <div class="panel-body">
            <h4 class="no-margin"><i class="fa fa-file-pdf-o text-danger"></i> Propostas enviadas</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Empreendimento</th><th>Unidade</th><th>Resultado</th><th>Quando</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php if (empty($propostas)) { ?>
                        <tr><td colspan="5" class="text-muted text-center">Ainda sem propostas.</td></tr>
                        <?php } ?>
                        <?php foreach ($propostas as $r) { ?>
                        <tr>
                            <td><?= e($r->empreendimento); ?></td>
                            <td><strong><?= e($r->unidade ?: '—'); ?></strong></td>
                            <td>
                                <?php if (($r->outcome ?? 'pendente') === 'aceite') { ?>
                                <span class="label label-success">Aceite</span> <strong><?= number_format((float) $r->valor, 0, ',', '.'); ?> €</strong>
                                <?php } elseif (($r->outcome ?? '') === 'recusado') { ?>
                                <span class="label label-danger">Recusada</span>
                                <?php } else { ?>
                                <span class="label label-default">Pendente</span>
                                <?php } ?>
                            </td>
                            <td class="text-muted" style="font-size:12px;"><?= e($r->created_at); ?></td>
                            <td>
                                <?php if (($r->outcome ?? 'pendente') === 'pendente') { ?>
                                <button type="button" class="btn btn-success btn-xs" onclick="dpsResultado(<?= (int) $r->id; ?>,'aceite')"><i class="fa fa-check"></i> Aceite</button>
                                <button type="button" class="btn btn-danger btn-xs" onclick="dpsResultado(<?= (int) $r->id; ?>,'recusado')"><i class="fa fa-times"></i> Recusada</button>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel_s">
        <div class="panel-body">
            <h4 class="no-margin"><i class="fa fa-paper-plane text-info"></i> Informação enviada</h4>
            <p class="text-muted" style="font-size:12px;">Envios de informação/disponíveis (não são propostas).</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Empreendimento</th><th>Detalhe</th><th>Quando</th><th>WA</th></tr></thead>
                    <tbody>
                        <?php if (empty($infos)) { ?>
                        <tr><td colspan="4" class="text-muted text-center">Ainda sem envios de informação.</td></tr>
                        <?php } ?>
                        <?php foreach ($infos as $r) { ?>
                        <tr>
                            <td><?= e($r->empreendimento); ?></td>
                            <td class="text-muted" style="font-size:12px;"><?= e($r->detalhe ?: '—'); ?></td>
                            <td class="text-muted" style="font-size:12px;"><?= e($r->created_at); ?></td>
                            <td><?= $r->wa_ok ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$dps_tl_ic = [
    'whatsapp' => ['fa-whatsapp', '#25D366'], 'proposta' => ['fa-file-pdf-o', '#c0392b'],
    'aceite'   => ['fa-check-circle', '#2f9e44'], 'recusado' => ['fa-times-circle', '#c0392b'],
    'info'     => ['fa-paper-plane', '#1d6fb8'], 'estado' => ['fa-exchange', '#b58105'],
    'nota'     => ['fa-sticky-note-o', '#7a8798'], 'log' => ['fa-circle-o', '#9aa6b2'],
];
?>
<div role="tabpanel" class="tab-pane" id="dps_historico_tab">
    <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin"><i class="fa fa-history"></i> Linha do tempo</h4>
        <div style="margin-top:14px;">
            <?php if (empty($timeline)) { ?><p class="text-muted">Sem interações registadas.</p><?php } ?>
            <?php foreach ($timeline as $ev) {
                $ic = $dps_tl_ic[$ev['tipo']] ?? ['fa-circle-o', '#9aa6b2'];
            ?>
            <div style="display:flex;gap:11px;padding:8px 0;border-bottom:0.5px solid #eef0f2;">
                <div style="width:30px;height:30px;border-radius:8px;flex:none;display:flex;align-items:center;justify-content:center;background:<?= $ic[1]; ?>1a;color:<?= $ic[1]; ?>;"><i class="fa <?= $ic[0]; ?>"></i></div>
                <div style="min-width:0;">
                    <div style="font-size:13px;color:#2b3440;"><?= e($ev['txt']); ?></div>
                    <div class="text-muted" style="font-size:11px;"><?= e($ev['t']); ?><?= $ev['quem'] ? ' · ' . e($ev['quem']) : ''; ?></div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div></div>
</div>

<script>
(function () {
    var pane = document.getElementById('dps_propostas_tab');
    if (!pane || pane.getAttribute('data-dps-init') === '1') { return; }
    pane.setAttribute('data-dps-init', '1');

    // Injeta separadores (Propostas, Histórico) + barra de ações no topo.
    function addTab(nav, href, label, icon) {
        if (nav.querySelector('a[href="' + href + '"]')) { return; }
        var li = document.createElement('li');
        li.setAttribute('role', 'presentation');
        li.innerHTML = '<a href="' + href + '" role="tab" data-toggle="tab"><i class="fa ' + icon + ' menu-icon"></i> ' + label + '</a>';
        nav.appendChild(li);
    }
    function injectActionBar(modal) {
        var body = modal.querySelector('.modal-body') || modal;
        if (body.querySelector('#dps_action_bar')) { return; }
        var tel = <?= json_encode(preg_replace('/[^0-9]/', '', (string) $lead->phonenumber)); ?>;
        var bar = document.createElement('div');
        bar.id = 'dps_action_bar';
        bar.style.cssText = 'display:flex;flex-wrap:wrap;gap:8px;padding:10px 14px;background:#f7f8fa;border-bottom:1px solid #eaecef;';
        var html = '';
        if (tel) {
            html += '<a href="https://wa.me/' + tel + '" target="_blank" rel="noopener" class="btn btn-sm" style="background:#25D366;color:#fff;"><i class="fa fa-whatsapp"></i> WhatsApp</a>';
            html += '<a href="tel:' + tel + '" class="btn btn-sm btn-primary"><i class="fa fa-phone"></i> Ligar</a>';
        }
        html += '<button type="button" class="btn btn-sm" style="background:#5a4fc4;color:#fff;" onclick="dpsGoProp()"><i class="fa fa-paper-plane"></i> Enviar info / Proposta</button>';
        bar.innerHTML = html;
        body.insertBefore(bar, body.firstChild);
    }
    window.dpsGoProp = function () {
        var a = document.querySelector('a[href="#dps_propostas_tab"]');
        if (a && window.jQuery) { window.jQuery(a).tab('show'); }
    };
    function injectTab() {
        var modal = pane.closest('#lead-modal') || pane.closest('.modal');
        var scope = modal || document;
        var nav = scope.querySelector('ul.nav-tabs');
        if (!nav) { return false; }
        addTab(nav, '#dps_propostas_tab', 'Propostas', 'fa-paper-plane');
        addTab(nav, '#dps_historico_tab', 'Histórico', 'fa-history');
        if (modal) { injectActionBar(modal); }
        return true;
    }
    if (!injectTab()) {
        var _tries = 0;
        var _iv = setInterval(function () {
            if (injectTab() || ++_tries > 20) { clearInterval(_iv); }
        }, 150);
    }

    var leadId = <?= (int) $lead->id; ?>;
    var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash(); ?>';
    var base = (typeof admin_url !== 'undefined') ? admin_url : '<?= admin_url(); ?>';

    window.dpsResultado = function (id, outcome) {
        var valor = '';
        if (outcome === 'aceite') {
            valor = prompt('Valor da proposta aceite (€):');
            if (valor === null || valor === '') { return; }
        } else {
            if (!confirm('Marcar como RECUSADA? A lead passa para "Para outras oportunidades".')) { return; }
        }
        var d = { proposta_id: id, outcome: outcome, valor: valor };
        d[csrfName] = csrfHash;
        $.post(base + 'dps_propostas/resultado_proposta', d, function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
            if (typeof alert_float === 'function') { alert_float(r && r.success ? 'success' : 'danger', (r && r.message) || 'Erro.'); }
            if (r && r.success) { setTimeout(function () { location.reload(); }, 1000); }
        }).fail(function () { if (typeof alert_float === 'function') { alert_float('danger', 'Erro de comunicação.'); } });
    };

    var infoBtn = document.getElementById('dps_info_btn');
    infoBtn.addEventListener('click', function () {
        var emp = document.getElementById('dps_info_emp').value;
        infoBtn.disabled = true;
        infoBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A enviar...';
        var data = { lead_id: leadId, empreendimento: emp };
        data[csrfName] = csrfHash;
        $.post(base + 'dps_propostas/enviar_info', data, function (resp) {
            try { resp = (typeof resp === 'string') ? JSON.parse(resp) : resp; } catch (e) {}
            alert_float(resp && resp.success ? 'success' : 'danger', (resp && resp.message) ? resp.message : 'Erro no envio.');
        }).fail(function () {
            alert_float('danger', 'Erro de comunicação.');
        }).always(function () {
            infoBtn.disabled = false;
            infoBtn.innerHTML = '<i class="fa fa-whatsapp"></i> Enviar informação';
        });
    });

    var propBtn = document.getElementById('dps_prop_btn');
    var staffId = <?= (int) $staff_id; ?>;
    var propToken = <?= json_encode($token); ?>;
    var leadNome = <?= json_encode($lead->name); ?>;
    var leadTel = <?= json_encode($lead->phonenumber); ?>;
    function buildSimUrl(emp) {
        return 'https://dpsimobiliario.pt/simuladorportugal/'
            + '?lead_id=' + leadId
            + '&staff_id=' + staffId
            + '&token=' + encodeURIComponent(propToken)
            + '&empreendimento=' + encodeURIComponent(emp)
            + '&nome=' + encodeURIComponent(leadNome || '')
            + '&telefone=' + encodeURIComponent(leadTel || '') + '&_sv=20260709c';
    }
    var simWrap = document.getElementById('dps_sim_wrap');
    var simFrame = document.getElementById('dps_sim_iframe');
    var lastSimUrl = '';
    propBtn.addEventListener('click', function () {
        var emp = document.getElementById('dps_prop_emp').value;
        lastSimUrl = buildSimUrl(emp);
        simFrame.src = lastSimUrl;
        simWrap.style.display = 'block';
        try { simWrap.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) {}
    });
    var closeBtn = document.getElementById('dps_sim_close');
    if (closeBtn) { closeBtn.addEventListener('click', function () { simWrap.style.display = 'none'; simFrame.src = 'about:blank'; }); }
    var openTabBtn = document.getElementById('dps_sim_open_tab');
    if (openTabBtn) { openTabBtn.addEventListener('click', function () { if (lastSimUrl) { window.open(lastSimUrl, '_blank'); } }); }

    // Envia a proposta pelo WhatsApp do UTILIZADOR LOGADO (controller autenticado do CRM).
    var capturedProp = null;
    var sendBtn = document.getElementById('dps_send_prop');
    function dpsEnviarProposta(prop, srcWin) {
        var data = { lead_id: leadId, empreendimento: prop.emp, unidade: prop.unit, file_name: prop.filename, pdf_base64: prop.base64 };
        data[csrfName] = csrfHash;
        return $.post(base + 'dps_propostas/enviar_proposta_pdf', data, function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
            if (typeof alert_float === 'function') { alert_float(r && r.success ? 'success' : 'danger', (r && r.message) || 'Erro ao enviar.'); }
            if (srcWin) { try { srcWin.postMessage({ dps_send_result: true, ok: !!(r && r.success) }, '*'); } catch (e) {} }
        }).fail(function () {
            if (typeof alert_float === 'function') { alert_float('danger', 'Erro de comunicação.'); }
            if (srcWin) { try { srcWin.postMessage({ dps_send_result: true, ok: false }, '*'); } catch (e) {} }
        });
    }
    window.addEventListener('message', function (ev) {
        if (ev.origin !== 'https://dpsimobiliario.pt') { return; }
        var d = ev && ev.data;
        if (d && d.dps_proposta && sendBtn) {
            capturedProp = d.dps_proposta;
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fa fa-whatsapp"></i> Enviar proposta' + (capturedProp.unit ? (' — ' + capturedProp.unit) : '') + ' ao cliente';
        }
        if (d && d.dps_send_now) {
            dpsEnviarProposta(d.dps_send_now, ev.source);
        }
    });
    if (sendBtn) {
        sendBtn.addEventListener('click', function () {
            if (!capturedProp) { return; }
            sendBtn.disabled = true;
            var t = sendBtn.innerHTML; sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A enviar...';
            dpsEnviarProposta(capturedProp, null).always(function () { sendBtn.disabled = false; sendBtn.innerHTML = t; });
        });
    }
})();
</script>
