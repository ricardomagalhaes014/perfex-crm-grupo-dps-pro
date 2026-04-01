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
const SESSIONS_DIR = path.resolve(__dirname, 'wa-sessions');

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
// { staff_id: { client, qr, connected, phone, connecting, reconnectTimer } }
const sessions = {};

/**
 * Verifica se existe uma sessão guardada válida para o staff_id
 * O LocalAuth guarda em: <dataPath>/.wwebjs_auth/session-<clientId>/
 */
function hasStoredSession(staff_id) {
    const sid = String(staff_id);
    const sessionDir = getSessionDir(sid);
    if (!fs.existsSync(sessionDir)) return false;

    // Estrutura principal do LocalAuth
    const authDir = path.join(sessionDir, '.wwebjs_auth', 'session-staff-' + sid);
    if (fs.existsSync(authDir)) {
        try {
            const files = fs.readdirSync(authDir);
            if (files.length > 0) {
                console.log(`[${sid}] Sessão guardada: ${authDir} (${files.length} ficheiros)`);
                return true;
            }
        } catch(e) {}
    }

    // Estrutura alternativa
    const altDir = path.join(sessionDir, '.wwebjs_auth');
    if (fs.existsSync(altDir)) {
        try {
            const entries = fs.readdirSync(altDir);
            if (entries.length > 0) {
                console.log(`[${sid}] Sessão guardada: ${altDir}`);
                return true;
            }
        } catch(e) {}
    }

    return false;
}

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
            if (sessions[sid].reconnectTimer) clearTimeout(sessions[sid].reconnectTimer);
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
        reconnectTimer: null,
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
            console.log(`[${sid}] WhatsApp pronto e ligado`);
            sessions[sid].connected = true;
            sessions[sid].connecting = false;
            sessions[sid].qr = null;
            
            // client.info é um objecto SÍNCRONO, NÃO uma Promise
            try {
                if (client.info && client.info.wid) {
                    sessions[sid].phone = client.info.wid.user;
                    console.log(`[${sid}] Número: ${client.info.wid.user}`);
                }
            } catch(e) {
                console.error(`[${sid}] Erro ao obter número:`, e.message);
            }
        });
        
        // Autenticação bem-sucedida (sessão guardada em disco)
        client.on('authenticated', () => {
            console.log(`[${sid}] Autenticado - sessão guardada`);
            sessions[sid].qr = null;
            sessions[sid].connecting = false;
        });
        
        // Falha de autenticação - limpar sessão corrompida
        client.on('auth_failure', (msg) => {
            console.error(`[${sid}] Falha de autenticação:`, msg);
            sessions[sid].connecting = false;
            sessions[sid].connected = false;
            sessions[sid].qr = null;
            // Limpar ficheiros de sessão corrompidos
            const sd = getSessionDir(sid);
            if (fs.existsSync(sd)) {
                try { fs.rmSync(sd, { recursive: true, force: true }); console.log(`[${sid}] Sessão corrompida removida`); } catch(e) {}
            }
        });
        
        // Desconectado - reconectar automaticamente se não foi logout intencional
        client.on('disconnected', (reason) => {
            console.log(`[${sid}] Desconectado: ${reason}`);
            sessions[sid].connected = false;
            sessions[sid].connecting = false;
            sessions[sid].qr = null;

            if (reason === 'LOGOUT') {
                console.log(`[${sid}] Logout remoto - a limpar sessão`);
                const sd = getSessionDir(sid);
                if (fs.existsSync(sd)) {
                    try { fs.rmSync(sd, { recursive: true, force: true }); } catch(e) {}
                }
                delete sessions[sid];
                return;
            }

            // Reconectar automaticamente após 15 segundos
            console.log(`[${sid}] A reconectar em 15 segundos...`);
            sessions[sid].reconnectTimer = setTimeout(async () => {
                if (sessions[sid] && !sessions[sid].connected && !sessions[sid].connecting) {
                    console.log(`[${sid}] A reconectar...`);
                    createSession(sid).catch(e => console.error(`[${sid}] Erro reconexão:`, e.message));
                }
            }, 15000);
        });
        
        // Inicializar cliente (não bloquear a função)
        client.initialize().catch(e => {
            console.error(`[${sid}] Erro ao inicializar:`, e.message);
            if (sessions[sid]) { sessions[sid].connecting = false; sessions[sid].connected = false; }
        });
        
    } catch (e) {
        console.error(`[${sid}] Erro ao criar sessão:`, e.message);
        sessions[sid].connecting = false;
        sessions[sid].connected = false;
    }
}

// Carregar sessões existentes ao iniciar o servidor
async function loadExistingSessions() {
    console.log(`A carregar sessões de: ${SESSIONS_DIR}`);
    
    if (!fs.existsSync(SESSIONS_DIR)) {
        console.log('Directório de sessões não existe');
        return;
    }
    
    let restored = 0;
    const dirs = fs.readdirSync(SESSIONS_DIR);
    for (const dir of dirs) {
        const fullPath = path.join(SESSIONS_DIR, dir);
        try {
            if (!fs.statSync(fullPath).isDirectory()) continue;
            if (hasStoredSession(dir)) {
                console.log(`[${dir}] A restaurar sessão...`);
                // Não aguardar - restaurar em paralelo
                createSession(dir).catch(e => console.error(`[${dir}] Erro ao restaurar:`, e.message));
                restored++;
            } else {
                console.log(`[${dir}] Pasta sem sessão válida - ignorar`);
            }
        } catch(e) {
            console.error(`Erro ao processar ${dir}:`, e.message);
        }
    }
    console.log(`${restored} sessão(ões) a restaurar`);
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
        return res.json({ connected: false, connecting: false });
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
        return res.json({ qr: null, message: 'QR code ainda não gerado', connecting: session.connecting });
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
    
    // Iniciar nova sessão (não bloquear a resposta)
    createSession(staff_id).catch(e => console.error(`[${sid}] Erro /connect:`, e.message));
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
        if (session.reconnectTimer) clearTimeout(session.reconnectTimer);
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
        // Forçar limpeza mesmo com erro
        delete sessions[sid];
        res.json({ success: true, message: 'Desligado (com erro)' });
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
    const info = {};
    for (const [sid, s] of Object.entries(sessions)) {
        info[sid] = { connected: s.connected, connecting: s.connecting, phone: s.phone };
    }
    res.json({ status: 'ok', sessions_count: Object.keys(sessions).length, sessions: info, sessions_dir: SESSIONS_DIR });
});

// ── Iniciar servidor ───────────────────────────────────────────────────────

app.listen(PORT, async () => {
    console.log(`✓ DPS WhatsApp Service a correr na porta ${PORT}`);
    console.log(`✓ Sessões guardadas em: ${SESSIONS_DIR}`);
    
    // Carregar sessões existentes (sem bloquear)
    loadExistingSessions().catch(e => console.error('Erro ao carregar sessões:', e.message));
    
    console.log('✓ Pronto para receber pedidos');
});
