/**
 * DPS WhatsApp Microservice
 * Porta: 3001
 * API Key: dps-wa-secret-2026
 * 
 * Suporta múltiplos utilizadores (staff_id)
 * Sessões persistidas em /var/wa-sessions/{staff_id}/
 * 
 * Endpoints:
 *   GET  /status?staff_id=X      - Estado da ligação
 *   GET  /qr?staff_id=X          - QR code (base64 PNG)
 *   POST /connect  {staff_id}    - Iniciar ligação
 *   POST /disconnect {staff_id}  - Desligar
 *   POST /send {staff_id, to, message} - Enviar mensagem
 */

'use strict';

const express    = require('express');
const QRCode     = require('qrcode');
const path       = require('path');
const fs         = require('fs');
const pino       = require('pino');

const logger = pino({ level: 'info' });

// Importar baileys de forma compatível com CommonJS
let makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion;
try {
    const baileys = require('@whiskeysockets/baileys');
    makeWASocket           = baileys.default || baileys.makeWASocket || baileys;
    useMultiFileAuthState  = baileys.useMultiFileAuthState;
    DisconnectReason       = baileys.DisconnectReason;
    fetchLatestBaileysVersion = baileys.fetchLatestBaileysVersion;
    if (!makeWASocket && baileys.makeWASocket) makeWASocket = baileys.makeWASocket;
    if (!makeWASocket) makeWASocket = baileys;
} catch (e) {
    logger.error('Erro ao importar baileys: ' + e.message);
    process.exit(1);
}

const API_KEY      = process.env.WA_API_KEY || 'dps-wa-secret-2026';
const PORT         = parseInt(process.env.PORT || '3001');
const SESSIONS_DIR = process.env.SESSIONS_DIR || '/var/wa-sessions';

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
// { staff_id: { socket, qr, connected, phone, connecting } }
const sessions = {};

function getSessionDir(staff_id) {
    return path.join(SESSIONS_DIR, String(staff_id));
}

async function createSession(staff_id) {
    const sid = String(staff_id);
    
    if (sessions[sid] && sessions[sid].connecting) {
        logger.info(`Sessão ${sid} já está a ligar`);
        return;
    }
    
    // Fechar sessão anterior se existir
    if (sessions[sid] && sessions[sid].socket) {
        try { sessions[sid].socket.end(); } catch(e) {}
    }
    
    sessions[sid] = {
        socket:     null,
        qr:         null,
        connected:  false,
        phone:      null,
        connecting: true,
    };
    
    const sessionDir = getSessionDir(sid);
    if (!fs.existsSync(sessionDir)) {
        fs.mkdirSync(sessionDir, { recursive: true });
    }
    
    try {
        const { state, saveCreds } = await useMultiFileAuthState(sessionDir);
        const { version } = await fetchLatestBaileysVersion();
        
        const sock = makeWASocket({
            version,
            auth: state,
            printQRInTerminal: false,
            logger: pino({ level: 'silent' }),
            browser: ['DPS CRM', 'Chrome', '120.0.0'],
            connectTimeoutMs: 60000,
            keepAliveIntervalMs: 30000,
            retryRequestDelayMs: 2000,
        });
        
        sessions[sid].socket = sock;
        sessions[sid].connecting = false;
        
        sock.ev.on('creds.update', saveCreds);
        
        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;
            
            if (qr) {
                try {
                    const qrDataUrl = await QRCode.toDataURL(qr);
                    sessions[sid].qr = qrDataUrl;
                    logger.info(`QR gerado para staff ${sid}`);
                } catch (e) {
                    logger.error('Erro ao gerar QR: ' + e.message);
                }
            }
            
            if (connection === 'open') {
                sessions[sid].connected = true;
                sessions[sid].qr = null;
                sessions[sid].connecting = false;
                const jid = sock.user?.id || '';
                sessions[sid].phone = jid.split(':')[0].split('@')[0];
                logger.info(`Staff ${sid} ligado: ${sessions[sid].phone}`);
            }
            
            if (connection === 'close') {
                const code = lastDisconnect?.error?.output?.statusCode;
                const reason = DisconnectReason[code] || code;
                logger.info(`Staff ${sid} desligado. Razão: ${reason}`);
                
                sessions[sid].connected = false;
                sessions[sid].qr = null;
                
                // Reconectar automaticamente EXCEPTO se foi logout explícito
                if (code !== DisconnectReason.loggedOut && code !== 401) {
                    logger.info(`A reconectar staff ${sid} em 5s...`);
                    setTimeout(() => createSession(staff_id), 5000);
                } else {
                    // Logout: apagar ficheiros de sessão
                    logger.info(`Logout detectado para staff ${sid}. A apagar sessão.`);
                    try {
                        fs.rmSync(sessionDir, { recursive: true, force: true });
                    } catch(e) {}
                    delete sessions[sid];
                }
            }
        });
        
        sock.ev.on('messages.upsert', async ({ messages }) => {
            // Processar mensagens recebidas se necessário
        });
        
    } catch (e) {
        logger.error(`Erro ao criar sessão ${sid}: ${e.message}`);
        sessions[sid].connecting = false;
    }
}

