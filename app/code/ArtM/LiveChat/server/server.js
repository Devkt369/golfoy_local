'use strict';

require('dotenv').config({ path: require('node:path').join(__dirname, '.env') });

const crypto = require('node:crypto');
const http = require('node:http');
const mysql = require('mysql2/promise');
const { createAdapter } = require('@socket.io/redis-adapter');
const { createClient } = require('redis');
const { Server } = require('socket.io');

const port = Number(process.env.PORT || 3001);
const secret = process.env.ARTM_LIVECHAT_SECRET || '';
const prefix = process.env.TABLE_PREFIX || '';
const allowedOrigins = (process.env.MAGENTO_ORIGIN || '*').split(',').map((origin) => origin.trim()).filter(Boolean);
let redisAdapterEnabled = false;

if (secret.length < 32) {
    throw new Error('ARTM_LIVECHAT_SECRET must contain at least 32 characters.');
}
if (!/^[A-Za-z0-9_]*$/.test(prefix)) {
    throw new Error('TABLE_PREFIX may contain only letters, numbers, and underscores.');
}

const conversationsTable = `\`${prefix}artm_livechat_conversation\``;
const messagesTable = `\`${prefix}artm_livechat_message\``;
const adminMessagePageSize = 5;
const conversationInactivityMinutes = 30;
const requestCategories = ['Order', 'Delivery', 'Return / Exchange', 'Payment', 'Product', 'Other'];
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
        const redisReady = !process.env.REDIS_URL || (redisAdapterEnabled && redisPubClient && redisPubClient.isReady && redisSubClient && redisSubClient.isReady);
        response.writeHead(redisReady ? 200 : 503, { 'content-type': 'application/json' });
        response.end(JSON.stringify({ status: redisReady ? 'ok' : 'unavailable' }));
        return;
    }
    response.writeHead(404);
    response.end();
});

const io = new Server(server, {
    cors: { origin: allowedOrigins.length === 1 && allowedOrigins[0] === '*' ? '*' : allowedOrigins, methods: ['GET', 'POST'] },
    maxHttpBufferSize: 16 * 1024
});
let redisPubClient;
let redisSubClient;
let cachedAgentCount = 0;
let agentCountUpdatedAt = 0;
let isShuttingDown = false;
let inactivityTimer;

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
    if (isShuttingDown) {
        return;
    }
    console.error('Live chat event failed:', error.message);
    socket.emit('chat:error', { message: 'Your message could not be processed. Please try again.' });
}

async function getAgentCount(forceRefresh = false) {
    if (isShuttingDown) {
        return cachedAgentCount;
    }
    if (!forceRefresh && Date.now() - agentCountUpdatedAt < 1000) {
        return cachedAgentCount;
    }
    const agentSockets = await io.in('agents').fetchSockets();
    cachedAgentCount = agentSockets.length;
    agentCountUpdatedAt = Date.now();
    return cachedAgentCount;
}

