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
                            <span class="text-muted"><?= count($propostas); ?> proposta<?= count($propostas) === 1 ? '' : 's'; ?><?= $comercial > 0 ? ' · ' . e(get_staff_full_name($comercial)) : ''; ?><?= ($empreendimento ?? '') !== '' ? ' · ' . e($empreendimento) : ''; ?><?php $dps_rot = ['aceite'=>'aceites','recusado'=>'recusadas','cancelado'=>'canceladas','pendente'=>'sem resposta']; ?><?= ($resultado ?? '') !== '' ? ' · ' . e($dps_rot[$resultado]) : ''; ?></span>

                            <form method="get" action="<?= admin_url('dps_propostas/todas'); ?>"
                                  style="margin-left:auto;display:flex;align-items:center;gap:8px;">
                                <?php if ($comercial > 0) { ?>
                                    <input type="hidden" name="comercial" value="<?= (int) $comercial; ?>">
                                <?php } ?>
                                <?php if (($empreendimento ?? '') !== '') { ?>
                                    <input type="hidden" name="empreendimento" value="<?= e($empreendimento); ?>">
                                <?php } ?>
                                <?php if (($resultado ?? '') !== '') { ?>
                                    <input type="hidden" name="resultado" value="<?= e($resultado); ?>">
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
                                <?php if (($empreendimento ?? '') !== '') { ?>
                                    <input type="hidden" name="empreendimento" value="<?= e($empreendimento); ?>">
                                <?php } ?>
                            </form>
                            <?php } ?>

                            <?php
                            /*
                             * Empreendimento. A lista sai das propostas que existem
                             * mesmo — e acompanha o comercial escolhido, para não
                             * oferecer empreendimentos onde ele nunca enviou nada.
                             */
                            ?>
                            <form method="get" action="<?= admin_url('dps_propostas/todas'); ?>" style="display:flex;align-items:center;gap:8px;">
                                <?php if (($procura ?? '') !== '') { ?>
                                    <input type="hidden" name="q" value="<?= e($procura); ?>">
                                <?php } ?>
                                <?php if ($comercial > 0) { ?>
                                    <input type="hidden" name="comercial" value="<?= (int) $comercial; ?>">
                                <?php } ?>
                                <label style="margin:0;font-size:13px;color:#5a6673;"><i class="fa fa-building-o"></i> Empreendimento:</label>
                                <select name="empreendimento" class="selectpicker" data-width="240px" onchange="this.form.submit()">
                                    <option value=""<?= ($empreendimento ?? '') === '' ? ' selected' : ''; ?>>Todos</option>
                                    <?php foreach (($emps_filtro ?? []) as $ef) { ?>
                                    <option value="<?= e($ef['empreendimento']); ?>"<?= ($empreendimento ?? '') === $ef['empreendimento'] ? ' selected' : ''; ?>>
                                        <?= e($ef['empreendimento']); ?> (<?= (int) $ef['c']; ?>)
                                    </option>
                                    <?php } ?>
                                </select>
                                <?php if (($resultado ?? '') !== '') { ?>
                                    <input type="hidden" name="resultado" value="<?= e($resultado); ?>">
                                <?php } ?>
                                <?php if (($empreendimento ?? '') !== '') { ?>
                                <a href="<?= admin_url('dps_propostas/todas'
                                    . ($comercial > 0 ? '?comercial=' . (int) $comercial : '')); ?>"
                                   class="btn btn-default btn-sm">Limpar</a>
                                <?php } ?>
                            </form>

                            <form method="get" action="<?= admin_url('dps_propostas/todas'); ?>" style="display:flex;align-items:center;gap:8px;">
                                <?php if (($procura ?? '') !== '') { ?>
                                    <input type="hidden" name="q" value="<?= e($procura); ?>">
                                <?php } ?>
                                <?php if ($comercial > 0) { ?>
                                    <input type="hidden" name="comercial" value="<?= (int) $comercial; ?>">
                                <?php } ?>
                                <?php if (($empreendimento ?? '') !== '') { ?>
                                    <input type="hidden" name="empreendimento" value="<?= e($empreendimento); ?>">
                                <?php } ?>
                                <label style="margin:0;font-size:13px;color:#5a6673;"><i class="fa fa-check-square-o"></i> Resultado:</label>
                                <select name="resultado" class="selectpicker" data-width="190px" onchange="this.form.submit()">
                                    <option value=""<?= ($resultado ?? '') === '' ? ' selected' : ''; ?>>Todos</option>
                                    <option value="aceite"<?= ($resultado ?? '') === 'aceite' ? ' selected' : ''; ?>>Aceites</option>
                                    <option value="recusado"<?= ($resultado ?? '') === 'recusado' ? ' selected' : ''; ?>>Recusadas</option>
                                    <option value="cancelado"<?= ($resultado ?? '') === 'cancelado' ? ' selected' : ''; ?>>Canceladas</option>
                                    <option value="pendente"<?= ($resultado ?? '') === 'pendente' ? ' selected' : ''; ?>>Sem resposta</option>
                                </select>
                            </form>
                        </div>


                        <?php if (isset($t_enviadas)) {
                            $pcAc = $t_enviadas > 0 ? round($t_aceites   / $t_enviadas * 100, 1) : 0;
                            $pcRe = $t_enviadas > 0 ? round($t_recusadas / $t_enviadas * 100, 1) : 0;
                            $t_can = (int) ($t_canceladas ?? 0);
                            $pcCa = $t_enviadas > 0 ? round($t_can / $t_enviadas * 100, 1) : 0;
                            /*
                             * Canceladas ao lado das recusadas, e não somadas
                             * com elas: uma é o cliente a dizer que não, a
                             * outra é a fracção a sair do mercado antes de ele
                             * responder. Juntá-las inflaciona a taxa de recusa
                             * com negócios que ninguém chegou a perder.
                             */
                            $kpis = [
                                [$t_enviadas,  'Enviadas',      '#2b3440', ''],
                                [$t_aceites,   'Aceites',       '#2f7d55', $pcAc . '%'],
                                [$t_recusadas, 'Recusadas',     '#c0392b', $pcRe . '%'],
                                [$t_can,       'Canceladas',    '#a06a1b', $pcCa . '%'],
                                [$t_abertas,   'Sem resultado', '#b07d19', ''],
                            ];
                        ?>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
                            <?php foreach ($kpis as $k) { ?>
                            <div style="flex:1;min-width:130px;background:#fff;border:1px solid #e6eaef;border-radius:10px;padding:13px 15px;">
                                <div style="font-size:1.7rem;font-weight:700;line-height:1.1;color:<?= $k[2]; ?>;font-variant-numeric:tabular-nums;"
                                     data-kpi="<?= e($k[1]); ?>">
                                    <span class="dps-kpi-n"><?= number_format((int) $k[0], 0, ',', '.'); ?></span>
                                    <?php if ($k[3] !== '') { ?><span class="dps-kpi-pc" style="font-size:.85rem;font-weight:600;opacity:.75;"><?= $k[3]; ?></span><?php } ?>
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
                                    <tr data-proposta="<?= (int) $p->id; ?>" data-lead="<?= (int) $p->lead_id; ?>">
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
                                                <?php
                                                /*
                                                 * O recibo do próprio WhatsApp, ao lado do canal.
                                                 *
                                                 * O comercial não vê no telemóvel dele as mensagens que o
                                                 * CRM envia — quem as escreve é o dispositivo associado, e
                                                 * o telemóvel nem sempre as mostra. Sem isto ficava sem
                                                 * maneira de saber se o cliente recebeu, e a única saída
                                                 * era ligar a perguntar. Aqui está a prova.
                                                 */
                                                $recibos = [
                                                    'READ'         => ['Lido pelo cliente', '#128, 90, 213'],
                                                    'DELIVERY_ACK' => ['Entregue',          '#25D366'],
                                                    'SERVER_ACK'   => ['Enviada',           '#1d6fb8'],
                                                    'ERROR'        => ['NÃO SAIU',          '#c0392b'],
                                                    'SEM_RECIBO'   => ['Sem confirmação',  '#c0392b'],
                                                    'PENDING'      => ['Por confirmar',     '#95a5a6'],
                                                ];
                                                /*
                                                 * Mostra-se SEMPRE alguma coisa. Quando não havia estado
                                                 * nenhum não aparecia etiqueta e a coluna ficava igual à
                                                 * de antes dos recibos existirem — quem olhasse não sabia
                                                 * se a proposta estava por confirmar ou se a coluna não
                                                 * estava a funcionar. "Sem registo" é uma resposta; um
                                                 * espaço em branco não é.
                                                 */
                                                $wa = strtoupper((string) ($p->wa_status ?? ''));
                                                if (! isset($recibos[$wa])) {
                                                    $wa = 'SEM_REGISTO';
                                                    $recibos['SEM_REGISTO'] = ['Sem registo', '#7f8c8d'];
                                                }
                                                $cor   = $wa === 'READ' ? '#8a5ad5' : $recibos[$wa][1];
                                                $ajuda = $p->wa_status_at
                                                    ? 'Confirmado em ' . $p->wa_status_at
                                                    : ($wa === 'SEM_REGISTO'
                                                        ? 'Enviada antes de existirem recibos de entrega — não há como confirmar.'
                                                        : 'Ainda sem confirmação do WhatsApp.');
                                                echo '<br><span class="label" style="background:' . $cor
                                                   . ';margin-top:3px;display:inline-block;" title="' . e($ajuda) . '">'
                                                   . $recibos[$wa][0] . '</span>';
                                                ?>
                                            <?php } ?>
                                        </td>
                                        <td class="dps-estado-lead"><?= e($p->estado_atual ?: ($p->lead_status_nome ?: '—')); ?></td>
                                        <td class="dps-resultado">
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
                                            <?php } elseif ($p->outcome === 'cancelado') { ?>
                                            <span class="label label-warning">Cancelada</span>
                                            <br><small class="text-muted">unidade já não disponível</small>
                                            <?php } else { ?>
                                            <span class="label label-default">Pendente</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-muted" style="font-size:12px;"><?= e($p->created_at); ?></td>
                                        <td class="dps-accoes" style="white-space:nowrap;">
                                            <?php if ($p->outcome === 'pendente') { ?>
                                            <button class="btn btn-success btn-xs dps-btn-desfecho" onclick="dpsResultado(<?= (int) $p->id; ?>,'aceite')"><i class="fa fa-check"></i> Aceite</button>
                                            <button class="btn btn-danger btn-xs dps-btn-desfecho" onclick="dpsResultado(<?= (int) $p->id; ?>,'recusado')"><i class="fa fa-times"></i> Recusada</button>
                                            <button class="btn btn-warning btn-xs dps-btn-desfecho" onclick="dpsResultado(<?= (int) $p->id; ?>,'cancelado')"><i class="fa fa-ban"></i> Cancelada</button>
                                            <?php } else { ?>
                                            <span class="text-muted dps-quando" style="font-size:11px;"><?= e($p->outcome_at); ?></span>
                                            <?php } ?>
                                            <?php
                                            /*
                                             * Marcar lembrete sem sair daqui.
                                             *
                                             * Uma proposta sem resposta morre de silêncio: fica na lista
                                             * semanas e ninguém sabe quando lhe voltar. O lembrete fica
                                             * ligado à LEAD, e não à proposta — assim aparece na agenda,
                                             * na lista de lembretes e no aviso de WhatsApp dos 30 minutos,
                                             * que é onde o comercial já olha. Pedido do dono (18/08/2026).
                                             */
                                            ?>
                                            <button class="btn btn-default btn-xs" title="Marcar lembrete para voltar a esta proposta"
                                                    onclick="dpsLembreteProposta(this)"
                                                    data-lead="<?= (int) $p->lead_id; ?>"
                                                    data-nome="<?= e($p->lead_nome ?: ('#' . (int) $p->lead_id)); ?>"
                                                    data-tel="<?= e($p->lead_telefone ?? ''); ?>"
                                                    data-emp="<?= e(trim(($p->empreendimento ?: '') . ' ' . ($p->unidade ?: ''))); ?>">
                                                <i class="fa fa-bell-o"></i> Lembrete
                                            </button>
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
    } else if (outcome === 'cancelado') {
        /*
         * Cancelar envia um email ao cliente a dizer que a fracção saiu do
         * mercado. É uma mensagem que sai para fora — pergunta-se antes.
         */
        if (!confirm('Marcar como CANCELADA?\n\nA fracção deixou de estar disponível. O cliente recebe um email a dizê-lo e a lead passa a "Para outras oportunidades".')) { return; }
        dpsEnviarResultado(id, outcome, '', '');
        return;
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
            if (r.redirect) {
                setTimeout(function () { window.location = r.redirect; }, 1200);
                return;
            }
            /*
             * Recusar não leva a lado nenhum — por isso não se recarrega nada.
             *
             * Recarregava, e a página voltava ao topo: quem estava a despachar
             * as propostas uma a uma tinha de fazer scroll até onde ia, de cada
             * vez. A linha passa a ser acertada onde está.
             */
            dpsMarcarFechada(id, r.outcome_at, r.lead_id, r.lead_estado, r.rotulo, r.cor);
        }
    }).fail(function () { alert_float('danger', 'Erro de comunicação.'); });
}

