<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Sofia Calls — Definições</h4>
            <hr>

            <?php if ($chave_definida) { ?>
            <div class="alert alert-success">
              Chave da ElevenLabs gravada (<?= (int) $chave_tamanho; ?> caracteres, termina em
              <strong><?= e($chave_fim); ?></strong>). Escreva uma nova para a substituir.
            </div>
            <?php } else { ?>
            <div class="alert alert-warning">
              <strong>Sem chave.</strong> As chamadas da Sofia não funcionam até a escrever aqui.
            </div>
            <?php } ?>

            <?= form_open(admin_url('dps_sofia_calls/definicoes')); ?>
              <div class="form-group">
                <label for="api_key">Chave da API ElevenLabs</label>
                <input type="password" class="form-control" name="api_key" id="api_key"
                       autocomplete="new-password" placeholder="colar aqui a chave nova">
                <p class="text-muted" style="margin-top:6px;">
                  Fica guardada na base de dados, não no código. Deixe em branco para não alterar.
                </p>
              </div>

              <div class="form-group">
                <label for="phone_number_id">ID do número de telefone (ElevenLabs)</label>
                <input type="text" class="form-control" name="phone_number_id" id="phone_number_id"
                       value="<?= e($phone_number_id); ?>">
              </div>

              <div class="form-group">
                <label for="simultaneas">Chamadas em simultâneo por campanha</label>
                <input type="number" min="1" max="10" class="form-control" name="simultaneas"
                       id="simultaneas" value="<?= (int) $simultaneas; ?>" style="max-width:120px;">
                <p class="text-muted" style="margin-top:6px;">
                  Quantas chamadas a Sofia tem em curso ao mesmo tempo. Com 1, uma campanha
                  de 68 contactos leva onze horas — o cron só corre de 10 em 10 minutos.
                  Três é um bom ponto de partida.
                </p>
              </div>

              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="<?= admin_url('dps_sofia_calls'); ?>" class="btn btn-default">Voltar</a>
            <?= form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
