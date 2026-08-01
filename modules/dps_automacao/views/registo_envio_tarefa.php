<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <div class="row mbot15">
            <div class="col-md-8">
                <h4 class="no-margin"><i class="fa fa-list"></i> Registo — Envio Massa Tarefa</h4>
                <small class="text-muted">
                    Cada envio, com quem recebeu e quem falhou. Fica guardado mesmo
                    depois de o lote acabar.
                </small>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('dps_automacao/envio_massa_tarefa'); ?>" class="btn btn-default">
                    <i class="fa fa-paper-plane"></i> Novo envio
                </a>
                <?php if (!empty($lote)) { ?>
                    <a href="<?php echo admin_url('dps_automacao/registo_envio_tarefa'); ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Todos os lotes
                    </a>
                <?php } ?>
            </div>
        </div>

        <div class="panel_s"><div class="panel-body">
        <?php if (empty($lote)) { ?>

            <?php if (empty($lotes)) { ?>
                <p class="text-muted">Ainda não houve envios.</p>
            <?php } else { ?>
                <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr>
                        <th>Quando</th><th>Assunto</th><th>Quem enviou</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Enviados</th>
                        <th class="text-right">Falhas</th>
                        <th class="text-right">Por sair</th>
                        <th>Anexo</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($lotes as $l) { ?>
                        <tr>
                            <td><?php echo _dt($l['inicio']); ?></td>
                            <td><?php echo html_escape($l['assunto']); ?></td>
                            <td><?php echo html_escape(get_staff_full_name((int) $l['staff_id']) ?: '—'); ?></td>
                            <td class="text-right"><?php echo (int) $l['total']; ?></td>
                            <td class="text-right text-success"><strong><?php echo (int) $l['enviados']; ?></strong></td>
                            <td class="text-right <?php echo $l['falhas'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo (int) $l['falhas']; ?>
                            </td>
                            <td class="text-right <?php echo $l['pendentes'] > 0 ? 'text-warning' : 'text-muted'; ?>">
                                <?php echo (int) $l['pendentes']; ?>
                            </td>
                            <td><small class="text-muted"><?php echo html_escape($l['anexo'] ?: '—'); ?></small></td>
                            <td class="text-right">
                                <a href="<?php echo admin_url('dps_automacao/registo_envio_tarefa/' . $l['lote']); ?>"
                                   class="btn btn-default btn-xs">Ver a quem</a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                </div>
            <?php } ?>

        <?php } else { ?>

            <?php
            /*
             * As falhas primeiro: é o que exige acção. Um registo que começa
             * pelos 480 que correram bem obriga a procurar os 3 que não.
             */
            $contas = ['enviado' => 0, 'falhou' => 0, 'pendente' => 0];
            foreach ($linhas as $l) {
                $contas[$l['estado']] = ($contas[$l['estado']] ?? 0) + 1;
            }
            ?>
            <p>
                <span class="label label-success"><?php echo $contas['enviado']; ?> enviados</span>
                <span class="label label-danger"><?php echo $contas['falhou']; ?> falharam</span>
                <span class="label label-warning"><?php echo $contas['pendente']; ?> por sair</span>
            </p>
            <hr>
            <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr>
                    <th>Estado</th><th>Nome</th><th>Email</th><th>Quando</th><th>Nota</th>
                </tr></thead>
                <tbody>
                <?php foreach ($linhas as $l) {
                    $cor = ['enviado' => 'success', 'falhou' => 'danger', 'pendente' => 'warning'][$l['estado']] ?? 'default';
                    ?>
                    <tr>
                        <td><span class="label label-<?php echo $cor; ?>"><?php echo html_escape($l['estado']); ?></span></td>
                        <td><?php echo html_escape($l['nome'] ?: '—'); ?></td>
                        <td><?php echo html_escape($l['email']); ?></td>
                        <td>
                            <?php echo $l['enviado_em']
                                ? _dt($l['enviado_em'])
                                : '<small class="text-muted">previsto ' . _d($l['agendado_para']) . '</small>'; ?>
                        </td>
                        <td><small class="text-muted"><?php echo html_escape($l['detalhe'] ?: ''); ?></small></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            </div>

        <?php } ?>
        </div></div>

    </div>
</div>
<?php init_tail(); ?>
