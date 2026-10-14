define(['require', 'Magento_Customer/js/customer-data'], function (require, customerData) {
    'use strict';

    return function (config, element) {
        const launcher = element.querySelector('.artm-chat__launcher');
        const panel = element.querySelector('.artm-chat__panel');
        const closeButton = element.querySelector('.artm-chat__close');
        const historyButton = element.querySelector('#artm-chat-history');
        const notificationButton = element.querySelector('#artm-chat-notify');
        const newChatButton = element.querySelector('#artm-chat-new');
        const status = element.querySelector('#artm-chat-status');
        const presenceDot = element.querySelector('#artm-chat-presence-dot');
        const messages = element.querySelector('#artm-chat-messages');
        const form = element.querySelector('#artm-chat-form');
        const typingIndicator = element.querySelector('#artm-chat-typing') || document.createElement('div');
        typingIndicator.id = 'artm-chat-typing';
        typingIndicator.className = 'artm-chat__typing';
        typingIndicator.setAttribute('role', 'status');
        typingIndicator.setAttribute('aria-live', 'polite');
        if (!typingIndicator.isConnected) {
            typingIndicator.hidden = true;
            form.parentNode.insertBefore(typingIndicator, form);
        }
        const input = element.querySelector('#artm-chat-input');
        const sendButton = form.querySelector('button[type="submit"]');
        const unreadBadge = element.querySelector('#artm-chat-unread');
        const toast = element.querySelector('#artm-chat-toast');
        let socket;
        let conversationId;
        let unreadCount = 0;
        let toastTimer;
        let typingIdleTimer;
        let typingHeartbeat;
        let remoteTypingTimer;
        let isLocallyTyping = false;
        let historyMode = false;
        let historyRequested = false;
        let conversationStatus = 'open';
        let intake = {};
        let queuePosition = null;
        const customerSection = customerData.get('customer');

        function addMessage(message) {
            const row = document.createElement('div');
            row.className = 'artm-chat__message artm-chat__message--' + message.senderType;
            const body = document.createElement('p');
            body.textContent = message.senderType === 'system'
                ? message.message.replace(/ is joining now\.$/, ' joined the conversation.')
                : message.message;
            const time = document.createElement('time');
            time.textContent = new Date(message.createdAt).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            row.append(body, time);
            messages.append(row);
            messages.scrollTop = messages.scrollHeight;
            return row;
        }

        function appendSystemMessage(message) {
            addMessage({ senderType: 'system', senderName: 'Customer Care', message: message, createdAt: new Date() });
        }

        function showQueuePosition(position) {
            queuePosition = Number(position) || null;
            let queueMessage = messages.querySelector('.artm-chat__queue-status');
            if (!queueMessage) {
                queueMessage = document.createElement('div');
                queueMessage.className = 'artm-chat__message artm-chat__message--system artm-chat__queue-status';
                const body = document.createElement('p');
                queueMessage.append(body);
                messages.append(queueMessage);
            }
            queueMessage.querySelector('p').textContent = queuePosition
                ? 'You are currently #' + queuePosition + ' in the queue.\nA customer-care executive will join shortly.'
                : 'A customer-care executive will join shortly.';
            messages.scrollTop = messages.scrollHeight;
            setStatus(queuePosition ? 'You are #' + queuePosition + ' in the queue' : 'Waiting for a customer-care executive', false);
        }

        function showInitialGreeting() {
            historyMode = false;
            intake = {};
            conversationStatus = 'intake';
            form.hidden = true;
            newChatButton.hidden = true;
            messages.replaceChildren();
            setStatus('Tell us what you need', false);
            appendSystemMessage('Hello! 👋\nWelcome to Golfoy Customer Care.');
            appendSystemMessage('Before connecting you to an agent, we need a few details.\n\nWhat can we help you with?');

            const choices = document.createElement('div');
            choices.className = 'artm-chat__category-options';
            ['Order', 'Delivery', 'Return / Exchange', 'Payment', 'Product', 'Other'].forEach(function (category) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = category;
                button.addEventListener('click', function () {
                    intake.category = category;
                    choices.remove();
                    addMessage({ senderType: 'customer', message: category, createdAt: new Date() });
                    if (category === 'Order') {
                        showIntakeTextStep('What is your order number?', 'Enter your order number', 'orderNumber', 64, showIssueSummary);
                    } else {
                        showIssueSummary();
                    }
                });
                choices.append(button);
            });
            messages.append(choices);
        }

        function showIntakeTextStep(prompt, placeholder, field, maxLength, onContinue, inputType) {
            appendSystemMessage(prompt);
            const stepForm = document.createElement('form');
            stepForm.className = 'artm-chat__intake-form';
            const fieldInput = inputType === 'textarea' ? document.createElement('textarea') : document.createElement('input');
            if (fieldInput.tagName === 'INPUT') {
                fieldInput.type = 'text';
            }
            fieldInput.maxLength = maxLength;
            fieldInput.required = true;
            fieldInput.placeholder = placeholder;
            fieldInput.setAttribute('aria-label', prompt);
            const continueButton = document.createElement('button');
            continueButton.type = 'submit';
            continueButton.textContent = field === 'customerMessage' ? 'Send request' : 'Continue';
            stepForm.append(fieldInput, continueButton);
            stepForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const answer = fieldInput.value.trim();
                if (!answer) {
                    fieldInput.focus();
                    return;
                }
                intake[field] = answer;
                addMessage({ senderType: 'customer', message: answer, createdAt: new Date() });
                stepForm.remove();
                onContinue();
            });
            messages.append(stepForm);
            messages.scrollTop = messages.scrollHeight;
            fieldInput.focus();
        }

        function showIssueSummary() {
            showIntakeTextStep('What do you need help with?', 'For example: I have not received my order', 'issueSummary', 255, function () {
                showIntakeTextStep('Please briefly describe your issue.', 'Add a few details about the issue', 'customerMessage', 2000, submitCustomerRequest, 'textarea');
            });
        }

        function submitCustomerRequest() {
            if (!socket || !socket.connected) {
                appendSystemMessage('Unable to submit your request right now. Please try again shortly.');
                return;
            }
            conversationStatus = 'submitting';
            setStatus('Sending your request...', false);
            socket.emit('customer:request', {
                category: intake.category,
                orderNumber: intake.category === 'Order' ? intake.orderNumber : '',
                issueSummary: intake.issueSummary,
                customerMessage: intake.customerMessage
            });
        }

        function renderQueuedRequest(conversation, storedMessages) {
            historyMode = false;
            conversationId = conversation.id;
            conversationStatus = 'queued';
            form.hidden = true;
            newChatButton.hidden = true;
            input.disabled = true;
            sendButton.disabled = true;
            messages.replaceChildren();
            appendSystemMessage('Your request is in the queue: ' + (conversation.category || 'Customer Care'));
            if (conversation.orderNumber) {
                addMessage({ senderType: 'customer', message: conversation.orderNumber, createdAt: new Date() });
            }
            if (conversation.issueSummary) {
                addMessage({ senderType: 'customer', message: conversation.issueSummary, createdAt: new Date() });
            }
            if (conversation.customerMessage) {
                addMessage({ senderType: 'customer', message: conversation.customerMessage, createdAt: new Date() });
            }
            (storedMessages || []).forEach(function (message) {
                if (message.senderType === 'system') {
                    addMessage(message);
                }
            });
            showQueuePosition(conversation.queuePosition);
        }

        function updateUnreadCount() {
            unreadBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            unreadBadge.hidden = unreadCount === 0;
        }

        function emitTypingState(isTyping) {
            if (socket && socket.connected && conversationId && !historyMode && conversationStatus === 'open') {
                socket.emit('conversation:typing', { conversationId: conversationId, isTyping: isTyping });
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

        function setStatus(message, isOnline) {
            status.textContent = message;
            status.classList.toggle('artm-chat__status--online', Boolean(isOnline));
            presenceDot.hidden = !isOnline;
        }

        function updateRemoteTyping(data) {
            if (data.conversationId !== conversationId || data.senderType !== 'agent') {
                return;
            }
            window.clearTimeout(remoteTypingTimer);
            if (!data.isTyping) {
                typingIndicator.hidden = true;
                return;
            }
            typingIndicator.textContent = 'Customer care is typing...';
            typingIndicator.hidden = false;
            remoteTypingTimer = window.setTimeout(clearRemoteTyping, 2200);
        }

        function showToast(message) {
            toast.textContent = message;
            toast.hidden = false;
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(function () { toast.hidden = true; }, 7000);
        }

        function renderHistory(conversations) {
            historyMode = true;
            newChatButton.hidden = true;
            input.hidden = true;
            sendButton.hidden = true;
            historyButton.setAttribute('aria-label', 'Return to chat');
            historyButton.title = 'Return to chat';
            messages.replaceChildren();
            if (!conversations.length) {
                const empty = document.createElement('p');
                empty.className = 'artm-chat__history-empty';
                empty.textContent = 'No previous conversations';
                messages.append(empty);
                return;
            }
            conversations.forEach(function (conversation) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'artm-chat__history-item';
                const title = document.createElement('strong');
                title.textContent = conversation.status === 'open' ? 'Current conversation' : 'Previous conversation';
                const preview = document.createElement('span');
                preview.textContent = conversation.lastMessage || 'No messages';
                const date = document.createElement('time');
                date.textContent = new Date(conversation.updatedAt).toLocaleString();
                button.append(title, preview, date);
                button.addEventListener('click', function () {
                    historyMode = false;
                    historyButton.setAttribute('aria-label', 'Conversation history');
                    historyButton.title = 'Conversation history';
                    conversationId = conversation.id;
                    socket.emit('conversation:join', { conversationId: conversation.id });
                });
                messages.append(button);
            });
        }

        function setConversationState(statusValue, isAssigned, assignedAgentName) {
            const isAccepted = statusValue === 'open' && Boolean(isAssigned);
            conversationStatus = statusValue === 'open' ? (isAccepted ? 'open' : 'queued') : statusValue;
            clearRemoteTyping();
            if (!isAccepted) {
                stopLocalTyping();
            }
            form.hidden = !isAccepted && statusValue !== 'closed';
            input.disabled = !isAccepted;
            sendButton.disabled = !isAccepted;
            input.hidden = !isAccepted;
            sendButton.hidden = !isAccepted;
            newChatButton.hidden = statusValue !== 'closed';
            if (statusValue === 'closed') {
                setStatus('Previous conversation (read only)', false);
            } else if (isAccepted) {
                setStatus(assignedAgentName ? 'Assigned to ' + assignedAgentName : 'Request accepted by customer care', false);
            }
        }

        function notifyInBackground(message) {
            if (!panel.hidden && !historyMode && conversationId === message.conversationId) {
                return;
            }
            if (!document.hidden && document.hasFocus()) {
                return;
            }
            if (!('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }
            const notification = new Notification('Golfoy Customer Care', {
                body: message,
                tag: 'artm-livechat-customer-' + (conversationId || 'message')
            });
            notification.onclick = function () {
                window.focus();
                showPanel(true);
                notification.close();
            };
        }

        function updateNotificationButton() {
            if (!window.isSecureContext || !('Notification' in window)) {
                notificationButton.disabled = true;
                notificationButton.title = 'Desktop notifications require HTTPS';
                notificationButton.setAttribute('aria-label', 'Desktop notifications require HTTPS');
                return;
            }
            notificationButton.disabled = Notification.permission === 'granted' || Notification.permission === 'denied';
            notificationButton.title = Notification.permission === 'granted' ? 'Desktop notifications enabled' :
                Notification.permission === 'denied' ? 'Allow notifications in browser settings' : 'Enable desktop notifications';
            notificationButton.setAttribute('aria-label', notificationButton.title);
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
                                setStatus(error.message, false);
                                callback({ token: '' });
                            });
                    }
                });
                socket.on('connect', function () {
                    setStatus('Connected', false);
                    if (historyRequested) {
                        socket.emit('customer:history');
                    } else {
                        socket.emit('customer:current');
                    }
                });
                socket.on('agents:presence', function (data) {
                    setStatus(data.online ? 'Customer care is online' : 'Leave a message for customer care', data.online);
                });
                socket.on('disconnect', function () {
                    setStatus('Reconnecting...', false);
                    stopLocalTyping();
                    clearRemoteTyping();
                });
                socket.on('conversation:ready', function (data) {
                    conversationId = data.conversation.id;
                    clearRemoteTyping();
                    if (isLocallyTyping) {
                        emitTypingState(true);
                    }
                    setConversationState(
                        data.conversation.status || 'open',
                        Boolean(data.conversation.assignedAgentId),
                        data.conversation.assignedAgentName
                    );
                    messages.replaceChildren();
                    data.messages.forEach(addMessage);
                });
                socket.on('customer:history', function (data) {
                    historyRequested = false;
                    renderHistory(data.conversations);
                });
                socket.on('customer:request:state', function (data) {
                    if (!data.conversation) {
                        conversationId = null;
                        showInitialGreeting();
                        return;
                    }
                    renderQueuedRequest(data.conversation, data.messages);
                });
                socket.on('customer:queue', function (data) {
                    if (conversationId && conversationStatus === 'queued') {
                        showQueuePosition(data.position);
                    }
                });
                socket.on('conversation:typing', updateRemoteTyping);
                socket.on('conversation:message', function (message) {
                    const isOpenConversation = !panel.hidden && !historyMode && conversationId === message.conversationId;
                    if (!historyMode) {
                        addMessage(message);
                    }
                    if (message.senderType === 'system' && /(?:is joining now|joined the conversation)\.$/.test(message.message)) {
                        setConversationState(
                            'open',
                            true,
                            message.message.replace(/(?: is joining now| joined the conversation)\.$/, '')
                        );
                    }
                    if (message.senderType !== 'customer') {
                        clearRemoteTyping();
                        if (!isOpenConversation) {
                            showToast('Golfoy Customer Care: ' + message.message);
                        }
                        if (!isOpenConversation && (panel.hidden || document.visibilityState !== 'visible' || !document.hasFocus())) {
                            unreadCount += 1;
                            updateUnreadCount();
                        }
                        notifyInBackground(message.message);
                    }
                });
                socket.on('conversation:closed', function () {
                    setConversationState('closed');
                });
                socket.on('chat:error', function (error) { setStatus(error.message, false); });
                socket.on('connect_error', function () { setStatus('Unable to connect. Try again shortly.', false); });
            }).catch(function (error) {
                setStatus(error.message, false);
            });
        }

        function showPanel(show) {
            panel.hidden = !show;
            launcher.hidden = show;
            launcher.style.display = show ? 'none' : '';
            launcher.setAttribute('aria-expanded', String(show));
            if (!show) {
                stopLocalTyping();
                clearRemoteTyping();
            }
            if (show) {
                unreadCount = 0;
                updateUnreadCount();
                toast.hidden = true;
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
        toast.addEventListener('click', function () { showPanel(true); });
        input.addEventListener('input', signalLocalTyping);
        historyButton.addEventListener('click', function () {
            stopLocalTyping();
            clearRemoteTyping();
            if (!socket || !socket.connected) {
                historyRequested = true;
                if (!socket) {
                    connect();
                }
                return;
            }
            if (historyMode) {
                historyMode = false;
                historyButton.setAttribute('aria-label', 'Conversation history');
                historyButton.title = 'Conversation history';
                input.hidden = false;
                sendButton.hidden = false;
                if (conversationId) {
                    socket.emit('conversation:join', { conversationId: conversationId });
                } else {
                    socket.emit('customer:current');
                }
                return;
            }
            historyRequested = true;
            socket.emit('customer:history');
        });
        notificationButton.addEventListener('click', function () {
            if (!window.isSecureContext || !('Notification' in window)) {
                setStatus('Desktop notifications require HTTPS', false);
                return;
            }
            Notification.requestPermission().then(updateNotificationButton).then(function () {
                if (Notification.permission === 'granted') {
                    setStatus('Desktop notifications enabled', false);
                }
            });
        });
        newChatButton.addEventListener('click', function () {
            if (socket && socket.connected) {
                conversationId = null;
                historyMode = false;
                showInitialGreeting();
            }
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            stopLocalTyping();
            const message = input.value.trim();
            if (!message || !socket || !socket.connected || !conversationId || conversationStatus !== 'open') {
                return;
            }
            socket.emit('message:send', { conversationId: conversationId, message: message });
            input.value = '';
        });
        customerSection.subscribe(updateCustomer);
        updateCustomer(customerSection());
        updateNotificationButton();
        customerData.reload(['customer'], true);
    };
});