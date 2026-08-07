<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="dps_painel_root">
    <p class="text-muted" style="margin-bottom:10px;">
        <i class="fa fa-user"></i> <strong><?= e($lead->name ?: ('Lead #' . (int) $lead->id)); ?></strong>
        <?php if (! empty($lead->phonenumber)) { ?> · <i class="fa fa-phone"></i> <?= e($lead->phonenumber); ?><?php } ?>
    </p>
    <?php $dps_tel = preg_replace('/[^0-9]/', '', (string) $lead->phonenumber); ?>
    <?php if ($dps_tel !== '') { ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
        <a href="https://wa.me/<?= e($dps_tel); ?>" target="_blank" rel="noopener" class="btn btn-sm" style="background:#25D366;color:#fff;"><i class="fa fa-whatsapp"></i> WhatsApp</a>
        <a href="tel:<?= e($dps_tel); ?>" class="btn btn-sm btn-primary"><i class="fa fa-phone"></i> Ligar</a>
    </div>
    <?php } ?>
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
                    <p class="text-muted" style="font-size:12px;">Abre o simulador aqui, gera a proposta e envia por WhatsApp.</p>
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
                    <strong><i class="fa fa-calculator"></i> Simulador</strong>
                    <span class="text-muted" style="font-size:12px;margin-left:auto;">Escolhe a unidade e clica em <b>Enviar Proposta ao Cliente</b> — é gerada e enviada pelo teu WhatsApp automaticamente.</span>
                    <button type="button" id="dps_send_prop" class="btn btn-success btn-sm" style="display:none;" disabled><i class="fa fa-whatsapp"></i> Enviar proposta ao cliente</button>
                    <button type="button" id="dps_sim_open_tab" class="btn btn-default btn-xs"><i class="fa fa-external-link"></i> Nova aba</button>
                    <button type="button" id="dps_sim_close" class="btn btn-default btn-xs">Fechar</button>
                </div>
                <iframe id="dps_sim_iframe" src="about:blank" style="width:100%;height:70vh;min-height:520px;border:1px solid #e3e7ec;border-radius:6px;background:#fff;"></iframe>
            </div>
        </div>
    </div>

    <?php
    $propostas = array_values(array_filter($rows, function ($r) { return $r->tipo === 'proposta'; }));
    ?>
    <?php if (! empty($propostas)) { ?>
    <div class="panel_s">
        <div class="panel-body">
            <h5 class="bold"><i class="fa fa-file-pdf-o text-danger"></i> Propostas enviadas</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Empreendimento</th><th>Unidade</th><th>Resultado</th><th>Quando</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php foreach ($propostas as $r) { ?>
                        <tr>
                            <td><?= e($r->empreendimento); ?></td>
                            <td><strong><?= e($r->unidade ?: '—'); ?></strong></td>
                            <td>
                                <?php if (($r->outcome ?? 'pendente') === 'aceite') { ?>
                                <span class="label label-success">Aceite</span> <strong><?= number_format((float) $r->valor, 0, ',', '.'); ?> €</strong>
                                <?php } elseif (($r->outcome ?? '') === 'recusado') { ?>
                                <span class="label label-danger">Recusada</span>
                                <?php if (! empty($r->motivo_perda)) { ?>
                                <br><small class="text-muted"><?= e(dps_propostas_motivo_label($r->motivo_perda)); ?></small>
                                <?php } ?>
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
    <?php } ?>
</div>

<script>
(function () {
    var root = document.getElementById('dps_painel_root');
    if (!root || root.getAttribute('data-dps-init') === '1') { return; }
    root.setAttribute('data-dps-init', '1');

    var leadId = <?= (int) $lead->id; ?>;
    var staffId = <?= (int) $staff_id; ?>;
    var propToken = <?= json_encode($token); ?>;
    var leadNome = <?= json_encode($lead->name); ?>;
    var leadTel = <?= json_encode($lead->phonenumber); ?>;
    var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash(); ?>';
    var base = (typeof admin_url !== 'undefined') ? admin_url : '<?= admin_url(); ?>';

    /*
     * Motivos de perda. Vêm do servidor para haver UMA lista só — se um dia se
     * acrescentar um motivo, acrescenta-se no módulo e este ecrã acompanha.
     */
    var MOTIVOS = <?= json_encode(dps_propostas_motivos_perda(), JSON_UNESCAPED_UNICODE); ?>;

    function pedirMotivo(aoEscolher) {
        var ov = document.createElement('div');
        ov.style.cssText = 'position:fixed;inset:0;background:rgba(8,21,40,.65);z-index:2147483000;'
            + 'display:flex;align-items:center;justify-content:center;padding:20px;';

        var opcoes = '';
        Object.keys(MOTIVOS).forEach(function (k) {
            opcoes += '<option value="' + k + '">' + $('<span>').text(MOTIVOS[k]).html() + '</option>';
        });

        var cx = document.createElement('div');
        cx.style.cssText = 'background:#fff;border-radius:12px;padding:22px 24px;max-width:400px;width:100%;'
            + 'box-shadow:0 20px 60px rgba(0,0,0,.3);font-family:inherit;';
        cx.innerHTML =
              '<div style="font-weight:700;font-size:1.05rem;margin-bottom:4px;">Proposta recusada</div>'
            + '<div style="color:#5a6675;font-size:.86rem;margin-bottom:16px;">'
            +   'A lead passa para "Para outras oportunidades". Porque é que se perdeu?</div>'
            + '<select class="form-control" id="dps-motivo-perda" style="margin-bottom:16px;">'
            +   '<option value="">— escolha o motivo —</option>' + opcoes + '</select>'
            + '<div style="display:flex;gap:8px;">'
            +   '<button type="button" class="btn btn-danger" id="dps-motivo-ok" style="flex:1;">Marcar como recusada</button>'
            +   '<button type="button" class="btn btn-default" id="dps-motivo-no">Cancelar</button>'
            + '</div>';

        ov.appendChild(cx);
        document.body.appendChild(ov);

        function fechar() { if (ov.parentNode) { ov.parentNode.removeChild(ov); } }
        cx.querySelector('#dps-motivo-no').onclick = fechar;
        ov.addEventListener('click', function (ev) { if (ev.target === ov) { fechar(); } });
        cx.querySelector('#dps-motivo-ok').onclick = function () {
            var m = cx.querySelector('#dps-motivo-perda').value;
            if (!m) {
                if (typeof alert_float === 'function') { alert_float('warning', 'Escolha o motivo — é obrigatório.'); }
                return;
            }
            fechar();
            aoEscolher(m);
        };
    }

    window.dpsResultado = function (id, outcome) {
        if (outcome !== 'aceite') {
            pedirMotivo(function (motivo) { dpsEnviarResultado(id, outcome, '', motivo); });
            return;
        }
        var valor = prompt('Valor da proposta aceite (€):');
        if (valor === null || valor === '') { return; }
        dpsEnviarResultado(id, outcome, valor, '');
    };

    function dpsEnviarResultado(id, outcome, valor, motivo) {
        var d = { proposta_id: id, outcome: outcome, valor: valor, motivo_perda: motivo };
        d[csrfName] = csrfHash;
        $.post(base + 'dps_propostas/resultado_proposta', d, function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
            if (typeof alert_float === 'function') { alert_float(r && r.success ? 'success' : 'danger', (r && r.message) || 'Erro.'); }
            if (r && r.success) { setTimeout(function () { location.reload(); }, 1000); }
        }).fail(function () { if (typeof alert_float === 'function') { alert_float('danger', 'Erro de comunicação.'); } });
    }

    var infoBtn = root.querySelector('#dps_info_btn');
    infoBtn.addEventListener('click', function () {
        var emp = root.querySelector('#dps_info_emp').value;
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

    var propBtn = root.querySelector('#dps_prop_btn');
    function buildSimUrl(emp) {
        return 'https://dpsimobiliario.pt/simuladorportugal/'
            + '?lead_id=' + leadId + '&staff_id=' + staffId
            + '&token=' + encodeURIComponent(propToken)
            + '&empreendimento=' + encodeURIComponent(emp)
            + '&nome=' + encodeURIComponent(leadNome || '')
            + '&telefone=' + encodeURIComponent(leadTel || '') + '&_sv=20260709h&_cb=' + Date.now();
    }
    var simWrap = root.querySelector('#dps_sim_wrap');
    var simFrame = root.querySelector('#dps_sim_iframe');
    var lastSimUrl = '';
    propBtn.addEventListener('click', function () {
        lastSimUrl = buildSimUrl(root.querySelector('#dps_prop_emp').value);
        simFrame.src = lastSimUrl;
        simWrap.style.display = 'block';
    });
    var closeBtn = root.querySelector('#dps_sim_close');
    if (closeBtn) { closeBtn.addEventListener('click', function () { simWrap.style.display = 'none'; simFrame.src = 'about:blank'; }); }
    var openTabBtn = root.querySelector('#dps_sim_open_tab');
    if (openTabBtn) { openTabBtn.addEventListener('click', function () { if (lastSimUrl) { window.open(lastSimUrl, '_blank'); } }); }

    var capturedProp = null;
    var sendBtn = root.querySelector('#dps_send_prop');
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
