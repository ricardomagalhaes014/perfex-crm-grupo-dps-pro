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
                    <p class="text-muted" style="font-size:12px;">Abre o simulador já com os dados do cliente para gerares e enviares a proposta.</p>
                    <select id="dps_prop_emp" class="form-control">
                        <?php foreach ($emps as $k => $e) { ?>
                        <option value="<?= e($k); ?>"><?= e($e['nome']); ?></option>
                        <?php } ?>
                    </select>
                    <button type="button" id="dps_prop_btn" class="btn btn-danger btn-block mtop10">
                        <i class="fa fa-external-link"></i> Abrir simulador / proposta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="panel_s">
        <div class="panel-body">
            <h4 class="no-margin">Registo de envios</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Tipo</th><th>Empreendimento</th><th>Unidade</th><th>Estado da lead</th><th>Quando</th><th>WA</th></tr></thead>
                    <tbody id="dps_propostas_log">
                        <?php if (empty($rows)) { ?>
                        <tr><td colspan="6" class="text-muted text-center">Ainda sem envios.</td></tr>
                        <?php } ?>
                        <?php foreach ($rows as $r) { ?>
                        <tr>
                            <td><span class="label label-<?= $r->tipo === 'proposta' ? 'danger' : 'info'; ?>"><?= e($r->tipo); ?></span></td>
                            <td><?= e($r->empreendimento); ?></td>
                            <td><?= e($r->unidade ?: '—'); ?></td>
                            <td><?= e($r->lead_status_nome ?: '—'); ?></td>
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

<script>
(function () {
    var pane = document.getElementById('dps_propostas_tab');
    if (!pane || pane.getAttribute('data-dps-init') === '1') { return; }
    pane.setAttribute('data-dps-init', '1');

    // Injeta o separador "Propostas" na navegação de abas da ficha da lead.
    var content = pane.closest('.tab-content');
    var container = content ? content.parentNode : document;
    var nav = container ? container.querySelector('ul.nav-tabs') : null;
    if (nav && !nav.querySelector('a[href="#dps_propostas_tab"]')) {
        var li = document.createElement('li');
        li.setAttribute('role', 'presentation');
        li.innerHTML = '<a href="#dps_propostas_tab" role="tab" data-toggle="tab"><i class="fa fa-paper-plane menu-icon"></i> Propostas</a>';
        nav.appendChild(li);
    }

    var leadId = <?= (int) $lead->id; ?>;
    var csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash(); ?>';
    var base = (typeof admin_url !== 'undefined') ? admin_url : '<?= admin_url(); ?>';

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
    propBtn.addEventListener('click', function () {
        var emp = document.getElementById('dps_prop_emp').value;
        var nome = <?= json_encode($lead->name); ?>;
        var tel = <?= json_encode($lead->phonenumber); ?>;
        var url = 'https://dpsimobiliario.pt/simuladorportugal/'
            + '?lead_id=' + leadId
            + '&empreendimento=' + encodeURIComponent(emp)
            + '&nome=' + encodeURIComponent(nome || '')
            + '&telefone=' + encodeURIComponent(tel || '');
        window.open(url, '_blank');
    });
})();
</script>
