/**
 * DPS WhatsApp Microservice
 * Porta: 3001
 * API Key: dps-wa-secret-2026
 * 
 * Suporta múltiplos utilizadores (staff_id)
 * Sessões persistidas em ./wa-sessions/{staff_id}/
 * 
 * Endpoints:
 *   GET  /status?staff_id=X      - Estado da ligação
 *   GET  /qr?staff_id=X          - QR code (base64 PNG)
 *   POST /connect  {staff_id}    - Iniciar ligação
 *   POST /disconnect {staff_id}  - Desligar
 *   POST /send {staff_id, to, message} - Enviar mensagem
 */

'use strict';

const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const path = require('path');
const fs = require('fs');

const API_KEY = process.env.WA_API_KEY || 'dps-wa-secret-2026';
const PORT = parseInt(process.env.PORT || '3001');
const SESSIONS_DIR = path.join(__dirname, 'wa-sessions');

// Garantir que o directório de sessões existe
if (!fs.existsSync(SESSIONS_DIR)) {
    fs.mkdirSync(SESSIONS_DIR, { recursive: true });
}

const app = express();
app.use(express.json());

// Middleware de autenticação
app.use((req, res, next) => {
    const key = req.headers['x-api-key'] || req.query.api_key;
    if (key !== API_KEY) {
        return res.status(401).json({ error: 'Não autorizado' });
    }
    next();
});

// Estado das sessões em memória
// { staff_id: { client, qr, connected, phone, connecting } }
const sessions = {};

function getSessionDir(staff_id) {
    return path.join(SESSIONS_DIR, String(staff_id));
}

async function createSession(staff_id) {
    const sid = String(staff_id);
    
    console.log(`[${sid}] Criar sessão WhatsApp`);
    
    if (sessions[sid] && sessions[sid].connecting) {
        console.log(`[${sid}] Sessão já está a ligar`);
        return;
    }
    
    // Fechar sessão anterior se existir
    if (sessions[sid] && sessions[sid].client) {
        try {
            await sessions[sid].client.destroy();
        } catch(e) {
            console.error(`[${sid}] Erro ao fechar sessão anterior:`, e.message);
        }
    }
    
    sessions[sid] = {
        client: null,
        qr: null,
        connected: false,
        phone: null,
        connecting: true,
    };
    
    const sessionDir = getSessionDir(sid);
    if (!fs.existsSync(sessionDir)) {
        fs.mkdirSync(sessionDir, { recursive: true });
    }
    
    try {
        const client = new Client({
            authStrategy: new LocalAuth({
                clientId: `staff-${sid}`,
                dataPath: sessionDir
            }),
            puppeteer: {
                headless: true,
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--disable-gpu'
                ]
            }
        });
        
        sessions[sid].client = client;
        
        // QR code gerado
        client.on('qr', async (qr) => {
            console.log(`[${sid}] QR code gerado`);
            try {
                const qrImage = await qrcode.toDataURL(qr);
                sessions[sid].qr = qrImage;
            } catch (e) {
                console.error(`[${sid}] Erro ao gerar QR:`, e.message);
            }
        });
        
        // Cliente pronto
        client.on('ready', () => {
            console.log(`[${sid}] WhatsApp ligado`);
            sessions[sid].connected = true;
            sessions[sid].connecting = false;
            sessions[sid].qr = null;
            
            // Obter número de telefone
            client.info.then(info => {
                sessions[sid].phone = info.wid.user;
                console.log(`[${sid}] Número: ${info.wid.user}`);
            }).catch(e => {
                console.error(`[${sid}] Erro ao obter número:`, e.message);
            });
        });
        
        // Autenticação bem-sucedida
        client.on('authenticated', () => {
            console.log(`[${sid}] Autenticado`);
            sessions[sid].connecting = false;
        });
        
        // Falha de autenticação
        client.on('auth_failure', (msg) => {
            console.error(`[${sid}] Falha de autenticação:`, msg);
            sessions[sid].connecting = false;
            sessions[sid].connected = false;
        });
        
        // Desconectado
        client.on('disconnected', (reason) => {
            console.log(`[${sid}] Desconectado:`, reason);
            sessions[sid].connected = false;
            sessions[sid].connecting = false;
            sessions[sid].qr = null;
        });
        
        // Inicializar cliente
        await client.initialize();
        
    } catch (e) {
        console.error(`[${sid}] Erro ao criar sessão:`, e.message);
        sessions[sid].connecting = false;
        sessions[sid].connected = false;
    }
}

