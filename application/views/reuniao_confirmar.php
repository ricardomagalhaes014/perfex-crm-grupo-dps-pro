<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Confirmação de reunião</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f4f5f7;margin:0;padding:24px;color:#22252a}
.caixa{max-width:520px;margin:40px auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 2px 16px rgba(0,0,0,.08)}
h1{font-size:20px;margin:0 0 6px}
.quando{font-size:22px;font-weight:600;color:#2d7ff9;margin:18px 0}
.btn{display:inline-block;border:0;border-radius:8px;padding:13px 22px;font-size:15px;font-weight:600;cursor:pointer;text-decoration:none}
.sim{background:#1e9e5a;color:#fff}
.nao{background:#eceef1;color:#555;margin-left:8px}
.ok{background:#e8f6ee;border:1px solid #b9e3cb;padding:14px;border-radius:8px}
.link{display:inline-block;margin-top:10px;color:#2d7ff9;word-break:break-all}
.muted{color:#7a8087;font-size:13px}
</style>
</head>
<body>
<div class="caixa">
<?php if ($proposta['estado'] === 'aceite' && $reuniao) { ?>
  <h1>Reunião confirmada</h1>
  <div class="quando"><?= dps_reunioes_quando_extenso($proposta['data_hora']); ?></div>
  <div class="ok">
    Obrigado! Ficou marcado com <strong><?= e($comercial); ?></strong>.
    Vai receber um lembrete 30 minutos antes.
    <div><a class="link" href="<?= e($reuniao['link']); ?>"><?= e($reuniao['link']); ?></a></div>
  </div>
  <p class="muted">Guarde este link — é por aí que entra na reunião, sem instalar nada.</p>

<?php } elseif ($proposta['estado'] === 'aceite') { ?>
  <h1>Já estava confirmada</h1>
  <div class="quando"><?= dps_reunioes_quando_extenso($proposta['data_hora']); ?></div>
  <p class="muted">Esta reunião já tinha sido confirmada. Não precisa de fazer mais nada.</p>

<?php } elseif ($proposta['estado'] === 'recusada') { ?>
  <h1>Sem problema</h1>
  <p>Ficámos a saber que este horário não lhe serve. O <?= e($comercial); ?> entra em contacto para combinarem outro.</p>

<?php } elseif ($proposta['estado'] === 'expirada') { ?>
  <h1>Este horário já passou</h1>
  <p>A hora proposta era <?= dps_reunioes_quando_extenso($proposta['data_hora']); ?> e entretanto passou.</p>
  <p class="muted">Fale com o <?= e($comercial); ?> para combinarem outra altura.</p>

<?php } else { ?>
  <h1>Olá <?= e($proposta['cliente_nome']); ?></h1>
  <p><?= e($comercial); ?>, da DPS Imobiliário, propõe-lhe uma conversa em:</p>
  <div class="quando"><?= dps_reunioes_quando_extenso($proposta['data_hora']); ?></div>
  <p class="muted">É por videochamada, dura cerca de 30 minutos e não precisa de instalar nada.</p>
  <form method="post">
    <button class="btn sim" name="accao" value="aceitar" type="submit">Sim, confirmo</button>
    <button class="btn nao" name="accao" value="recusar" type="submit">Não posso a esta hora</button>
  </form>
<?php } ?>
</div>
</body>
</html>
