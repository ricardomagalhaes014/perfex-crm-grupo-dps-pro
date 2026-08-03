<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
</main>

<nav class="fundo">
  <?php
  $abas = [
      'inicio'  => ['Hoje',    '☀', 'dps_movel'],
      'leads'   => ['Leads',   '☰', 'dps_movel/leads'],
      'tarefas' => ['Tarefas', '✓', 'dps_movel/tarefas'],
      'agenda'  => ['Agenda',  '◷', 'dps_movel/agenda'],
  ];
  foreach ($abas as $chave => $a) { ?>
    <a href="<?php echo admin_url($a[2]); ?>" class="<?php echo ($aba ?? '') === $chave ? 'on' : ''; ?>">
      <i><?php echo $a[1]; ?></i><?php echo $a[0]; ?>
    </a>
  <?php } ?>
</nav>

<script>
/*
 * O trabalhador de serviço serve só para a app poder ser instalada e para o
 * ecrã de "sem ligação" não ser a página de erro do browser. NÃO guarda leads
 * nem tarefas: dados de vendas guardados no telemóvel envelhecem em minutos, e
 * dois comerciais a trabalhar sobre a mesma cópia velha é pior do que uma
 * mensagem honesta a dizer que não há rede.
 */
if ('serviceWorker' in navigator) {
    /*
     * O ficheiro é servido de DENTRO do caminho da app. Um trabalhador de
     * serviço só manda no caminho onde vive: em /dps-movel/sw.js ficaria a
     * mandar numa pasta onde não há páginas nenhumas, e a app nunca seria
     * instalável.
     */
    navigator.serviceWorker
        .register('<?php echo admin_url('dps_movel/sw'); ?>', { scope: '<?php echo admin_url('dps_movel/'); ?>' })
        .catch(function () { /* sem trabalhador de serviço a app continua a funcionar */ });
}
</script>
</body>
</html>
