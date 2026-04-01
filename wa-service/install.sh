#!/bin/bash
#
# Script de instalação do DPS WhatsApp Service
# Corre no servidor CRM (crm.grupo-dps.com)
# Uso: cd wa-service && bash install.sh
#

set -e

echo "=== DPS WhatsApp Service - Instalação ==="
echo ""

# Verificar Node.js
if ! command -v node &> /dev/null; then
    echo "ERRO: Node.js não está instalado"
    exit 1
fi

NODE_VERSION=$(node --version)
echo "✓ Node.js: $NODE_VERSION"

# Instalar dependências (inclui puppeteer/chromium para whatsapp-web.js)
echo ""
echo "A instalar dependências (pode demorar 2-5 minutos)..."
npm install --production

# Instalar PM2 globalmente (se não existir)
if ! command -v pm2 &> /dev/null; then
    echo ""
    echo "A instalar PM2..."
    npm install -g pm2
fi

# Parar o serviço se já estiver a correr
pm2 delete dps-wa-service 2>/dev/null || true

# Iniciar o serviço com PM2
echo ""
echo "A iniciar o serviço com PM2..."
pm2 start server.js --name dps-wa-service --restart-delay=5000

# Guardar a configuração do PM2
pm2 save

# Configurar PM2 para iniciar automaticamente no boot
pm2 startup 2>/dev/null || true

echo ""
echo "=== Instalação concluída ==="
echo ""
echo "Comandos úteis:"
echo "  pm2 status                 - Ver estado do serviço"
echo "  pm2 logs dps-wa-service    - Ver logs em tempo real"
echo "  pm2 restart dps-wa-service - Reiniciar o serviço"
echo ""
echo "Teste: curl -H 'x-api-key: dps-wa-secret-2026' http://127.0.0.1:3001/health"
echo ""
