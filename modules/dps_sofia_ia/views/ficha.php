<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-9 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin"><?= $ficha ? 'Editar conhecimento' : 'Escrever conhecimento'; ?></h4>
            <hr>

            <?php if ($ficha && $ficha['fonte'] === 'ficheiro' && trim($ficha['conteudo']) === '') { ?>
            <div class="alert alert-warning">
              Este documento foi carregado mas não deu texto legível — quase sempre porque é uma
              digitalização. Copie o texto do documento e cole-o aqui em baixo.
            </div>
            <?php } ?>

            <?= form_open(); ?>
              <div class="form-group">
                <label for="titulo">Título</label>
                <input type="text" name="titulo" id="titulo" class="form-control" required
                       value="<?= $ficha ? e($ficha['titulo']) : ''; ?>"
                       placeholder="ex.: Boavista Towers — tabela de preços 2026">
              </div>

              <div class="form-group">
                <label for="categoria">Categoria</label>
                <select name="categoria" id="categoria" class="form-control">
                  <?php foreach (dps_sofia_ia_categorias() as $chave => $nome) { ?>
                  <option value="<?= e($chave); ?>" <?= $ficha && $ficha['categoria'] === $chave ? 'selected' : ''; ?>>
                    <?= e($nome); ?>
                  </option>
                  <?php } ?>
                </select>
              </div>

              <div class="form-group">
                <label for="conteudo">Conteúdo</label>
                <textarea name="conteudo" id="conteudo" class="form-control" rows="22"
                          placeholder="Escreva como explicaria a um comercial novo."><?= $ficha ? e($ficha['conteudo']) : ''; ?></textarea>
                <p class="text-muted" style="font-size:12px; margin-top:6px;">
                  Separe assuntos diferentes por uma linha em branco. O texto é partido em trechos
                  e é por trecho que a Sofia procura — parágrafos bem separados dão respostas mais certeiras.
                </p>
              </div>

              <div class="checkbox">
                <input type="checkbox" name="sempre_incluir" id="sempre_incluir" value="1"
                       <?= $ficha && $ficha['sempre_incluir'] ? 'checked' : ''; ?>>
                <label for="sempre_incluir">Incluir em todas as respostas</label>
              </div>

              <div class="checkbox">
                <input type="checkbox" name="ativo" id="ativo" value="1"
                       <?= !$ficha || $ficha['ativo'] ? 'checked' : ''; ?>>
                <label for="ativo">Activo</label>
                <p class="text-muted" style="font-size:11px;">
                  Desligar é a forma de tirar de circulação uma tabela antiga sem a apagar.
                </p>
              </div>

              <hr>
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="<?= admin_url('dps_sofia_ia/conhecimento'); ?>" class="btn btn-default">Voltar</a>
            <?= form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