// Restaurar sessões existentes ao iniciar
async function restoreExistingSessions() {
    if (!fs.existsSync(SESSIONS_DIR)) return;
    const dirs = fs.readdirSync(SESSIONS_DIR);
    for (const dir of dirs) {
        const sessionDir = path.join(SESSIONS_DIR, dir);
        if (fs.statSync(sessionDir).isDirectory()) {
            // Verificar se há ficheiros de credenciais
            const credsFile = path.join(sessionDir, 'creds.json');
            if (fs.existsSync(credsFile)) {
                logger.info(`A restaurar sessão para staff ${dir}`);
                await createSession(dir);
            }
        }
    }
}

// ─── Endpoints ────────────────────────────────────────────────────────────────

// GET /status?staff_id=X
app.get('/status', (req, res) => {
    const sid = String(req.query.staff_id || '');
    if (!sid) return res.status(400).json({ error: 'staff_id obrigatório' });
    
    const sess = sessions[sid];
    if (!sess) {
        return res.json({ connected: false, phone: null });
    }
    return res.json({
        connected: sess.connected,
        phone:     sess.phone,
        has_qr:    !!sess.qr,
    });
});

// GET /qr?staff_id=X
app.get('/qr', (req, res) => {
    const sid = String(req.query.staff_id || '');
    if (!sid) return res.status(400).json({ error: 'staff_id obrigatório' });
    
    const sess = sessions[sid];
    if (!sess) {
        return res.json({ connected: false, qr: null });
    }
    if (sess.connected) {
        return res.json({ connected: true, qr: null });
    }
    return res.json({ connected: false, qr: sess.qr || null });
});

// POST /connect  { staff_id }
app.post('/connect', async (req, res) => {
    const sid = String(req.body.staff_id || '');
    if (!sid) return res.status(400).json({ error: 'staff_id obrigatório' });
    
    const sess = sessions[sid];
    if (sess && sess.connected) {
        return res.json({ success: true, already_connected: true });
    }
    
    await createSession(sid);
    return res.json({ success: true });
});

// POST /disconnect  { staff_id }
app.post('/disconnect', async (req, res) => {
    const sid = String(req.body.staff_id || '');
    if (!sid) return res.status(400).json({ error: 'staff_id obrigatório' });
    
    const sess = sessions[sid];
    if (sess && sess.socket) {
        try {
            await sess.socket.logout();
        } catch(e) {
            try { sess.socket.end(); } catch(e2) {}
        }
    }
    
    // Apagar ficheiros de sessão
    const sessionDir = getSessionDir(sid);
    try {
        fs.rmSync(sessionDir, { recursive: true, force: true });
    } catch(e) {}
    
    delete sessions[sid];
    return res.json({ success: true });
});

// POST /send  { staff_id, to, message }
app.post('/send', async (req, res) => {
    const sid     = String(req.body.staff_id || '');
    const to      = String(req.body.to || '').replace(/[^0-9]/g, '');
    const message = String(req.body.message || '');
    
    if (!sid || !to || !message) {
        return res.status(400).json({ error: 'staff_id, to e message são obrigatórios' });
    }
    
    const sess = sessions[sid];
    if (!sess || !sess.connected || !sess.socket) {
        return res.status(503).json({ error: 'WhatsApp não ligado para este utilizador' });
    }
    
    try {
        const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
        await sess.socket.sendMessage(jid, { text: message });
        return res.json({ success: true });
    } catch (e) {
        logger.error(`Erro ao enviar para ${to}: ${e.message}`);
        return res.status(500).json({ error: e.message });
    }
});

// Health check
app.get('/health', (req, res) => {
    const activeSessions = Object.keys(sessions).filter(k => sessions[k].connected).length;
    res.json({ 
        status: 'ok', 
        active_sessions: activeSessions,
        total_sessions: Object.keys(sessions).length,
    });
});

// Iniciar servidor
app.listen(PORT, '127.0.0.1', async () => {
    logger.info(`DPS WhatsApp Service a correr na porta ${PORT}`);
    await restoreExistingSessions();
    logger.info('Sessões anteriores restauradas');
});

// Graceful shutdown
process.on('SIGTERM', () => {
    logger.info('A terminar o serviço...');
    process.exit(0);
});
