<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row">

    <div class="col-md-4">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Reserva</h4>
        <p class="text-muted" style="font-size:13px;">
          A lead passou a <strong><?php echo html_escape(DPS_VENDAS_ESTADO_CONTRATO); ?></strong>.
          Escolha a unidade e a venda entra no mapa.
        </p>
        <hr>

        <table class="table table-condensed no-margin" style="font-size:13px;">
          <tr>
            <td class="text-muted" style="width:90px;">Cliente</td>
            <td><strong><?php echo html_escape($lead->name); ?></strong></td>
          </tr>
          <tr>
            <td class="text-muted">Email</td>
            <td><?php echo $lead->email ? html_escape($lead->email)
                : '<span class="text-danger">em falta</span>'; ?></td>
          </tr>
          <tr>
            <td class="text-muted">Telefone</td>
            <td><?php echo $lead->phonenumber ? html_escape($lead->phonenumber)
                : '<span class="text-danger">em falta</span>'; ?></td>
          </tr>
          <tr>
            <td class="text-muted">Comercial</td>
            <td><?php echo $lead->assigned
                ? html_escape(get_staff_full_name($lead->assigned))
                : '<span class="text-warning">sem responsável — fica para si</span>'; ?></td>
          </tr>
        </table>

        <hr>
        <a href="<?php echo admin_url('leads/index/' . (int) $lead->id); ?>" class="btn btn-default btn-block">
          <i class="fa fa-arrow-left"></i> Voltar à lead
        </a>
        <p class="text-muted mtop15" style="font-size:12px;">
          Se ainda não é para reservar, é só voltar — nada foi criado.
        </p>
      </div></div>
    </div>

    <div class="col-md-8">
      <div class="panel_s"><div class="panel-body">

        <label class="control-label">Empreendimento</label>
        <select class="form-control" id="dps-emp"
                onchange="window.location='<?php echo admin_url('dps_vendas/reserva/' . (int) $lead->id); ?>?emp=' + encodeURIComponent(this.value);">
          <option value="">— escolher —</option>
          <?php foreach ($empreendimentos as $s => $e) { ?>
            <option value="<?php echo html_escape($s); ?>" <?php echo $s === $slug ? 'selected' : ''; ?>>
              <?php echo html_escape($e['nome']); ?>
            </option>
          <?php } ?>
        </select>

        <?php if (!$slug) { ?>
          <p class="text-muted mtop15">Escolha o empreendimento para ver as unidades disponíveis.</p>

        <?php } elseif (empty($disponibilidade['ok'])) { ?>
          <div class="alert alert-danger mtop15">
            <?php echo html_escape($disponibilidade['erro']
                ?? 'Não consegui ler as unidades disponíveis do simulador.'); ?>
            <br><a href="">Tentar de novo</a>
          </div>

        <?php } elseif (empty($disponibilidade['unidades'])) { ?>
          <div class="alert alert-warning mtop15">
            Este empreendimento não tem unidades disponíveis no simulador.
          </div>

        <?php } else { ?>

          <hr>
          <p class="text-muted" style="font-size:13px;">
            <strong><?php echo (int) $disponibilidade['count']; ?></strong> unidades disponíveis.
            A lista vem do simulador neste momento — ao confirmar, é lida outra vez.
          </p>

          <?php echo form_open(admin_url('dps_vendas/reserva/' . (int) $lead->id), ['id' => 'dps-form-reserva']); ?>
          <input type="hidden" name="slug"    value="<?php echo html_escape($slug); ?>">
          <input type="hidden" name="unidade" id="dps-unidade">
          <input type="hidden" name="valor"   id="dps-valor">

          <div class="table-responsive">
            <table class="table table-hover" style="font-size:13px;">
              <thead>
                <tr>
                  <th style="width:40px;"></th>
                  <th>Fração</th>
                  <th>Tipologia</th>
                  <th class="text-right">Área</th>
                  <th class="text-right">Preço</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($disponibilidade['unidades'] as $u) { ?>
                  <tr class="dps-linha" style="cursor:pointer;"
                      data-unidade="<?php echo html_escape($u['fraccao']); ?>"
                      data-valor="<?php echo (float) $u['preco']; ?>">
                    <td><input type="radio" name="escolha" class="dps-radio"></td>
                    <td><strong><?php echo html_escape($u['fraccao']); ?></strong></td>
                    <td><?php echo html_escape((string) $u['tipologia']); ?></td>
                    <td class="text-right">
                      <?php echo $u['area'] ? html_escape((string) $u['area']) . ' m²' : '—'; ?>
                    </td>
                    <td class="text-right">
                      <?php echo $u['preco']
                          ? number_format((float) $u['preco'], 0, ',', ' ') . ' €'
                          : '<span class="text-muted">sem preço</span>'; ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

          <hr>
          <div id="dps-escolhido" class="alert alert-info" style="display:none;"></div>

          <button type="submit" class="btn btn-info btn-lg" id="dps-confirmar" disabled>
            <i class="fa fa-check"></i> Criar reserva no mapa de vendas
          </button>
          <span class="text-muted" style="font-size:12px;margin-left:10px;">
            A venda nasce em <strong>Pendente</strong> e a fração fica <strong>Reservada</strong> no simulador.
          </span>
          <?php echo form_close(); ?>

        <?php } ?>

      </div></div>
    </div>

  </div>
</div></div>

<script>
(function () {
    var linhas = document.querySelectorAll('.dps-linha');
    var campoU = document.getElementById('dps-unidade');
    var campoV = document.getElementById('dps-valor');
    var botao  = document.getElementById('dps-confirmar');
    var caixa  = document.getElementById('dps-escolhido');

    if (!linhas.length || !botao) { return; }

    function euros(v) {
        return v ? new Intl.NumberFormat('pt-PT').format(v) + ' €' : 'sem preço definido';
    }

    Array.prototype.forEach.call(linhas, function (linha) {
        linha.addEventListener('click', function () {
            var unidade = linha.getAttribute('data-unidade');
            var valor   = parseFloat(linha.getAttribute('data-valor')) || 0;

            Array.prototype.forEach.call(linhas, function (l) { l.classList.remove('success'); });
            linha.classList.add('success');
            linha.querySelector('.dps-radio').checked = true;

            campoU.value = unidade;
            campoV.value = valor;

            caixa.style.display = '';
            caixa.innerHTML = 'Fração <strong>' + unidade + '</strong> — ' + euros(valor)
                            + '. Confirme para criar a reserva.';
            botao.disabled = false;
        });
    });

    // Uma reserva é um compromisso: vale a pena o segundo de pausa.
    document.getElementById('dps-form-reserva').addEventListener('submit', function (e) {
        if (!campoU.value) { e.preventDefault(); return; }
        if (!confirm('Criar a reserva da fração ' + campoU.value + '?')) { e.preventDefault(); return; }
        botao.disabled  = true;
        botao.innerHTML = 'A criar...';
    });
})();
</script>
<?php init_tail(); ?>
</body></html>
