<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">

  <div class="row"><div class="col-md-12">
    <?php $this->load->view('_barra', ['atalho' => 'equipa']); ?>
  </div></div>
  <div class="row">

    <div class="col-md-7">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Reunião de equipa</h4>
        <p class="text-muted" style="font-size:13px;">
          Uma sala online para dentro de casa. Convide a equipa toda ou só quem interessa —
          cada pessoa recebe aviso no CRM e no WhatsApp, com o link da sala.
        </p>
        <hr>

        <?php echo form_open(admin_url('dps_reunioes/equipa')); ?>

          <label class="control-label">Assunto</label>
          <input type="text" name="assunto" class="form-control" maxlength="180"
                 placeholder="Ex.: ponto de situação semanal">

          <div class="row mtop15">
            <div class="col-md-5">
              <label class="control-label">Data</label>
              <input type="date" name="data" class="form-control" required
                     min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
              <label class="control-label">Hora</label>
              <input type="time" name="hora" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="control-label">Duração</label>
              <select name="duracao_min" class="form-control">
                <?php foreach ([30, 45, 60, 90, 120] as $m) { ?>
                  <option value="<?php echo $m; ?>" <?php echo $m === 45 ? 'selected' : ''; ?>>
                    <?php echo $m; ?> minutos
                  </option>
                <?php } ?>
              </select>
            </div>
          </div>

          <hr>
          <label class="control-label">Quem convidar</label>

          <div class="radio radio-primary">
            <input type="radio" name="alcance" id="alc-todos" value="todos" checked>
            <label for="alc-todos">
              <strong>A equipa toda</strong>
              <span class="text-muted">(<?php echo count($equipa); ?> pessoas)</span>
            </label>
          </div>
          <div class="radio radio-primary">
            <input type="radio" name="alcance" id="alc-alguns" value="alguns">
            <label for="alc-alguns"><strong>Só algumas pessoas</strong></label>
          </div>

          <div id="dps-lista" style="display:none;border:1px solid #eee;border-radius:4px;
                                     padding:12px;max-height:280px;overflow:auto;">
            <?php foreach ($equipa as $p) { ?>
              <div class="checkbox checkbox-primary" style="margin:4px 0;">
                <input type="checkbox" name="participantes[]" value="<?php echo (int) $p['staffid']; ?>"
                       id="p<?php echo (int) $p['staffid']; ?>">
                <label for="p<?php echo (int) $p['staffid']; ?>"><?php echo html_escape($p['nome']); ?></label>
              </div>
            <?php } ?>
          </div>

          <hr>
          <button type="submit" class="btn btn-info btn-lg">
            <i class="fa fa-video-camera"></i> Marcar reunião de equipa
          </button>
        <?php echo form_close(); ?>
      </div></div>
    </div>

    <div class="col-md-5">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">As minhas reuniões internas</h4>
        <hr>

        <?php if (empty($internas)) { ?>
          <p class="text-muted" style="font-size:13px;">Nenhuma marcada.</p>
        <?php } else { ?>
          <table class="table table-condensed" style="font-size:13px;">
            <tbody>
            <?php foreach ($internas as $r) { ?>
              <tr>
                <td>
                  <strong><?php echo date('d/m', strtotime($r['data_hora'])); ?></strong>
                  <?php echo date('H:i', strtotime($r['data_hora'])); ?>
                </td>
                <td>
                  <a href="<?php echo admin_url('dps_reunioes/ver/' . (int) $r['id']); ?>">
                    <?php echo html_escape($r['assunto']); ?>
                  </a>
                  <br><small class="text-muted">
                    por <?php echo html_escape((string) $r['anfitriao']); ?>
                  </small>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        <?php } ?>

        <hr>
        <a href="<?php echo admin_url('dps_reunioes/agenda'); ?>" class="btn btn-default btn-block btn-sm">
          <i class="fa fa-calendar-check-o"></i> Marcar com uma pessoa só
        </a>
      </div></div>
    </div>

  </div>
</div></div>

<script>
(function () {
    var lista = document.getElementById('dps-lista');
    var radios = document.querySelectorAll('input[name="alcance"]');

    Array.prototype.forEach.call(radios, function (r) {
        r.addEventListener('change', function () {
            lista.style.display = (r.value === 'alguns' && r.checked) ? '' : 'none';
        });
    });
})();
</script>
<?php init_tail(); ?>
</body></html>
