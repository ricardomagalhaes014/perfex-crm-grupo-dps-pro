# DPS WhatsApp Microservice

Microserviço Node.js para integração WhatsApp no CRM DPS.

## Características

- Suporta múltiplos utilizadores (por `staff_id`)
- **Sessões persistentes** — após ligar uma vez, o serviço reconecta automaticamente ao reiniciar
- QR code gerado como imagem base64 (sem dependências externas)
- Reconexão automática em caso de queda de rede
- Desligamento explícito apaga a sessão guardada

## Instalação no Servidor

```bash
# 1. Copiar os ficheiros para o servidor
scp server.js package.json install.sh root@crm.grupo-dps.com:/tmp/wa-install/

# 2. No servidor, executar o script de instalação
ssh root@crm.grupo-dps.com
cd /tmp/wa-install
bash install.sh
```

## Verificar o Estado

```bash
# Estado do serviço
systemctl status dps-whatsapp

# Logs em tempo real
journalctl -u dps-whatsapp -f

# Testar o endpoint de saúde
curl -H "x-api-key: dps-wa-secret-2026" http://127.0.0.1:3001/health
```

## Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/status?staff_id=X` | Estado da ligação |
| GET | `/qr?staff_id=X` | QR code (base64) |
| POST | `/connect` | Iniciar ligação |
| POST | `/disconnect` | Desligar e apagar sessão |
| POST | `/send` | Enviar mensagem |
| GET | `/health` | Saúde do serviço |

## Sessões

As sessões são guardadas em `/var/wa-sessions/{staff_id}/`.  
Ao reiniciar o servidor, o serviço restaura automaticamente todas as sessões guardadas — **sem necessidade de novo QR code**.

## Variáveis de Ambiente

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `PORT` | `3001` | Porta do servidor |
| `WA_API_KEY` | `dps-wa-secret-2026` | Chave de API |
| `SESSIONS_DIR` | `/var/wa-sessions` | Directório de sessões |
