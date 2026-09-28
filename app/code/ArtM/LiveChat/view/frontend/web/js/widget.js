define(['require', 'Magento_Customer/js/customer-data'], function (require, customerData) {
    'use strict';

    return function (config, element) {
        const launcher = element.querySelector('.artm-chat__launcher');
        const panel = element.querySelector('.artm-chat__panel');
        const closeButton = element.querySelector('.artm-chat__close');
        const status = element.querySelector('#artm-chat-status');
        const messages = element.querySelector('#artm-chat-messages');
        const form = element.querySelector('#artm-chat-form');
        const input = element.querySelector('#artm-chat-input');
        let socket;
        let conversationId;
        let pendingMessage = '';
        const customerSection = customerData.get('customer');

        function addMessage(message) {
            const row = document.createElement('div');
            row.className = 'artm-chat__message artm-chat__message--' + message.senderType;
            const body = document.createElement('p');
            body.textContent = message.message;
            const time = document.createElement('time');
            time.textContent = new Date(message.createdAt).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            row.append(body, time);
            messages.append(row);
            messages.scrollTop = messages.scrollHeight;
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

        function connect() {
            loadSocketClient().then(function (socketFactory) {
                socket = socketFactory(config.serverUrl, {
                    auth: function (callback) {
                        fetch(config.tokenUrl, { credentials: 'same-origin', cache: 'no-store' })
                            .then(function (response) {
                                if (!response.ok) {
                                    throw new Error('Please sign in again to use chat.');
                                }
                                return response.json();
                            })
                            .then(function (data) { callback({ token: data.token }); })
                            .catch(function (error) {
                                status.textContent = error.message;
                                callback({ token: '' });
                            });
                    }
                });
                socket.on('connect', function () {
                    status.textContent = 'Connected';
                    socket.emit('conversation:open');
                });
                socket.on('agents:presence', function (data) {
                    status.textContent = data.online ? 'Customer care is online' : 'Leave a message for customer care';
                });
                socket.on('disconnect', function () { status.textContent = 'Reconnecting...'; });
                socket.on('conversation:ready', function (data) {
                    conversationId = data.conversation.id;
                    messages.replaceChildren();
                    data.messages.forEach(addMessage);
                    if (pendingMessage) {
                        socket.emit('message:send', { conversationId: conversationId, message: pendingMessage });
                        pendingMessage = '';
                    }
                });
                socket.on('conversation:message', addMessage);
                socket.on('conversation:closed', function () {
                    conversationId = null;
                    status.textContent = 'This chat ended. Send a message to start a new chat.';
                });
                socket.on('chat:error', function (error) { status.textContent = error.message; });
                socket.on('connect_error', function () { status.textContent = 'Unable to connect. Try again shortly.'; });
            }).catch(function (error) {
                status.textContent = error.message;
            });
        }

        function showPanel(show) {
            panel.hidden = !show;
            launcher.setAttribute('aria-expanded', String(show));
            if (show) {
                input.focus();
                if (!socket) {
                    connect();
                }
            }
        }

        function updateCustomer(customer) {
            const isLoggedIn = Boolean(customer && customer.fullname);
            element.hidden = !isLoggedIn;
            if (!isLoggedIn && socket) {
                socket.disconnect();
                socket = null;
                conversationId = null;
            }
        }

        launcher.addEventListener('click', function () { showPanel(panel.hidden); });
        closeButton.addEventListener('click', function () { showPanel(false); });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const message = input.value.trim();
            if (!message || !socket || !socket.connected) {
                return;
            }
            if (conversationId) {
                socket.emit('message:send', { conversationId: conversationId, message: message });
            } else {
                pendingMessage = message;
                socket.emit('conversation:open');
                status.textContent = 'Starting a new chat...';
            }
            input.value = '';
        });
        customerSection.subscribe(updateCustomer);
        updateCustomer(customerSection());
        customerData.reload(['customer'], true);
    };
});