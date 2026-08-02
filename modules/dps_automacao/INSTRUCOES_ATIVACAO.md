# DPS Automação — Instruções de ativação

O módulo NÃO fica ativo sozinho: um módulo novo do Perfex exige ativação
manual. Passos, por ordem:

## 1. Deploy

Fazer o deploy via GitHub Actions, como habitualmente (o SSH ao servidor está
bloqueado). Confirmar que a pasta `modules/dps_automacao/` chegou completa ao
servidor.

## 2. Ativar o módulo

1. Entrar no CRM como administrador.
2. Ir a **Setup > Modules**.
3. Encontrar **DPS Automação** e clicar em **Activate**.

A ativação cria as tabelas e as opções (tudo desligado por omissão).

## 3. Confirmar as tabelas

No phpMyAdmin (ou equivalente), confirmar que existem:

- `tbldps_automacao_envios`
- `tbldps_automacao_guioes`
- `tbldps_automacao_guiao_escolhas`

Se não existirem, basta abrir qualquer página do admin: a migração automática
(`admin_init`) volta a tentar criá-las e regista qualquer falha no Activity Log.

## 4. Ligar os interruptores (quando quiser começar a enviar)

Por segurança, o módulo nasce com TUDO desligado:

1. Menu lateral **Automação > Definições**.
2. Ligar o **interruptor geral** (`dps_automacao_ativo`) — sem ele, nada é
   enviado: nem massa, nem testes, nem follow-ups.
3. Se quiser os follow-ups automáticos de 7/15/30 dias, ligar também o
   interruptor próprio e rever as três mensagens.

## 5. Pré-requisitos dos canais

- **WhatsApp**: as opções `dps_whatsapp_evolution_url` e
  `dps_whatsapp_evolution_api_key` têm de estar preenchidas em `tbloptions`,
  e a instância de cada comercial (`staff-<staffid>`) tem de estar ligada
  (connectionState = open). O módulo verifica antes de cada lote.
- **Email**: usa o SMTP do Perfex já configurado — nada a fazer.
- **SMS**: só aparece na UI se houver uma gateway SMS ativa em
  **Setup > Settings > SMS**.

## 6. Follow-ups exigem o cron do Perfex

Os follow-ups correm no hook `after_cron_run`. Confirmar que o cron do Perfex
está a correr (Setup > Settings > Cron Job); sem cron, não há follow-ups.
Cada corrida envia no máximo 50 emails — listas grandes esvaziam-se ao longo
de várias corridas.

## Nota de segurança

Todas as ações de envio/gravação são POST com CSRF; comerciais só conseguem
enviar às leads que lhes estão atribuídas (validado no servidor, não na UI).