async function broadcastAgentPresence() {
    if (isShuttingDown) {
        return;
    }
    try {
        io.emit('agents:presence', { online: await getAgentCount(true) });
    } catch (error) {
        console.error('Unable to update agent presence:', error.message);
    }
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

async function assignAgentAndCreateNotice(conversationId, agentId, agentName) {
    const connection = await pool.getConnection();
    let transactionStarted = false;
    try {
        await connection.beginTransaction();
        transactionStarted = true;
        const [rows] = await connection.execute(
            `SELECT status, assigned_agent_id FROM ${conversationsTable} WHERE entity_id = ? FOR UPDATE`,
            [conversationId]
        );
        if (!rows.length || rows[0].status !== 'open' || rows[0].assigned_agent_id !== null) {
            await connection.commit();
            transactionStarted = false;
            return null;
        }

        const message = `${agentName} joined the conversation.`;
        await connection.execute(
            `UPDATE ${conversationsTable} SET assigned_agent_id = ?, assigned_agent_name = ? WHERE entity_id = ?`,
            [agentId, agentName, conversationId]
        );
        const [inserted] = await connection.execute(
            `INSERT INTO ${messagesTable} (conversation_id, sender_type, sender_id, sender_name, message)
             VALUES (?, 'system', 0, 'Customer Care', ?)`,
            [conversationId, message]
        );
        const [stored] = await connection.execute(
            `SELECT entity_id, conversation_id, sender_type, sender_id, sender_name, message, created_at
             FROM ${messagesTable} WHERE entity_id = ?`,
            [inserted.insertId]
        );
        await connection.commit();
        transactionStarted = false;
        return messageRow(stored[0]);
    } catch (error) {
        if (transactionStarted) {
            await connection.rollback();
        }
        throw error;
    } finally {
        connection.release();
    }
}

async function getAdminMessagePage(conversationId, beforeMessageId = null) {
    const whereClause = beforeMessageId === null ? 'conversation_id = ?' : 'conversation_id = ? AND entity_id < ?';
    const parameters = beforeMessageId === null ? [conversationId] : [conversationId, beforeMessageId];
    const [rows] = await pool.execute(
        `SELECT entity_id, conversation_id, sender_type, sender_id, sender_name, message, created_at
         FROM ${messagesTable} WHERE ${whereClause} ORDER BY entity_id DESC LIMIT ${adminMessagePageSize + 1}`,
        parameters
    );
    const hasOlder = rows.length > adminMessagePageSize;
    return {
        messages: rows.slice(0, adminMessagePageSize).reverse().map(messageRow),
        hasOlder
    };
}

async function fetchConversations(whereClause, parameters = []) {
    const [rows] = await pool.execute(
        `SELECT c.entity_id, c.customer_id, c.customer_name, c.customer_email, c.status, c.assigned_agent_id,
                c.assigned_agent_name, c.intake_category, c.order_number, c.issue_summary, c.customer_message,
                c.created_at, c.updated_at,
                m.message AS last_message
         FROM ${conversationsTable} c
         LEFT JOIN ${messagesTable} m ON m.entity_id = (
             SELECT MAX(latest.entity_id) FROM ${messagesTable} latest WHERE latest.conversation_id = c.entity_id
         )
         WHERE ${whereClause}
         ORDER BY CASE WHEN c.status = 'open' AND c.assigned_agent_id IS NULL THEN 0
                       WHEN c.status = 'open' THEN 1 ELSE 2 END, c.created_at ASC LIMIT 500`,
        parameters
    );
    let queuePosition = 0;
    return rows.map((row) => {
        const isQueued = row.status === 'open' && row.assigned_agent_id === null;
        if (isQueued) {
            queuePosition += 1;
        }
        return {
            id: Number(row.entity_id),
            customerId: Number(row.customer_id),
            customerName: row.customer_name,
            customerEmail: row.customer_email,
            status: row.status,
            assignedAgentId: row.assigned_agent_id === null ? null : Number(row.assigned_agent_id),
            assignedAgentName: row.assigned_agent_name,
            category: row.intake_category,
            orderNumber: row.order_number,
            issueSummary: row.issue_summary,
            customerMessage: row.customer_message,
            createdAt: row.created_at,
            updatedAt: row.updated_at,
            lastMessage: row.last_message,
            queuePosition: isQueued ? queuePosition : null
        };
    });
}

async function listConversations(agentId) {
    return fetchConversations(
        '(c.status = \'closed\' OR c.assigned_agent_id = ? OR (c.status = \'open\' AND c.assigned_agent_id IS NULL))',
        [agentId]
    );
}

async function listCustomerConversations(customerId) {
    return fetchConversations('c.customer_id = ?', [customerId]);
}

async function getQueuePosition(conversationId) {
    const [rows] = await pool.execute(
        `SELECT COUNT(*) AS position FROM ${conversationsTable} queued
         WHERE queued.status = 'open' AND queued.assigned_agent_id IS NULL
           AND (queued.created_at < (SELECT target.created_at FROM ${conversationsTable} target WHERE target.entity_id = ?)
                OR (queued.created_at = (SELECT target.created_at FROM ${conversationsTable} target WHERE target.entity_id = ?)
                    AND queued.entity_id <= ?))`,
        [conversationId, conversationId, conversationId]
    );
    return Number(rows[0].position);
}

async function broadcastQueuePositions() {
    const [rows] = await pool.execute(
        `SELECT entity_id FROM ${conversationsTable}
         WHERE status = 'open' AND assigned_agent_id IS NULL ORDER BY created_at ASC, entity_id ASC`
    );
    rows.forEach((row, index) => {
        io.to(`conversation:${Number(row.entity_id)}`).emit('customer:queue', { position: index + 1 });
    });
}

async function expireInactiveConversations() {
    const [staleConversations] = await pool.execute(
        `SELECT entity_id FROM ${conversationsTable}
         WHERE status = 'open' AND updated_at < DATE_SUB(NOW(), INTERVAL ${conversationInactivityMinutes} MINUTE)`
    );
    let closedAny = false;
    for (const row of staleConversations) {
        const conversationId = Number(row.entity_id);
        const [updated] = await pool.execute(
            `UPDATE ${conversationsTable}
             SET status = 'closed', updated_at = CURRENT_TIMESTAMP
             WHERE entity_id = ? AND status = 'open'
               AND updated_at < DATE_SUB(NOW(), INTERVAL ${conversationInactivityMinutes} MINUTE)`,
            [conversationId]
        );
        if (updated.affectedRows) {
            io.to(`conversation:${conversationId}`).emit('conversation:closed', { conversationId });
            io.to('agents').emit('conversation:closed', { conversationId });
            closedAny = true;
        }
    }
    if (closedAny) {
        io.to('agents').emit('conversation:updated');
        await broadcastQueuePositions();
    }
}

async function isAgentOnline(agentId) {
    const agentSockets = await io.in('agents').fetchSockets();
    return agentSockets.some((agentSocket) => Number(agentSocket.data.actor.sub) === agentId);
}

async function sendCurrentCustomerRequest(socket, customerId) {
    await expireInactiveConversations();
    const [rows] = await pool.execute(
        `SELECT entity_id, status, assigned_agent_id, assigned_agent_name, intake_category, order_number,
                issue_summary, customer_message, created_at
         FROM ${conversationsTable}
         WHERE customer_id = ? AND status = 'open' ORDER BY entity_id DESC LIMIT 1`,
        [customerId]
    );
    if (!rows.length) {
        socket.emit('customer:request:state', { conversation: null });
        return;
    }
    const row = rows[0];
    const conversationId = Number(row.entity_id);
    let assignedAgentId = row.assigned_agent_id === null ? null : Number(row.assigned_agent_id);
    let assignedAgentName = row.assigned_agent_name;
    if (assignedAgentId !== null && !(await isAgentOnline(assignedAgentId))) {
        const [updated] = await pool.execute(
            `UPDATE ${conversationsTable}
             SET assigned_agent_id = NULL, assigned_agent_name = NULL, updated_at = CURRENT_TIMESTAMP
             WHERE entity_id = ? AND status = 'open' AND assigned_agent_id = ?`,
            [conversationId, assignedAgentId]
        );
        if (updated.affectedRows) {
            assignedAgentId = null;
            assignedAgentName = null;
            io.to('agents').emit('conversation:updated');
            await broadcastQueuePositions();
        }
    }
    socket.join(`conversation:${conversationId}`);
    const conversation = {
        id: conversationId,
        status: row.status,
        assignedAgentId,
        assignedAgentName,
        category: row.intake_category,
        orderNumber: row.order_number,
        issueSummary: row.issue_summary,
        customerMessage: row.customer_message,
        createdAt: row.created_at,
        queuePosition: assignedAgentId === null ? await getQueuePosition(conversationId) : null
    };
    if (assignedAgentId !== null) {
        socket.emit('conversation:ready', {
            conversation,
            messages: await getMessages(conversationId)
        });
    } else {
        socket.emit('customer:request:state', {
            conversation,
            messages: await getMessages(conversationId)
        });
    }
}

io.on('connection', (socket) => {
    const actor = socket.data.actor;
    if (actor.role === 'agent') {
        socket.join('agents');
        void broadcastAgentPresence();
    } else {
        void getAgentCount().then((agentCount) => {
            socket.emit('agents:presence', { online: agentCount });
        }).catch((error) => {
            if (!isShuttingDown) {
                console.error('Unable to read agent presence:', error.message);
            }
        });
    }

    socket.on('disconnect', () => {
        if (actor.role === 'agent' && !isShuttingDown) {
            void broadcastAgentPresence();
        }
    });

    socket.on('conversation:typing', (data = {}) => {
        const conversationId = Number(data.conversationId);
        if (!Number.isSafeInteger(conversationId) || conversationId < 1 || typeof data.isTyping !== 'boolean') {
            return;
        }
        const room = `conversation:${conversationId}`;
        if (!socket.rooms.has(room)) {
            return;
        }
        socket.to(room).emit('conversation:typing', {
            conversationId,
            senderType: actor.role,
            isTyping: data.isTyping
        });
    });

    socket.on('customer:current', async () => {
        if (actor.role !== 'customer') {
            return sendError(socket, new Error('Customer permission required.'));
        }
        try {
            await sendCurrentCustomerRequest(socket, Number(actor.sub));
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('customer:request', async (data = {}) => {
        if (actor.role !== 'customer') {
            return sendError(socket, new Error('Customer permission required.'));
        }
        const category = typeof data.category === 'string' ? data.category.trim() : '';
        const orderNumber = typeof data.orderNumber === 'string' ? data.orderNumber.trim() : '';
        const issueSummary = typeof data.issueSummary === 'string' ? data.issueSummary.trim() : '';
        const customerMessage = typeof data.customerMessage === 'string' ? data.customerMessage.trim() : '';
        if (!requestCategories.includes(category) ||
            (category === 'Order' && (!orderNumber || orderNumber.length > 64)) ||
            (category !== 'Order' && orderNumber) ||
            !issueSummary || issueSummary.length > 255 ||
            !customerMessage || customerMessage.length > 2000) {
            return sendError(socket, new Error('Please complete all required request details.'));
        }
        try {
            await expireInactiveConversations();
            const [existing] = await pool.execute(
                `SELECT entity_id FROM ${conversationsTable}
                 WHERE customer_id = ? AND status = 'open' ORDER BY entity_id DESC LIMIT 1`,
                [Number(actor.sub)]
            );
            if (existing.length) {
                return sendCurrentCustomerRequest(socket, Number(actor.sub));
            }
            const [created] = await pool.execute(
                `INSERT INTO ${conversationsTable}
                    (customer_id, customer_name, customer_email, status, intake_category, order_number, issue_summary, customer_message)
                 VALUES (?, ?, ?, 'open', ?, ?, ?, ?)`,
                [
                    Number(actor.sub),
                    actor.name || 'Customer',
                    actor.email || '',
                    category,
                    category === 'Order' ? orderNumber : null,
                    issueSummary,
                    customerMessage
                ]
            );
            const conversationId = Number(created.insertId);
            const [message] = await pool.execute(
                `INSERT INTO ${messagesTable} (conversation_id, sender_type, sender_id, sender_name, message)
                 VALUES (?, 'system', 0, 'Customer Care Assistant', ?)`,
                [conversationId, 'Thank you. We have received your request.']
            );
            socket.join(`conversation:${conversationId}`);
            io.to('agents').emit('conversation:updated');
            const queuePosition = await getQueuePosition(conversationId);
            const [stored] = await pool.execute(
                `SELECT entity_id, conversation_id, sender_type, sender_id, sender_name, message, created_at
                 FROM ${messagesTable} WHERE entity_id = ?`,
                [message.insertId]
            );
            socket.emit('customer:request:state', {
                conversation: {
                    id: conversationId,
                    status: 'open',
                    assignedAgentId: null,
                    category,
                    orderNumber: category === 'Order' ? orderNumber : null,
                    issueSummary,
                    customerMessage,
                    queuePosition
                },
                messages: [messageRow(stored[0])]
            });
            await broadcastQueuePositions();
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversations:list', async () => {
        if (actor.role !== 'agent') {
            return sendError(socket, new Error('Agent permission required.'));
        }
        try {
            socket.emit('conversations:list', { conversations: await listConversations(Number(actor.sub)) });
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversation:accept', async (data = {}) => {
        if (actor.role !== 'agent') {
            return sendError(socket, new Error('Agent permission required.'));
        }
        const conversationId = Number(data.conversationId);
        if (!Number.isSafeInteger(conversationId) || conversationId < 1) {
            return sendError(socket, new Error('Invalid conversation.'));
        }
        try {
            const agentName = String(actor.name || 'Customer Care').trim().slice(0, 255) || 'Customer Care';
            const notice = await assignAgentAndCreateNotice(conversationId, Number(actor.sub), agentName);
            if (!notice) {
                socket.emit('conversation:accept:failed', {
                    conversationId,
                    message: 'This request is no longer waiting in the queue.'
                });
                return;
            }
            socket.join(`conversation:${conversationId}`);
            io.to(`conversation:${conversationId}`).emit('conversation:message', notice);
            const messagePage = await getAdminMessagePage(conversationId);
            socket.emit('conversation:ready', {
                conversation: {
                    id: conversationId,
                    status: 'open',
                    assignedAgentId: Number(actor.sub),
                    assignedAgentName: agentName
                },
                messages: messagePage.messages,
                hasOlder: messagePage.hasOlder
            });
            io.to('agents').emit('conversation:accepted', {
                conversationId,
                assignedAgentId: Number(actor.sub),
                assignedAgentName: agentName
            });
            io.to('agents').emit('conversation:updated');
            await broadcastQueuePositions();
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversation:reject', async (data = {}) => {
        if (actor.role !== 'agent') {
            return sendError(socket, new Error('Agent permission required.'));
        }
        const conversationId = Number(data.conversationId);
        if (!Number.isSafeInteger(conversationId) || conversationId < 1) {
            return sendError(socket, new Error('Invalid conversation.'));
        }
        try {
            const [rows] = await pool.execute(
                `SELECT entity_id FROM ${conversationsTable}
                 WHERE entity_id = ? AND status = 'open' AND assigned_agent_id IS NULL`,
                [conversationId]
            );
            if (!rows.length) {
                socket.emit('conversation:reject:failed', {
                    conversationId,
                    message: 'This request is no longer waiting in the queue.'
                });
                return;
            }
            socket.emit('conversation:rejected', {
                conversationId,
                message: 'The request remains in the queue for another agent.'
            });
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('customer:history', async () => {
        if (actor.role !== 'customer') {
            return sendError(socket, new Error('Customer permission required.'));
        }
        try {
            socket.emit('customer:history', {
                conversations: await listCustomerConversations(Number(actor.sub))
            });
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversation:join', async (data = {}) => {
        if (!['agent', 'customer'].includes(actor.role)) {
            return sendError(socket, new Error('Chat permission required.'));
        }
        const conversationId = Number(data.conversationId);
        if (!Number.isSafeInteger(conversationId) || conversationId < 1) {
            return sendError(socket, new Error('Invalid conversation.'));
        }
        try {
            const [rows] = await pool.execute(
                `SELECT entity_id, customer_id, status, assigned_agent_id, assigned_agent_name
                 FROM ${conversationsTable} WHERE entity_id = ?`,
                [conversationId]
            );
            if (!rows.length) {
                return sendError(socket, new Error('Conversation not found.'));
            }
            if (actor.role === 'customer' && Number(rows[0].customer_id) !== Number(actor.sub)) {
                return sendError(socket, new Error('Conversation access denied.'));
            }
            if (actor.role === 'customer' && rows[0].status === 'open' && rows[0].assigned_agent_id === null) {
                await sendCurrentCustomerRequest(socket, Number(actor.sub));
                return;
            }
            if (actor.role === 'agent' && rows[0].status === 'open' && Number(rows[0].assigned_agent_id) !== Number(actor.sub)) {
                return sendError(socket, new Error('Accept this request before joining the conversation.'));
            }
            const room = `conversation:${conversationId}`;
            socket.join(room);
            const messagePage = actor.role === 'agent' ? await getAdminMessagePage(conversationId) : null;
            socket.emit('conversation:ready', {
                conversation: {
                    id: conversationId,
                    status: rows[0].status,
                    assignedAgentId: rows[0].assigned_agent_id === null ? null : Number(rows[0].assigned_agent_id),
                    assignedAgentName: rows[0].assigned_agent_name
                },
                messages: messagePage ? messagePage.messages : await getMessages(conversationId),
                hasOlder: messagePage ? messagePage.hasOlder : false
            });
        } catch (error) {
            sendError(socket, error);
        }
    });

    socket.on('conversation:older', async (data = {}) => {
        if (actor.role !== 'agent') {
            return sendError(socket, new Error('Agent permission required.'));
        }
        const conversationId = Number(data.conversationId);
        const beforeMessageId = Number(data.beforeMessageId);
        if (!Number.isSafeInteger(conversationId) || conversationId < 1 || !Number.isSafeInteger(beforeMessageId) || beforeMessageId < 1) {
            return sendError(socket, new Error('Invalid conversation history request.'));
        }
        try {
            const [conversations] = await pool.execute(
                `SELECT status, assigned_agent_id FROM ${conversationsTable} WHERE entity_id = ?`,
                [conversationId]
            );
            if (!conversations.length ||
                (conversations[0].status === 'open' && Number(conversations[0].assigned_agent_id) !== Number(actor.sub))) {
                return sendError(socket, new Error('Conversation access denied.'));
            }
            const messagePage = await getAdminMessagePage(conversationId, beforeMessageId);
            socket.emit('conversation:older', {
                conversationId,
                messages: messagePage.messages,
                hasOlder: messagePage.hasOlder
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
                `SELECT entity_id, customer_id, status, assigned_agent_id FROM ${conversationsTable} WHERE entity_id = ?`,
                [conversationId]
            );
            const conversation = rows[0];
            if (!conversation || conversation.status !== 'open') {
                return sendError(socket, new Error('Conversation is closed.'));
            }
            if (actor.role === 'customer' && Number(conversation.customer_id) !== Number(actor.sub)) {
                return sendError(socket, new Error('Conversation access denied.'));
            }
            if (conversation.assigned_agent_id === null ||
                (actor.role === 'agent' && Number(conversation.assigned_agent_id) !== Number(actor.sub))) {
                return sendError(socket, new Error('An agent must accept this request before messaging.'));
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
            io.to('agents').to(`conversation:${conversationId}`).emit('conversation:message', messageRow(stored[0]));
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
                `UPDATE ${conversationsTable}
                 SET status = 'closed', updated_at = CURRENT_TIMESTAMP
                 WHERE entity_id = ? AND status = 'open' AND assigned_agent_id = ?`,
                [conversationId, Number(actor.sub)]
            );
            if (updated.affectedRows) {
                io.to(`conversation:${conversationId}`).emit('conversation:closed', { conversationId });
                io.to('agents').emit('conversation:closed', { conversationId });
                await broadcastQueuePositions();
            }
        } catch (error) {
            sendError(socket, error);
        }
    });
});

async function startServer() {
    if (process.env.REDIS_URL) {
        redisPubClient = createClient({ url: process.env.REDIS_URL });
        redisSubClient = redisPubClient.duplicate();
        redisPubClient.on('error', (error) => {
            if (redisAdapterEnabled) {
                console.error('Redis publisher error:', error.message);
            }
        });
        redisSubClient.on('error', (error) => {
            if (redisAdapterEnabled) {
                console.error('Redis subscriber error:', error.message);
            }
        });
        let redisTimeout;
        try {
            await Promise.race([
                Promise.all([redisPubClient.connect(), redisSubClient.connect()]),
                new Promise((resolve, reject) => {
                    redisTimeout = setTimeout(() => reject(new Error('Redis did not become available within 3 seconds.')), 3000);
                })
            ]);
            io.adapter(createAdapter(redisPubClient, redisSubClient, { key: 'artm-livechat' }));
            redisAdapterEnabled = true;
            console.log('Socket.IO Redis adapter enabled');
        } catch (error) {
            console.warn('Redis unavailable; starting with local Socket.IO broadcasts:', error.message);
            await Promise.allSettled([redisPubClient, redisSubClient].filter((client) => client.isOpen).map((client) => client.disconnect()));
            redisPubClient = null;
            redisSubClient = null;
        } finally {
            clearTimeout(redisTimeout);
        }
    } else {
        console.warn('REDIS_URL is unset; Socket.IO broadcasts are local to this process.');
    }

    server.listen(port, () => {
        console.log(`ArtM LiveChat listening on port ${port}`);
        void expireInactiveConversations().catch((error) => {
            console.error('Unable to expire inactive conversations:', error.message);
        });
        inactivityTimer = setInterval(() => {
            void expireInactiveConversations().catch((error) => {
                console.error('Unable to expire inactive conversations:', error.message);
            });
        }, 60 * 1000);
        inactivityTimer.unref();
    });
}

startServer().catch((error) => {
    console.error('Unable to start ArtM LiveChat:', error.message);
    process.exitCode = 1;
});

async function shutdown() {
    if (isShuttingDown) {
        return;
    }
    isShuttingDown = true;
    clearInterval(inactivityTimer);
    io.close();
    await pool.end();
    const redisClients = [redisPubClient, redisSubClient].filter((client) => client && client.isOpen);
    await Promise.all(redisClients.map((client) => client.quit()));
    process.exit(0);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);