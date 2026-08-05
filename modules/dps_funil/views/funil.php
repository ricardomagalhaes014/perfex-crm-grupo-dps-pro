<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dps-funnel{max-width:860px;margin:10px auto 30px;}
.dps-funnel-head{display:flex;align-items:baseline;gap:10px;margin-bottom:18px;}
.dps-funnel-head h4{margin:0;font-weight:600;}
.dps-funnel-head .muted{color:#888;font-size:13px;}
.dps-filters{display:flex;flex-wrap:wrap;align-items:center;gap:14px;margin-bottom:16px;}
.dps-filters .fgrp{display:flex;align-items:center;gap:8px;}
.dps-filters label{margin:0;font-size:13px;color:#5a6673;}
.dps-phase{margin:0 auto 8px;border-radius:12px;padding:12px 16px;background:#f6f8fa;border:1px solid #eaecef;}
.dps-phase.vip{border:2px solid #2f80c9;background:#eef5fc;}
.dps-phase-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#3a4757;margin-bottom:10px;text-transform:uppercase;letter-spacing:.03em;}
.dps-phase-title .ptot{margin-left:auto;font-size:12px;font-weight:400;color:#7a8798;}
.dps-chips{display:flex;flex-wrap:wrap;gap:8px;}
.dps-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #e3e7ec;border-radius:24px;padding:6px 13px;font-size:13px;color:#2b3440;cursor:pointer;transition:box-shadow .12s,transform .12s;}
.dps-chip:hover{box-shadow:0 3px 10px rgba(0,0,0,.10);transform:translateY(-1px);}
.dps-chip.active{box-shadow:0 3px 10px rgba(0,0,0,.12);border-color:#c7d2dd;}
.dps-chip .dot{width:10px;height:10px;border-radius:50%;flex:none;box-shadow:0 0 0 1px rgba(0,0,0,.06);}
.dps-chip .cname{font-weight:500;}
.dps-chip .cnum{color:#8a97a6;font-variant-numeric:tabular-nums;}
.dps-chip .caret{color:#b6c1cd;font-size:11px;transition:transform .15s;}
.dps-chip.active .caret{transform:rotate(180deg);}
.dps-brk-area{margin-top:2px;}
.dps-brk{background:#fff;border:1px solid #e6eaef;border-radius:10px;padding:13px 15px;margin-top:9px;}
.dps-brk-head{font-size:12px;font-weight:600;color:#5a6673;margin-bottom:10px;letter-spacing:.02em;}
.dps-brk-head .n{color:#8a97a6;font-weight:400;}
.dps-brk-row{display:flex;align-items:center;gap:10px;margin:6px 0;font-size:13px;}
.dps-brk-name{flex:0 0 170px;color:#2b3440;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.dps-brk-bar{flex:1;height:16px;background:#eef1f5;border-radius:8px;overflow:hidden;}
.dps-brk-fill{display:block;height:100%;border-radius:8px;min-width:2px;}
.dps-brk-num{flex:0 0 46px;text-align:right;font-variant-numeric:tabular-nums;font-weight:600;color:#2b3440;}
.dps-brk-empty{color:#9aa6b2;font-size:13px;}
.dps-brk-link{display:inline-block;margin-top:10px;font-size:12px;}
.dps-flowarrow{text-align:center;color:#c2cad4;font-size:14px;line-height:1;margin:2px 0;}
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="dps-funnel">
                    <div class="dps-funnel-head">
                        <h4><i class="fa fa-filter"></i> Funil de Leads</h4>
                        <span class="muted"><?= number_format((int) $total_leads, 0, ',', '.'); ?> leads<?= $comercial > 0 ? ' · ' . e(get_staff_full_name($comercial)) : ''; ?></span>
                        <span class="muted" style="margin-left:auto;"><a href="<?= admin_url('leads'); ?>"><i class="fa fa-th-large"></i> Ver Kanban / lista completa</a></span>
                    </div>

                    <form method="get" action="<?= admin_url('dps_funil'); ?>" class="dps-filters">
                        <div class="fgrp">
                            <label><i class="fa fa-tag"></i> Fonte:</label>
                            <select name="fonte" class="selectpicker" data-width="240px" data-live-search="true" onchange="this.form.submit()">
                                <option value="0"<?= $fonte == 0 ? ' selected' : ''; ?>>Todas as fontes</option>
                                <?php foreach ($fontes as $f) {
                                    $fid = (int) $f['id'];
                                ?>
                                <option value="<?= $fid; ?>"<?= $fonte == $fid ? ' selected' : ''; ?>><?= e($f['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php if ($can_view_all) { ?>
                        <div class="fgrp">
                            <label><i class="fa fa-user-o"></i> Comercial:</label>
                            <select name="comercial" class="selectpicker" data-width="240px" data-live-search="true" onchange="this.form.submit()">
                                <option value="0"<?= $comercial == 0 ? ' selected' : ''; ?>>Todos os comerciais</option>
                                <?php foreach ($comerciais as $c) {
                                    $cid = (int) $c['staffid'];
                                    $cnm = trim($c['firstname'] . ' ' . $c['lastname']);
                                ?>
                                <option value="<?= $cid; ?>"<?= $comercial == $cid ? ' selected' : ''; ?>><?= e($cnm); ?> (<?= (int) $c['c']; ?>)</option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php } ?>
                        <?php if ($comercial > 0 || $fonte > 0) { ?>
                        <a href="<?= admin_url('dps_funil'); ?>" class="btn btn-default btn-sm">Limpar</a>
                        <?php } ?>
                    </form>

                    <?php
                    $qsparts = [];
                    if ($comercial > 0) { $qsparts[] = 'comercial=' . (int) $comercial; }
                    if ($fonte > 0)     { $qsparts[] = 'fonte=' . (int) $fonte; }
                    $qs = $qsparts ? ('?' . implode('&', $qsparts)) : '';
                    ?>
                    <?php
                    /* ---------------------------------------------------- *
                     * O FUNIL, DESENHADO
                     * ---------------------------------------------------- *
                     * Antes eram caixas empilhadas com a largura a diminuir
                     * cinco por cento de cada vez — um degrau decorativo, igual
                     * quer houvesse mil leads ou três. Aqui a largura de cada
                     * banda é a proporção real: o estreitamento é o que os
                     * números fazem, não um efeito.
                     *
                     * A ordem é a do negócio, de cima para baixo: os novos
                     * entram em cima, e o que sobrevive desce até às propostas.
                     *
                     * Ficam de fora do corpo: Perdidas e Outros, que não são
                     * uma fase por onde se passa mas onde se sai, e
                     * Oportunidades, retirada por decisão do dono (05/08/2026)
                     * — com 1.156 leads inchava o funil a meio e a forma
                     * deixava de se ler.
                     *
                     * Obedece aos mesmos filtros do resto da página: escolhido
                     * um comercial, o funil passa a ser o dele. Pedido do dono
                     * (05/08/2026).
                     */
                    $corpo = [];
                    $fora  = [];

                    foreach ($phases as $ph) {
                        if (in_array($ph['key'], ['perdidas', 'outros', 'oportunidades'], true)) {
                            $fora[] = $ph;
                        } else {
                            $corpo[] = $ph;
                        }
                    }

                    /*
                     * DUAS CONTAS DIFERENTES, e vale a pena não as confundir.
                     *
                     * $topo  — a maior fase. Serve só para DESENHAR: é ela que
                     *          define a banda mais larga, senão o funil não
                     *          cabia na folha.
                     * $soma  — o total de leads no funil todo. É o que vale
                     *          100%, e é sobre ele que se calcula a
                     *          percentagem de cada fase.
                     *
                     * Antes a percentagem era em relação à maior fase, e por
                     * isso o "Em contacto" dizia sempre 100% — o que não é uma
                     * informação, é uma tautologia. Agora 100% é o funil
                     * inteiro e cada fase diz que fatia dele ocupa. Pedido do
                     * dono (05/08/2026).
                     */
                    $topo = 0;
                    $soma = 0;
                    foreach ($corpo as $ph) {
                        $topo  = max($topo, (int) $ph['total']);
                        $soma += (int) $ph['total'];
                    }

                    $LARG = 720;   // largura útil do desenho
                    $ALT  = 78;    // altura de cada banda
                    $MIN  = 90;    // nunca mais estreito do que isto, para caber o texto

                    $cores = ['#3b82c4', '#4a93cf', '#5aa4d8', '#6ab5e0', '#7bc6e8'];

                    $larguras = [];
                    foreach ($corpo as $k => $ph) {
                        $t = (int) $ph['total'];
                        $larguras[$k] = $topo > 0
                            ? max($MIN, (int) round($LARG * $t / $topo))
                            : $MIN;
                    }

                    $alt_total = count($corpo) * $ALT;
                    ?>

                    <?php if (!empty($corpo)) { ?>
                    <div style="text-align:center;margin:0 0 18px;">
                        <svg viewBox="0 0 <?= $LARG; ?> <?= $alt_total; ?>" style="width:100%;max-width:<?= $LARG; ?>px;height:auto;"
                             role="img" aria-label="Funil de leads por fase">
                            <?php foreach ($corpo as $k => $ph) {
                                $w  = $larguras[$k];
                                $w2 = $larguras[$k + 1] ?? $w;   // a última fecha a direito
                                $x1 = ($LARG - $w) / 2;
                                $x2 = ($LARG - $w2) / 2;
                                $y  = $k * $ALT;
                                $cor = $cores[$k % count($cores)];
                                $t   = (int) $ph['total'];
                                $pct = $soma > 0 ? round($t * 100 / $soma, 1) : 0;
                            ?>
                                <polygon points="<?= $x1; ?>,<?= $y; ?> <?= $x1 + $w; ?>,<?= $y; ?> <?= $x2 + $w2; ?>,<?= $y + $ALT - 4; ?> <?= $x2; ?>,<?= $y + $ALT - 4; ?>"
                                         fill="<?= $cor; ?>" />
                                <text x="<?= $LARG / 2; ?>" y="<?= $y + 30; ?>" text-anchor="middle"
                                      fill="#fff" font-size="15" font-weight="600"><?= e($ph['title']); ?></text>
                                <text x="<?= $LARG / 2; ?>" y="<?= $y + 52; ?>" text-anchor="middle"
                                      fill="rgba(255,255,255,.9)" font-size="13">
                                    <?= number_format($t, 0, ',', '.'); ?> leads · <?= number_format($pct, 1, ',', '.'); ?>%
                                </text>
                            <?php } ?>
                        </svg>

                        <div style="margin-top:4px;font-size:12px;color:#5a6879;">
                            <strong><?= number_format($soma, 0, ',', '.'); ?></strong> leads no funil = 100%
                        </div>

                        <?php if (!empty($fora)) { ?>
                            <div style="margin-top:6px;font-size:12px;color:#7a8798;">
                                <?php $p = [];
                                foreach ($fora as $ph) {
                                    $p[] = e($ph['title']) . ': ' . number_format((int) $ph['total'], 0, ',', '.');
                                }
                                echo implode(' &nbsp;·&nbsp; ', $p); ?>
                                &nbsp;— fora do funil
                            </div>
                        <?php } ?>
                    </div>
                    <?php } ?>

                    <?php $nphases = count($phases); $i = 0; ?>
                    <?php foreach ($phases as $phase) {
                        $i++;
                        $width = 100 - (($i - 1) * 5);
                        if ($width < 62) { $width = 62; }
                        $isVip = ($phase['key'] === 'vip');
                    ?>
                    <div class="dps-phase<?= $isVip ? ' vip' : ''; ?>" style="width:<?= $width; ?>%;">
                        <div class="dps-phase-title">
                            <i class="<?= e($phase['icon']); ?>"></i>
                            <span><?= $i; ?> · <?= e($phase['title']); ?></span>
                            <span class="ptot"><?= number_format((int) $phase['total'], 0, ',', '.'); ?> leads</span>
                        </div>

                        <?php
                        /*
                         * QUEM TEM O QUÊ NESTA FASE.
                         *
                         * O total da fase dizia quantas leads lá estavam mas
                         * não de quem eram — e é essa a pergunta que se faz a
                         * seguir. Barras simples, proporcionais ao maior, com o
                         * número à frente: não precisa de biblioteca nenhuma
                         * nem de esperar por JavaScript.
                         *
                         * Só quando se está a ver TODOS os comerciais: com um
                         * escolhido, o funil já é o dele e a barra repetiria o
                         * total da fase.
                         */
                        $pf = $por_fase[$phase['key']] ?? null;

                        if ($comercial === 0 && $pf && !empty($pf['valores'])) {
                            $maior = max($pf['valores']);
                        ?>
                        <div style="margin:8px 0 4px;">
                            <?php foreach ($pf['etiquetas'] as $ix => $nome) {
                                $n   = (int) $pf['valores'][$ix];
                                $pc  = $maior > 0 ? max(2, round($n * 100 / $maior)) : 0;
                            ?>
                            <div style="display:flex;align-items:center;gap:8px;margin:2px 0;font-size:12px;">
                                <span style="width:150px;color:#5a6879;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                      title="<?= e($nome); ?>"><?= e($nome); ?></span>
                                <span style="flex:1;background:#e6ebf0;border-radius:3px;height:12px;overflow:hidden;">
                                    <span style="display:block;height:100%;width:<?= $pc; ?>%;background:#3b82c4;"></span>
                                </span>
                                <span style="width:44px;text-align:right;color:#3a4757;font-variant-numeric:tabular-nums;"><?= $n; ?></span>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <div class="dps-chips">
                            <?php if (empty($phase['items'])) { ?>
                            <span class="muted" style="font-size:12px;color:#9aa6b2;">— sem estados —</span>
                            <?php } ?>
                            <?php foreach ($phase['items'] as $it) { $sid = (int) $it['id']; ?>
                            <button type="button" class="dps-chip" data-sid="<?= $sid; ?>" onclick="dpsToggleBrk(<?= $sid; ?>)">
                                <span class="dot" style="background:<?= e($it['color'] ?: '#cccccc'); ?>;"></span>
                                <span class="cname"><?= e($it['name']); ?></span>
                                <span class="cnum"><?= number_format((int) $it['count'], 0, ',', '.'); ?></span>
                                <i class="fa fa-caret-down caret"></i>
                            </button>
                            <?php } ?>
                        </div>

                        <div class="dps-brk-area">
                            <?php foreach ($phase['items'] as $it) {
                                $sid  = (int) $it['id'];
                                $rows = isset($breakdown[$sid]) ? $breakdown[$sid] : [];
                                arsort($rows);
                                $max  = $rows ? max($rows) : 0;
                                $cor  = $it['color'] ?: '#2f80c9';
                            ?>
                            <div class="dps-brk" id="brk-<?= $sid; ?>" style="display:none;">
                                <div class="dps-brk-head">
                                    Distribuição de <strong><?= e($it['name']); ?></strong> por comercial
                                    <span class="n">· <?= number_format((int) $it['count'], 0, ',', '.'); ?> leads</span>
                                </div>
                                <?php if (empty($rows)) { ?>
                                    <div class="dps-brk-empty">Sem leads neste estado<?= $fonte > 0 ? ' para esta fonte' : ''; ?>.</div>
                                <?php } else { foreach ($rows as $staff_id => $cnt) {
                                    $nome = $staff_id > 0
                                        ? (isset($staff_map[$staff_id]) ? $staff_map[$staff_id] : ('#' . $staff_id))
                                        : 'Sem comercial';
                                    $w = $max > 0 ? round($cnt / $max * 100) : 0;
                                ?>
                                    <div class="dps-brk-row">
                                        <span class="dps-brk-name" title="<?= e($nome); ?>"><?= e($nome); ?></span>
                                        <span class="dps-brk-bar"><span class="dps-brk-fill" style="width:<?= $w; ?>%;background:<?= e($cor); ?>;"></span></span>
                                        <span class="dps-brk-num"><?= number_format((int) $cnt, 0, ',', '.'); ?></span>
                                    </div>
                                <?php } } ?>
                                <a class="dps-brk-link" href="<?= admin_url('dps_funil/estado/' . $sid) . $qs; ?>"><i class="fa fa-list"></i> Ver lista de leads deste estado</a>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($i < $nphases) { ?>
                    <div class="dps-flowarrow"><i class="fa fa-chevron-down"></i></div>
                    <?php } ?>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
function dpsToggleBrk(sid){
    var panel = document.getElementById('brk-' + sid);
    if (!panel) return;
    var chip = document.querySelector('.dps-chip[data-sid="' + sid + '"]');
    var open = panel.style.display === 'none' || !panel.style.display;
    panel.style.display = open ? 'block' : 'none';
    if (chip) { chip.classList.toggle('active', open); }
}
</script>
<?php init_tail(); ?>
