<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('_topo', ['titulo' => $titulo]); ?>

<a class="btn btn-a" style="margin-bottom:16px;"
   href="<?php echo admin_url('dps_reunioes/agenda'); ?>">
  Marcar com um colega
</a>

<?php if (empty($reunioes)) { ?>
  <div class="vazio">Nenhuma reunião marcada.</div>
<?php } else { ?>
  <?php
  $dia_anterior = null;
  foreach ($reunioes as $r) {
      $t   = strtotime($r['data_hora']);
      $dia = date('Y-m-d', $t);

      if ($dia !== $dia_anterior) {
          $dia_anterior = $dia;
          $etiqueta = $dia === date('Y-m-d')
              ? 'Hoje'
              : ($dia === date('Y-m-d', strtotime('+1 day'))
                  ? 'Amanhã'
                  : date('d/m', $t));
          echo '<div class="titulo-seccao">' . $etiqueta . '</div>';
      }
      ?>
    <div class="cartao">
      <div class="linha">
        <div style="min-width:0;">
          <div class="nome"><?php echo html_escape($r['assunto'] ?: $r['cliente_nome']); ?></div>
          <div class="sub">
            <?php echo date('H:i', $t); ?> · <?php echo (int) $r['duracao_min']; ?> min
            <?php if (!empty($r['anfitriao'])) { ?> · <?php echo html_escape($r['anfitriao']); ?><?php } ?>
          </div>
        </div>
      </div>
      <?php if (!empty($r['link'])) { ?>
        <a class="btn btn-a" style="margin-top:12px;"
           href="<?php echo html_escape($r['link']); ?>" target="_blank" rel="noopener">Entrar na sala</a>
      <?php } ?>
    </div>
  <?php } ?>
<?php } ?>

<?php $this->load->view('_fundo', ['aba' => $aba]); ?>
