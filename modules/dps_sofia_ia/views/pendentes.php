<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Perguntas à espera da administração</h4>
            <p class="text-muted" style="font-size:13px;">
              Cada resposta dada aqui passa a fazer parte da base de conhecimento. É assim que a
              Sofia aprende: o mesmo comercial — e todos os outros — deixam de precisar de perguntar.
            </p>
            <hr>

            <ul class="nav nav-tabs" style="margin-bottom:16px;">
              <?php foreach (['aberta' => 'Por responder', 'respondida' => 'Respondidas', 'ignorada' => 'Ignoradas', 'todos' => 'Todas'] as $chave => $nome) { ?>
              <li class="<?= $estado === $chave ? 'active' : ''; ?>">
                <a href="<?= admin_url('dps_sofia_ia/pendentes/' . $chave); ?>"><?= $nome; ?></a>
              </li>
              <?php } ?>
            </ul>

            <?php if (empty($pendentes)) { ?>
            <div class="alert alert-info">Nada aqui.</div>
            <?php } else { ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th style="width:110px;">Tipo</th>
                  <th>Pergunta</th>
                  <th style="width:150px;">Comercial</th>
                  <th style="width:140px;">Quando</th>
                  <th style="width:170px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendentes as $pendente) { ?>
                <tr>
                  <td>
                    <?php if ($pendente['tipo'] === 'reporte') { ?>
                    <span class="label label-danger">Resposta errada</span>
                    <?php } else { ?>
                    <span class="label label-warning">Não soube</span>
                    <?php } ?>
                  </td>
                  <td>
                    <?= e($pendente['pergunta']); ?>
                    <?php if (!empty($pendente['nota'])) { ?>
                    <div class="text-muted" style="font-size:12px; margin-top:4px;">
                      <strong>Nota do comercial:</strong> <?= e($pendente['nota']); ?>
                    </div>
                    <?php } ?>
                  </td>
                  <td><?= e($pendente['comercial']); ?></td>
                  <td><span class="text-muted" style="font-size:12px;"><?= _dt($pendente['dateadded']); ?></span></td>
                  <td class="text-right">
                    <?php if ($pendente['estado'] === 'aberta') { ?>
                    <a href="<?= admin_url('dps_sofia_ia/responder/' . $pendente['id']); ?>" class="btn btn-primary btn-xs">Responder</a>
                    <a href="<?= admin_url('dps_sofia_ia/ignorar/' . $pendente['id']); ?>" class="btn btn-default btn-xs"
                       onclick="return confirm('Ignorar esta pergunta?');">Ignorar</a>
                    <?php } else {
                        list($etiqueta, $cor) = dps_sofia_ia_etiqueta_estado($pendente['estado']); ?>
                    <span class="label label-<?= $cor; ?>"><?= $etiqueta; ?></span>
                    <?php if (!empty($pendente['conhecimento_id'])) { ?>
                    <a href="<?= admin_url('dps_sofia_ia/ficha/' . $pendente['conhecimento_id']); ?>" class="btn btn-default btn-xs">Ver ficha</a>
                    <?php } ?>
                    <?php } ?>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
