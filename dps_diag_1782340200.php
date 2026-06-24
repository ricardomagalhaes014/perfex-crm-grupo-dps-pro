<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "PHP: " . PHP_VERSION . "\n";
$mod = __DIR__ . '/modules/dps_voip';
echo "Module: " . (is_dir($mod) ? 'YES' : 'NO') . "\n";
foreach (['dps_voip.php','controllers/Dps_voip.php','models/Dps_voip_model.php','views/dps_voip/index.php'] as $f) {
    $fp = $mod.'/'.$f;
    echo basename($f).": ".(file_exists($fp) ? filesize($fp)."b" : "MISSING")."\n";
}
// Ler error_log
foreach ([__DIR__.'/error_log', __DIR__.'/../error_log'] as $lp) {
    if (file_exists($lp) && filesize($lp) > 0) {
        echo "\n=== ERROR LOG ($lp) ===\n";
        echo substr(file_get_contents($lp), -2000);
    }
}
