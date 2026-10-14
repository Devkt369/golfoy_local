define(['require'], function (require) {
    'use strict';

    return function (config, element) {
        const connection = element.querySelector('#artm-inbox-connection');
        const list = element.querySelector('#artm-inbox-list');
        const heading = element.querySelector('#artm-inbox-customer');
        const email = element.querySelector('#artm-inbox-email');
        const conversationStatus = element.querySelector('#artm-inbox-status');
        const olderButton = element.querySelector('#artm-inbox-older');
        const requestDetails = element.querySelector('#artm-inbox-request');
        const messages = element.querySelector('#artm-inbox-messages');
        const form = element.querySelector('#artm-inbox-form');
        const typingIndicator = element.querySelector('#artm-inbox-typing') || document.createElement('div');
        typingIndicator.id = 'artm-inbox-typing';
        typingIndicator.className = 'artm-inbox__typing';
        typingIndicator.setAttribute('role', 'status');
        typingIndicator.setAttribute('aria-live', 'polite');
        if (!typingIndicator.isConnected) {
            typingIndicator.hidden = true;
            form.parentNode.insertBefore(typingIndicator, form);
        }
        const input = element.querySelector('#artm-inbox-input');
        const sendButton = form.querySelector('button');
        const closeButton = element.querySelector('#artm-inbox-close');
        const notificationButton = element.querySelector('#artm-inbox-notify');
        const unreadTotalBadge = element.querySelector('#artm-inbox-unread-total');
        const toast = element.querySelector('#artm-inbox-toast');
        let socket;
        let activeConversation;
        let selectedRequest;
        let conversations = [];
        let oldestMessageId;
        let hasOlderMessages = false;
        let loadingOlderMessages = false;
        let typingIdleTimer;
        let typingHeartbeat;
        let remoteTypingTimer;
        let isLocallyTyping = false;
        const unreadByConversation = new Map();
        let toastTimer;
        let queueTimer;

        function updateUnreadTotal() {
            const total = Array.from(unreadByConversation.values()).reduce(function (sum, count) { return sum + count; }, 0);
            unreadTotalBadge.textContent = total > 99 ? '99+' : String(total);
            unreadTotalBadge.hidden = total === 0;
        }

        function showToast(message, conversationId) {
            toast.textContent = message;
            toast.hidden = false;
            toast.dataset.conversationId = String(conversationId);
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(function () { toast.hidden = true; }, 7000);
        }

        function emitTypingState(isTyping) {
            if (socket && socket.connected && activeConversation) {
                socket.emit('conversation:typing', { conversationId: activeConversation, isTyping: isTyping });
            }
        }

        function stopLocalTyping() {
            window.clearTimeout(typingIdleTimer);
            window.clearInterval(typingHeartbeat);
            if (isLocallyTyping) {
                emitTypingState(false);
            }
            isLocallyTyping = false;
        }

        function signalLocalTyping() {
            if (!input.value.trim()) {
                stopLocalTyping();
                return;
            }
            if (!isLocallyTyping) {
                isLocallyTyping = true;
                emitTypingState(true);
                typingHeartbeat = window.setInterval(function () { emitTypingState(true); }, 1000);
            }
            window.clearTimeout(typingIdleTimer);
            typingIdleTimer = window.setTimeout(stopLocalTyping, 1700);
        }

        function clearRemoteTyping() {
            window.clearTimeout(remoteTypingTimer);
            typingIndicator.hidden = true;
        }

        function updateRemoteTyping(data) {
            if (data.conversationId !== activeConversation || data.senderType !== 'customer') {
                return;
            }
            window.clearTimeout(remoteTypingTimer);
            if (!data.isTyping) {
                typingIndicator.hidden = true;
                return;
            }
            typingIndicator.textContent = 'Customer is typing...';
            typingIndicator.hidden = false;
            remoteTypingTimer = window.setTimeout(clearRemoteTyping, 2200);
        }

        function createMessageRow(message) {
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
            return row;
        }

        function addMessage(message) {
            const row = createMessageRow(message);
            messages.append(row);
            messages.scrollTop = messages.scrollHeight;
        }

        function prependOlderMessages(data) {
            if (data.conversationId !== activeConversation || !data.messages.length) {
                loadingOlderMessages = false;
                olderButton.disabled = false;
                return;
            }
            const previousHeight = messages.scrollHeight;
            const fragment = document.createDocumentFragment();
            data.messages.forEach(function (message) { fragment.append(createMessageRow(message)); });
            messages.prepend(fragment);
            messages.scrollTop += messages.scrollHeight - previousHeight;
            oldestMessageId = data.messages[0].id;
            hasOlderMessages = data.hasOlder;
            olderButton.hidden = !hasOlderMessages;
            olderButton.disabled = false;
            loadingOlderMessages = false;
        }

        function renderList() {
            list.replaceChildren();
            conversations.forEach(function (conversation) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'artm-inbox__conversation' + (activeConversation === conversation.id ? ' is-active' : '');
                const name = document.createElement('strong');
                name.textContent = conversation.customerName;
                const unreadCount = unreadByConversation.get(conversation.id) || 0;
                const unread = document.createElement('span');
                unread.className = 'artm-inbox__unread';
                unread.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                unread.hidden = unreadCount === 0;
                const title = document.createElement('div');
                title.className = 'artm-inbox__conversation-title';
                title.append(name, unread);
                const state = document.createElement('span');
                state.className = 'artm-inbox__state artm-inbox__state--' + conversation.status;
                const isQueued = conversation.status === 'open' && conversation.assignedAgentId === null;
                state.textContent = conversation.status === 'closed' ? 'Closed' :
                    isQueued ? 'New request' : 'In chat';
                if (isQueued) {
                    state.classList.add('artm-inbox__state--queued');
                }
                const preview = document.createElement('span');
                preview.textContent = conversation.issueSummary || conversation.lastMessage || 'New conversation';
                const date = document.createElement('time');
                date.textContent = new Date(conversation.updatedAt).toLocaleString();
                button.append(title, state, preview, date);
                button.addEventListener('click', function () { openConversation(conversation); });
                list.append(button);
            });
            if (!conversations.length) {
                const empty = document.createElement('p');
                empty.className = 'artm-inbox__empty';
                empty.textContent = 'No conversations yet';
                list.append(empty);
            }
            if (selectedRequest) {
                const refreshed = conversations.find(function (item) { return item.id === selectedRequest.id; });
                if (refreshed) {
                    selectedRequest = refreshed;
                    renderRequestDetails(refreshed);
                }
            }
        }

        function formatQueueTime(createdAt) {
            const elapsedSeconds = Math.max(0, Math.floor((Date.now() - new Date(createdAt).getTime()) / 1000));
            const minutes = String(Math.floor(elapsedSeconds / 60)).padStart(2, '0');
            const seconds = String(elapsedSeconds % 60).padStart(2, '0');
            return minutes + ':' + seconds;
        }

        function renderRequestDetails(conversation) {
            window.clearInterval(queueTimer);
            selectedRequest = conversation;
            requestDetails.replaceChildren();
            requestDetails.hidden = false;
            const title = document.createElement('h2');
            title.textContent = conversation.status === 'open' && conversation.assignedAgentId === null
                ? 'New Customer Request'
                : conversation.assignedAgentName ? 'Accepted by ' + conversation.assignedAgentName : 'Customer Request';
            const details = document.createElement('dl');
            const fields = [
                ['Customer', conversation.customerName],
                ['Category', conversation.category || 'Not provided'],
                ['Order No', conversation.orderNumber || 'Not applicable'],
                ['Issue', conversation.issueSummary || 'Not provided'],
                ['Customer message', conversation.customerMessage || 'Not provided']
            ];
            fields.forEach(function (field) {
                const label = document.createElement('dt');
                label.textContent = field[0] + ':';
                const value = document.createElement('dd');
                value.textContent = field[1];
                details.append(label, value);
            });
            const queueTime = document.createElement('dd');
            queueTime.className = 'artm-inbox__queue-time';
            const queueTimeLabel = document.createElement('dt');
            queueTimeLabel.textContent = 'Queue time:';
            details.append(queueTimeLabel, queueTime);
            requestDetails.append(title, details);
            const updateQueueTime = function () {
                queueTime.textContent = formatQueueTime(conversation.createdAt);
            };
            updateQueueTime();
            const isQueued = conversation.status === 'open' && conversation.assignedAgentId === null;
            if (isQueued) {
                queueTimer = window.setInterval(updateQueueTime, 1000);
                const actions = document.createElement('div');
                actions.className = 'artm-inbox__request-actions';
                const accept = document.createElement('button');
                accept.type = 'button';
                accept.textContent = 'Accept Chat';
                accept.addEventListener('click', function () {
                    accept.disabled = true;
                    reject.disabled = true;
                    socket.emit('conversation:accept', { conversationId: conversation.id });
                });
                const reject = document.createElement('button');
                reject.type = 'button';
                reject.textContent = 'Reject';
                reject.addEventListener('click', function () {
                    accept.disabled = true;
                    reject.disabled = true;
                    socket.emit('conversation:reject', { conversationId: conversation.id });
                });
                actions.append(accept, reject);
                requestDetails.append(actions);
            }
        }

        function openConversation(conversation) {
            stopLocalTyping();
            clearRemoteTyping();
            activeConversation = conversation.id;
            oldestMessageId = undefined;
            hasOlderMessages = false;
            loadingOlderMessages = false;
            olderButton.hidden = true;
            olderButton.disabled = false;
            unreadByConversation.delete(conversation.id);
            updateUnreadTotal();
            toast.hidden = true;
            heading.textContent = conversation.customerName;
            email.textContent = conversation.customerEmail;
            const isQueued = conversation.status === 'open' && conversation.assignedAgentId === null;
            const isOpen = conversation.status === 'open' && !isQueued;
            input.disabled = !isOpen;
            sendButton.disabled = !isOpen;
            closeButton.disabled = !isOpen;
            messages.replaceChildren();
            olderButton.hidden = true;
            if (isQueued) {
                conversationStatus.textContent = 'New request · waiting for an agent to accept';
                input.value = '';
                renderRequestDetails(conversation);
                renderList();
                return;
            }
            requestDetails.hidden = true;
            window.clearInterval(queueTimer);
            selectedRequest = null;
            conversationStatus.textContent = isOpen ? 'Accepted conversation' : 'Closed conversation · history';
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

        function notifyInBackground(message) {
            if (document.visibilityState === 'visible' && document.hasFocus()) {
                return;
            }
            if (!('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }
            const notification = new Notification('New Golfoy customer message', {
                body: message.message,
                tag: 'artm-livechat-agent-' + message.conversationId
            });
            notification.onclick = function () {
                window.focus();
                const conversation = conversations.find(function (item) { return item.id === message.conversationId; });
                if (conversation) {
                    openConversation(conversation);
                }
                notification.close();
            };
        }

        function updateNotificationButton() {
            if (!window.isSecureContext || !('Notification' in window)) {
                notificationButton.disabled = true;
                notificationButton.title = 'Desktop notifications require HTTPS';
                notificationButton.textContent = 'Alerts require HTTPS';
                return;
            }
            notificationButton.disabled = Notification.permission === 'granted' || Notification.permission === 'denied';
            notificationButton.textContent = Notification.permission === 'granted' ? 'Alerts enabled' :
                Notification.permission === 'denied' ? 'Alerts blocked' : 'Enable alerts';
            notificationButton.title = Notification.permission === 'denied' ? 'Allow notifications in browser settings' : notificationButton.textContent;
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
            socket.on('disconnect', function () {
                connection.textContent = 'Reconnecting...';
                stopLocalTyping();
                clearRemoteTyping();
            });
            socket.on('connect_error', function () { connection.textContent = 'Unable to connect'; });
            socket.on('conversations:list', function (data) {
                conversations = data.conversations;
                renderList();
            });
            socket.on('conversation:updated', function () { socket.emit('conversations:list'); });
            socket.on('conversation:accepted', function (data) {
                if (selectedRequest && selectedRequest.id === data.conversationId) {
                    selectedRequest.assignedAgentId = data.assignedAgentId;
                    selectedRequest.assignedAgentName = data.assignedAgentName;
                    renderRequestDetails(selectedRequest);
                    conversationStatus.textContent = 'Accepted by ' + data.assignedAgentName;
                }
            });
            socket.on('conversation:ready', function (data) {
                if (data.conversation.id !== activeConversation) {
                    return;
                }
                messages.replaceChildren();
                requestDetails.hidden = true;
                window.clearInterval(queueTimer);
                selectedRequest = null;
                oldestMessageId = data.messages.length ? data.messages[0].id : undefined;
                hasOlderMessages = Boolean(data.hasOlder);
                olderButton.hidden = !hasOlderMessages;
                olderButton.disabled = false;
                loadingOlderMessages = false;
                conversationStatus.textContent = 'Accepted conversation';
                input.disabled = data.conversation.status !== 'open';
                sendButton.disabled = data.conversation.status !== 'open';
                closeButton.disabled = data.conversation.status !== 'open';
                data.messages.forEach(addMessage);
            });
            socket.on('conversation:accept:failed', function (data) {
                connection.textContent = data.message;
                socket.emit('conversations:list');
            });
            socket.on('conversation:rejected', function (data) {
                connection.textContent = data.message;
                socket.emit('conversations:list');
                if (selectedRequest && selectedRequest.id === data.conversationId) {
                    const refreshed = conversations.find(function (item) { return item.id === data.conversationId; });
                    if (refreshed) {
                        selectedRequest = refreshed;
                        renderRequestDetails(refreshed);
                    }
                }
            });
            socket.on('conversation:reject:failed', function (data) {
                connection.textContent = data.message;
                socket.emit('conversations:list');
            });
            socket.on('conversation:older', prependOlderMessages);
            socket.on('conversation:typing', updateRemoteTyping);
            socket.on('conversation:message', function (message) {
                if (message.conversationId === activeConversation) {
                    addMessage(message);
                }
                if (message.senderType === 'customer') {
                    clearRemoteTyping();
                    const conversation = conversations.find(function (item) { return item.id === message.conversationId; });
                    const isVisibleThread = message.conversationId === activeConversation && document.visibilityState === 'visible' && document.hasFocus();
                    if (!isVisibleThread) {
                        unreadByConversation.set(message.conversationId, (unreadByConversation.get(message.conversationId) || 0) + 1);
                        updateUnreadTotal();
                        renderList();
                    }
                    showToast((conversation ? conversation.customerName : 'Customer') + ': ' + message.message, message.conversationId);
                    notifyInBackground(message);
                }
            });
            socket.on('conversation:closed', function (data) {
                if (data.conversationId === activeConversation) {
                    stopLocalTyping();
                    clearRemoteTyping();
                    const active = conversations.find(function (item) { return item.id === data.conversationId; });
                    if (active) {
                        active.status = 'closed';
                    }
                    conversationStatus.textContent = 'Closed conversation · history';
                    input.disabled = true;
                    sendButton.disabled = true;
                    closeButton.disabled = true;
                }
                unreadByConversation.delete(data.conversationId);
                updateUnreadTotal();
                socket.emit('conversations:list');
            });
            socket.on('chat:error', function (error) {
                connection.textContent = error.message;
                loadingOlderMessages = false;
                olderButton.disabled = false;
            });
        }).catch(function (error) { connection.textContent = error.message; });

        input.addEventListener('input', signalLocalTyping);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            stopLocalTyping();
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
        olderButton.addEventListener('click', function () {
            if (socket && activeConversation && oldestMessageId && hasOlderMessages && !loadingOlderMessages) {
                loadingOlderMessages = true;
                olderButton.disabled = true;
                socket.emit('conversation:older', {
                    conversationId: activeConversation,
                    beforeMessageId: oldestMessageId
                });
            }
        });
        notificationButton.addEventListener('click', function () {
            if (!window.isSecureContext || !('Notification' in window)) {
                connection.textContent = 'Desktop notifications require HTTPS';
                return;
            }
            Notification.requestPermission().then(updateNotificationButton).then(function () {
                if (Notification.permission === 'granted') {
                    connection.textContent = 'Desktop notifications enabled';
                }
            });
        });
        toast.addEventListener('click', function () {
            const conversationId = Number(toast.dataset.conversationId);
            const conversation = conversations.find(function (item) { return item.id === conversationId; });
            if (conversation) {
                openConversation(conversation);
            }
        });
        updateNotificationButton();
    };
});