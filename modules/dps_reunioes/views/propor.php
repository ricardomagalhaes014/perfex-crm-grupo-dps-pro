<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Propor reuniões em massa</h4>
            <p class="text-muted" style="font-size:13px;">
              Escolha um estado de leads e um dia. Cada lead recebe um horário diferente,
              de 30 em 30 minutos, e um link para confirmar. A reunião só é marcada quando o
              cliente aceita.
            </p>
            <hr>

            <?= form_open(); ?>
              <div class="form-group">
                <label for="lead_status_id">Estado das leads</label>
                <select name="lead_status_id" id="lead_status_id" class="form-control" required>
                  <option value="">— escolher —</option>
                  <?php foreach ($estados as $e) { ?>
                  <option value="<?= (int) $e['id']; ?>"><?= e($e['name']); ?></option>
                  <?php } ?>
                </select>
                <p class="text-muted" style="font-size:11px;">
                  Só entram leads com telefone ou email. As de outros comerciais não são tocadas.
                </p>
              </div>

              <div class="form-group">
                <label for="dia_inicio">Primeiro dia</label>
                <input type="date" name="dia_inicio" id="dia_inicio" class="form-control" required
                       min="<?= date('Y-m-d'); ?>" value="<?= date('Y-m-d', strtotime('+1 day')); ?>">
                <p class="text-muted" style="font-size:11px;">
                  São 21 horários por dia (<?= e(get_option('dps_reunioes_hora_inicio')); ?> às
                  <?= e(get_option('dps_reunioes_hora_fim')); ?>). Se houver mais leads,
                  continua nos dias úteis seguintes. Fins-de-semana são saltados.
                </p>
              </div>

              <div class="form-group">
                <label for="canal">Enviar por</label>
                <select name="canal" id="canal" class="form-control">
                  <option value="ambos">WhatsApp e email</option>
                  <option value="whatsapp">Só WhatsApp</option>
                  <option value="email">Só email</option>
                </select>
                <p class="text-muted" style="font-size:11px;">
                  O WhatsApp sai do seu número, no máximo
                  <?= (int) get_option('dps_reunioes_wa_por_dia'); ?> por dia. O que passar disso
                  sai no dia seguinte, sozinho.
                </p>
              </div>

              <hr>
              <button type="submit" class="btn btn-primary">Criar propostas</button>
              <a href="<?= admin_url('dps_reunioes/propostas'); ?>" class="btn btn-default">Ver campanhas</a>
            <?= form_close(); ?>
          </div>
        </div>
      </div>

      <div class="col-md-5">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-margin">O que o cliente recebe</h5>
            <hr>
            <div style="background:#f7f8fa; padding:12px; border-radius:6px; font-size:13px; white-space:pre-wrap;"><?= e(strtr(
                get_option('dps_reunioes_texto_convite') ?: dps_reunioes_texto_convite_por_omissao(),
                [
                    '{nome}'      => 'João Silva',
                    '{comercial}' => get_staff_full_name(get_staff_user_id()),
                    '{quando}'    => dps_reunioes_quando_extenso(date('Y-m-d 15:00:00', strtotime('+2 days'))),
                    '{link}'      => '[link para confirmar]',
                ]
            )); ?></div>
            <p class="text-muted" style="font-size:11px; margin-top:8px;">
              O texto altera-se em Reuniões online → Definições. Marcas disponíveis:
              <code>{nome}</code> <code>{comercial}</code> <code>{quando}</code> <code>{link}</code>
            </p>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-margin">Depois de aceitarem</h5>
            <hr>
            <ul class="text-muted" style="font-size:13px; padding-left:18px;">
              <li>A reunião entra na sua agenda e na do cliente</li>
              <li>É criada a sala de vídeo e enviado o link</li>
              <li>30 minutos antes, os dois são avisados pelo seu WhatsApp</li>
              <li>No fim, nasce uma tarefa de seguimento</li>
            </ul>
            <p class="text-muted" style="font-size:11px;">
              Quem não responder até à hora proposta liberta o horário, e pode ser proposto
              outra vez noutra campanha.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
