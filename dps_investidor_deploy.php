<?php
/**
 * Instala/actualiza o Simulador do Investidor no site dpsimobiliario.pt
 * (pasta /simuladorinvestidor). Corre no CRM (mesma conta Hostinger) e
 * escreve no disco do site, com backup datado. Idempotente.
 *
 *   GET               — mostra o estado
 *   POST a=instalar   — copia dps_investidor_page.html -> index.html e
 *                       gera units.json a partir do módulo dps_propostas
 *                       (sem o projecto Raízes)
 *   POST a=restaurar  — repõe o backup mais recente do index.html
 */

declare(strict_types=1);

const CANDIDATOS_SITE = [
    '/home/u172337921/domains/dpsimobiliario.pt/public_html',
    '/home/u172337921/public_html',
];

header('Content-Type: text/html; charset=utf-8');

function localizar_site(): ?string
{
    foreach (CANDIDATOS_SITE as $c) {
        if (is_dir($c) && is_readable($c . '/index.html')) {
            return $c;
        }
    }

    return null;
}

$site = localizar_site();
if ($site === null) {
    http_response_code(404);
    echo 'Não encontrei o docroot do site. Tentei:<br>' . implode('<br>', CANDIDATOS_SITE);
    exit;
}

$pasta   = $site . '/simuladorinvestidor';
$destino = $pasta . '/index.html';
$origem  = __DIR__ . '/dps_investidor_page.html';
$modulo  = __DIR__ . '/modules/dps_propostas/units.json';

$a = $_POST['a'] ?? null;

if ($a === 'instalar') {
    if (!is_readable($origem)) {
        echo '❌ Falta o ficheiro dps_investidor_page.html no CRM — reimplanta primeiro.';
        exit;
    }
    if (!is_readable($modulo)) {
        echo '❌ Falta modules/dps_propostas/units.json no CRM.';
        exit;
    }
    if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
        echo '❌ Não consegui criar a pasta ' . htmlspecialchars($pasta);
        exit;
    }
    if (is_file($destino)) {
        $bak = $destino . '.bak-' . date('Ymd-His');
        if (!copy($destino, $bak)) {
            echo '❌ Não consegui criar o backup. Nada foi alterado.';
            exit;
        }
    }

    $catalogo = json_decode((string) file_get_contents($modulo), true);
    if (!is_array($catalogo)) {
        echo '❌ units.json do módulo inválido. Nada foi alterado.';
        exit;
    }
    unset($catalogo['raizes']); // o Raízes sai de todas as montras novas

    $ok1 = copy($origem, $destino);
    $ok2 = file_put_contents(
        $pasta . '/units.json',
        json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ) !== false;

    if (!$ok1 || !$ok2) {
        echo '❌ Falha na escrita (index: ' . ($ok1 ? 'ok' : 'FALHOU') . ', units: ' . ($ok2 ? 'ok' : 'FALHOU') . ').';
        exit;
    }

    $total = 0;
    foreach ($catalogo as $unidades) {
        $total += count($unidades);
    }
    echo '✅ Simulador do Investidor instalado.<br>'
        . 'Página: <a href="https://dpsimobiliario.pt/simuladorinvestidor/" target="_blank">dpsimobiliario.pt/simuladorinvestidor/</a><br>'
        . 'Catálogo: ' . $total . ' unidades em ' . count($catalogo) . ' empreendimentos (sem Raízes).<br>'
        . 'Os estados vendido/reservado são lidos em directo do simulador (save_states.php).';
    exit;
}

if ($a === 'restaurar') {
    $baks = glob($destino . '.bak-*');
    if (empty($baks)) {
        echo 'Não há backups para restaurar.';
        exit;
    }
    rsort($baks);
    echo copy($baks[0], $destino)
        ? '✅ Restaurado a partir de ' . htmlspecialchars($baks[0])
        : '❌ Falha no restauro.';
    exit;
}

// GET — estado
$instalado    = is_file($destino);
$tem_origem   = is_readable($origem);
$tem_catalogo = is_readable($modulo);
$iguais       = $instalado && $tem_origem && md5_file($destino) === md5_file($origem);
$baks         = is_dir($pasta) ? (glob($destino . '.bak-*') ?: []) : [];

echo '<div style="font-family:sans-serif;padding:30px;max-width:640px;">';
echo '<h3>Simulador do Investidor — instalação no site</h3>';
echo '<p>Destino: <code>' . htmlspecialchars($destino) . '</code></p>';
echo '<p>Página no CRM (origem): ' . ($tem_origem ? '✅ presente' : '❌ em falta — reimplantar') . '</p>';
echo '<p>Catálogo do módulo dps_propostas: ' . ($tem_catalogo ? '✅ presente' : '❌ em falta') . '</p>';
echo '<p>Instalado no site: ' . ($instalado ? ($iguais ? '✅ sim, actualizado' : '⚠️ sim, mas há versão nova para instalar') : '⬜ ainda não') . '</p>';
echo '<p>Backups: ' . count($baks) . '</p>';
if ($tem_origem && $tem_catalogo) {
    echo '<form method="post"><input type="hidden" name="a" value="instalar">'
        . '<button type="submit" style="padding:10px 20px;background:#1a73e8;color:#fff;border:0;border-radius:6px;">'
        . ($instalado ? 'Actualizar' : 'Instalar') . ' o Simulador do Investidor</button></form>';
}
if (!empty($baks)) {
    echo '<form method="post" style="margin-top:12px;"><input type="hidden" name="a" value="restaurar">'
        . '<button type="submit" style="padding:8px 16px;">Restaurar último backup</button></form>';
}
echo '</div>';
