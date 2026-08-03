<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$dias_nome = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta',
              5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'];

// As linhas que o formulário mostra: as que existem, mais uma vazia por dia
// útil sem nada — assim abre-se a página e escreve-se logo, sem carregar
// primeiro num "adicionar".
$linhas = [];
foreach ($dias_nome as $d => $nome) {
    if (!empty($horario[$d])) {
        foreach ($horario[$d] as $b) {
            $linhas[] = [$d, substr($b['hora_inicio'], 0, 5), substr($b['hora_fim'], 0, 5)];
        }
    }
    $linhas[] = [$d, '', ''];
}
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">

  <div class="row"><div class="col-md-12">
    <?php $this->load->view('_barra', ['atalho' => 'disponibilidade']); ?>
  </div></div>

  <div class="row">
    <div class="col-md-12">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">A minha disponibilidade</h4>
        <p class="text-muted" style="font-size:13px;margin-bottom:0;">
          Defina uma vez os dias e horas em que aceita reuniões. Os colegas passam a ver
          só esses horários e marcam sozinhos — sem trocar mensagens a perguntar se pode.
          Reuniões que já tenha na agenda desaparecem da lista automaticamente.
        </p>
      </div></div>
    </div>
  </div>

  <?php echo form_open(admin_url('dps_reunioes/disponibilidade')); ?>
  <input type="hidden" name="accao" value="guardar">

  <div class="row">

    <div class="col-md-7">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Horário semanal</h4>
        <p class="text-muted" style="font-size:13px;">
          Deixe em branco os dias em que não quer ser incomodado.
          Pode ter dois períodos no mesmo dia (manhã e tarde).
        </p>
        <hr>

        <table class="table table-condensed" style="font-size:13px;">
          <thead>
            <tr><th style="width:130px;">Dia</th><th>Das</th><th>Às</th></tr>
          </thead>
          <tbody id="dps-horario">
            <?php foreach ($linhas as $i => $l) { ?>
              <tr>
                <td>
                  <input type="hidden" name="dia_semana[]" value="<?php echo (int) $l[0]; ?>">
                  <strong><?php echo $dias_nome[$l[0]]; ?></strong>
                </td>
                <td><input type="time" name="hora_inicio[]" class="form-control input-sm"
                           value="<?php echo html_escape($l[1]); ?>"></td>
                <td><input type="time" name="hora_fim[]" class="form-control input-sm"
                           value="<?php echo html_escape($l[2]); ?>"></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div></div>
    </div>

    <div class="col-md-5">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Regras</h4>
        <hr>

        <div class="checkbox checkbox-primary">
          <input type="checkbox" name="publicada" id="publicada" value="1"
                 <?php echo !empty($partilha['publicada']) ? 'checked' : ''; ?>>
          <label for="publicada"><strong>Publicar a agenda aos colegas</strong></label>
        </div>
        <p class="text-muted" style="font-size:12px;">
          Enquanto estiver desligada, ninguém vê os seus horários nem lhe pode marcar nada.
        </p>

        <label class="control-label mtop15">Duração de cada reunião</label>
        <select name="duracao_min" class="form-control">
          <?php foreach ([15, 20, 30, 45, 60, 90] as $m) { ?>
            <option value="<?php echo $m; ?>"
              <?php echo (int) $partilha['duracao_min'] === $m ? 'selected' : ''; ?>>
              <?php echo $m; ?> minutos
            </option>
          <?php } ?>
        </select>

        <label class="control-label mtop15">Intervalo entre reuniões</label>
        <select name="intervalo_min" class="form-control">
          <?php foreach ([0 => 'Sem intervalo', 5 => '5 minutos', 10 => '10 minutos', 15 => '15 minutos'] as $m => $t) { ?>
            <option value="<?php echo $m; ?>"
              <?php echo (int) $partilha['intervalo_min'] === $m ? 'selected' : ''; ?>>
              <?php echo $t; ?>
            </option>
          <?php } ?>
        </select>

        <label class="control-label mtop15">Antecedência mínima</label>
        <select name="antecedencia_h" class="form-control">
          <?php foreach ([0 => 'Nenhuma', 1 => '1 hora', 2 => '2 horas', 4 => '4 horas',
                          24 => '1 dia', 48 => '2 dias'] as $h => $t) { ?>
            <option value="<?php echo $h; ?>"
              <?php echo (int) $partilha['antecedencia_h'] === $h ? 'selected' : ''; ?>>
              <?php echo $t; ?>
            </option>
          <?php } ?>
        </select>
        <span class="help-block" style="font-size:12px;">
          Impede que lhe marquem uma reunião para daqui a dez minutos.
        </span>

        <label class="control-label">Até quando pode ser marcado</label>
        <select name="horizonte_dias" class="form-control">
          <?php foreach ([7 => '1 semana', 14 => '2 semanas', 21 => '3 semanas',
                          30 => '1 mês', 60 => '2 meses'] as $d => $t) { ?>
            <option value="<?php echo $d; ?>"
              <?php echo (int) $partilha['horizonte_dias'] === $d ? 'selected' : ''; ?>>
              <?php echo $t; ?>
            </option>
          <?php } ?>
        </select>

        <label class="control-label mtop15">Nota para os colegas (opcional)</label>
        <input type="text" name="nota" class="form-control" maxlength="255"
               value="<?php echo html_escape((string) $partilha['nota']); ?>"
               placeholder="Ex.: para assuntos de contratos, marquem de manhã.">

        <hr>
        <button type="submit" class="btn btn-info btn-block">
          <i class="fa fa-check"></i> Guardar disponibilidade
        </button>
      </div></div>
    </div>

  </div>
  <?php echo form_close(); ?>

  <div class="row">

    <div class="col-md-7">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Dias e horas bloqueados</h4>
        <p class="text-muted" style="font-size:13px;">
          Férias, um dia cheio, uma tarde que deixou de dar.
          Sem horas, bloqueia o dia inteiro.
        </p>
        <hr>

        <?php echo form_open(admin_url('dps_reunioes/disponibilidade'), ['class' => 'form-inline']); ?>
        <input type="hidden" name="accao" value="bloquear">
        <input type="date" name="data" class="form-control input-sm" required
               min="<?php echo date('Y-m-d'); ?>">
        <input type="time" name="bl_inicio" class="form-control input-sm" placeholder="das">
        <input type="time" name="bl_fim" class="form-control input-sm" placeholder="às">
        <input type="text" name="motivo" class="form-control input-sm" placeholder="motivo (opcional)">
        <button type="submit" class="btn btn-default btn-sm">Bloquear</button>
        <?php echo form_close(); ?>

        <?php if (empty($bloqueios)) { ?>
          <p class="text-muted mtop15" style="font-size:13px;">Nada bloqueado.</p>
        <?php } else { ?>
          <table class="table table-condensed mtop15" style="font-size:13px;">
            <tbody>
            <?php foreach ($bloqueios as $b) { ?>
              <tr>
                <td><strong><?php echo date('d/m/Y', strtotime($b['data'])); ?></strong></td>
                <td>
                  <?php echo $b['hora_inicio'] && $b['hora_fim']
                      ? substr($b['hora_inicio'], 0, 5) . ' — ' . substr($b['hora_fim'], 0, 5)
                      : '<span class="text-muted">dia inteiro</span>'; ?>
                </td>
                <td><?php echo html_escape((string) $b['motivo']); ?></td>
                <td class="text-right">
                  <a href="<?php echo admin_url('dps_reunioes/desbloquear/' . (int) $b['id']); ?>"
                     class="text-danger" onclick="return confirm('Remover este bloqueio?');">
                    <i class="fa fa-times"></i>
                  </a>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        <?php } ?>
      </div></div>
    </div>

    <div class="col-md-5">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">O que os colegas vão ver</h4>
        <p class="text-muted" style="font-size:13px;">
          Isto é a sua agenda vista de fora, agora mesmo.
        </p>
        <hr>

        <?php if (empty($partilha['publicada'])) { ?>
          <div class="alert alert-warning" style="font-size:13px;">
            A agenda está por publicar — ninguém vê nada.
          </div>
        <?php } elseif (empty($livres)) { ?>
          <div class="alert alert-warning" style="font-size:13px;">
            Não há um único horário livre. Confirme se o horário semanal está preenchido.
          </div>
        <?php } else { ?>
          <?php $mostrados = 0; ?>
          <?php foreach ($livres as $data => $slots) { ?>
            <?php if ($mostrados++ >= 5) { break; } ?>
            <p style="margin-bottom:4px;">
              <strong><?php echo $dias_nome[(int) date('N', strtotime($data))]; ?></strong>
              <span class="text-muted"><?php echo date('d/m', strtotime($data)); ?></span>
            </p>
            <p style="margin-bottom:12px;">
              <?php foreach ($slots as $s) { ?>
                <span class="label label-default" style="font-weight:400;margin-right:3px;">
                  <?php echo $s['hhmm']; ?>
                </span>
              <?php } ?>
            </p>
          <?php } ?>
          <?php if (count($livres) > 5) { ?>
            <p class="text-muted" style="font-size:12px;">
              ... e mais <?php echo count($livres) - 5; ?> dia(s).
            </p>
          <?php } ?>
        <?php } ?>
      </div></div>
    </div>

  </div>

</div></div>
<?php init_tail(); ?>
</body></html>