// Carregar sessões existentes ao iniciar o servidor
async function loadExistingSessions() {
    console.log('A carregar sessões existentes...');
    
    if (!fs.existsSync(SESSIONS_DIR)) {
        console.log('Nenhuma sessão existente');
        return;
    }
    
    const dirs = fs.readdirSync(SESSIONS_DIR);
    for (const dir of dirs) {
        const fullPath = path.join(SESSIONS_DIR, dir);
        if (fs.statSync(fullPath).isDirectory()) {
            // Verificar se tem ficheiros de sessão
            const files = fs.readdirSync(fullPath);
            if (files.length > 0) {
                console.log(`Restaurar sessão: ${dir}`);
                await createSession(dir);
            }
        }
    }
}

// ── Endpoints ──────────────────────────────────────────────────────────────

// GET /status?staff_id=X
app.get('/status', (req, res) => {
    const staff_id = req.query.staff_id;
    if (!staff_id) {
        return res.status(400).json({ error: 'staff_id obrigatório' });
    }
    
    const sid = String(staff_id);
    const session = sessions[sid];
    
    if (!session || !session.client) {
        return res.json({ connected: false });
    }
    
    res.json({
        connected: session.connected,
        phone: session.phone,
        connecting: session.connecting
    });
});

// GET /qr?staff_id=X
app.get('/qr', (req, res) => {
    const staff_id = req.query.staff_id;
    if (!staff_id) {
        return res.status(400).json({ error: 'staff_id obrigatório' });
    }
    
    const sid = String(staff_id);
    const session = sessions[sid];
    
    if (!session) {
        return res.json({ error: 'Sessão não iniciada. Chame /connect primeiro.' });
    }
    
    if (session.connected) {
        return res.json({ connected: true });
    }
    
    if (!session.qr) {
        return res.json({ qr: null, message: 'QR code ainda não gerado' });
    }
    
    res.json({ qr: session.qr });
});

// POST /connect
app.post('/connect', async (req, res) => {
    const { staff_id } = req.body;
    if (!staff_id) {
        return res.status(400).json({ error: 'staff_id obrigatório' });
    }
    
    const sid = String(staff_id);
    
    // Se já está ligado, retornar sucesso
    if (sessions[sid] && sessions[sid].connected) {
        return res.json({ success: true, message: 'Já ligado' });
    }
    
    // Se já está a ligar, retornar sucesso
    if (sessions[sid] && sessions[sid].connecting) {
        return res.json({ success: true, message: 'A ligar...' });
    }
    
    // Iniciar nova sessão
    createSession(staff_id);
    res.json({ success: true, message: 'A iniciar ligação' });
});

// POST /disconnect
app.post('/disconnect', async (req, res) => {
    const { staff_id } = req.body;
    if (!staff_id) {
        return res.status(400).json({ error: 'staff_id obrigatório' });
    }
    
    const sid = String(staff_id);
    const session = sessions[sid];
    
    if (!session || !session.client) {
        return res.json({ success: true, message: 'Já desligado' });
    }
    
    try {
        await session.client.destroy();
        
        // Apagar ficheiros de sessão
        const sessionDir = getSessionDir(sid);
        if (fs.existsSync(sessionDir)) {
            fs.rmSync(sessionDir, { recursive: true, force: true });
        }
        
        delete sessions[sid];
        
        res.json({ success: true, message: 'Desligado' });
    } catch (e) {
        console.error(`[${sid}] Erro ao desligar:`, e.message);
        res.status(500).json({ error: e.message });
    }
});

// POST /send
app.post('/send', async (req, res) => {
    const { staff_id, to, message } = req.body;
    if (!staff_id || !to || !message) {
        return res.status(400).json({ error: 'staff_id, to e message obrigatórios' });
    }
    
    const sid = String(staff_id);
    const session = sessions[sid];
    
    if (!session || !session.connected) {
        return res.status(400).json({ error: 'WhatsApp não ligado' });
    }
    
    try {
        // Formatar número (remover caracteres não numéricos)
        let phone = to.replace(/\D/g, '');
        
        // Adicionar código de país se não tiver
        if (!phone.startsWith('351') && !phone.startsWith('55') && !phone.startsWith('971')) {
            phone = '351' + phone; // Portugal por defeito
        }
        
        // Adicionar @c.us
        const chatId = phone + '@c.us';
        
        await session.client.sendMessage(chatId, message);
        
        res.json({ success: true, message: 'Mensagem enviada' });
    } catch (e) {
        console.error(`[${sid}] Erro ao enviar mensagem:`, e.message);
        res.status(500).json({ error: e.message });
    }
});

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', sessions: Object.keys(sessions).length });
});

// ── Iniciar servidor ───────────────────────────────────────────────────────

app.listen(PORT, async () => {
    console.log(`✓ DPS WhatsApp Service a correr na porta ${PORT}`);
    console.log(`✓ Sessões guardadas em: ${SESSIONS_DIR}`);
    
    // Carregar sessões existentes
    await loadExistingSessions();
    
    console.log('✓ Pronto para receber pedidos');
});
