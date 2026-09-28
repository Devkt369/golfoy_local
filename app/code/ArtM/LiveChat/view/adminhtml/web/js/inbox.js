define(['require'], function (require) {
    'use strict';

    return function (config, element) {
        const connection = element.querySelector('#artm-inbox-connection');
        const list = element.querySelector('#artm-inbox-list');
        const heading = element.querySelector('#artm-inbox-customer');
        const email = element.querySelector('#artm-inbox-email');
        const messages = element.querySelector('#artm-inbox-messages');
        const form = element.querySelector('#artm-inbox-form');
        const input = element.querySelector('#artm-inbox-input');
        const sendButton = form.querySelector('button');
        const closeButton = element.querySelector('#artm-inbox-close');
        let socket;
        let activeConversation;
        let conversations = [];

        function addMessage(message) {
            const row = document.createElement('article');
            row.className = 'artm-inbox__message artm-inbox__message--' + message.senderType;
            const meta = document.createElement('header');
            const sender = document.createElement('strong');
            const time = document.createElement('time');
            sender.textContent = message.senderName;
            time.textContent = new Date(message.createdAt).toLocaleString();
            meta.append(sender, time);
            const body = document.createElement('p');
            body.textContent = message.message;
            row.append(meta, body);
            messages.append(row);
            messages.scrollTop = messages.scrollHeight;
        }

        function renderList() {
            list.replaceChildren();
            conversations.forEach(function (conversation) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'artm-inbox__conversation' + (activeConversation === conversation.id ? ' is-active' : '');
                const name = document.createElement('strong');
                name.textContent = conversation.customerName;
                const preview = document.createElement('span');
                preview.textContent = conversation.lastMessage || 'New conversation';
                const date = document.createElement('time');
                date.textContent = new Date(conversation.updatedAt).toLocaleString();
                button.append(name, preview, date);
                button.addEventListener('click', function () { openConversation(conversation); });
                list.append(button);
            });
            if (!conversations.length) {
                const empty = document.createElement('p');
                empty.className = 'artm-inbox__empty';
                empty.textContent = 'No open conversations';
                list.append(empty);
            }
        }

        function openConversation(conversation) {
            activeConversation = conversation.id;
            heading.textContent = conversation.customerName;
            email.textContent = conversation.customerEmail;
            input.disabled = false;
            sendButton.disabled = false;
            closeButton.disabled = false;
            renderList();
            socket.emit('conversation:join', { conversationId: conversation.id });
        }

        function loadSocketClient() {
            if (typeof window.io === 'function') {
                return Promise.resolve(window.io);
            }
            return new Promise(function (resolve, reject) {
                require([config.serverUrl + '/socket.io/socket.io.js'], function (socketFactory) {
                    if (typeof socketFactory === 'function') {
                        resolve(socketFactory);
                    } else {
                        reject(new Error('Socket.IO client did not initialize.'));
                    }
                }, reject);
            });
        }

        loadSocketClient().then(function (socketFactory) {
            socket = socketFactory(config.serverUrl, {
                auth: function (callback) {
                    fetch(config.tokenUrl, { credentials: 'same-origin', cache: 'no-store' })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Your admin session has expired.');
                            }
                            return response.json();
                        })
                        .then(function (data) { callback({ token: data.token }); })
                        .catch(function (error) {
                            connection.textContent = error.message;
                            callback({ token: '' });
                        });
                }
            });
            socket.on('connect', function () {
                connection.textContent = 'Connected';
                socket.emit('conversations:list');
            });
            socket.on('disconnect', function () { connection.textContent = 'Reconnecting...'; });
            socket.on('connect_error', function () { connection.textContent = 'Unable to connect'; });
            socket.on('conversations:list', function (data) {
                conversations = data.conversations;
                renderList();
            });
            socket.on('conversation:updated', function () { socket.emit('conversations:list'); });
            socket.on('conversation:ready', function (data) {
                if (data.conversation.id !== activeConversation) {
                    return;
                }
                messages.replaceChildren();
                data.messages.forEach(addMessage);
            });
            socket.on('conversation:message', function (message) {
                if (message.conversationId === activeConversation) {
                    addMessage(message);
                }
            });
            socket.on('conversation:closed', function (data) {
                if (data.conversationId === activeConversation) {
                    activeConversation = null;
                    heading.textContent = 'Select a conversation';
                    email.textContent = '';
                    input.disabled = true;
                    sendButton.disabled = true;
                    closeButton.disabled = true;
                    messages.replaceChildren();
                }
                socket.emit('conversations:list');
            });
            socket.on('chat:error', function (error) { connection.textContent = error.message; });
        }).catch(function (error) { connection.textContent = error.message; });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const message = input.value.trim();
            if (message && socket && activeConversation) {
                socket.emit('message:send', { conversationId: activeConversation, message: message });
                input.value = '';
            }
        });
        closeButton.addEventListener('click', function () {
            if (socket && activeConversation) {
                socket.emit('conversation:close', { conversationId: activeConversation });
            }
        });
    };
});