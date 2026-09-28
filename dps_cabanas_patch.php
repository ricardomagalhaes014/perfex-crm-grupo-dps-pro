<?php
/**
 * PATCH — página Cabanas Residence (dpsimobiliario.pt/cabanasresidence).
 * 1) Botão "Abrir no Google Maps" (pin certo) na secção Localização
 * 2) Secção "Condições de pagamento" com as fases e datas
 * 3) Item "Pagamento" no menu e FAQ com as datas
 * Idempotente (marcador id="pagamento"), backup datado. ?t=dps2026cabanas
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if (($_GET['t'] ?? '') !== 'dps2026cabanas') { http_response_code(403); exit('{"error":"forbidden"}'); }

$alvo = '/home/u172337921/domains/dpsimobiliario.pt/public_html/cabanasresidence/index.html';
if (!is_readable($alvo) || !is_writable($alvo)) { http_response_code(500); exit(json_encode(['error' => 'index ilegivel'])); }
$h = (string) file_get_contents($alvo);

if (strpos($h, 'id="pagamento"') !== false) { exit(json_encode(['ok' => true, 'ja_aplicado' => true])); }

$mudou = [];

// 1) menu
$a = '<a href="#lugar">Localização</a>';
$b = '<a href="#pagamento">Pagamento</a>' . "\n      " . $a;
if (strpos($h, $a) !== false) { $h = str_replace($a, $b, $h); $mudou[] = 'menu'; }

// 2) botão Google Maps na Localização
$a = '<div><strong>25 min de carro</strong><span>Baixa do Porto, a 9,1 km</span></div>
        </div>';
$b = $a . '
        <p style="margin-top:26px"><a class="btn" href="https://maps.app.goo.gl/muzak7SjP8zEUi6W6" target="_blank" rel="noopener">Abrir no Google Maps</a></p>';
if (strpos($h, $a) !== false) { $h = str_replace($a, $b, $h); $mudou[] = 'maps'; }

// 3) secção de pagamento antes do contacto
$a = '<section id="contacto" class="cta">';
$b = <<<'HTML'
<section class="escuro" id="pagamento">
  <div class="env">
    <p class="olho">Condições de pagamento</p>
    <h2 style="max-width:28ch">Quarenta por cento durante a obra, o resto só na escritura.</h2>
    <div class="tabela-caixa" style="margin-top:clamp(24px,4vw,36px)">
      <table>
        <thead><tr><th>Fase</th><th>Quando</th><th>Valor</th></tr></thead>
        <tbody>
          <tr><td class="tipo">CPCV</td><td>Na reserva</td><td><strong>15%</strong></td></tr>
          <tr><td class="tipo">Conclusão do betão</td><td>Final de 2027</td><td><strong>15%</strong></td></tr>
          <tr><td class="tipo">Colocação das caixilharias</td><td>1.º semestre de 2028</td><td><strong>10%</strong></td></tr>
          <tr><td class="tipo">Escritura</td><td>Dezembro de 2028</td><td><strong>60%</strong></td></tr>
        </tbody>
      </table>
    </div>
    <p class="nota" style="color:rgba(251,250,248,.55)">Plano de pagamento faseado do promotor, sujeito a contrato.</p>
  </div>
</section>

HTML;
if (strpos($h, $a) !== false) { $h = str_replace($a, $b . $a, $h); $mudou[] = 'seccao_pagamento'; }

// 4) FAQ com datas
$a = 'Quarenta por cento durante a obra — quinze por cento no contrato promessa, quinze na conclusão do betão e dez na caixilharia — e os restantes sessenta por cento na escritura.';
$b = 'Quarenta por cento durante a obra — quinze por cento no CPCV, quinze na conclusão do betão (final de 2027) e dez na colocação das caixilharias (primeiro semestre de 2028) — e os restantes sessenta por cento na escritura, prevista para dezembro de 2028.';
if (strpos($h, $a) !== false) { $h = str_replace($a, $b, $h); $mudou[] = 'faq'; }

if (empty($mudou)) { exit(json_encode(['ok' => false, 'erro' => 'nenhuma ancora encontrada — pagina diferente do esperado, nada alterado'])); }

$bak = $alvo . '.bak-' . date('Ymd-His');
if (!copy($alvo, $bak)) { exit(json_encode(['ok' => false, 'erro' => 'backup falhou'])); }
if (file_put_contents($alvo, $h) === false) { exit(json_encode(['ok' => false, 'erro' => 'escrita falhou', 'backup' => $bak])); }

echo json_encode(['ok' => true, 'alterado' => $mudou, 'backup' => $bak]);
