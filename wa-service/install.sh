#!/bin/bash
# Script de instalação do DPS WhatsApp Service
# Executar como root no servidor crm.grupo-dps.com
# Uso: bash install.sh

set -e

SERVICE_DIR="/opt/dps-whatsapp"
SERVICE_USER="www-data"
NODE_BIN=$(which node || which nodejs)

echo "=== DPS WhatsApp Service - Instalação ==="

# 1. Criar directório do serviço
mkdir -p "$SERVICE_DIR"
mkdir -p /var/wa-sessions
chown -R "$SERVICE_USER:$SERVICE_USER" /var/wa-sessions

# 2. Copiar ficheiros
cp server.js "$SERVICE_DIR/"
cp package.json "$SERVICE_DIR/"

# 3. Instalar dependências
cd "$SERVICE_DIR"
npm install --production

# 4. Criar ficheiro de serviço systemd
cat > /etc/systemd/system/dps-whatsapp.service << 'UNIT'
[Unit]
Description=DPS WhatsApp Microservice
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/dps-whatsapp
ExecStart=/usr/bin/node /opt/dps-whatsapp/server.js
Restart=always
RestartSec=5
Environment=PORT=3001
Environment=WA_API_KEY=dps-wa-secret-2026
Environment=SESSIONS_DIR=/var/wa-sessions
StandardOutput=journal
StandardError=journal
SyslogIdentifier=dps-whatsapp

[Install]
WantedBy=multi-user.target
UNIT

# 5. Activar e iniciar o serviço
systemctl daemon-reload
systemctl enable dps-whatsapp
systemctl restart dps-whatsapp

echo ""
echo "=== Instalação concluída ==="
echo "Estado do serviço:"
systemctl status dps-whatsapp --no-pager
echo ""
echo "Para ver logs: journalctl -u dps-whatsapp -f"
