<?php
/**
 * Patch cirúrgico ao index.html do site dpsimobiliario.pt (que vive fora
 * do git). Corre no CRM (mesma conta) e altera o ficheiro no disco, com
 * backup datado. Cada patch é código fixo e idempotente — nada de escrita
 * arbitrária.
 *
 *   GET              — mostra o estado (patch aplicado? backups?)
 *   POST a=aura_card — insere o card Aura Residence no injector de cards
 *   POST a=restaurar — repõe o backup mais recente
 */

declare(strict_types=1);

const CANDIDATOS_INDEX = [
    '/home/u172337921/domains/dpsimobiliario.pt/public_html/index.html',
    '/home/u172337921/public_html/index.html',
];

const MARCADOR_ANCORA = "// 2. Tornar o banner Portugal clicável";
const ID_PATCH        = 'aura-card-injected';

const BLOCO_AURA = <<<'JS'
    // 1b. Adicionar card Aura Residence ao lado do Lake Towers
    var lakeLink3 = allLinks.find(function(a) { return a.textContent.includes('Lake Towers'); });
    if (lakeLink3 && !document.getElementById('aura-card-injected')) {
      var container3 = lakeLink3.parentElement;
      var auraCard = document.createElement('a');
      auraCard.id = 'aura-card-injected';
      auraCard.href = 'https://dpsimobiliario.pt/auraresidence';
      auraCard.target = '_blank';
      auraCard.rel = 'noopener noreferrer';
      auraCard.className = lakeLink3.className;
      auraCard.style.textDecoration = 'none';
      auraCard.innerHTML = '<div class="border border-[#C5A55A]/20 overflow-hidden hover:border-[#C5A55A]/60 transition-all duration-300 hover:shadow-lg hover:shadow-[#C5A55A]/10"><div class="relative h-48 overflow-hidden"><img alt="Aura Residence" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" src="https://dpsimobiliario.pt/auraresidence/assets/render7.jpg" style="object-position:center"></div><div class="p-4 bg-white"><h4 class="font-bold text-[#1a1a1a] text-base mb-1" style="font-family: var(--font-display);">Aura Residence</h4><p class="text-[#666] text-xs mb-2">Meixomil, Paços de Ferreira · Portugal</p><p class="text-[#C5A55A] font-semibold text-sm">Novo - T0 a T3 com varandas</p></div></div>';
      container3.appendChild(auraCard);
    }

JS;

header('Content-Type: text/html; charset=utf-8');

function localizar_index(): ?string
{
    foreach (CANDIDATOS_INDEX as $c) {
        if (is_readable($c)) {
            return $c;
        }
    }

    return null;
}

$alvo = localizar_index();
if ($alvo === null) {
    http_response_code(404);
    echo 'Não encontrei o index.html do site. Tentei:<br>' . implode('<br>', CANDIDATOS_INDEX);
    exit;
}

$html = (string) file_get_contents($alvo);
$a    = $_POST['a'] ?? null;

if ($a === 'aura_card') {
    if (strpos($html, ID_PATCH) !== false) {
        echo '✅ O card Aura Residence já está aplicado — nada a fazer.';
        exit;
    }
    $pos = strpos($html, MARCADOR_ANCORA);
    if ($pos === false) {
        echo '❌ Não encontrei o ponto de inserção ("' . htmlspecialchars(MARCADOR_ANCORA) . '") — o ficheiro do servidor é diferente do esperado. Nada foi alterado.';
        exit;
    }

    $bak = $alvo . '.bak-' . date('Ymd-His');
    if (!copy($alvo, $bak)) {
        echo '❌ Não consegui criar o backup. Nada foi alterado.';
        exit;
    }

    $novo = substr($html, 0, $pos) . BLOCO_AURA . '    ' . substr($html, $pos);
    if (file_put_contents($alvo, $novo) === false) {
        echo '❌ Falha na escrita. O backup está em ' . htmlspecialchars($bak);
        exit;
    }
    echo '✅ Card Aura Residence aplicado com sucesso.<br>Backup: ' . htmlspecialchars($bak)
        . '<br><br>Abre <a href="https://dpsimobiliario.pt" target="_blank">dpsimobiliario.pt</a> e faz Ctrl+F5 para confirmar.';
    exit;
}

if ($a === 'restaurar') {
    $baks = glob($alvo . '.bak-*');
    if (empty($baks)) {
        echo 'Não há backups para restaurar.';
        exit;
    }
    rsort($baks);
    echo copy($baks[0], $alvo)
        ? '✅ Restaurado a partir de ' . htmlspecialchars($baks[0])
        : '❌ Falha no restauro.';
    exit;
}

// GET — estado + botões
$aplicado = strpos($html, ID_PATCH) !== false;
$ancora   = strpos($html, MARCADOR_ANCORA) !== false;
$baks     = glob($alvo . '.bak-*') ?: [];

echo '<div style="font-family:sans-serif;padding:30px;max-width:620px;">';
echo '<h3>Patch do site dpsimobiliario.pt</h3>';
echo '<p>Ficheiro: <code>' . htmlspecialchars($alvo) . '</code> (' . filesize($alvo) . ' bytes)</p>';
echo '<p>Card Aura Residence: ' . ($aplicado ? '✅ já aplicado' : '⬜ por aplicar') . '</p>';
echo '<p>Ponto de inserção encontrado: ' . ($ancora ? '✅ sim' : '❌ NÃO — não é seguro aplicar') . '</p>';
echo '<p>Backups existentes: ' . count($baks) . '</p>';
if (!$aplicado && $ancora) {
    echo '<form method="post"><input type="hidden" name="a" value="aura_card">'
        . '<button type="submit" style="padding:10px 20px;background:#1a73e8;color:#fff;border:0;border-radius:6px;">Aplicar card Aura Residence (com backup)</button></form>';
}
if (!empty($baks)) {
    echo '<form method="post" style="margin-top:12px;"><input type="hidden" name="a" value="restaurar">'
        . '<button type="submit" style="padding:8px 16px;">Restaurar último backup</button></form>';
}
echo '</div>';
