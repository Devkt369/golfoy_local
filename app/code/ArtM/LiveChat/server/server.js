'use strict';

require('dotenv').config({ path: require('node:path').join(__dirname, '.env') });

const crypto = require('node:crypto');
const http = require('node:http');
const mysql = require('mysql2/promise');
const { Server } = require('socket.io');

const port = Number(process.env.PORT || 3001);
const secret = process.env.ARTM_LIVECHAT_SECRET || '';
const prefix = process.env.TABLE_PREFIX || '';
const allowedOrigins = (process.env.MAGENTO_ORIGIN || '*').split(',').map((origin) => origin.trim()).filter(Boolean);

if (secret.length < 32) {
    throw new Error('ARTM_LIVECHAT_SECRET must contain at least 32 characters.');
}
if (!/^[A-Za-z0-9_]*$/.test(prefix)) {
    throw new Error('TABLE_PREFIX may contain only letters, numbers, and underscores.');
}

const conversationsTable = `\`${prefix}artm_livechat_conversation\``;
const messagesTable = `\`${prefix}artm_livechat_message\``;
const pool = mysql.createPool({
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || 3306),
    database: process.env.DB_NAME,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD || '',
    waitForConnections: true,
    connectionLimit: 10,
    charset: 'utf8mb4'
});

const server = http.createServer((request, response) => {
    if (request.url === '/healthz') {
        response.writeHead(200, { 'content-type': 'application/json' });
        response.end(JSON.stringify({ status: 'ok' }));
        return;
    }
    response.writeHead(404);
    response.end();
});

const io = new Server(server, {
    cors: { origin: allowedOrigins.length === 1 && allowedOrigins[0] === '*' ? '*' : allowedOrigins, methods: ['GET', 'POST'] },
    maxHttpBufferSize: 16 * 1024
});

function decodeBase64Url(value) {
    return Buffer.from(value.replace(/-/g, '+').replace(/_/g, '/'), 'base64');
}

function verifyToken(token) {
    const parts = String(token || '').split('.');
    if (parts.length !== 3) {
        throw new Error('Invalid access token.');
    }
    const header = JSON.parse(decodeBase64Url(parts[0]).toString('utf8'));
    if (header.alg !== 'HS256' || header.typ !== 'JWT') {
        throw new Error('Invalid access token.');
    }
    const unsigned = `${parts[0]}.${parts[1]}`;
    const expected = crypto.createHmac('sha256', secret).update(unsigned).digest();
    const actual = decodeBase64Url(parts[2]);
    if (actual.length !== expected.length || !crypto.timingSafeEqual(actual, expected)) {
        throw new Error('Invalid access token.');
    }

    const claims = JSON.parse(decodeBase64Url(parts[1]).toString('utf8'));
    if (!/^\d+$/.test(String(claims.sub)) || Number(claims.sub) < 1 || !['customer', 'agent'].includes(claims.role) || !Number.isInteger(claims.exp) || claims.exp <= Math.floor(Date.now() / 1000)) {
        throw new Error('Expired or invalid access token.');
    }
    return claims;
}

io.use((socket, next) => {
    try {
        socket.data.actor = verifyToken(socket.handshake.auth && socket.handshake.auth.token);
        next();
    } catch (error) {
        next(new Error('Unauthorized'));
    }
});

function sendError(socket, error) {
    console.error('Live chat event failed:', error.message);
    socket.emit('chat:error', { message: 'Your message could not be processed. Please try again.' });
}

function messageRow(row) {
    return {
        id: Number(row.entity_id),
        conversationId: Number(row.conversation_id),
        senderType: row.sender_type,
        senderId: Number(row.sender_id),
        senderName: row.sender_name,
        message: row.message,
        createdAt: row.created_at
    };
}

async function getMessages(conversationId) {
    const [rows] = await pool.execute(
        `SELECT entity_id, conversation_id, sender_type, sender_id, sender_name, message, created_at
         FROM ${messagesTable} WHERE conversation_id = ? ORDER BY entity_id DESC LIMIT 100`,
        [conversationId]
    );
    return rows.reverse().map(messageRow);
}

async function listConversations() {
    const [rows] = await pool.execute(
        `SELECT c.entity_id, c.customer_id, c.customer_name, c.customer_email, c.status, c.updated_at,
                m.message AS last_message
         FROM ${conversationsTable} c
         LEFT JOIN ${messagesTable} m ON m.entity_id = (
             SELECT MAX(latest.entity_id) FROM ${messagesTable} latest WHERE latest.conversation_id = c.entity_id
         )
         WHERE c.status = 'open' ORDER BY c.updated_at DESC LIMIT 200`
    );
    return rows.map((row) => ({
        id: Number(row.entity_id),
        customerId: Number(row.customer_id),
        customerName: row.customer_name,
        customerEmail: row.customer_email,
        status: row.status,
        updatedAt: row.updated_at,
        lastMessage: row.last_message
    }));
}

