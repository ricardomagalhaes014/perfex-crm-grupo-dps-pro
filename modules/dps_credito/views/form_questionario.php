<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<form id="dps-credito-form" data-lead="<?php echo (int) $lead['id']; ?>">

    <p class="text-muted">
        Lead: <strong><?php echo html_escape($lead['name']); ?></strong>
    </p>

    <div class="form-group">
        <label class="control-label">Crédito abordado? <span class="text-danger">*</span></label><br>
        <div class="radio radio-primary radio-inline">
            <input type="radio" name="abordado" id="dps-credito-abordado-sim" value="sim"
                <?php echo ($resposta['abordado'] ?? '') === 'sim' ? 'checked' : ''; ?>>
            <label for="dps-credito-abordado-sim">Sim</label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" name="abordado" id="dps-credito-abordado-nao" value="nao"
                <?php echo ($resposta['abordado'] ?? '') === 'nao' ? 'checked' : ''; ?>>
            <label for="dps-credito-abordado-nao">Não</label>
        </div>
        <?php
        /*
         * Terceira resposta: não atendeu.
         *
         * Sem ela, uma chamada sem resposta era gravada como "não abordou" e o
         * comercial aparecia na análise a não trabalhar o crédito. São coisas
         * diferentes e passam a contar em separado.
         */
        ?>
        <div class="radio radio-primary radio-inline">
            <input type="radio" name="abordado" id="dps-credito-abordado-na" value="nao_atendeu"
                <?php echo ($resposta['abordado'] ?? '') === 'nao_atendeu' ? 'checked' : ''; ?>>
            <label for="dps-credito-abordado-na">Não atendeu</label>
        </div>
        <p class="text-muted" style="font-size:12px;margin:6px 0 0;">
            &laquo;Não atendeu&raquo; é para quem não chegou a ser falado — não conta
            como crédito por abordar.
        </p>
    </div>

    <?php
    /*
     * Dito "sim", falta saber se o cliente QUER proposta.
     *
     * É esta resposta que decide se a lead segue para o parceiro do crédito
     * habitação — abordar não é a mesma coisa que haver interesse, e mandar
     * para fora quem disse que não queria era mandar trabalho e dados de
     * clientes sem razão. Regra do dono (19/08/2026).
     */
    ?>
    <div id="dps-credito-interesse" style="display:none;">
        <div class="form-group">
            <label class="control-label">O cliente tem interesse? <span class="text-danger">*</span></label><br>
            <div class="radio radio-primary radio-inline">
                <input type="radio" name="interessado_proposta" id="dps-credito-interessado-sim" value="sim"
                    <?php echo ($resposta['interessado_proposta'] ?? '') === 'sim' ? 'checked' : ''; ?>>
                <label for="dps-credito-interessado-sim">Com interesse</label>
            </div>
            <div class="radio radio-primary radio-inline">
                <input type="radio" name="interessado_proposta" id="dps-credito-interessado-nao" value="nao"
                    <?php echo ($resposta['interessado_proposta'] ?? '') === 'nao' ? 'checked' : ''; ?>>
                <label for="dps-credito-interessado-nao">Sem interesse</label>
            </div>
            <div class="alert alert-info mtop10" id="dps-credito-aviso-proposta" style="display:none;">
                Com interesse, a ficha desta lead segue por email para o parceiro do
                crédito habitação e fica registada em <strong>DPS Crédito &rarr; Propostas</strong>.
            </div>
        </div>
    </div>

    <div id="dps-credito-detalhes" style="display:none;">
        <hr>

        <div class="form-group">
            <label class="control-label">Situação <span class="text-danger">*</span></label>
            <select name="situacao" id="dps-credito-situacao" class="form-control">
                <option value="">Escolher...</option>
                <option value="novo_pedido" <?php echo ($resposta['situacao'] ?? '') === 'novo_pedido' ? 'selected' : ''; ?>>
                    Novo pedido de crédito
                </option>
                <option value="financiamento_existente" <?php echo ($resposta['situacao'] ?? '') === 'financiamento_existente' ? 'selected' : ''; ?>>
                    Já tem financiamento
                </option>
            </select>
        </div>

        <div class="form-group" id="dps-credito-banco-grupo">
            <label class="control-label">
                Banco <span class="text-danger" id="dps-credito-banco-obrigatorio" style="display:none;">*</span>
            </label>
            <input type="text" name="banco" id="dps-credito-banco" class="form-control"
                   list="dps-credito-bancos" value="<?php echo html_escape($resposta['banco'] ?? ''); ?>">
            <datalist id="dps-credito-bancos">
                <?php foreach (dps_credito_bancos() as $banco) { ?>
                    <option value="<?php echo html_escape($banco); ?>"></option>
                <?php } ?>
            </datalist>
            <small class="text-muted" id="dps-credito-banco-ajuda">
                Banco onde está financiado, ou onde pretende pedir.
            </small>
        </div>

        <div class="form-group">
            <label class="control-label">Montante (€) <span class="text-danger">*</span></label>
            <input type="text" name="montante" class="form-control"
                   value="<?php echo $resposta['montante'] ?? ''; ?>">
        </div>

        <div class="form-group" id="dps-credito-docs-grupo" style="display:none;">
            <label class="control-label">Documentos do cliente</label>
            <input type="file" name="documentos[]" class="form-control" multiple
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
            <small class="text-muted">
                Anexe os documentos para o pedido de crédito. Ficam no processo e a equipa recebe-os.
                Pode acrescentar mais depois.
            </small>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label">Observações</label>
        <textarea name="observacoes" class="form-control" rows="2"><?php echo html_escape($resposta['observacoes'] ?? ''); ?></textarea>
    </div>

</form>
