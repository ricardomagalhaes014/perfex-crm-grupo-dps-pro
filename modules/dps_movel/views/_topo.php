<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo html_escape($titulo ?? 'DPS'); ?> · DPS</title>

<link rel="manifest" href="<?php echo admin_url('dps_movel/manifest'); ?>">
<meta name="theme-color" content="#10151c">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="<?php echo base_url('dps-movel/icone-192.png'); ?>">

<style>
/* --------------------------------------------------------------------
 * Tudo aqui dentro, sem ficheiros externos.
 *
 * São ~4 KB que viajam com a página. Um ficheiro à parte seria mais uma
 * ida ao servidor antes de o ecrã aparecer, e em rede móvel cada ida
 * custa mais do que o próprio conteúdo.
 * ----------------------------------------------------------------- */
:root {
  --ink:#10151c; --surface:#171e28; --surface2:#1e2733; --line:#2a3441;
  --texto:#e8edf3; --fraco:#93a1b3; --accent:#f0a028; --accent-ink:#10151c;
  --ok:#3fb27f; --mau:#e0575b;
  --raio:14px;
}
@media (prefers-color-scheme: light) {
  :root {
    --ink:#f4f6f8; --surface:#ffffff; --surface2:#eef1f5; --line:#dfe4ea;
    --texto:#10151c; --fraco:#5d6b7c; --accent:#c97a06; --accent-ink:#ffffff;
  }
}
* { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
html,body { margin:0; padding:0; }
body {
  background:var(--ink); color:var(--texto);
  font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",sans-serif;
  padding-bottom:calc(72px + env(safe-area-inset-bottom));
  -webkit-font-smoothing:antialiased;
}
a { color:inherit; text-decoration:none; }

/* Barra de cima ------------------------------------------------------ */
.topo {
  position:sticky; top:0; z-index:20;
  background:color-mix(in srgb, var(--ink) 88%, transparent);
  backdrop-filter:blur(12px);
  border-bottom:1px solid var(--line);
  padding:calc(10px + env(safe-area-inset-top)) 16px 10px;
  display:flex; align-items:center; gap:12px;
}
.topo h1 { font-size:19px; font-weight:650; margin:0; letter-spacing:-.01em;
           white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.voltar { font-size:22px; line-height:1; color:var(--fraco); padding:2px 4px 2px 0; }

.corpo { padding:16px; max-width:640px; margin:0 auto; }

/* Peças -------------------------------------------------------------- */
.cartao {
  background:var(--surface); border:1px solid var(--line);
  border-radius:var(--raio); padding:14px 16px; margin-bottom:10px;
  display:block;
}
.cartao:active { background:var(--surface2); }
.titulo-seccao {
  font-size:12px; text-transform:uppercase; letter-spacing:.08em;
  color:var(--fraco); font-weight:650; margin:22px 0 10px;
}
.titulo-seccao:first-child { margin-top:0; }
.linha { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.nome { font-weight:600; letter-spacing:-.01em; }
.sub { color:var(--fraco); font-size:13.5px; margin-top:3px; }
.vazio { color:var(--fraco); text-align:center; padding:34px 16px; font-size:15px; }

.selo {
  display:inline-block; padding:3px 9px; border-radius:999px;
  font-size:11.5px; font-weight:650; letter-spacing:.01em; color:#fff;
  white-space:nowrap;
}
.pilula {
  display:inline-flex; align-items:center; gap:7px;
  background:var(--surface); border:1px solid var(--line);
  border-radius:999px; padding:8px 14px; font-size:14px; margin:0 8px 8px 0;
}
.pilula.on { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); font-weight:650; }
.pilula b { font-variant-numeric:tabular-nums; }

/* Botões ------------------------------------------------------------- */
.btn {
  display:flex; align-items:center; justify-content:center; gap:8px;
  min-height:50px; border-radius:12px; border:1px solid var(--line);
  background:var(--surface); color:var(--texto);
  font-size:15.5px; font-weight:600; width:100%; cursor:pointer;
  padding:0 16px; font-family:inherit;
}
.btn:active { transform:scale(.985); }
.btn-a { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
.btn-ok { background:var(--ok); color:#fff; border-color:var(--ok); }
.dois { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }

input,select,textarea {
  width:100%; background:var(--surface); color:var(--texto);
  border:1px solid var(--line); border-radius:12px; padding:13px 14px;
  font-size:16px; /* 16px trava o zoom automático do iOS ao tocar no campo */
  font-family:inherit; margin-bottom:10px;
}
textarea { min-height:88px; resize:vertical; }

/* Barra de baixo ----------------------------------------------------- */
.fundo {
  position:fixed; left:0; right:0; bottom:0; z-index:30;
  display:grid; grid-template-columns:repeat(4,1fr);
  background:color-mix(in srgb, var(--surface) 94%, transparent);
  backdrop-filter:blur(12px);
  border-top:1px solid var(--line);
  padding-bottom:env(safe-area-inset-bottom);
}
.fundo a {
  padding:11px 4px 13px; text-align:center;
  font-size:11px; color:var(--fraco); font-weight:600; letter-spacing:.01em;
}
.fundo a i { display:block; font-size:20px; margin-bottom:3px; font-style:normal; }
.fundo a.on { color:var(--accent); }

.aviso { border-radius:12px; padding:12px 14px; margin-bottom:12px; font-size:14.5px; }
.aviso-ok { background:color-mix(in srgb, var(--ok) 18%, transparent); border:1px solid var(--ok); }
.aviso-mau { background:color-mix(in srgb, var(--mau) 18%, transparent); border:1px solid var(--mau); }
</style>
</head>
<body>

<header class="topo">
  <?php if (!empty($voltar)) { ?>
    <a class="voltar" href="<?php echo $voltar; ?>" aria-label="Voltar">&larr;</a>
  <?php } ?>
  <h1><?php echo html_escape($titulo ?? 'DPS'); ?></h1>
</header>

<main class="corpo">
<?php
foreach (['success' => 'ok', 'danger' => 'mau', 'warning' => 'mau', 'info' => 'ok'] as $tipo => $classe) {
    $m = $this->session->flashdata('message-' . $tipo);
    if ($m) {
        echo '<div class="aviso aviso-' . $classe . '">' . $m . '</div>';
    }
}
?>
