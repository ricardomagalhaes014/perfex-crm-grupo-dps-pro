<?php
/** DIAGNÓSTICO do apagão de notas + procura de backups na conta. ?t=dps2026notas */
declare(strict_types=1);
ini_set('memory_limit','256M'); set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['t'] ?? '') !== 'dps2026notas') { http_response_code(403); exit('forbidden'); }

$c=(string)@file_get_contents(__DIR__.'/application/config/app-config.php');
$ler=function(string $k) use ($c){ return preg_match("/define\(\s*'{$k}'\s*,\s*'([^']*)'\s*\)/",$c,$m)?$m[1]:''; };
$bd=@new mysqli($ler('APP_DB_HOSTNAME')?:'localhost',$ler('APP_DB_USERNAME'),$ler('APP_DB_PASSWORD'),$ler('APP_DB_NAME'));
if($bd->connect_error){ exit("bd indisponivel\n"); }
$bd->set_charset('utf8mb4'); $p=$ler('APP_DB_PREFIX')?:'tbl';
$q=function(string $sql) use ($bd){ $r=$bd->query($sql); $o=[]; if($r) while($l=$r->fetch_assoc()) $o[]=$l; return $o; };

echo "== NOTAS ==\n";
foreach($q("SELECT COUNT(*) c, MIN(dateadded) mn, MAX(dateadded) mx FROM {$p}notes WHERE rel_type='lead'") as $l) print_r($l);
echo "\nNotas de lead por dia (últimos 10 dias):\n";
foreach($q("SELECT DATE(dateadded) d, COUNT(*) c FROM {$p}notes WHERE rel_type='lead' AND dateadded>=DATE_SUB(CURDATE(),INTERVAL 10 DAY) GROUP BY d ORDER BY d") as $l) echo "{$l['d']}: {$l['c']}\n";

echo "\n== LEADS mexidas hoje ==\n";
foreach($q("SELECT COUNT(*) c FROM {$p}leads WHERE DATE(dateadded)=CURDATE()") as $l) echo "leads com dateadded hoje: {$l['c']}\n";
foreach($q("SELECT COUNT(DISTINCT l.id) c FROM {$p}leads l JOIN {$p}notes n ON n.rel_id=l.id AND n.rel_type='lead' WHERE DATE(l.dateadded)=CURDATE()") as $l) echo "dessas, com alguma nota: {$l['c']}\n";

echo "\n== ACTIVITY LOG das leads (tem o texto das notas?) ==\n";
foreach($q("SHOW TABLES LIKE '{$p}lead_activity_log'") as $l) print_r($l);
foreach($q("SELECT COUNT(*) c FROM {$p}lead_activity_log WHERE description LIKE '%note%' OR additional_data LIKE '%note%'") as $l) echo "entradas com 'note': {$l['c']}\n";
echo "amostra (3):\n";
foreach($q("SELECT leadid, description, LEFT(additional_data,300) ad, date FROM {$p}lead_activity_log WHERE additional_data IS NOT NULL AND additional_data!='' ORDER BY id DESC LIMIT 3") as $l) print_r($l);

$modo = $_GET['modo'] ?? 'bd';
if ($modo !== 'files') { echo "\n(varrimento de ficheiros: usar &modo=files&dir=/home/u172337921/...)\n"; exit; }

echo "\n== BACKUPS em " . ($_GET['dir'] ?? '') . " ==\n";
$base = $_GET['dir'] ?? '/home/u172337921';
if (!is_dir($base)) { exit("pasta inexistente\n"); }
$achados = []; $n = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($it as $f) {
    $n++; if ($n > 60000) { echo "(limite de 60000 ficheiros atingido)\n"; break; }
    if (preg_match('/\.sql(\.gz)?$|backup.*\.(zip|gz|tar)$/i', $f->getFilename())) {
        $achados[] = $f->getPathname() . ' | ' . $f->getSize() . ' bytes | ' . date('Y-m-d H:i', $f->getMTime());
        if (count($achados) > 40) { break; }
    }
}
echo $achados ? implode("\n", $achados) . "\n" : "nenhum ficheiro de backup encontrado\n";
echo "(percorridos: {$n})\n";
