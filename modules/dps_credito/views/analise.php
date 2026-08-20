<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dpsa-kpis{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:18px;}
.dpsa-kpi{flex:1;min-width:150px;background:#fff;border:1px solid #e6eaef;border-radius:10px;padding:14px 16px;}
.dpsa-kpi .n{font-size:1.9rem;font-weight:700;line-height:1.1;color:#2b3440;font-variant-numeric:tabular-nums;}
.dpsa-kpi .l{font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;color:#8a97a6;margin-top:3px;}
.dpsa-kpi.ok .n{color:#2f7d55;} .dpsa-kpi.warn .n{color:#b07d19;} .dpsa-kpi.gold .n{color:#a8873c;}
.dpsa-tab td,.dpsa-tab th{vertical-align:middle!important;}
.dpsa-nome{font-weight:600;}
.dpsa-bar{height:8px;border-radius:6px;background:#eef1f5;overflow:hidden;min-width:70px;margin-top:4px;}
.dpsa-bar span{display:block;height:100%;border-radius:6px;background:linear-gradient(90deg,#a8873c,#c5a55a);}
.dpsa-pct{font-weight:700;font-variant-numeric:tabular-nums;}
.dpsa-mut{color:#95a1af;}
.dpsa-nota{background:#fbf1de;border:1px solid #e0ae5c;color:#8a6414;border-radius:8px;padding:10px 13px;font-size:.87rem;margin-bottom:16px;}
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row mbot15">
                            <div class="col-md-6">
                                <h4 class="no-margin">Análise Comercial — DPS Crédito</h4>
                                <p class="text-muted" style="margin:4px 0 0;font-size:.86rem;">
                                    Percentagens calculadas sobre as leads atribuídas a cada comercial.
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php echo form_open(admin_url('dps_credito/analise'), ['method' => 'get', 'class' => 'text-right']); ?>
                                <div style="display:inline-flex;gap:8px;align-items:flex-end;">
                                    <div><label style="font-size:.78rem;">De</label>
                                        <input type="date" name="de" value="<?php echo html_escape($de); ?>" class="form-control input-sm"></div>
                                    <div><label style="font-size:.78rem;">Até</label>
                                        <input type="date" name="ate" value="<?php echo html_escape($ate); ?>" class="form-control input-sm"></div>
                                    <button class="btn btn-default btn-sm">Aplicar</button>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                        </div>

                        <?php if (empty($tem_historico)) { ?>
                            <div class="dpsa-nota">
                                O histórico de respostas começou a ser registado agora — as colunas
                                <strong>Respostas</strong> e <strong>Passou a Sim</strong> só contam a partir deste momento.
                                As restantes já refletem toda a base.
                            </div>
                        <?php } ?>

                        <?php
                        $t = ['leads' => 0, 'sim' => 0, 'nao' => 0, 'na' => 0, 'cont' => 0,
                              'ind' => 0, 'int' => 0, 'props' => 0, 'mont' => 0];
                        foreach ($linhas as $l) {
                            $t['leads'] += $l['leads_total'];
                            $t['sim']   += $l['sim'];
                            $t['nao']   += $l['nao'];
                            $t['na']    += (int) ($l['nao_atendeu'] ?? 0);
                            $t['cont']  += (int) ($l['contactaveis'] ?? $l['leads_total']);
                            $t['ind']   += $l['indefinido'];
                            $t['int']   += $l['interessados'];
                            $t['props'] += $l['propostas'];
                            $t['mont']  += (float) $l['montante_total'];
                        }
                        $pct = function ($a, $b) { return $b > 0 ? round($a / $b * 100, 1) : 0; };
                        ?>

                        <div class="dpsa-kpis">
                            <div class="dpsa-kpi"><div class="n"><?php echo number_format($t['leads'], 0, ',', '.'); ?></div><div class="l">Leads atribuídas</div></div>
                            <?php
                            /*
                             * A taxa é sobre quem foi possível falar. Contar aqui
                             * as leads que nunca atenderam fazia o número descer
                             * por uma razão que não é do comercial.
                             */
                            ?>
                            <div class="dpsa-kpi ok"><div class="n"><?php echo $pct($t['sim'], $t['cont']); ?>%</div><div class="l">Crédito abordado<br><small style="font-weight:normal;opacity:.75;">de quem atendeu</small></div></div>
                            <div class="dpsa-kpi warn"><div class="n"><?php echo number_format($t['na'], 0, ',', '.'); ?></div><div class="l">Não atenderam</div></div>
                            <div class="dpsa-kpi"><div class="n"><?php echo number_format($t['sim'], 0, ',', '.'); ?></div><div class="l">Respostas "Sim"</div></div>
                            <div class="dpsa-kpi warn"><div class="n"><?php echo number_format($t['ind'], 0, ',', '.'); ?></div><div class="l">Por responder</div></div>
                            <div class="dpsa-kpi gold"><div class="n"><?php echo $pct($t['props'], $t['sim']); ?>%</div><div class="l">Sim → proposta</div></div>
                            <div class="dpsa-kpi gold"><div class="n"><?php echo app_format_money($t['mont'], get_base_currency()); ?></div><div class="l">Montante de crédito</div></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped dpsa-tab">
                                <thead>
                                    <tr>
                                        <th>Comercial</th>
                                        <th class="text-center">Leads</th>
                                        <th class="text-center">Sim</th>
                                        <th class="text-center">Não</th>
                                        <th class="text-center">Não atendeu</th>
                                        <th class="text-center">Por responder</th>
                                        <th>% Abordagem</th>
                                        <th class="text-center">Quer proposta</th>
                                        <th class="text-center">Propostas enviadas</th>
                                        <th>% Sim → proposta</th>
                                        <th class="text-center">Respostas</th>
                                        <th class="text-center">Passou a Sim</th>
                                        <th class="text-right">Montante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($linhas)) { ?>
                                        <tr><td colspan="13" class="text-center text-muted">Sem dados no período seleccionado.</td></tr>
                                    <?php } ?>
                                    <?php foreach ($linhas as $l) { ?>
                                        <tr>
                                            <td class="dpsa-nome"><?php echo html_escape($l['comercial']); ?></td>
                                            <td class="text-center"><?php echo (int) $l['leads_total']; ?></td>
                                            <td class="text-center"><strong style="color:#2f7d55;"><?php echo (int) $l['sim']; ?></strong></td>
                                            <td class="text-center"><?php echo (int) $l['nao']; ?></td>
                                            <td class="text-center">
                                                <?php if ((int) ($l['nao_atendeu'] ?? 0) > 0) { ?>
                                                    <span class="label label-warning"><?php echo (int) $l['nao_atendeu']; ?></span>
                                                <?php } else { ?><span class="dpsa-mut">0</span><?php } ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ((int) $l['indefinido'] > 0) { ?>
                                                    <span class="label label-warning"><?php echo (int) $l['indefinido']; ?></span>
                                                <?php } else { ?><span class="dpsa-mut">0</span><?php } ?>
                                            </td>
                                            <td>
                                                <span class="dpsa-pct"><?php echo $l['pct_abordagem']; ?>%</span>
                                                <div class="dpsa-bar"><span style="width:<?php echo min(100, $l['pct_abordagem']); ?>%;"></span></div>
                                            </td>
                                            <td class="text-center"><?php echo (int) $l['interessados']; ?></td>
                                            <td class="text-center"><?php echo (int) $l['propostas']; ?></td>
                                            <td>
                                                <span class="dpsa-pct"><?php echo $l['pct_proposta']; ?>%</span>
                                                <div class="dpsa-bar"><span style="width:<?php echo min(100, $l['pct_proposta']); ?>%;"></span></div>
                                            </td>
                                            <td class="text-center"><?php echo (int) $l['respostas']; ?></td>
                                            <td class="text-center"><?php echo (int) $l['passou_a_sim']; ?></td>
                                            <td class="text-right"><?php echo app_format_money($l['montante_total'], get_base_currency()); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-muted" style="font-size:.8rem;margin-top:10px;">
                            <strong>% Abordagem</strong> = leads com crédito respondido "Sim" ÷ leads atribuídas.
                            <strong>% Sim → proposta</strong> = leads com proposta enviada ÷ leads com "Sim".
                            <strong>Passou a Sim</strong> = nº de vezes que o comercial mudou o campo para "Sim" (histórico).
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