/* Acerta a linha recusada — e os contadores do topo com ela, senão ficavam a
 * dizer o que era verdade antes de se carregar no botão. */
function dpsMarcarFechada(id, quando, leadId, estadoNovo, rotulo, cor) {
    var tr = document.querySelector('tr[data-proposta="' + id + '"]');
    if (!tr) { return; }

    /*
     * A lead muda de estado do lado do servidor — e a coluna "Estado da lead"
     * mostra o estado ACTUAL, não o que a lead tinha quando a proposta saiu.
     * Tem de mudar em TODAS as linhas dessa lead, não só nesta: o mesmo
     * cliente aparece na lista uma vez por cada proposta que recebeu, e ficaria
     * a dizer coisas diferentes de si próprio na mesma página.
     */
    if (leadId && estadoNovo) {
        var linhas = document.querySelectorAll('tr[data-lead="' + leadId + '"]');
        Array.prototype.forEach.call(linhas, function (linha) {
            var cel = linha.querySelector('.dps-estado-lead');
            if (cel) { cel.textContent = estadoNovo; }
        });
    }

    var res = tr.querySelector('.dps-resultado');
    if (res) {
        res.innerHTML = '<span class="label label-' + (cor || 'danger') + '"></span>';
        res.firstChild.textContent = rotulo || 'Recusada';
    }

    var acc = tr.querySelector('.dps-accoes');
    if (acc) {
        // Saem só os botões de desfecho; o do lembrete fica — pode ser
        // precisamente agora que faz sentido marcar o próximo contacto.
        acc.querySelectorAll('.dps-btn-desfecho').forEach(function (b) { b.remove(); });

        if (!acc.querySelector('.dps-quando')) {
            var q = document.createElement('span');
            q.className = 'text-muted dps-quando';
            q.style.fontSize = '11px';
            q.style.marginRight = '6px';
            acc.insertBefore(q, acc.firstChild);
        }
        acc.querySelector('.dps-quando').textContent = quando || '';
    }

    // Um sinal curto de que foi aquela linha que mudou, para não se perder de
    // vista numa tabela com dezenas.
    tr.style.transition = 'background-color 1.6s';
    tr.style.backgroundColor = (cor === 'warning') ? '#fdf3e2' : '#fdecea';
    setTimeout(function () { tr.style.backgroundColor = ''; }, 1600);

    dpsAcertarKpi((cor === 'warning') ? 'Canceladas' : 'Recusadas', 1);
    dpsAcertarKpi('Sem resultado', -1);
}

