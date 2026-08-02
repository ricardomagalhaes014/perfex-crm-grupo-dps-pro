<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row">

    <div class="col-md-7">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Envio Massa Cliente</h4>
        <p class="text-muted" style="font-size:13px;">
          Para quem já comprou — acompanhamento de obra, fotografias, avisos.
          Quem comprou em dois empreendimentos aparece nos dois envios.
        </p>
        <hr>

        <?php echo form_open_multipart(admin_url('dps_automacao/envio_massa_cliente_enviar')); ?>

          <label class="control-label">Empreendimento</label>
          <select name="empreendimento" class="form-control"
                  onchange="window.location='<?php echo admin_url('dps_automacao/envio_massa_cliente'); ?>?empreendimento=' + encodeURIComponent(this.value);">
            <option value="">Todos os empreendimentos</option>
            <?php foreach ($empreendimentos as $e) { ?>
              <option value="<?php echo html_escape($e['empreendimento']); ?>"
                <?php echo $e['empreendimento'] === $empreendimento ? 'selected' : ''; ?>>
                <?php echo html_escape($e['empreendimento']); ?> (<?php echo (int) $e['n']; ?>)
              </option>
            <?php } ?>
          </select>

          <label class="control-label mtop15">Assunto</label>
          <input type="text" name="assunto" class="form-control" required
                 placeholder="Ponto de situação da obra — <?php echo html_escape($empreendimento ?: 'empreendimento'); ?>">

          <label class="control-label mtop15">Mensagem</label>
          <textarea name="mensagem" class="form-control" rows="10" required
placeholder="Estimado(a) {nome},

Escrevemos para lhe dar conta do andamento da obra do {empreendimento}.

[o que quiser contar]

Com os melhores cumprimentos,
DPS Imobiliário"></textarea>
          <span class="help-block">
            Pode usar <code>{nome}</code>, <code>{empreendimento}</code> e <code>{unidade}</code> —
            são substituídos em cada email. Assim escreve uma mensagem só e ela sai personalizada.
          </span>

          <label class="control-label mtop15">Anexo (opcional)</label>
          <input type="file" name="anexo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
          <span class="help-block">
            PDF, JPG ou PNG, até 10 MB. É aqui que vai a fotografia da obra.
          </span>

          <hr>
          <?php if (empty($clientes)) { ?>
            <div class="alert alert-warning">
              Não há clientes <?php echo $empreendimento ? 'neste empreendimento' : 'ainda'; ?>.
              As fichas de cliente nascem das vendas concluídas.
            </div>
          <?php } else { ?>
            <button type="submit" class="btn btn-info btn-lg"
                    onclick="return confirm('Enviar para <?php echo count($clientes); ?> cliente(s)?');">
              <i class="fa fa-paper-plane"></i>
              Enviar para <?php echo count($clientes); ?> cliente(s)
            </button>
          <?php } ?>
        <?php echo form_close(); ?>
      </div></div>
    </div>

    <div class="col-md-5">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">
          Quem vai receber
          <small class="text-muted"><?php echo count($clientes); ?></small>
        </h4>
        <hr>
        <?php if (empty($clientes)) { ?>
          <p class="text-muted">Ninguém.</p>
        <?php } else { ?>
          <table class="table table-condensed">
            <tbody>
            <?php
            $sem_email = 0;
foreach ($clientes as $c) {
    $valido = $c['email'] && filter_var($c['email'], FILTER_VALIDATE_EMAIL);
    if (!$valido) {
        $sem_email++;
    } ?>
              <tr <?php echo $valido ? '' : 'class="danger"'; ?>>
                <td>
                  <strong><?php echo html_escape($c['company']); ?></strong>
                  <br><small class="text-muted">
                    <?php echo html_escape((string) $c['empreendimentos']); ?>
                    · <?php echo html_escape((string) $c['unidades']); ?>
                  </small>
                </td>
                <td style="font-size:12px;">
                  <?php echo $valido
                      ? html_escape($c['email'])
                      : '<span class="text-danger">sem email — não recebe</span>'; ?>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
          <?php if ($sem_email) { ?>
            <div class="alert alert-warning" style="font-size:13px;">
              <strong><?php echo $sem_email; ?></strong> sem email válido.
              Ficam registados como falhados no registo de envios, para não
              desaparecerem em silêncio.
            </div>
          <?php } ?>
        <?php } ?>
      </div></div>
    </div>

  </div>
</div></div>
<?php init_tail(); ?>
</body></html>
