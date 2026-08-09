<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="clearfix" style="margin-bottom:12px;">
              <h4 class="no-margin pull-left">Base de conhecimento da Sofia</h4>
              <a href="<?= admin_url('dps_sofia_ia/ficha'); ?>" class="btn btn-primary btn-sm pull-right">Escrever conhecimento</a>
            </div>
            <p class="text-muted" style="font-size:13px;">
              É daqui que saem as respostas. O que não estiver aqui, a Sofia não sabe — e diz que não sabe,
              em vez de inventar.
            </p>
            <hr>

            <?php if (empty($conhecimentos)) { ?>
            <div class="alert alert-info">
              Ainda não há nada carregado. Comece por importar da Sofia das chamadas, ou carregue
              um PDF com a tabela de preços.
            </div>
            <?php } else { ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Título</th>
                  <th>Categoria</th>
                  <th>Origem</th>
                  <th class="text-center">Sempre</th>
                  <th class="text-center">Activo</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php $categorias = dps_sofia_ia_categorias(); ?>
                <?php foreach ($conhecimentos as $ficha) { ?>
                <tr>
                  <td>
                    <a href="<?= admin_url('dps_sofia_ia/ficha/' . $ficha['id']); ?>"><?= e($ficha['titulo']); ?></a>
                    <div class="text-muted" style="font-size:11px;">
                      <?= (int) mb_strlen($ficha['conteudo']); ?> caracteres
                    </div>
                  </td>
                  <td><?= isset($categorias[$ficha['categoria']]) ? e($categorias[$ficha['categoria']]) : '—'; ?></td>
                  <td><span class="text-muted" style="font-size:12px;"><?= e($ficha['fonte']); ?></span></td>
                  <td class="text-center"><?= $ficha['sempre_incluir'] ? '<i class="fa fa-check text-success"></i>' : '—'; ?></td>
                  <td class="text-center"><?= $ficha['ativo'] ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'; ?></td>
                  <td class="text-right">
                    <a href="<?= admin_url('dps_sofia_ia/apagar_ficha/' . $ficha['id']); ?>"
                       class="btn btn-danger btn-xs _delete"
                       onclick="return confirm('Apagar este conhecimento? A Sofia deixa de o saber.');">
                      <i class="fa fa-remove"></i>
                    </a>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-margin">Carregar um documento</h5>
            <hr>
            <?= form_open_multipart(admin_url('dps_sofia_ia/importar_ficheiro')); ?>
              <div class="form-group">
                <label for="documento">Ficheiro (PDF, Word, texto)</label>
                <input type="file" name="documento" id="documento" class="form-control"
                       accept=".pdf,.docx,.txt,.md,.csv" required>
                <p class="text-muted" style="font-size:11px; margin-top:6px;">
                  PDFs digitalizados (fotografias de páginas) não têm texto legível.
                  Nesses casos copie o texto e escreva-o à mão.
                </p>
              </div>
              <div class="form-group">
                <label for="titulo">Título (opcional)</label>
                <input type="text" name="titulo" id="titulo" class="form-control"
                       placeholder="fica com o nome do ficheiro">
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
                <p class="text-muted" style="font-size:11px;">
                  Só para o essencial (preços em vigor, regras de comissão). Cada ficha marcada
                  assim é enviada em todas as perguntas e faz subir o custo.
                </p>
              </div>
              <button type="submit" class="btn btn-primary btn-block">Carregar e ler</button>
            <?= form_close(); ?>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-margin">Importar</h5>
            <hr>
            <a href="<?= admin_url('dps_sofia_ia/importar_elevenlabs'); ?>" class="btn btn-default btn-block btn-sm">
              Trazer da Sofia das chamadas
            </a>
            <p class="text-muted" style="font-size:11px;">
              Traz as instruções e os documentos do agente da ElevenLabs, usando a chave já guardada
              em Sofia Calls. Importar outra vez actualiza as fichas em vez de as duplicar.
            </p>
            <hr>
            <a href="<?= admin_url('dps_sofia_ia/reindexar'); ?>" class="btn btn-default btn-block btn-sm">
              Reconstruir o índice
            </a>
            <p class="text-muted" style="font-size:11px;">
              Só é preciso se a Sofia deixar de encontrar coisas que estão claramente carregadas.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
