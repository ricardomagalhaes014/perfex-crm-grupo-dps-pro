<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel_s mtop15" id="dps-credito-painel-lead">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-8">
                <h5 class="no-margin">
                    <i class="fa fa-university"></i> DPS Crédito
                </h5>
            </div>
            <div class="col-md-4 text-right">
                <button type="button" class="btn btn-<?php echo $resposta ? 'default' : 'warning'; ?> btn-sm dps-credito-abrir"
                        data-lead="<?php echo (int) $lead->id; ?>">
                    <?php echo $resposta ? 'Editar respostas' : 'Responder'; ?>
                </button>
            </div>
        </div>

        <hr>

        <?php if (!$resposta) { ?>
            <div class="alert alert-warning no-margin">
                <strong>Por responder.</strong>
                Esta lead não pode ser fechada enquanto o questionário de crédito não for preenchido.
            </div>
        <?php } else { ?>
            <table class="table table-borderless no-margin">
                <tr>
                    <td width="40%"><strong>Crédito abordado</strong></td>
                    <td>
                        <?php if ($resposta['abordado'] === 'sim') { ?>
                            <span class="label label-success">Sim</span>
                        <?php } else { ?>
                            <span class="label label-default">Não</span>
                        <?php } ?>
                    </td>
                </tr>
                <?php if ($resposta['abordado'] === 'sim') { ?>
                    <tr>
                        <td><strong>Situação</strong></td>
                        <td><?php echo dps_credito_nome_situacao($resposta['situacao']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Banco</strong></td>
                        <td><?php echo html_escape($resposta['banco'] ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Montante</strong></td>
                        <td>
                            <?php echo $resposta['montante'] !== null
                                ? app_format_money($resposta['montante'], get_base_currency())
                                : '—'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Quer proposta</strong></td>
                        <td>
                            <?php if ($resposta['interessado_proposta'] === 'sim') { ?>
                                <span class="label label-info">Sim</span>
                            <?php } else { ?>
                                <span class="label label-default">Não</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!empty($resposta['observacoes'])) { ?>
                    <tr>
                        <td><strong>Observações</strong></td>
                        <td><?php echo nl2br(html_escape($resposta['observacoes'])); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
</div>
