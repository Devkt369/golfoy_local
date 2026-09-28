require(['jquery', 'mage/translate', 'mage/url'], function ($, $t, urlBuilder) {
    'use strict';

    var MODE_STORAGE_KEY = 'adminai_gemini_enabled';

    function isGeminiEnabled() {
        return window.localStorage.getItem(MODE_STORAGE_KEY) === '1';
    }

    function setGeminiEnabled(enabled) {
        window.localStorage.setItem(MODE_STORAGE_KEY, enabled ? '1' : '0');
    }

    function getModeLabel(enabled) {
        return enabled ? $t('Gemini: ON') : $t('Gemini: OFFLINE');
    }

    function escapeHtml(text) {
        return $('<div/>').text(text).html();
    }

    function formatDate(dateStr) {
        if (!dateStr) {
            return '';
        }

        return String(dateStr).replace('T', ' ').substring(0, 16);
    }

    function initializeAdminAiChat() {
        var $container = $('#ai-chat-container');
        if (!$container.length || $container.data('adminai-initialized')) {
            return;
        }

        $container.data('adminai-initialized', true);

        var urls = {
            ask: $container.data('ask-url') || urlBuilder.build('adminai/chat/ask'),
            sessions: $container.data('sessions-url') || urlBuilder.build('adminai/chat/sessions'),
            messages: $container.data('messages-url') || urlBuilder.build('adminai/chat/messages'),
            createSession: $container.data('create-session-url') || urlBuilder.build('adminai/chat/createSession'),
            renameSession: $container.data('rename-session-url') || urlBuilder.build('adminai/chat/renameSession'),
            attributes: $container.data('attributes-url') || urlBuilder.build('adminai/chat/attributes'),
            customerEditTemplate: $container.data('customer-edit-url-template') || '',
            productEditTemplate: $container.data('product-edit-url-template') || '',
            orderViewTemplate: $container.data('order-view-url-template') || '',
            ordersGridUrl: $container.data('orders-grid-url') || ''
        };

        var state = {
            geminiEnabled: isGeminiEnabled(),
            activeSessionId: 0,
            sessions: [],
            attributeOptionsByEntity: {},
            pendingIntentPayload: null
        };

        var $messages = $container.find('.adminai-messages');
        var $input = $container.find('.adminai-input');
        var $sendButton = $container.find('.adminai-send');
        var $modeLabel = $container.find('.adminai-mode-label');
        var $toggleButton = $container.find('.adminai-toggle-mode span');
        var $sessionList = $container.find('.adminai-session-list');
        var $newChat = $container.find('.adminai-new-chat');
        var $renameChat = $container.find('.adminai-rename-chat');
        var $searchSessions = $container.find('.adminai-search-sessions');

        function refreshModeUi() {
            $modeLabel.text(getModeLabel(state.geminiEnabled));
            $toggleButton.text(state.geminiEnabled ? $t('Disable Gemini') : $t('Enable Gemini'));
        }

        function formatAssistantMessageHtml(text) {
            var safe = escapeHtml(text || '');
            safe = safe.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1<\/a>');
            safe = safe.replace(/\[\[INPUT:([^|\]]+)\|([^|\]]+)\|([^\]]+)\]\]/g, function (_, prefix, placeholder, label) {
                return '<span class="adminai-chat-inline-input" data-prefix="'
                    + escapeHtml(prefix)
                    + '">' 
                    + '<input type="text" class="admin__control-text adminai-chat-inline-field" placeholder="'
                    + escapeHtml(placeholder)
                    + '" />'
                    + '<button type="button" class="action-default scalable adminai-chat-inline-send"><span>'
                    + escapeHtml(label)
                    + '</span></button>'
                    + '</span>';
            });
            safe = safe.replace(/\[\[CMD:([^|\]]+)\|([^\]]+)\]\]/g, function (_, cmd, label) {
                return '<button type="button" class="adminai-chat-cmd-edit" title="'
                    + escapeHtml(label)
                    + '" aria-label="'
                    + escapeHtml(label)
                    + '" data-command="'
                    + escapeHtml(cmd)
                    + '">✎</button>';
            });
            safe = safe.replace(/\[\[PREFILL:([^|\]]+)\|([^\]]+)\]\]/g, function (_, prefix, label) {
                return '<button type="button" class="action-default scalable adminai-chat-prefill" data-prefix="'
                    + escapeHtml(prefix) + '"><span>' + escapeHtml(label) + '</span></button>';
            });
            safe = safe.replace(/\[\[ADMIN:([^:|\]]+):([^|\]]+)\|([^\]]+)\]\]/g, function (_, entity, id, label) {
                return '<a href="#" class="adminai-admin-link" data-entity="'
                    + escapeHtml(entity)
                    + '" data-id="'
                    + escapeHtml(id)
                    + '">' + escapeHtml(label) + '</a>';
            });
            safe = safe.replace(/\[\[SEP\]\]/g, '<span class="adminai-result-sep"></span>');
            safe = safe.replace(/\n/g, '<br>');
            return safe;
        }

        function resolveAdminLink(entity, id) {
            var itemId = $.trim(String(id || ''));
            switch (String(entity || '')) {
                case 'customer':
                    return urls.customerEditTemplate ? urls.customerEditTemplate.replace('__id__', itemId) : '';
                case 'product':
                    return urls.productEditTemplate ? urls.productEditTemplate.replace('__id__', itemId) : '';
                case 'order':
                    return urls.orderViewTemplate ? urls.orderViewTemplate.replace('__id__', itemId) : '';
                case 'orders':
                    return urls.ordersGridUrl || '';
                default:
                    return '';
            }
        }

        function parseUserMessageDisplay(text) {
            var lines = String(text || '').split(/\n/);
            if (lines.length < 2) {
                return null;
            }

            var firstLine = $.trim(lines[0]);
            var restLines = lines.slice(1).join('\n');

            if (firstLine === '' || !firstLine.match(/^\w+/)) {
                return null;
            }

            if (restLines.indexOf('Query for') === -1 && restLines.indexOf('intent') === -1) {
                return null;
            }

            return {
                command: firstLine,
                explanation: $.trim(restLines)
            };
        }

        function appendMessage(text, sender) {
            var senderLabel = sender === 'user' ? $t('You') : $t('AI Assistant');
            var contentHtml;
            var isIntentMessage = false;

            if (sender === 'user') {
                var parsed = parseUserMessageDisplay(text);
                if (parsed) {
                    isIntentMessage = true;
                    contentHtml = buildUserMessageHtml(text);
                }
            }

            if (!isIntentMessage) {
                contentHtml = sender === 'assistant'
                    ? formatAssistantMessageHtml(text)
                    : escapeHtml(text);
                contentHtml = contentHtml.replace(/\n/g, '<br>');
            }

            var html = [
                '<div class="adminai-message adminai-message-' + sender + '">',
                '<strong>' + senderLabel + '</strong>',
                '<div class="adminai-message-content">' + contentHtml + '</div>',
                '</div>'
            ].join('');

            $messages.append(html);
            $messages.scrollTop($messages[0].scrollHeight);
        }

        function buildUserMessageHtml(text) {
            var parsed = parseUserMessageDisplay(text);
            if (parsed) {
                return '<div class="adminai-user-intent-display">' +
                    '<div class="adminai-intent-command">' +
                    '<code>' + escapeHtml(parsed.command) + '</code>' +
                    '<button type="button" class="adminai-copy-command" title="Copy command">📋</button>' +
                    '</div>' +
                    '<div class="adminai-intent-separator"></div>' +
                    '<div class="adminai-intent-explanation">' + escapeHtml(parsed.explanation).replace(/\n/g, '<br>') + '</div>' +
                    '</div>';
            }
            return escapeHtml(text).replace(/\n/g, '<br>');
        }

        function updateLastUserMessage(text) {
            var $last = $messages.find('.adminai-message-user:last .adminai-message-content');
            if ($last.length) {
                $last.html(buildUserMessageHtml(text));
            }
        }

        function renderMessages(messages) {
            $messages.empty();

            if (!messages.length) {
                appendMessage(
                    state.geminiEnabled
                        ? $t('Gemini mode enabled. Start chatting or use an intent command.')
                        : $t('Offline mode enabled. Use exact intents from the right panel.'),
                    'assistant'
                );
                return;
            }

            $.each(messages, function (_, msg) {
                appendMessage(msg.content || '', msg.role === 'user' ? 'user' : 'assistant');
            });
        }

        function renderSessions() {
            var filterTerm = $.trim($searchSessions.val()).toLowerCase();
            $sessionList.empty();

            var filtered = state.sessions;
            if (filterTerm) {
                filtered = $.grep(state.sessions, function (session) {
                    return (session.title || '').toLowerCase().indexOf(filterTerm) !== -1;
                });
            }

            if (!filtered.length) {
                $sessionList.append(
                    '<li class="adminai-session-item">' +
                    $t(filterTerm ? 'No matching chats.' : 'No chats yet.') +
                    '</li>'
                );
                return;
            }

            $.each(filtered, function (_, session) {
                var activeClass = Number(session.session_id) === Number(state.activeSessionId) ? ' is-active' : '';
                var html = [
                    '<li class="adminai-session-item' + activeClass + '" data-session-id="' + session.session_id + '">',
                    '<div class="adminai-session-title">' + escapeHtml(session.title || ('Chat #' + session.session_id)) + '</div>',
                    '<div class="adminai-session-date">' + escapeHtml(formatDate(session.updated_at || session.created_at)) + '</div>',
                    '<div class="adminai-session-preview">' + escapeHtml(session.last_message || '') + '</div>',
                    '</li>'
                ].join('');

                $sessionList.append(html);
            });
        }

        function loadSessions(onDone) {
            $.ajax({
                url: urls.sessions,
                type: 'GET',
                dataType: 'json'
            }).done(function (response) {
                state.sessions = response && response.sessions ? response.sessions : [];
                renderSessions();

                if (typeof onDone === 'function') {
                    onDone(state.sessions);
                }
            }).fail(function () {
                appendMessage($t('Unable to load chat list.'), 'assistant');
            });
        }

        function loadMessages(sessionId) {
            if (!sessionId) {
                renderMessages([]);
                return;
            }

            $.ajax({
                url: urls.messages,
                type: 'GET',
                dataType: 'json',
                data: { session_id: sessionId }
            }).done(function (response) {
                renderMessages(response && response.messages ? response.messages : []);
            }).fail(function () {
                appendMessage($t('Unable to load chat history.'), 'assistant');
            });
        }

        function setActiveSession(sessionId) {
            state.activeSessionId = Number(sessionId || 0);
            renderSessions();
            loadMessages(state.activeSessionId);
        }

        function createSession(callback) {
            $.ajax({
                url: urls.createSession,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY
                }
            }).done(function (response) {
                var sessionId = response && response.session_id ? Number(response.session_id) : 0;
                loadSessions(function () {
                    if (sessionId) {
                        setActiveSession(sessionId);
                    }
                    if (typeof callback === 'function') {
                        callback(sessionId);
                    }
                });
            }).fail(function () {
                appendMessage($t('Unable to create a new chat.'), 'assistant');
            });
        }

        function renameActiveSession() {
            if (!state.activeSessionId) {
                return;
            }

            var current = null;
            $.each(state.sessions, function (_, session) {
                if (Number(session.session_id) === Number(state.activeSessionId)) {
                    current = session;
                    return false;
                }
            });

            var nextTitle = window.prompt($t('Enter new chat name:'), current && current.title ? current.title : '');
            if (nextTitle === null) {
                return;
            }

            nextTitle = $.trim(nextTitle);
            if (!nextTitle) {
                return;
            }

            $.ajax({
                url: urls.renameSession,
                type: 'POST',
                dataType: 'json',
                data: {
                    session_id: state.activeSessionId,
                    title: nextTitle,
                    form_key: window.FORM_KEY
                }
            }).done(function () {
                loadSessions();
            }).fail(function () {
                appendMessage($t('Unable to rename chat.'), 'assistant');
            });
        }

        function sendMessage() {
            var userMessage = $.trim($input.val());
            var intentPayload = state.pendingIntentPayload;
            if (!userMessage) {
                return;
            }

            state.pendingIntentPayload = null;

            function runSend(sessionId) {
                appendMessage(userMessage, 'user');
                $input.val('');
                $input.prop('disabled', true);
                $sendButton.prop('disabled', true);

                $.ajax({
                    url: urls.ask,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        message: userMessage,
                        intent_payload: intentPayload ? JSON.stringify(intentPayload) : '',
                        session_id: sessionId,
                        gemini_enabled: state.geminiEnabled ? 1 : 0,
                        form_key: window.FORM_KEY
                    }
                }).done(function (response) {
                    if (response && response.user_message_display) {
                        updateLastUserMessage(response.user_message_display);
                    }

                    var replyText = $t('No reply received.');
                    if (response && response.reply) {
                        if (typeof response.reply === 'object' && response.reply.message) {
                            replyText = response.reply.message;
                        } else if (typeof response.reply === 'string') {
                            replyText = response.reply;
                        }
                    }

                    appendMessage(replyText, 'assistant');

                    if (response && response.session_id) {
                        state.activeSessionId = Number(response.session_id);
                    }

                    loadSessions();
                }).fail(function () {
                    appendMessage($t('Unable to connect to Magento right now.'), 'assistant');
                }).always(function () {
                    $input.prop('disabled', false).trigger('focus');
                    $sendButton.prop('disabled', false);
                });
            }

            if (!state.activeSessionId) {
                createSession(function (sessionId) {
                    runSend(sessionId);
                });
                return;
            }

            runSend(state.activeSessionId);
        }

        function submitCommand(commandText) {
            state.pendingIntentPayload = null;
            $input.val(commandText);
            sendMessage();
        }

        function submitIntentCommand(commandText, intentCode, values) {
            state.pendingIntentPayload = {
                intent: String(intentCode || ''),
                parameters: values || {}
            };

            $input.val(commandText);
            sendMessage();
        }

        function parseTokenValues(raw) {
            return $.grep($.map(String(raw || '').split(/[;,]/), function (part) {
                var value = $.trim(part);
                return value ? value : null;
            }), function (value, idx, arr) {
                return $.inArray(value, arr) === idx;
            });
        }

        function updateTokenFieldValue($field, values) {
            var source = String($field.data('options-source') || '').toLowerCase();
            var separator = source === 'attribute_filters' ? ';' : ',';
            $field.val(values.join(separator));
            $field.trigger('change');
            $field.trigger('adminai:value-changed');
        }

        function renderAttributeTokens($tokensWrap, values) {
            $tokensWrap.empty();
            $.each(values, function (_, code) {
                $tokensWrap.append(
                    '<span class="adminai-attr-token" data-value="' + escapeHtml(code) + '">' +
                    '<span>' + escapeHtml(code) + '</span>' +
                    '<button type="button" class="adminai-attr-token-remove" title="Remove">x</button>' +
                    '</span>'
                );
            });
        }

        function getEntityTypeForField($field) {
            var entityType = $.trim($field.data('entity-type') || '');
            return entityType || 'product';
        }

        function getAttributeOptions(entityType, source, extraParams, callback) {
            var sourceKey = source || 'attributes';
            var requestData = $.extend({
                entity_type: entityType,
                source: sourceKey
            }, extraParams || {});
            var cacheKey = entityType + '::' + sourceKey + '::' + JSON.stringify(requestData);

            if (state.attributeOptionsByEntity[cacheKey]) {
                callback(state.attributeOptionsByEntity[cacheKey]);
                return;
            }

            $.ajax({
                url: urls.attributes,
                type: 'GET',
                dataType: 'json',
                data: requestData
            }).done(function (response) {
                var attrs = response && response.attributes ? response.attributes : [];
                var options = $.grep($.map(attrs, function (row) {
                    var code = row && row.code ? String(row.code) : '';
                    if (!code) {
                        return null;
                    }
                    return {
                        code: code,
                        label: row && row.label ? String(row.label) : code
                    };
                }), function (item) {
                    return !!item;
                });
                state.attributeOptionsByEntity[cacheKey] = options;
                callback(options);
            }).fail(function () {
                state.attributeOptionsByEntity[cacheKey] = [];
                callback([]);
            });
        }

        function setupAttributePicker($field) {
            if ($field.data('attr-picker-init')) {
                return;
            }

            $field.data('attr-picker-init', true);
            $field.hide();

            var values = parseTokenValues($field.val());
            var $picker = $(
                '<div class="adminai-attr-picker">' +
                '<div class="adminai-attr-tokens"></div>' +
                '<div class="adminai-attr-search-wrap">' +
                '<input type="text" class="admin__control-text adminai-attr-search" placeholder="Search attributes..." />' +
                '<div class="adminai-attr-dropdown"></div>' +
                '</div>' +
                '</div>'
            );

            var $tokensWrap = $picker.find('.adminai-attr-tokens');
            var $search = $picker.find('.adminai-attr-search');
            var $dropdown = $picker.find('.adminai-attr-dropdown');
            var options = [];

            var source = $.trim(String($field.data('options-source') || '')) || 'attributes';
            var entityType = getEntityTypeForField($field);
            var $editor = $field.closest('.adminai-intent-editor');
            var $attributeSetsField = $editor.find('.adminai-param-input[data-param-name="attribute_sets"]');

            function getRequestParams() {
                if (source !== 'attribute_filters') {
                    return {};
                }

                var selectedSets = parseTokenValues($attributeSetsField.val());
                if (!selectedSets.length) {
                    return {};
                }

                return {
                    attribute_sets: selectedSets.join(',')
                };
            }

            function loadAndRefreshOptions() {
                getAttributeOptions(entityType, source, getRequestParams(), function (nextOptions) {
                    options = nextOptions || [];
                    refresh(options);
                });
            }

            function renderDropdown(options) {
                var term = $.trim($search.val()).toLowerCase();
                $dropdown.empty();

                var filtered = $.grep(options, function (option) {
                    var code = option && option.code ? String(option.code) : '';
                    var label = option && option.label ? String(option.label) : code;
                    if (!code || $.inArray(code, values) !== -1) {
                        return false;
                    }
                    if (!term) {
                        return true;
                    }
                    return code.toLowerCase().indexOf(term) !== -1 || label.toLowerCase().indexOf(term) !== -1;
                });

                if (!filtered.length) {
                    $dropdown.hide();
                    return;
                }

                $.each(filtered, function (_, option) {
                    var code = option.code;
                    var label = option.label || code;
                    $dropdown.append(
                        '<button type="button" class="adminai-attr-option" data-value="' + escapeHtml(code) + '">' + escapeHtml(label) + '</button>'
                    );
                });

                $dropdown.show();
            }

            function refresh(options) {
                renderAttributeTokens($tokensWrap, values);
                updateTokenFieldValue($field, values);
                renderDropdown(options);
            }

            loadAndRefreshOptions();

            $search.on('input focus', function () {
                renderDropdown(options);
            });

            $dropdown.on('click', '.adminai-attr-option', function () {
                var code = $.trim($(this).data('value') || '');
                if (!code || $.inArray(code, values) !== -1) {
                    return;
                }
                values.push(code);
                $search.val('');
                refresh(options);
            });

            $tokensWrap.on('click', '.adminai-attr-token-remove', function () {
                var code = String($(this).closest('.adminai-attr-token').data('value') || '');
                values = $.grep(values, function (item) {
                    return item !== code;
                });
                refresh(options);
            });

            if (source === 'attribute_filters' && $attributeSetsField.length) {
                $attributeSetsField.on('change.adminaiAttrDep input.adminaiAttrDep adminai:value-changed.adminaiAttrDep', function () {
                    loadAndRefreshOptions();
                });
            }

            $field.after($picker);
        }

        function initAttributePickersInEditor($editor) {
            $editor.find('.adminai-param-input').each(function () {
                var $field = $(this);
                var paramName = String($field.data('param-name') || '').toLowerCase();
                var source = $.trim(String($field.data('options-source') || '')).toLowerCase();
                if (source || paramName === 'attributes') {
                    setupAttributePicker($field);
                }
            });
        }

        function toggleIntentItem($item) {
            var isOpen = $item.hasClass('is-open');
            $item.closest('.adminai-intent-list').find('.adminai-intent-item').removeClass('is-open');
            if (!isOpen) {
                $item.addClass('is-open');
                initAttributePickersInEditor($item.find('.adminai-intent-editor'));
                $item.find('.adminai-param-input:visible:first, .adminai-attr-search:first').trigger('focus');
            }
        }

        function buildCommandFromTemplate(template, values) {
            var command = template;
            $.each(values, function (paramName, paramValue) {
                command = command.replace('{' + paramName + '}', paramValue);
            });
            // Remove unresolved placeholders for optional params.
            command = command.replace(/\{[^}]+\}/g, '');
            return $.trim(command.replace(/\s+/g, ' '));
        }

        refreshModeUi();

        $container.find('.adminai-toggle-mode').on('click', function () {
            state.geminiEnabled = !state.geminiEnabled;
            setGeminiEnabled(state.geminiEnabled);
            refreshModeUi();
        });

        $newChat.on('click', function () {
            createSession();
        });

        $renameChat.on('click', function () {
            renameActiveSession();
        });

        $searchSessions.on('input', function () {
            renderSessions();
        });

        $container.on('click', '.adminai-session-item', function () {
            var sessionId = Number($(this).data('session-id') || 0);
            if (sessionId) {
                setActiveSession(sessionId);
            }
        });

        $container.on('click', '.adminai-intent-trigger', function () {
            var $item = $(this).closest('.adminai-intent-item');
            var template = $.trim($item.data('command-template') || '');

            if (!template) {
                return;
            }

            toggleIntentItem($item);
        });

        $container.on('click', '.adminai-intent-edit', function () {
            var $item = $(this).closest('.adminai-intent-item');
            toggleIntentItem($item);
        });

        $container.on('click', '.adminai-intent-send', function () {
            var $item = $(this).closest('.adminai-intent-item');
            var template = $.trim($item.data('command-template') || '');
            var intentCode = $.trim($item.data('intent-code') || '');
            var values = {};
            var hasError = false;

            if (!template) {
                return;
            }

            $item.find('.adminai-param-input').each(function () {
                var $field = $(this);
                var paramName = $.trim($field.data('param-name') || '');
                var isRequired = $field.data('required') === 1 || $field.data('required') === '1';
                var value = $.trim($field.val());

                if (isRequired && !value) {
                    hasError = true;
                    $field.addClass('adminai-param-error');
                    return;
                }

                $field.removeClass('adminai-param-error');

                if (paramName && value) {
                    values[paramName] = value;
                }
            });

            if (hasError) {
                appendMessage($t('Please fill all required parameters.'), 'assistant');
                return;
            }

            submitIntentCommand(buildCommandFromTemplate(template, values), intentCode, values);
            $item.removeClass('is-open');
        });

        $container.on('keydown', '.adminai-param-input', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $(this).closest('.adminai-intent-editor').find('.adminai-intent-send').trigger('click');
            }
        });

        $container.on('click', '.adminai-chat-cmd-edit', function () {
            var command = $.trim($(this).data('command') || '');
            if (!command) {
                return;
            }
            submitCommand(command);
        });

        $container.on('click', '.adminai-chat-prefill', function () {
            var prefix = $.trim($(this).data('prefix') || '');
            if (!prefix) {
                return;
            }
            $input.val(prefix + ' ').trigger('focus');
        });

        $container.on('click', '.adminai-chat-inline-send', function () {
            var $wrap = $(this).closest('.adminai-chat-inline-input');
            var prefix = $.trim($wrap.data('prefix') || '');
            var value = $.trim($wrap.find('.adminai-chat-inline-field').val() || '');

            if (!prefix || !value) {
                return;
            }

            submitCommand(prefix + ' ' + value);
        });

        $container.on('keydown', '.adminai-chat-inline-field', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $(this).closest('.adminai-chat-inline-input').find('.adminai-chat-inline-send').trigger('click');
            }
        });

        $container.on('click', '.adminai-admin-link', function (event) {
            event.preventDefault();
            var entity = $(this).data('entity');
            var id = $(this).data('id');
            var targetUrl = resolveAdminLink(entity, id);
            if (!targetUrl) {
                return;
            }
            window.open(targetUrl, '_blank');
        });

        $container.on('click', '.adminai-copy-command', function () {
            var $command = $(this).closest('.adminai-intent-command').find('code');
            var commandText = $.trim($command.text() || '');
            if (!commandText) {
                return;
            }
            $input.val(commandText).trigger('focus');
        });

        $sendButton.on('click', sendMessage);
        $input.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendMessage();
            }
        });

        loadSessions(function (sessions) {
            if (sessions.length) {
                setActiveSession(sessions[0].session_id);
            } else {
                createSession();
            }
        });
    }

    $(initializeAdminAiChat);
});