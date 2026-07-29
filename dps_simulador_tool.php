<?php
/**
 * Ferramenta de manutenção do simulador (dpsimobiliario.pt/simuladorportugal).
 *
 * O simulador vive no docroot do SITE, fora do git — este script corre no
 * CRM (mesma conta Hostinger) e alcança-o pelo filesystem.
 *
 * Acções:
 *   GET  ?a=info      — relatório da estrutura do ficheiro (para diagnóstico)
 *   POST  a=backup    — cria cópia .bak datada
 *   POST  a=restaurar — repõe o .bak mais recente
 *
 * Não há acção de escrita arbitrária: as futuras alterações (retirar Raízes,
 * estado no Aura) serão código fixo neste ficheiro, aplicado com backup.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

const CANDIDATOS = [
    '/home/u172337921/domains/dpsimobiliario.pt/public_html/simuladorportugal/index.html',
    '/home/u172337921/public_html/simuladorportugal/index.html',
];

function localizar(): ?string
{
    foreach (CANDIDATOS as $c) {
        if (is_readable($c)) {
            return $c;
        }
    }

    return null;
}

$alvo = localizar();
if ($alvo === null) {
    http_response_code(404);
    echo "Não encontrei o index.html do simulador. Tentei:\n" . implode("\n", CANDIDATOS) . "\n";
    // Ajudar a descobrir a estrutura real
    foreach (['/home/u172337921/domains', '/home/u172337921'] as $dir) {
        if (is_dir($dir)) {
            echo "\nConteúdo de $dir:\n" . implode("\n", array_slice(scandir($dir), 0, 40)) . "\n";
        }
    }
    exit;
}

$a = $_GET['a'] ?? $_POST['a'] ?? 'info';

if ($a === 'backup' || $a === 'restaurar') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        // Página de confirmação com os botões
        echo "Ficheiro: $alvo\n\n";
        header('Content-Type: text/html; charset=utf-8');
        echo '<form method="post"><input type="hidden" name="a" value="' . htmlspecialchars($a) . '">'
            . '<button type="submit" style="padding:10px 20px;">Confirmar ' . htmlspecialchars($a) . '</button></form>';
        exit;
    }

    if ($a === 'backup') {
        $bak = $alvo . '.bak-' . date('Ymd-His');
        echo copy($alvo, $bak) ? "Backup criado: $bak\n" : "FALHOU o backup.\n";
        exit;
    }

    // restaurar: usar o .bak mais recente
    $baks = glob($alvo . '.bak-*');
    if (empty($baks)) {
        echo "Não há backups.\n";
        exit;
    }
    rsort($baks);
    echo copy($baks[0], $alvo) ? "Restaurado a partir de {$baks[0]}\n" : "FALHOU o restauro.\n";
    exit;
}

/* ---------------------------------------------------------------------
 * a=info — relatório compacto da estrutura (colar na conversa)
 * ------------------------------------------------------------------ */

$html = (string) file_get_contents($alvo);

echo "=== SIMULADOR — RELATÓRIO ===\n";
echo 'Ficheiro : ' . $alvo . "\n";
echo 'Tamanho  : ' . strlen($html) . " bytes\n";
echo 'Alterado : ' . date('Y-m-d H:i:s', (int) filemtime($alvo)) . "\n";
echo 'MD5      : ' . md5($html) . "\n\n";

echo "--- Contagens de palavras-chave ---\n";
foreach ([
    'Aura Residence', 'aura', 'AURA',
    'Gaia Douro', 'gaia',
    'Boavista', 'boavista',
    'Belo Horizonte', 'bh_states',
    'Lake', 'lake_states',
    'Raízes', 'Raizes', 'raizes', 'raizes_states',
    'aura_states', 'gaia_states', 'boavista_states',
    'save_states.php', 'Reservado', 'Vendido', 'Disponível',
    'EMPREENDIMENTO',
] as $kw) {
    echo str_pad($kw, 18) . ': ' . substr_count($html, $kw) . "\n";
}

echo "\n--- Excertos (1000 chars à volta da 1.ª ocorrência) ---\n";
foreach (['Aura Residence', 'Raízes', 'aura_states', 'raizes_states', 'save_states.php'] as $kw) {
    $p = strpos($html, $kw);
    echo "\n>>> [$kw] " . ($p === false ? "NÃO EXISTE\n" : "offset $p\n");
    if ($p !== false) {
        echo substr($html, max(0, $p - 400), 1000) . "\n";
    }
}

echo "\n--- Ficheiros na pasta ---\n";
foreach (scandir(dirname($alvo)) as $f) {
    if ($f !== '.' && $f !== '..') {
        $fp = dirname($alvo) . '/' . $f;
        echo str_pad($f, 40) . (is_file($fp) ? filesize($fp) . ' bytes' : '[dir]') . "\n";
    }
}
