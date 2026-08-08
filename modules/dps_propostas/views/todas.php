<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:15px;">
                            <h4 class="no-margin"><i class="fa fa-file-pdf-o text-danger"></i> Propostas Enviadas</h4>
                            <span class="text-muted"><?= count($propostas); ?> proposta<?= count($propostas) === 1 ? '' : 's'; ?><?= $comercial > 0 ? ' · ' . e(get_staff_full_name($comercial)) : ''; ?></span>

                            <form method="get" action="<?= admin_url('dps_propostas/todas'); ?>"
                                  style="margin-left:auto;display:flex;align-items:center;gap:8px;">
                                <?php if ($comercial > 0) { ?>
                                    <input type="hidden" name="comercial" value="<?= (int) $comercial; ?>">
                                <?php } ?>
                                <input type="search" name="q" value="<?= e($procura ?? ''); ?>"
                                       class="form-control input-sm" style="width:230px;"
                                       placeholder="Número ou nome do cliente">
                                <button type="submit" class="btn btn-default btn-sm">
                                    <i class="fa fa-search"></i>
                                </button>
                                <?php if (($procura ?? '') !== '') { ?>
                                    <a href="<?= admin_url('dps_propostas/todas'
                                        . ($comercial > 0 ? '?comercial=' . (int) $comercial : '')); ?>"
                                       class="btn btn-default btn-sm">Limpar</a>
                                <?php } ?>
                            </form>

                            <?php if ($can_view_all) { ?>
                            <form method="get" action="<?= admin_url('dps_propostas/todas'); ?>" style="display:flex;align-items:center;gap:8px;">
                                <?php if (($procura ?? '') !== '') { ?>
                                    <input type="hidden" name="q" value="<?= e($procura); ?>">
                                <?php } ?>
                                <label style="margin:0;font-size:13px;color:#5a6673;"><i class="fa fa-user-o"></i> Comercial:</label>
                                <select name="comercial" class="selectpicker" data-width="260px" data-live-search="true" onchange="this.form.submit()">
                                    <option value="0"<?= $comercial == 0 ? ' selected' : ''; ?>>Todos os comerciais</option>
                                    <?php foreach ($comerciais as $c) {
                                        $cid = (int) $c['staffid'];
                                    ?>
                                    <option value="<?= $cid; ?>"<?= $comercial == $cid ? ' selected' : ''; ?>><?= e(trim($c['firstname'] . ' ' . $c['lastname'])); ?> (<?= (int) $c['c']; ?>)</option>
                                    <?php } ?>
                                </select>
                                <?php if ($comercial > 0) { ?>
                                <a href="<?= admin_url('dps_propostas/todas'); ?>" class="btn btn-default btn-sm">Limpar</a>
                                <?php } ?>
                            </form>
                            <?php } ?>
                        </div>


                        <?php if (isset($t_enviadas)) {
                            $pcAc = $t_enviadas > 0 ? round($t_aceites   / $t_enviadas * 100, 1) : 0;
                            $pcRe = $t_enviadas > 0 ? round($t_recusadas / $t_enviadas * 100, 1) : 0;
                            $kpis = [
                                [$t_enviadas,  'Enviadas',      '#2b3440', ''],
                                [$t_aceites,   'Aceites',       '#2f7d55', $pcAc . '%'],
                                [$t_recusadas, 'Recusadas',     '#c0392b', $pcRe . '%'],
                                [$t_abertas,   'Sem resultado', '#b07d19', ''],
                            ];
                        ?>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
                            <?php foreach ($kpis as $k) { ?>
                            <div style="flex:1;min-width:130px;background:#fff;border:1px solid #e6eaef;border-radius:10px;padding:13px 15px;">
                                <div style="font-size:1.7rem;font-weight:700;line-height:1.1;color:<?= $k[2]; ?>;font-variant-numeric:tabular-nums;">
                                    <?= number_format((int) $k[0], 0, ',', '.'); ?>
                                    <?php if ($k[3] !== '') { ?><span style="font-size:.85rem;font-weight:600;opacity:.75;"><?= $k[3]; ?></span><?php } ?>
                                </div>
                                <div style="font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;color:#8a97a6;margin-top:3px;"><?= $k[1]; ?></div>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>

                        <?php if (!empty($g_comerciais)) { ?>
                        <!-- Gráfico: propostas por comercial, segmentadas por empreendimento -->
                        <div style="border:1px solid #e6eaef;border-radius:10px;padding:16px;margin-bottom:18px;background:#fff;">
                            <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                                <strong>Propostas por comercial</strong>
                                <span class="text-muted" style="font-size:.82rem;">
                                    segmentadas por empreendimento · toda a base (não afectado pelo filtro acima)
                                </span>
                                <span class="text-muted" style="margin-left:auto;font-size:.82rem;">
                                    <?= count($g_comerciais); ?> comerciais · <?= count($g_emps); ?> empreendimentos
                                </span>
                            </div>
                            <div style="position:relative;height:<?= max(260, count($g_comerciais) * 38 + 90); ?>px;">
                                <canvas id="dpsGrafPropostas"></canvas>
                            </div>
                        </div>
                        <?php } ?>

                        <?php
                        /*
                         * RESULTADOS POR COMERCIAL — aceites, recusadas e pendentes.
                         *
                         * Ao contrário do gráfico de cima, este RESPEITA o filtro de
                         * comercial: um serve para comparar a equipa, este para olhar
                         * pessoa a pessoa. Um comercial sem permissão de ver tudo só se vê
                         * a si — o controlador já lhe fixou o filtro.
                         */
                        if (!empty($r_nomes)) { ?>
                        <div style="border:1px solid #e6eaef;border-radius:10px;padding:16px;margin-bottom:18px;background:#fff;">
                            <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                                <strong>Resultados por comercial</strong>
                                <span class="text-muted" style="font-size:.82rem;">
                                    aceites, recusadas e ainda sem resposta · segue o filtro acima
                                </span>
                            </div>
                            <div style="position:relative;height:<?= max(220, count($r_nomes) * 38 + 80); ?>px;">
                                <canvas id="dpsGrafResultados"></canvas>
                            </div>
                        </div>
                        <?php } ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Lead</th>
                                        <th>Telefone</th>
                                        <th>Comercial</th>
                                        <th>Empreendimento</th>
                                        <th>Unidade</th>
                                        <th>Canal</th>
                                        <th>Estado da lead</th>
                                        <th>Resultado</th>
                                        <th>Enviada em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($propostas)) { ?>
                                    <tr><td colspan="10" class="text-muted text-center">
                                        <?= ($procura ?? '') !== ''
                                            ? 'Nenhuma proposta para «' . e($procura) . '».'
                                            : 'Sem propostas enviadas.'; ?>
                                    </td></tr>
                                    <?php } ?>
                                    <?php foreach ($propostas as $p) { ?>
                                    <tr>
                                        <td><a href="<?= admin_url('leads/index/' . (int) $p->lead_id); ?>"><?= e($p->lead_nome ?: ('#' . (int) $p->lead_id)); ?></a></td>
                                        <td style="white-space:nowrap;">
                                            <?php
                                            $tel = trim((string) ($p->lead_telefone ?? ''));
                                            if ($tel === '') {
                                                echo '<span class="text-muted">—</span>';
                                            } else {
                                                // Clicável nas duas vias: no computador liga pelo
                                                // softphone, no telemóvel liga mesmo.
                                                $wa = preg_replace('/\D+/', '', $tel);
                                                if (strlen($wa) === 9 && $wa[0] === '9') { $wa = '351' . $wa; }
                                                echo '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $tel)) . '">' . e($tel) . '</a>';
                                                echo ' <a href="https://wa.me/' . e($wa) . '" target="_blank" rel="noopener"'
                                                   . ' title="Abrir no WhatsApp" style="color:#25d366;margin-left:4px;">'
                                                   . '<i class="fa fa-whatsapp"></i></a>';
                                            }
                                            ?>
                                        </td>
                                        <td><?= $p->staff_id ? e(get_staff_full_name($p->staff_id)) : '—'; ?></td>
                                        <td><?= e($p->empreendimento ?: '—'); ?></td>
                                        <td><strong><?= e($p->unidade ?: '—'); ?></strong></td>
                                        <td>
                                            <?php
                                            // As propostas antigas não têm coluna 'canal' preenchida:
                                            // deduz-se pelo detalhe (o envio por email guarda lá o
                                            // endereço; o do WhatsApp guarda a resposta da Evolution).
                                            $canal = isset($p->canal) && $p->canal ? $p->canal : '';
                                            if ($canal === '' && stripos((string) $p->detalhe, 'email') !== false) {
                                                $canal = 'email';
                                            } elseif ($canal === '') {
                                                $canal = 'whatsapp';
                                            }
                                            ?>
                                            <?php if ($canal === 'email') { ?>
                                                <span class="label" style="background:#C5A55A;">✉️ Email</span>
                                            <?php } else { ?>
                                                <span class="label" style="background:#25D366;">WhatsApp</span>
                                            <?php } ?>
                                        </td>
                                        <td><?= e($p->estado_atual ?: ($p->lead_status_nome ?: '—')); ?></td>
                                        <td>
                                            <?php if ($p->outcome === 'aceite') { ?>
                                            <span class="label label-success">Aceite</span>
                                            <?php if ((float) $p->valor > 0) { ?>
                                                <strong><?= number_format((float) $p->valor, 0, ',', '.'); ?> €</strong>
                                            <?php } elseif (!empty($p->venda_id)) { ?>
                                                <a href="<?= admin_url('dps_vendas/form/' . (int) $p->venda_id); ?>"
                                                   class="text-muted" style="font-size:12px;">valor por definir</a>
                                            <?php } ?>
                                            <?php } elseif ($p->outcome === 'recusado') { ?>
                                            <span class="label label-danger">Recusada</span>
                                            <?php } else { ?>
                                            <span class="label label-default">Pendente</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-muted" style="font-size:12px;"><?= e($p->created_at); ?></td>
                                        <td>
                                            <?php if ($p->outcome === 'pendente') { ?>
                                            <button class="btn btn-success btn-xs" onclick="dpsResultado(<?= (int) $p->id; ?>,'aceite')"><i class="fa fa-check"></i> Aceite</button>
                                            <button class="btn btn-danger btn-xs" onclick="dpsResultado(<?= (int) $p->id; ?>,'recusado')"><i class="fa fa-times"></i> Recusada</button>
                                            <?php } else { ?>
                                            <span class="text-muted" style="font-size:11px;"><?= e($p->outcome_at); ?></span>
                                            <?php } ?>
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
<script>
var DPS_CSRF = { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' };
function dpsResultado(id, outcome) {
    var valor = '';
    if (outcome === 'aceite') {
        /*
         * Já não se pergunta o valor aqui.
         *
         * Perguntava-se, e a seguir abria-se a ficha da venda onde o valor é
         * escolhido outra vez ao escolher a unidade — que é onde ele existe
         * a sério, com o preço da fração. Escrevê-lo de cor num prompt era
         * pedir um número que ninguém tem à mão e que ficava a divergir do
         * preço real da unidade.
         */
        if (!confirm('Marcar como ACEITE?\n\nA seguir abre-se a ficha da venda para escolher a unidade e o valor.')) { return; }
    } else {
        // O motivo é obrigatório — a caixa vem do rodapé do módulo
        // (dpsPedirMotivoPerda), a mesma que a ficha da lead usa.
        window.dpsPedirMotivoPerda(function (motivo) { dpsEnviarResultado(id, outcome, '', motivo); });
        return;
    }
    dpsEnviarResultado(id, outcome, valor, '');
}

function dpsEnviarResultado(id, outcome, valor, motivo) {
    var data = { proposta_id: id, outcome: outcome, valor: valor, motivo_perda: motivo };
    data[DPS_CSRF.name] = DPS_CSRF.hash;
    $.post(admin_url + 'dps_propostas/resultado_proposta', data, function (r) {
        try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
        alert_float(r && r.success ? 'success' : 'danger', (r && r.message) || 'Erro.');
        if (r && r.success) {
            /*
             * Aceite, vai-se completar a reserva. A venda já foi criada pelo
             * servidor com o valor e a taxa; o que falta é o cliente, a unidade
             * e os documentos. Sem este salto, a venda ficava a metade até
             * alguém se lembrar dela.
             */
            setTimeout(function () {
                window.location = (r.redirect) ? r.redirect : window.location.href;
            }, 1200);
        }
    }).fail(function () { alert_float('danger', 'Erro de comunicação.'); });
}
</script>

<script src="<?= base_url('assets/plugins/Chart.js/Chart.min.js'); ?>"></script>
<script>
(function () {
    var el = document.getElementById('dpsGrafPropostas');
    if (!el || typeof Chart === 'undefined') { return; }

    var comerciais = <?= json_encode(array_values($g_comerciais ?? []), JSON_UNESCAPED_UNICODE); ?>;
    var ids        = <?= json_encode(array_keys($g_comerciais ?? [])); ?>;
    var emps       = <?= json_encode(array_values($g_emps ?? []), JSON_UNESCAPED_UNICODE); ?>;
    var valores    = <?= json_encode($g_valores ?? [], JSON_UNESCAPED_UNICODE); ?>;

    // Paleta estável: a mesma cor para o mesmo empreendimento em toda a página.
    var cores = ['#1d6fb8','#a8873c','#2f9e44','#c0392b','#7c5cbf','#0f8b8d','#e08a1e','#5a6673','#b0407a','#3aa0d1'];

    var datasets = emps.map(function (emp, i) {
        return {
            label: emp,
            backgroundColor: cores[i % cores.length],
            data: ids.map(function (id) {
                var v = valores[id] || {};
                return v[emp] || 0;
            })
        };
    });

    new Chart(el.getContext('2d'), {
        type: 'horizontalBar',
        data: { labels: comerciais, datasets: datasets },
        options: {
            maintainAspectRatio: false,
            tooltips: { mode: 'index', intersect: false },
            scales: {
                xAxes: [{ stacked: true, ticks: { beginAtZero: true, precision: 0 } }],
                yAxes: [{ stacked: true }]
            },
            legend: { position: 'bottom' }
        }
    });
})();

(function () {
    var el = document.getElementById('dpsGrafResultados');
    if (!el || typeof Chart === 'undefined') { return; }

    var nomes = <?= json_encode(array_values($r_nomes ?? []), JSON_UNESCAPED_UNICODE); ?>;
    var dados = <?= json_encode(array_values($r_dados ?? [])); ?>;

    function coluna(chave) { return dados.map(function (d) { return d[chave] || 0; }); }

    new Chart(el, {
        type: 'horizontalBar',
        data: {
            labels: nomes,
            datasets: [
                { label: 'Aceites',   data: coluna('aceite'),   backgroundColor: '#2f9e44' },
                { label: 'Recusadas', data: coluna('recusado'), backgroundColor: '#c0392b' },
                { label: 'Pendentes', data: coluna('pendente'), backgroundColor: '#9aa3ab' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' },
            // Empilhado: a barra inteira é o total de propostas do comercial,
            // e vê-se de relance a fatia que fechou.
            scales: {
                xAxes: [{ stacked: true, ticks: { beginAtZero: true,
                    callback: function (v) { return Math.floor(v) === v ? v : undefined; } } }],
                yAxes: [{ stacked: true, gridLines: { display: false } }]
            }
        }
    });
})();
</script>

<?php init_tail(); ?>
