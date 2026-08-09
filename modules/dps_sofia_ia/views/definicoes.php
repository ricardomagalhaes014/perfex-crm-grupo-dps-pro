<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-9 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Sofia IA — Definições</h4>
            <hr>

            <?= form_open(); ?>
              <?php $fornecedor = get_option('dps_sofia_ia_fornecedor'); ?>
              <div class="form-group">
                <label for="fornecedor">Como a Sofia responde</label>
                <select name="fornecedor" id="fornecedor" class="form-control">
                  <option value="claude" <?= $fornecedor === 'claude' ? 'selected' : ''; ?>>Claude (Anthropic) — responde por palavras próprias</option>
                  <option value="openai" <?= $fornecedor === 'openai' ? 'selected' : ''; ?>>OpenAI — responde por palavras próprias</option>
                  <option value="local"  <?= $fornecedor === 'local'  ? 'selected' : ''; ?>>Sem IA — procura interna, sem custos</option>
                </select>
                <p class="text-muted" style="font-size:12px; margin-top:6px;">
                  <strong>Sem IA</strong> não precisa de chave nem de conta em lado nenhum e não tem custo:
                  a Sofia procura as palavras da pergunta na base de conhecimento e mostra os textos onde
                  elas aparecem. Não redige, não resume e não percebe sinónimos — quem escrever
                  "está caro" não encontra a ficha que fala em "preço".
                  <br><br>
                  A base de conhecimento é a mesma nos dois modos, por isso pode começar sem IA e
                  ligar a chave mais tarde sem perder nada do que carregou.
                </p>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="api_key_claude">Chave da API Claude</label>
                    <?php if ($chave_pt) { ?>
                    <p class="text-success" style="font-size:12px;">
                      Guardada (<?= (int) $chave_pt['tamanho']; ?> caracteres, termina em <strong><?= e($chave_pt['fim']); ?></strong>).
                    </p>
                    <?php } ?>
                    <input type="password" name="api_key_claude" id="api_key_claude" class="form-control"
                           autocomplete="new-password" placeholder="deixe em branco para não alterar">
                    <p class="text-muted" style="font-size:11px;">Obtém-se em console.anthropic.com.</p>
                  </div>
                  <div class="form-group">
                    <label for="modelo">Modelo Claude</label>
                    <select name="modelo" id="modelo" class="form-control">
                      <?php foreach (dps_sofia_ia_modelos_claude() as $chave => $nome) { ?>
                      <option value="<?= e($chave); ?>" <?= get_option('dps_sofia_ia_modelo') === $chave ? 'selected' : ''; ?>>
                        <?= e($nome); ?>
                      </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="api_key_openai">Chave da API OpenAI</label>
                    <?php if ($chave_oai) { ?>
                    <p class="text-success" style="font-size:12px;">
                      Guardada (<?= (int) $chave_oai['tamanho']; ?> caracteres, termina em <strong><?= e($chave_oai['fim']); ?></strong>).
                    </p>
                    <?php } ?>
                    <input type="password" name="api_key_openai" id="api_key_openai" class="form-control"
                           autocomplete="new-password" placeholder="deixe em branco para não alterar">
                  </div>
                  <div class="form-group">
                    <label for="modelo_openai">Modelo OpenAI</label>
                    <select name="modelo_openai" id="modelo_openai" class="form-control">
                      <?php foreach (dps_sofia_ia_modelos_openai() as $chave => $nome) { ?>
                      <option value="<?= e($chave); ?>" <?= get_option('dps_sofia_ia_modelo_openai') === $chave ? 'selected' : ''; ?>>
                        <?= e($nome); ?>
                      </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <hr>

              <div class="form-group">
                <label for="limite_hora">Perguntas por comercial, por hora</label>
                <input type="number" name="limite_hora" id="limite_hora" class="form-control" min="0"
                       value="<?= (int) get_option('dps_sofia_ia_limite_hora'); ?>">
                <p class="text-muted" style="font-size:11px;">
                  Trava de segurança para a factura da API. 0 desliga o limite.
                </p>
              </div>

              <div class="form-group">
                <label for="notificar_staff">Quem recebe os avisos</label>
                <select name="notificar_staff[]" id="notificar_staff" class="form-control selectpicker"
                        multiple data-live-search="true" data-none-selected-text="todos os administradores">
                  <?php $escolhidos = array_filter(explode(',', (string) get_option('dps_sofia_ia_notificar_staff'))); ?>
                  <?php foreach ($staff as $membro) { ?>
                  <option value="<?= (int) $membro['staffid']; ?>"
                          <?= in_array($membro['staffid'], $escolhidos) ? 'selected' : ''; ?>>
                    <?= e($membro['firstname'] . ' ' . $membro['lastname']); ?>
                  </option>
                  <?php } ?>
                </select>
                <p class="text-muted" style="font-size:11px;">
                  Em branco: todos os administradores activos.
                </p>
              </div>

              <div class="form-group">
                <label for="persona">Instruções da Sofia</label>
                <textarea name="persona" id="persona" class="form-control" rows="14"><?= e(get_option('dps_sofia_ia_persona')); ?></textarea>
                <p class="text-muted" style="font-size:11px;">
                  Definem o tom e as regras. A regra de não inventar números é a mais importante:
                  sem ela, um modelo confrontado com uma lacuna preenche-a com um valor plausível.
                </p>
              </div>

              <hr>
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="<?= admin_url('dps_sofia_ia'); ?>" class="btn btn-default">Voltar</a>
            <?= form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
