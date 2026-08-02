<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Registo de Envios</h4>
                        <p class="text-muted">
                            <?php if (is_admin()) { ?>
                                Todas as tentativas de envio (massa, propostas em massa, follow-ups e testes).
                            <?php } else { ?>
                                As tentativas de envio às suas leads.
                            <?php } ?>
                            Mostram-se os 300 registos mais recentes.
                        </p>
                        <hr>

                        <?php if (is_admin()) { ?>
                            <form method="get" action="<?php echo admin_url('dps_automacao/envios'); ?>">
                                <div class="row mbot15">
                                    <div class="col-md-3">
                                        <label for="filtro_comercial">Comercial</label>
                                        <select name="comercial_id" id="filtro_comercial" class="form-control">
                                            <option value="">Todos</option>
                                            <?php foreach ($comerciais as $comercial) { ?>
                                                <option value="<?php echo (int) $comercial['staffid']; ?>"
                                                    <?php echo ((string) $filtros['staff_id'] === (string) $comercial['staffid']) ? 'selected' : ''; ?>>
                                                    <?php echo html_escape($comercial['firstname'] . ' ' . $comercial['lastname']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filtro_canal">Canal</label>
                                        <select name="canal" id="filtro_canal" class="form-control">
                                            <option value="">Todos</option>
                                            <?php foreach (['whatsapp' => 'WhatsApp', 'email' => 'Email', 'sms' => 'SMS'] as $valor => $nome) { ?>
                                                <option value="<?php echo $valor; ?>"
                                                    <?php echo ($filtros['canal'] === $valor) ? 'selected' : ''; ?>>
                                                    <?php echo $nome; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" class="btn btn-default">Filtrar</button>
                                    </div>
                                </div>
                            </form>
                            <hr>
                        <?php } ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Lead</th>
                                        <th>Comercial</th>
                                        <th>Canal</th>
                                        <th>Tipo</th>
                                        <th>Marco</th>
                                        <th>Resultado</th>
                                        <th>Detalhe</th>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($envios)) { ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">Ainda não há envios registados.</td>
                                        </tr>
                                    <?php } ?>
                                    <?php foreach ($envios as $envio) { ?>
                                        <tr>
                                            <td><?php echo html_escape(_dt($envio['dateadded'])); ?></td>
                                            <td>
                                                <?php if ((int) $envio['lead_id'] > 0) { ?>
                                                    <a href="<?php echo admin_url('leads/index/' . (int) $envio['lead_id']); ?>">
                                                        <?php echo html_escape($envio['lead_nome'] ?: ('Lead #' . (int) $envio['lead_id'])); ?>
                                                    </a>
                                                <?php } else { ?>
                                                    <span class="text-muted">— (teste)</span>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo html_escape(trim((string) $envio['staff_nome']) ?: '—'); ?></td>
                                            <td><?php echo html_escape($envio['canal']); ?></td>
                                            <td>
                                                <?php
                                                // Etiquetas legíveis para os tipos; um tipo desconhecido
                                                // mostra-se tal como está (escapado).
                                                $tipos_legiveis = [
                                                    'massa'          => 'Massa',
                                                    'followup'       => 'Follow-up',
                                                    'teste'          => 'Teste',
                                                    'proposta_massa' => 'Proposta em massa',
                                                ];
                                                $tipo = (string) $envio['tipo'];
                                                echo html_escape(isset($tipos_legiveis[$tipo]) ? $tipos_legiveis[$tipo] : $tipo);
                                                ?>
                                            </td>
                                            <td><?php echo $envio['marco'] !== null ? (int) $envio['marco'] . ' dias' : '—'; ?></td>
                                            <td>
                                                <?php if ((int) $envio['ok'] === 1) { ?>
                                                    <span class="label label-success">OK</span>
                                                <?php } else { ?>
                                                    <span class="label label-danger">Falhou</span>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo html_escape((string) $envio['detalhe']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php
                                                    // A mensagem vem da BD (pode conter o que o utilizador escreveu
                                                    // e nomes de leads) — escapar sempre.
                                                    $resumo_msg = (string) $envio['mensagem'];
                                                    if (mb_strlen($resumo_msg) > 120) {
                                                        $resumo_msg = mb_substr($resumo_msg, 0, 120) . '…';
                                                    }
                                                    echo html_escape($resumo_msg);
                                                    ?>
                                                </small>
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
<?php init_tail(); ?>
