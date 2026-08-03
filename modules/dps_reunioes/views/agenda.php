<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$dias_nome = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira',
              4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 7 => 'Domingo'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row">

    <div class="col-md-3">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Agenda partilhada</h4>
        <p class="text-muted" style="font-size:13px;">
          Escolha com quem quer reunir e carregue numa hora livre. Fica marcado na hora,
          com sala online, sem ter de perguntar se pode.
        </p>
        <hr>

        <?php if (empty($agendas)) { ?>
          <p class="text-muted" style="font-size:13px;">
            Ninguém publicou a agenda ainda.
          </p>
        <?php } else { ?>
          <div class="list-group" style="margin-bottom:0;">
            <?php foreach ($agendas as $a) { ?>
              <a href="<?php echo admin_url('dps_reunioes/agenda/' . (int) $a['staff_id']); ?>"
                 class="list-group-item <?php echo (int) $a['staff_id'] === (int) $staff_id ? 'active' : ''; ?>">
                <strong><?php echo html_escape($a['nome']); ?></strong>
                <br><small><?php echo (int) $a['duracao_min']; ?> min por reunião</small>
              </a>
            <?php } ?>
          </div>
        <?php } ?>

        <hr>
        <a href="<?php echo admin_url('dps_reunioes/disponibilidade'); ?>" class="btn btn-default btn-block btn-sm">
          <i class="fa fa-calendar"></i> A minha disponibilidade
        </a>
        <a href="<?php echo admin_url('dps_reunioes/equipa'); ?>" class="btn btn-default btn-block btn-sm">
          <i class="fa fa-users"></i> Reunião de equipa
        </a>
      </div></div>
    </div>

    <div class="col-md-9">
      <div class="panel_s"><div class="panel-body">

        <?php if (!$staff_id) { ?>
          <p class="text-muted">Escolha uma pessoa à esquerda.</p>

        <?php } else { ?>

          <h4 class="no-margin">
            Horários livres — <?php echo html_escape($dono['nome']); ?>
          </h4>
          <p class="text-muted" style="font-size:13px;">
            Reuniões de <?php echo (int) $regras['duracao_min']; ?> minutos.
            <?php if (!empty($regras['nota'])) { ?>
              <br><em><?php echo html_escape($regras['nota']); ?></em>
            <?php } ?>
          </p>
          <hr>

          <?php if (empty($livres)) { ?>
            <div class="alert alert-warning">
              Não há horários livres nas próximas semanas.
              Fale directamente com <?php echo html_escape(explode(' ', $dono['nome'])[0]); ?>.
            </div>
          <?php } else { ?>

            <?php echo form_open(admin_url('dps_reunioes/agendar'), ['id' => 'dps-form-agendar']); ?>
            <input type="hidden" name="staff_id" value="<?php echo (int) $staff_id; ?>">
            <input type="hidden" name="inicio" id="dps-inicio">

            <label class="control-label">Assunto</label>
            <input type="text" name="assunto" class="form-control" maxlength="180"
                   placeholder="Sobre o que é a reunião — ajuda quem a vai ter.">

            <hr>

            <?php foreach ($livres as $data => $slots) { ?>
              <p style="margin-bottom:6px;">
                <strong><?php echo $dias_nome[(int) date('N', strtotime($data))]; ?></strong>
                <span class="text-muted"><?php echo date('d/m/Y', strtotime($data)); ?></span>
              </p>
              <p style="margin-bottom:16px;">
                <?php foreach ($slots as $s) { ?>
                  <button type="button" class="btn btn-default btn-sm dps-slot"
                          data-inicio="<?php echo (int) $s['inicio']; ?>"
                          data-quando="<?php echo date('d/m', $s['inicio']) . ' às ' . $s['hhmm']; ?>"
                          style="margin:0 4px 6px 0;">
                    <?php echo $s['hhmm']; ?>
                  </button>
                <?php } ?>
              </p>
            <?php } ?>

            <?php echo form_close(); ?>

          <?php } ?>
        <?php } ?>

      </div></div>
    </div>

  </div>
</div></div>

<script>
(function () {
    var botoes = document.querySelectorAll('.dps-slot');
    var campo  = document.getElementById('dps-inicio');
    var form   = document.getElementById('dps-form-agendar');

    if (!botoes.length || !form) { return; }

    Array.prototype.forEach.call(botoes, function (b) {
        b.addEventListener('click', function () {
            if (!confirm('Marcar reunião em ' + b.getAttribute('data-quando') + '?')) { return; }

            campo.value = b.getAttribute('data-inicio');

            // Desligar tudo evita o duplo clique nervoso, que marcava duas
            // reuniões seguidas antes de a página mudar.
            Array.prototype.forEach.call(botoes, function (o) { o.disabled = true; });
            b.innerHTML = 'A marcar...';

            form.submit();
        });
    });
})();
</script>
<?php init_tail(); ?>
</body></html>
