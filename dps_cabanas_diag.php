<?php
/**
 * DIAGNÓSTICO — página Cabanas Residence (server-only).
 * Envia por email os excertos relevantes (morada/mapa e condições de
 * pagamento) do index.html, para se poder corrigir cirurgicamente.
 * Uso: ?t=dps2026cabanas  — apaga-se depois de enviar.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
if (($_GET['t'] ?? '') !== 'dps2026cabanas') { http_response_code(403); exit('{"error":"forbidden"}'); }

$pasta = '/home/u172337921/domains/dpsimobiliario.pt/public_html/cabanasresidence';
$rel = "PASTA: {$pasta}\n";
$rel .= is_dir($pasta) ? ("FICHEIROS:\n" . implode("\n", array_slice(scandir($pasta) ?: [], 0, 60)) . "\n\n") : "NAO EXISTE\n";

$idx = $pasta . '/index.html';
if (is_readable($idx)) {
    $h = (string) file_get_contents($idx);
    $rel .= 'index.html: ' . strlen($h) . " bytes, md5 " . md5($h) . "\n\n";
    foreach (['maps.app.goo.gl', 'google.com/maps', 'goo.gl', 'morada', 'address', 'Cabanas', 'CPCV', 'Betão', 'Betao', 'Caixilhari', 'Escritura', 'Pagamento', '15%', '10%', '60%', 'iframe'] as $chave) {
        $pos = 0; $n = 0;
        while (($pos = stripos($h, $chave, $pos)) !== false && $n < 4) {
            $rel .= "== [{$chave}] @{$pos} ==\n" . substr($h, max(0, $pos - 260), 640) . "\n\n";
            $pos += strlen($chave); $n++;
        }
    }
} else {
    $rel .= "index.html ILEGIVEL\n";
}

$para = 'ricardomagalhaes014@gmail.com';
$ok = @mail($para, 'DPS-CABANAS-DIAG ' . date('His'), $rel, "From: crm@grupo-dps.com\r\nContent-Type: text/plain; charset=utf-8");
@unlink(__FILE__);
echo json_encode(['ok' => (bool) $ok, 'bytes_relatorio' => strlen($rel)]);