/* ---------------------------------------------------------------
 * LEMBRETE A PARTIR DA LISTA DE PROPOSTAS
 *
 * Reaproveita o endereço que o botão Agenda da ficha da lead já usa
 * (dps_automacao/agendar_lembrete): o lembrete nasce igual aos outros — de
 * quem o marca, ligado à lead, com o aviso dos 30 minutos e a linha no
 * histórico da lead. Escrever aqui uma segunda forma de gravar lembretes era
 * garantir que as duas divergiam.
 * ------------------------------------------------------------ */

function dpsLembreteProposta(btn) {
    var lead = btn.getAttribute('data-lead');
    var nome = btn.getAttribute('data-nome');
    var tel  = btn.getAttribute('data-tel') || '';
    var emp  = btn.getAttribute('data-emp') || '';

    if (!lead || lead === '0') { alert_float('warning', 'Esta proposta não tem lead associada.'); return; }

    var cx = document.getElementById('dps-lembrete-cx');
    if (!cx) { cx = dpsCriarCaixaLembrete(); }

    // Por omissão, amanhã à mesma hora — arredondado ao quarto de hora.
    var d = new Date(Date.now() + 24 * 3600 * 1000);
    d.setMinutes(Math.ceil(d.getMinutes() / 15) * 15, 0, 0);
    var iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-'
            + String(d.getDate()).padStart(2, '0') + 'T'
            + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');

    document.getElementById('dps-lembrete-lead').value  = lead;
    document.getElementById('dps-lembrete-quando').value = iso;
    document.getElementById('dps-lembrete-nota').value =
        'Voltar à proposta' + (emp ? ' — ' + emp : '') + ' — ' + nome + (tel ? ' — ' + tel : '');
    document.getElementById('dps-lembrete-quem').textContent = nome + (emp ? '  ·  ' + emp : '');
    document.getElementById('dps-lembrete-erro').style.display = 'none';

    cx.style.display = 'flex';
    document.getElementById('dps-lembrete-quando').focus();
}

