<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Auto-aplicação do arranjo da imagem do banner "Portugal" no site
 * dpsimobiliario.pt.
 *
 * O banner usava um URL temporário do Manus (private-us-east-1.manuscdn.com)
 * que expirou. O dps_site_patch.php tem o botão manual; este hook faz o
 * mesmo sozinho, uma única vez, na primeira visita ao CRM depois do
 * reimplante — assim ninguém precisa de clicar em nada.
 *
 * Guardas: ficheiro-testemunho fora do docroot; marcador idempotente no
 * próprio index.html; backup datado antes de escrever; silêncio total em
 * caso de erro (o CRM nunca pode cair por causa disto).
 */
function dps_portugal_img_autopatch()
{
    $testemunho = '/home/u172337921/.dps_portugal_img_done';

    try {
        if (file_exists($testemunho)) {
            return;
        }

        $candidatos = [
            '/home/u172337921/domains/dpsimobiliario.pt/public_html/index.html',
            '/home/u172337921/public_html/index.html',
        ];
        $alvo = null;
        foreach ($candidatos as $c) {
            if (is_readable($c) && is_writable($c)) {
                $alvo = $c;
                break;
            }
        }
        if ($alvo === null) {
            return; // ambiente sem o site (ex.: dev local) — fica para a próxima
        }

        $html = (string) file_get_contents($alvo);

        if (strpos($html, 'dps-portugal-img-fix') !== false) {
            @file_put_contents($testemunho, date('c') . " ja estava aplicado\n");

            return;
        }

        $pos = strripos($html, '</body>');
        if ($pos === false) {
            return;
        }

        $bloco = <<<'HTML'
<script id="dps-portugal-img-fix">
(function(){
  var BOA = 'https://dpsimobiliario.pt/auraresidence/assets/render3.jpg';
  function corrigir(){
    document.querySelectorAll('img').forEach(function(img){
      var s = img.getAttribute('src') || '';
      if (s.indexOf('private-us-east-1.manuscdn.com') !== -1 && img.src !== BOA) { img.src = BOA; }
    });
    document.querySelectorAll('[style*="private-us-east-1.manuscdn.com"]').forEach(function(el){
      el.style.backgroundImage = 'url(' + BOA + ')';
    });
  }
  corrigir();
  new MutationObserver(corrigir).observe(document.documentElement, {childList:true, subtree:true});
})();
</script>
HTML;

        $bak = $alvo . '.bak-' . date('Ymd-His');
        if (!@copy($alvo, $bak)) {
            return;
        }

        $novo = substr($html, 0, $pos) . $bloco . "\n" . substr($html, $pos);
        if (@file_put_contents($alvo, $novo) === false) {
            return;
        }

        @file_put_contents($testemunho, date('c') . " aplicado (backup: {$bak})\n");
    } catch (\Throwable $e) {
        // nunca interromper o CRM
    }
}