io.on('connection', (socket) => {
    const actor = socket.data.actor;
    if (actor.role === 'agent') {
        socket.join('agents');
        io.emit('agents:presence', { online: io.sockets.adapter.rooms.get('agents').size });
    } else {
        socket.emit('agents:presence', { online: io.sockets.adapter.rooms.get('agents')?.size || 0 });
    }

    socket.on('disconnect', () => {
        if (actor.role === 'agent') {
            io.emit('agents:presence', { online: io.sockets.adapter.rooms.get('agents')?.size || 0 });
        }
    });

    socket.on('conversation:open', async () => {
        if (actor.role !== 'customer') {
            return sendError(socket, new Error('Only customers can open conversations.'));
        }
        try {
            const [existing] = await pool.execute(
                `SELECT entity_id FROM ${conversationsTable} WHERE customer_id = ? AND status = 'open' ORDER BY entity_id DESC LIMIT 1`,
                [Number(actor.sub)]
            );
            let conversationId;
            if (existing.length) {
                conversationId = Number(existing[0].entity_id);
            } else {
                const [created] = await pool.execute(
                    `INSERT INTO ${conversationsTable} (customer_id, customer_name, customer_email, status)
                     VALUES (?, ?, ?, 'open')`,
                    [Number(actor.sub), actor.name || 'Customer', actor.email || '']
                );
                conversationId = Number(created.insertId);
                io.to('agents').emit('conversation:updated');
            }
            socket.join(`conversation:${conversationId}`);
            socket.emit('conversation:ready', {
                conversation: { id: conversationId },
                messages: await getMessages(conversationId)
            });
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversations:list', async () => {
        if (actor.role !== 'agent') {
            return sendError(socket, new Error('Agent permission required.'));
        }
        try {
            socket.emit('conversations:list', { conversations: await listConversations() });
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversation:join', async (data = {}) => {
        if (actor.role !== 'agent') {
            return sendError(socket, new Error('Agent permission required.'));
        }
        const conversationId = Number(data.conversationId);
        if (!Number.isSafeInteger(conversationId) || conversationId < 1) {
            return sendError(socket, new Error('Invalid conversation.'));
        }
        try {
            const [rows] = await pool.execute(
                `SELECT entity_id, status FROM ${conversationsTable} WHERE entity_id = ?`,
                [conversationId]
            );
            if (!rows.length) {
                return sendError(socket, new Error('Conversation not found.'));
            }
            socket.join(`conversation:${conversationId}`);
            socket.emit('conversation:ready', {
                conversation: { id: conversationId, status: rows[0].status },
                messages: await getMessages(conversationId)
            });
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('message:send', async (data = {}) => {
        const conversationId = Number(data.conversationId);
        const message = typeof data.message === 'string' ? data.message.trim() : '';
        if (!Number.isSafeInteger(conversationId) || conversationId < 1 || !message || message.length > 2000) {
            return sendError(socket, new Error('Invalid message.'));
        }
        try {
            const [rows] = await pool.execute(
                `SELECT entity_id, customer_id, status FROM ${conversationsTable} WHERE entity_id = ?`,
                [conversationId]
            );
            const conversation = rows[0];
            if (!conversation || conversation.status !== 'open') {
                return sendError(socket, new Error('Conversation is closed.'));
            }
            if (actor.role === 'customer' && Number(conversation.customer_id) !== Number(actor.sub)) {
                return sendError(socket, new Error('Conversation access denied.'));
            }
            const [inserted] = await pool.execute(
                `INSERT INTO ${messagesTable} (conversation_id, sender_type, sender_id, sender_name, message)
                 VALUES (?, ?, ?, ?, ?)`,
                [conversationId, actor.role, Number(actor.sub), actor.name || (actor.role === 'agent' ? 'Customer Care' : 'Customer'), message]
            );
            await pool.execute(`UPDATE ${conversationsTable} SET updated_at = CURRENT_TIMESTAMP WHERE entity_id = ?`, [conversationId]);
            const [stored] = await pool.execute(
                `SELECT entity_id, conversation_id, sender_type, sender_id, sender_name, message, created_at
                 FROM ${messagesTable} WHERE entity_id = ?`,
                [inserted.insertId]
            );
            io.to(`conversation:${conversationId}`).emit('conversation:message', messageRow(stored[0]));
            io.to('agents').emit('conversation:updated');
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversation:close', async (data = {}) => {
        if (actor.role !== 'agent') {
            return sendError(socket, new Error('Agent permission required.'));
        }
        const conversationId = Number(data.conversationId);
        if (!Number.isSafeInteger(conversationId) || conversationId < 1) {
            return sendError(socket, new Error('Invalid conversation.'));
        }
        try {
            const [updated] = await pool.execute(
                `UPDATE ${conversationsTable} SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE entity_id = ? AND status = 'open'`,
                [conversationId]
            );
            if (updated.affectedRows) {
                io.to(`conversation:${conversationId}`).emit('conversation:closed', { conversationId });
                io.to('agents').emit('conversation:closed', { conversationId });
            }
        } catch (error) {
            sendError(socket, error);
        }
    });
});

server.listen(port, () => {
    console.log(`ArtM LiveChat listening on port ${port}`);
});

async function shutdown() {
    io.close();
    await pool.end();
    process.exit(0);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);