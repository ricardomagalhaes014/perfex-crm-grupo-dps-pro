<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-9 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">
              <?= $pendente['tipo'] === 'reporte' ? 'Corrigir uma resposta da Sofia' : 'Responder a uma pergunta'; ?>
            </h4>
            <hr>

            <div class="well" style="background:#f7f8fa;">
              <div><strong>Pergunta de <?= e($pendente['comercial']); ?>:</strong></div>
              <div style="margin-top:6px;"><?= nl2br(e($pendente['pergunta'])); ?></div>
            </div>

            <?php if ($pendente['tipo'] === 'reporte') { ?>
            <div class="alert alert-danger">
              <strong>A Sofia respondeu isto, e está errado:</strong>
              <div style="margin-top:8px;"><?= nl2br(e((string) $pendente['resposta_sofia'])); ?></div>
              <?php if (!empty($pendente['nota'])) { ?>
              <hr style="margin:10px 0;">
              <strong>O que o comercial diz que está mal:</strong>
              <div style="margin-top:6px;"><?= nl2br(e($pendente['nota'])); ?></div>
              <?php } ?>
            </div>
            <p class="text-muted" style="font-size:13px;">
              A resposta certa que escrever aqui entra na base de conhecimento. Se a resposta errada
              veio de uma ficha desactualizada, vale a pena ir corrigir ou desactivar essa ficha
              também — senão a informação errada continua lá.
            </p>
            <?php } ?>

            <?= form_open(); ?>
              <div class="form-group">
                <label for="resposta">Resposta certa</label>
                <textarea name="resposta" id="resposta" class="form-control" rows="10" required
                          placeholder="Escreva como explicaria a um comercial novo. É este texto que a Sofia vai usar."></textarea>
              </div>

              <div class="form-group">
                <label for="titulo">Título da ficha de conhecimento</label>
                <input type="text" name="titulo" id="titulo" class="form-control"
                       placeholder="em branco: fica com a própria pergunta">
              </div>

              <div class="form-group">
                <label for="categoria">Categoria</label>
                <select name="categoria" id="categoria" class="form-control">
                  <?php foreach (dps_sofia_ia_categorias() as $chave => $nome) { ?>
                  <option value="<?= e($chave); ?>"><?= e($nome); ?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="checkbox">
                <input type="checkbox" name="sempre_incluir" id="sempre_incluir" value="1">
                <label for="sempre_incluir">Incluir em todas as respostas</label>
              </div>

              <hr>
              <button type="submit" class="btn btn-primary">Guardar e ensinar a Sofia</button>
              <a href="<?= admin_url('dps_sofia_ia/pendentes'); ?>" class="btn btn-default">Voltar</a>
            <?= form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