function dpsCriarCaixaLembrete() {
    var html =
      '<div id="dps-lembrete-cx" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);align-items:flex-start;justify-content:center;padding:60px 16px;">' +
        '<div style="background:#fff;border-radius:8px;max-width:440px;width:100%;padding:20px;">' +
          '<h4 style="margin:0 0 2px;">Marcar lembrete</h4>' +
          '<p class="text-muted" id="dps-lembrete-quem" style="font-size:12px;margin:0 0 14px;"></p>' +
          '<input type="hidden" id="dps-lembrete-lead">' +
          '<label class="control-label" style="font-size:13px;">Quando</label>' +
          '<input type="datetime-local" id="dps-lembrete-quando" class="form-control">' +
          '<label class="control-label" style="font-size:13px;margin-top:10px;">Lembrar de</label>' +
          '<textarea id="dps-lembrete-nota" class="form-control" rows="2"></textarea>' +
          '<p class="text-muted" style="font-size:11px;margin:8px 0 0;">' +
            'Fica na sua agenda e na lista de lembretes. Recebe aviso 30 minutos antes, ' +
            'no sino e por WhatsApp.' +
          '</p>' +
          '<div id="dps-lembrete-erro" class="text-danger" style="display:none;font-size:12px;margin-top:8px;"></div>' +
          '<div style="margin-top:14px;text-align:right;">' +
            '<button class="btn btn-default btn-sm" onclick="document.getElementById(\'dps-lembrete-cx\').style.display=\'none\';">Cancelar</button> ' +
            '<button class="btn btn-info btn-sm" id="dps-lembrete-gravar">Marcar lembrete</button>' +
          '</div>' +
        '</div>' +
      '</div>';

    document.body.insertAdjacentHTML('beforeend', html);
    document.getElementById('dps-lembrete-gravar').addEventListener('click', dpsGravarLembrete);

    return document.getElementById('dps-lembrete-cx');
}

