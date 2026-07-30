<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
$pct = function ($n) {
    return rtrim(rtrim(number_format((float) $n, 4, ',', ''), '0'), ',');
};
$csv_100 = implode(',', $regras['comerciais_100']);
$csv_0   = implode(',', $regras['comerciais_0'] ?? []);
?>
<div id="wrapper">
    <div class="content">

        <div class="row mbot15">
            <div class="col-md-8"><h4 class="no-margin"><i class="fa fa-cogs"></i> Definições do Painel</h4></div>
            <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('dps_painel'); ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Voltar ao painel</a>
            </div>
        </div>

        <!-- ==============================================================
             Regras do negócio: override da direcção + comerciais a 100%.
             ============================================================== -->
        <div class="row">
            <div class="col-md-7">
                <div class="panel_s"><div class="panel-body">
                    <h4 class="no-margin"><i class="fa fa-balance-scale"></i> Regras do negócio</h4>
                    <p class="text-muted">
                        Estas regras mandam no cálculo de cada venda no painel. Nada disto é visível aos comerciais.
                    </p>
                    <hr>

                    <?php echo form_open(admin_url('dps_painel/definicoes')); ?>
                    <?php // Diz ao controlador qual dos dois formulários da página submeteu. ?>
                    <input type="hidden" name="bloco" value="regras">

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="control-label">Quem leva o override da direcção</label>
                                <select name="director_id" id="dir-quem" class="form-control">
                                    <option value="0">— ninguém —</option>
                                    <?php foreach ($staff as $s) { ?>
                                        <option value="<?php echo (int) $s['staffid']; ?>" <?php echo (int) $s['staffid'] === $regras['director_id'] ? 'selected' : ''; ?>>
                                            <?php echo html_escape($s['firstname'] . ' ' . $s['lastname']); ?> (#<?php echo (int) $s['staffid']; ?>)
                                        </option>
                                    <?php } ?>
                                </select>
                                <small class="text-muted">
                                    Recebe uma percentagem de <strong>todas</strong> as vendas da casa.
                                    Se for ele a vender, acumula: leva a comissão de comercial <em>e</em> o override.
                                    Com <strong>— ninguém —</strong> não há override nenhum: a percentagem abaixo
                                    é ignorada e nada é descontado às vendas.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="control-label">Percentagem do override (%)</label>
                                <input type="text" name="director_pct" id="dir-pct" class="form-control"
                                       value="<?php echo $pct($regras['director_pct']); ?>" placeholder="0,5">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">O override incide sobre</label>
                        <select name="director_base" id="dir-base" class="form-control">
                            <option value="venda" <?php echo $regras['director_base'] === 'venda' ? 'selected' : ''; ?>>
                                O valor da venda (recomendado)
                            </option>
                            <option value="recebida" <?php echo $regras['director_base'] === 'recebida' ? 'selected' : ''; ?>>
                                O que a DPS recebe do promotor
                            </option>
                            <option value="paga" <?php echo $regras['director_base'] === 'paga' ? 'selected' : ''; ?>>
                                A comissão paga ao comercial
                            </option>
                        </select>
                        <small class="text-muted">
                            "O valor da venda" é a base pedida pela direcção: os 0,5 são meio ponto do preço do imóvel,
                            na mesma escala das taxas do empreendimento. Se recebemos 5%, ficamos com 4,5; se recebemos 4%,
                            ficamos com 3,5. É sempre 0,5, seja qual for a taxa. Pago no CPCV.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Comerciais sem comissão &mdash; fica na casa (0%)</label>
                        <input type="text" name="comerciais_0" class="form-control"
                               value="<?php echo html_escape($csv_0); ?>" placeholder="ex.: 1">
                        <small class="text-muted">
                            IDs de staff separados por vírgula. Estes vendem mas <strong>não recebem comissão</strong>
                            &mdash; ela fica na empresa. Não entra em "a pagar agora" nem em "futuro", e
                            <strong>não gera override</strong> da direcção: não há de onde tirar meio ponto de
                            uma comissão que nunca sai de casa. Por omissão: o Ricardo (1).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Comerciais que levam 100% do que recebemos</label>
                        <input type="text" name="comerciais_100" class="form-control"
                               value="<?php echo html_escape($csv_100); ?>" placeholder="ex.: 17">
                        <small class="text-muted">
                            IDs de staff separados por vírgula. Nestas vendas a DPS entrega tudo o que recebeu:
                            o resultado da venda dá <strong>zero</strong> e <strong>não há override</strong> da direcção.
                            Deixa vazio para não haver nenhum.
                        </small>
                        <div class="mtop10">
                            <?php foreach ($staff as $s) { ?>
                                <?php $marcado = in_array((int) $s['staffid'], $regras['comerciais_100'], true); ?>
                                <button type="button" class="btn btn-xs <?php echo $marcado ? 'btn-info' : 'btn-default'; ?> alternar-100"
                                        data-id="<?php echo (int) $s['staffid']; ?>">
                                    <?php echo html_escape($s['firstname'] . ' ' . $s['lastname']); ?>
                                </button>
                            <?php } ?>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-info">Guardar regras</button>
                    <?php echo form_close(); ?>
                </div></div>
            </div>

            <div class="col-md-5">
                <div class="panel_s"><div class="panel-body">
                    <h5 class="no-margin"><i class="fa fa-calculator"></i> Exemplo, com estas regras</h5>
                    <hr>
                    <p class="text-muted">
                        Numa venda de <strong>300.000 €</strong> em que recebemos <strong>5%</strong>
                        (= 15.000 €) e pagamos <strong>3.000 €</strong> de comissão ao comercial:
                    </p>
                    <table class="table table-condensed">
                        <tr><td>Recebemos do promotor</td><td class="text-right text-success">15.000,00 €</td></tr>
                        <tr><td>Comissão do comercial</td><td class="text-right">− 3.000,00 €</td></tr>
                        <tr><td>Direcção (<span id="ex-pct">—</span>% <span id="ex-base-txt">—</span>)</td>
                            <td class="text-right">− <strong id="ex-dir">—</strong></td></tr>
                        <tr><th>Fica para a DPS</th><th class="text-right" id="ex-res">—</th></tr>
                    </table>
                    <p class="text-muted">
                        <i class="fa fa-info-circle"></i>
                        Se a mesma venda fosse de um comercial a 100%, ele levava os 15.000 €,
                        a direcção não levava nada e o resultado era 0 €.
                    </p>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="panel_s"><div class="panel-body">
                    <h4 class="no-margin"><i class="fa fa-plug"></i> Ligação Moloni</h4>
                    <p class="text-muted">
                        Credenciais da API Moloni (área de programador em moloni.pt). Ficam guardadas só aqui.
                        A password só é gravada se escreveres uma nova — deixa em branco para manter a atual.
                    </p>
                    <hr>
                    <?php echo form_open(admin_url('dps_painel/definicoes')); ?>
                        <input type="hidden" name="bloco" value="moloni">
                        <div class="form-group">
                            <label class="control-label">Developer ID (Client ID)</label>
                            <input type="text" name="dev_id" class="form-control" value="<?php echo html_escape($moloni['dev_id']); ?>" placeholder="517881390">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Client Secret</label>
                            <input type="text" name="secret" class="form-control" value="<?php echo html_escape($moloni['secret']); ?>" placeholder="a chave secreta da Moloni">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Email da conta Moloni</label>
                            <input type="email" name="email" class="form-control" value="<?php echo html_escape($moloni['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Password</label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password"
                                   placeholder="<?php echo !empty($moloni['password']) ? '•••••••• (guardada)' : 'password da conta Moloni'; ?>">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Empresa (Company ID) <small class="text-muted">— opcional</small></label>
                            <input type="text" name="company_id" class="form-control" value="<?php echo html_escape($moloni['company_id']); ?>" placeholder="deixa vazio para descobrir no teste">
                        </div>
                        <button type="submit" class="btn btn-info">Guardar</button>
                        <a href="<?php echo admin_url('dps_painel/moloni_testar'); ?>" class="btn btn-success"
                           onclick="return confirm('Testar a ligação à Moloni com estas credenciais?');">
                            <i class="fa fa-bolt"></i> Testar ligação
                        </a>
                        <a href="<?php echo admin_url('dps_painel'); ?>" class="btn btn-default">Voltar</a>
                    <?php echo form_close(); ?>
                </div></div>
            </div>
            <div class="col-md-5">
                <div class="panel_s"><div class="panel-body">
                    <h5 class="no-margin">Como obter</h5>
                    <hr>
                    <ol class="text-muted" style="padding-left:18px;">
                        <li>Em <strong>moloni.pt → Área de Programador / API</strong>, ativa a API.</li>
                        <li>Copia o <strong>Developer ID</strong> e o <strong>Client Secret</strong>.</li>
                        <li>No campo "URI de Resposta (Callback)" põe:<br>
                            <code>https://crm.grupo-dps.com/moloni-callback.php</code></li>
                        <li>Cola aqui as credenciais + email/password da conta e carrega <strong>Testar ligação</strong>.</li>
                    </ol>
                </div></div>
            </div>
        </div>

    </div>
</div>
<?php init_tail(); ?>
<script>
(function () {
    var campoPct  = document.getElementById('dir-pct');
    var campoBase = document.getElementById('dir-base');
    var campoQuem = document.getElementById('dir-quem');
    var campo100  = document.querySelector('input[name="comerciais_100"]');

    // Venda-tipo do exemplo: 300.000 €, recebemos 5% (15.000 €), comercial 3.000 €.
    var VENDA = 300000, RECEBIDO = 15000, COMERCIAL = 3000;
    var ROTULOS = {
        recebida: 'do que recebemos',
        paga: 'da comissão do comercial',
        venda: 'do valor da venda'
    };

    function euros(n) {
        return n.toLocaleString('pt-PT', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    }

    function recalcular() {
        // Aceita "0,5" e "0.5" — no painel escreve-se à portuguesa.
        var pct  = parseFloat(String(campoPct.value).replace(',', '.')) || 0;
        var base = campoBase.value;
        var valorBase = base === 'venda' ? VENDA : (base === 'paga' ? COMERCIAL : RECEBIDO);

        // Sem director escolhido não há override — tem de bater certo com o
        // aplicar_regras_venda() do model, senão o exemplo confirma ao
        // utilizador um desconto que o painel já não faz.
        var temDirector = parseInt(campoQuem.value, 10) > 0;
        var dir = temDirector ? Math.round(valorBase * pct / 100 * 100) / 100 : 0;

        document.getElementById('ex-pct').textContent = temDirector ? String(pct).replace('.', ',') : '0';
        document.getElementById('ex-base-txt').textContent = temDirector ? (ROTULOS[base] || '') : '— ninguém escolhido';
        document.getElementById('ex-dir').textContent = euros(dir);
        document.getElementById('ex-res').textContent = euros(RECEBIDO - COMERCIAL - dir);
    }

    campoPct.addEventListener('input', recalcular);
    campoBase.addEventListener('change', recalcular);
    campoQuem.addEventListener('change', recalcular);
    recalcular();

    // Os botões só acrescentam/removem o id no campo de texto — o que é
    // submetido continua a ser o CSV, para não haver duas fontes de verdade.
    document.querySelectorAll('.alternar-100').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var id = this.dataset.id;
            var ids = campo100.value.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
            var i = ids.indexOf(id);
            if (i === -1) {
                ids.push(id);
                this.classList.remove('btn-default');
                this.classList.add('btn-info');
            } else {
                ids.splice(i, 1);
                this.classList.remove('btn-info');
                this.classList.add('btn-default');
            }
            campo100.value = ids.join(',');
        });
    });
})();
</script>
