<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/*
 * A barra de navegação do módulo, dentro da própria página.
 *
 * Existe porque os submenus da barra lateral NÃO são desenhados por este tema:
 * o Cláudio entrou em "Reuniões online" para publicar a agenda, viu "ainda não
 * há reuniões" e não tinha por onde lá chegar (03/08/2026). Uma entrada de
 * menu que depende do tema é uma entrada que um dia desaparece; um botão
 * dentro da página está sempre lá.
 */
$aqui = strtolower((string) ($atalho ?? ''));

$paginas = [
    ''                => ['Todas as reuniões',       'fa-video-camera'],
    'agenda'          => ['Agenda partilhada',       'fa-calendar-check-o'],
    'disponibilidade' => ['A minha disponibilidade', 'fa-clock-o'],
    'equipa'          => ['Reunião de equipa',       'fa-users'],
];
?>
<div class="panel_s"><div class="panel-body" style="padding:10px 14px;">
  <?php foreach ($paginas as $rota => $p) {
      $activo = $aqui === $rota; ?>
    <a href="<?php echo admin_url('dps_reunioes' . ($rota ? '/' . $rota : '')); ?>"
       class="btn <?php echo $activo ? 'btn-info' : 'btn-default'; ?>"
       style="margin:2px 4px 2px 0;">
      <i class="fa <?php echo $p[1]; ?>"></i> <?php echo $p[0]; ?>
    </a>
  <?php } ?>
</div></div>