function dpsGravarLembrete() {
    var botao = document.getElementById('dps-lembrete-gravar');
    var erro  = document.getElementById('dps-lembrete-erro');
    var quando = document.getElementById('dps-lembrete-quando').value;

    if (!quando) { erro.textContent = 'Escolha a data e a hora.'; erro.style.display = 'block'; return; }

    var dados = {
        lead_id: document.getElementById('dps-lembrete-lead').value,
        quando:  quando,
        nota:    document.getElementById('dps-lembrete-nota').value.trim()
    };
    dados[DPS_CSRF.name] = DPS_CSRF.hash;

    botao.disabled = true;

    $.post(admin_url + 'dps_automacao/agendar_lembrete', dados, function (r) {
        botao.disabled = false;
        try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) { r = null; }

        if (!r || !r.sucesso) {
            erro.textContent = (r && r.mensagem) ? r.mensagem : 'Não foi possível gravar o lembrete.';
            erro.style.display = 'block';
            return;
        }

        document.getElementById('dps-lembrete-cx').style.display = 'none';
        alert_float('success', r.mensagem);
    }).fail(function () {
        botao.disabled = false;
        erro.textContent = 'Erro de comunicação com o servidor.';
        erro.style.display = 'block';
    });
}

function dpsAcertarKpi(rotulo, delta) {
    var cx = document.querySelector('[data-kpi="' + rotulo + '"]');
    if (!cx) { return; }
    var alvo = cx.querySelector('.dps-kpi-n');
    if (!alvo) { return; }

    var n = parseInt(String(alvo.textContent).replace(/\D+/g, ''), 10);
    if (isNaN(n)) { return; }
    n = Math.max(0, n + delta);
    alvo.textContent = n.toLocaleString('pt-PT');

    // A percentagem é sobre o total enviado, que não muda ao recusar.
    var pc = cx.querySelector('.dps-kpi-pc');
    var envCx = document.querySelector('[data-kpi="Enviadas"] .dps-kpi-n');
    if (pc && envCx) {
        var env = parseInt(String(envCx.textContent).replace(/\D+/g, ''), 10);
        if (env > 0) { pc.textContent = (Math.round(n / env * 1000) / 10).toString().replace('.', ',') + '%'; }
    }
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
                { label: 'Aceites',    data: coluna('aceite'),    backgroundColor: '#2f9e44' },
                { label: 'Recusadas',  data: coluna('recusado'),  backgroundColor: '#c0392b' },
                { label: 'Canceladas', data: coluna('cancelado'), backgroundColor: '#a06a1b' },
                { label: 'Pendentes',  data: coluna('pendente'),  backgroundColor: '#9aa3ab' }
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
