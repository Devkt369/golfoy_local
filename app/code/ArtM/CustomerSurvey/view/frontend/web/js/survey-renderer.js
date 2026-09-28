define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    var VISITOR_KEY = 'artm_cs_visitor_key';
    var PAGE_VIEWS_KEY = 'artm_cs_page_views';
    var LOGIN_STATE_KEY = 'artm_cs_login_state';

    function buildMediaUrl(config, path) {
        var mediaUrl = (config && config.mediaUrl) ? String(config.mediaUrl) : '/media/';
        var normalizedBase = mediaUrl.replace(/\/+$/, '') + '/';
        var normalizedPath = String(path || '').replace(/^\/+/, '');

        return normalizedBase + normalizedPath;
    }

    function safeClone(value) {
        try {
            return JSON.parse(JSON.stringify(value));
        } catch (e) {
            return value;
        }
    }

    function createDebugReporter(config) {
        var dump = {
            module: 'ArtM_CustomerSurvey/js/survey-renderer',
            js_loaded: true,
            started_at: new Date().toISOString(),
            should_render: false,
            reason: 'Not evaluated yet',
            config: safeClone(config || {}),
            context: {},
            eligible_request: {},
            eligible_http: null,
            eligible_response: null,
            eligible_count: 0,
            selected_survey: null,
            selected_location: null,
            inline_result: null,
            popup_result: null,
            notes: []
        };

        function render() {
            // Frontend debug window removed by request.
        }

        return {
            set: function (key, value) {
                dump[key] = safeClone(value);
                render();
            },
            note: function (text) {
                dump.notes.push(String(text || ''));
                render();
            },
            getDump: function () {
                return dump;
            }
        };
    }

    function safeJson(response) {
        if (!response) {
            return [];
        }
        if (Array.isArray(response)) {
            return response;
        }
        if (response.items && Array.isArray(response.items)) {
            return response.items;
        }
        return [];
    }

    function detectPageType() {
        var body = document.body;
        if (!body) {
            return 'custom_url';
        }

        var classes = body.className || '';
        if (classes.indexOf('catalog-product-view') !== -1) {
            return 'product';
        }
        if (classes.indexOf('catalog-category-view') !== -1) {
            return 'category';
        }
        if (classes.indexOf('checkout-cart-index') !== -1) {
            return 'cart';
        }
        if (classes.indexOf('checkout-index-index') !== -1) {
            return 'checkout';
        }
        if (classes.indexOf('checkout-onepage-success') !== -1) {
            return 'success';
        }
        if (classes.indexOf('customer-account') !== -1) {
            return 'customer';
        }
        if (classes.indexOf('cms-') !== -1) {
            return 'cms';
        }

        return 'custom_url';
    }

    function getCustomerContext() {
        var customer = customerData.get('customer')();
        var loggedIn = !!(customer && customer.firstname);

        return {
            is_logged_in: loggedIn,
            customer_id: 0,
            customer_group_id: 0
        };
    }

    function getProductId() {
        var productInput = document.querySelector('input[name="product"]');
        if (!productInput) {
            return 0;
        }
        var val = parseInt(productInput.value, 10);
        return Number.isNaN(val) ? 0 : val;
    }

    function getCategoryId() {
        var node = document.querySelector('[data-category-id]');
        if (!node) {
            return 0;
        }
        var val = parseInt(node.getAttribute('data-category-id'), 10);
        return Number.isNaN(val) ? 0 : val;
    }

    function getOrCreateVisitorKey() {
        var existing = localStorage.getItem(VISITOR_KEY);
        if (existing) {
            return existing;
        }

        var key = 'guest_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);
        localStorage.setItem(VISITOR_KEY, key);
        return key;
    }

    function incrementPageViews() {
        var current = parseInt(localStorage.getItem(PAGE_VIEWS_KEY) || '0', 10);
        if (Number.isNaN(current)) {
            current = 0;
        }
        current += 1;
        localStorage.setItem(PAGE_VIEWS_KEY, String(current));
        return current;
    }

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(function (res) {
            if (!res.ok) {
                return null;
            }
            return res.json().catch(function () {
                return null;
            });
        }).catch(function () {
            return null;
        });
    }

    function postJsonDetailed(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload || {})
        }).then(function (res) {
            return res.text().then(function (text) {
                var parsed = null;
                if (text) {
                    try {
                        parsed = JSON.parse(text);
                    } catch (e) {
                        parsed = null;
                    }
                }

                return {
                    ok: !!res.ok,
                    status: res.status,
                    status_text: res.statusText || '',
                    body: parsed,
                    body_text: text || ''
                };
            });
        }).catch(function (error) {
            return {
                ok: false,
                status: 0,
                status_text: 'NETWORK_ERROR',
                body: null,
                body_text: '',
                error_message: error && error.message ? String(error.message) : 'Network request failed'
            };
        });
    }

    function createContainer() {
        var container = document.createElement('div');
        container.className = 'artm-cs-widget';
        return container;
    }

    function getMatchedLocation(survey) {
        if (!survey.locations || !survey.locations.length) {
            return null;
        }

        for (var i = 0; i < survey.locations.length; i++) {
            var location = survey.locations[i];
            var selector = location.css_selector || '';
            if (selector && document.querySelector(selector)) {
                return location;
            }
        }

        return survey.locations[0];
    }

    function getProgressPercent(index, total) {
        if (!total) {
            return 0;
        }
        return Math.round(((index + 1) / total) * 100);
    }

    function normalizeAnswers(answersMap) {
        var entries = Object.keys(answersMap).map(function (questionId) {
            return {
                question_id: parseInt(questionId, 10),
                value: answersMap[questionId]
            };
        });

        return entries
            .filter(function (item) {
                return !Number.isNaN(item.question_id);
            })
            .map(function (item) {
                var payload = {
                    question_id: item.question_id,
                    option_ids: [],
                    answer_text: ''
                };

                if (Array.isArray(item.value)) {
                    payload.option_ids = item.value.map(function (id) {
                        return parseInt(id, 10);
                    }).filter(function (id) {
                        return !Number.isNaN(id);
                    });
                } else if (typeof item.value === 'number') {
                    payload.option_ids = [item.value];
                } else if (typeof item.value === 'string') {
                    payload.answer_text = item.value;
                } else if (item.value && typeof item.value === 'object') {
                    if (item.value.option_ids) {
                        payload.option_ids = item.value.option_ids;
                    }
                    if (item.value.answer_text) {
                        payload.answer_text = item.value.answer_text;
                    }
                }

                return payload;
            });
    }

    function questionHasValue(question, value) {
        if (value === undefined || value === null) {
            return false;
        }

        if (question.type === 'checkbox') {
            return Array.isArray(value) && value.length > 0;
        }

        if (question.type === 'text' || question.type === 'textarea') {
            return String(value).trim() !== '';
        }

        return value !== '';
    }

    function validateCurrentQuestion(question, value) {
        if (question.type === 'checkbox') {
            var selectedValues = Array.isArray(value) ? value : [];
            var minSel = question.min_selection || 0;
            var maxSel = question.max_selection || 0;

            if (minSel > 0 && selectedValues.length < minSel) {
                return { valid: false, message: 'Choose at least ' + minSel + ' option(s).' };
            }
            if (maxSel > 0 && selectedValues.length > maxSel) {
                return { valid: false, message: 'Choose at most ' + maxSel + ' option(s).' };
            }
        }

        if (!question.is_required) {
            return { valid: true, message: '' };
        }

        if (!questionHasValue(question, value)) {
            if (question.type === 'checkbox') {
                var minRequired = question.min_selection || 0;
                if (minRequired > 0) {
                    return { valid: false, message: 'Choose at least ' + minRequired + ' option(s).' };
                }
            }
            return { valid: false, message: 'This question is required.' };
        }

        return { valid: true, message: '' };
    }

    function createQuestionInput(question, answers, onChange) {
        var wrap = document.createElement('div');
        wrap.className = 'artm-cs-input-wrap';

        var qId = String(question.question_id);
        var current = answers[qId];

        if (question.type === 'text' || question.type === 'textarea') {
            var textInput = question.type === 'textarea'
                ? document.createElement('textarea')
                : document.createElement('input');
            if (question.type === 'text') {
                textInput.type = 'text';
            }
            textInput.className = 'artm-cs-field';
            textInput.value = typeof current === 'string' ? current : '';
            textInput.addEventListener('input', function () {
                onChange(textInput.value);
            });
            wrap.appendChild(textInput);
            return wrap;
        }

        if (question.type === 'dropdown') {
            var select = document.createElement('select');
            select.className = 'artm-cs-field';

            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select an option';
            select.appendChild(placeholder);

            (question.options || []).forEach(function (option) {
                var opt = document.createElement('option');
                opt.value = String(option.option_id);
                opt.textContent = option.title;
                if (String(current) === String(option.option_id)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });

            select.addEventListener('change', function () {
                var val = parseInt(select.value, 10);
                onChange(Number.isNaN(val) ? '' : val);
            });
            wrap.appendChild(select);
            return wrap;
        }

        if (question.type === 'yes_no') {
            var yesNoOptions = [
                { option_id: 1, title: 'Yes' },
                { option_id: 2, title: 'No' }
            ];
            question.options = yesNoOptions;
            question.type = 'radio';
        }

        if (question.type === 'rating') {
            var ratingWrap = document.createElement('div');
            ratingWrap.className = 'artm-cs-rating';
            var currentRating = parseInt(current || 0, 10);

            for (var i = 1; i <= 5; i++) {
                (function (score) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'artm-cs-rating-star' + (score <= currentRating ? ' is-active' : '');
                    btn.textContent = '★';
                    btn.addEventListener('click', function () {
                        currentRating = score;
                        onChange(String(score));
                        Array.prototype.forEach.call(ratingWrap.children, function (child, idx) {
                            child.classList.toggle('is-active', idx < score);
                        });
                    });
                    ratingWrap.appendChild(btn);
                })(i);
            }

            wrap.appendChild(ratingWrap);
            return wrap;
        }

        var options = question.options || [];
        var isMulti = question.type === 'checkbox';
        var currentMulti = Array.isArray(current) ? current.map(String) : [];

        options.forEach(function (option) {
            var label = document.createElement('label');
            label.className = 'artm-cs-option';

            var input = document.createElement('input');
            input.type = isMulti ? 'checkbox' : 'radio';
            input.name = 'question_' + qId;
            input.value = String(option.option_id);
            if (isMulti) {
                input.checked = currentMulti.indexOf(String(option.option_id)) !== -1;
            } else {
                input.checked = String(current || '') === String(option.option_id);
            }

            input.addEventListener('change', function () {
                if (isMulti) {
                    var selected = Array.prototype.slice.call(
                        wrap.querySelectorAll('input[type="checkbox"]:checked')
                    ).map(function (el) {
                        return parseInt(el.value, 10);
                    });
                    onChange(selected);
                    return;
                }
                onChange(parseInt(input.value, 10));
            });

            var contentWrapper = document.createElement('div');
            contentWrapper.className = 'artm-cs-option-content';

            // If option has an image, display it; otherwise show title
            if (option.image && option.image.trim() !== '') {
                var img = document.createElement('img');
                img.src = buildMediaUrl(window.artmCustomerSurveyConfig || {}, option.image);
                img.alt = option.title;
                img.className = 'artm-cs-option-image';
                contentWrapper.appendChild(img);

                var title = document.createElement('div');
                title.className = 'artm-cs-option-title';
                title.textContent = option.title;
                contentWrapper.appendChild(title);
            } else {
                var text = document.createElement('span');
                text.textContent = option.title;
                contentWrapper.appendChild(text);
            }

            label.appendChild(input);
            label.appendChild(contentWrapper);
            wrap.appendChild(label);
        });

        return wrap;
    }

    function renderSurveyCard(root, survey, state, handlers) {
        var questions = survey.questions || [];
        if (!questions.length) {
            root.innerHTML = '';
            return;
        }

        var index = state.currentIndex;
        var question = questions[index];

        var progress = getProgressPercent(index, questions.length);

        root.innerHTML = '';

        var card = document.createElement('div');
        card.className = 'artm-cs-card';

        var title = document.createElement('h3');
        title.className = 'artm-cs-title';
        title.textContent = survey.name || 'Quick Survey';

        var desc = document.createElement('p');
        desc.className = 'artm-cs-desc';
        desc.textContent = survey.description || '';

        var progressBar = document.createElement('div');
        progressBar.className = 'artm-cs-progress';
        progressBar.innerHTML = '<span style="width:' + progress + '%"></span>';

        var questionTitle = document.createElement('h4');
        questionTitle.className = 'artm-cs-q-title';
        questionTitle.textContent = question.title;

        var questionDesc = document.createElement('p');
        questionDesc.className = 'artm-cs-q-desc';
        questionDesc.textContent = question.description || '';

        var validation = document.createElement('p');
        validation.className = 'artm-cs-validation';

        var inputWrap = createQuestionInput(question, state.answers, function (value) {
            state.answers[String(question.question_id)] = value;
            if (handlers && typeof handlers.onAnswerChange === 'function') {
                handlers.onAnswerChange(question, value);
            }
            var liveResult = validateCurrentQuestion(question, value);
            if (liveResult.valid) {
                validation.textContent = '';
            } else {
                validation.textContent = liveResult.message;
            }
            updateNextButtonState();
        });

        var footer = document.createElement('div');
        footer.className = 'artm-cs-footer';

        var backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'artm-cs-btn artm-cs-btn-light';
        backBtn.textContent = 'Back';
        backBtn.disabled = index === 0;
        backBtn.addEventListener('click', handlers.onPrev);

        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'artm-cs-btn artm-cs-btn-primary';
        nextBtn.textContent = index === questions.length - 1 ? 'Submit' : 'Next';
        nextBtn.addEventListener('click', function () {
            var result = validateCurrentQuestion(question, state.answers[String(question.question_id)]);
            if (!result.valid) {
                validation.textContent = result.message;
                return;
            }
            handlers.onNext();
        });

        function updateNextButtonState() {
            var currentValue = state.answers[String(question.question_id)];
            var stateResult = validateCurrentQuestion(question, currentValue);
            nextBtn.disabled = !stateResult.valid;
            nextBtn.title = stateResult.valid ? '' : stateResult.message;
        }

        updateNextButtonState();

        footer.appendChild(backBtn);
        footer.appendChild(nextBtn);

        card.appendChild(title);
        if (desc.textContent) {
            card.appendChild(desc);
        }
        card.appendChild(progressBar);
        card.appendChild(questionTitle);
        if (questionDesc.textContent) {
            card.appendChild(questionDesc);
        }
        card.appendChild(inputWrap);
        card.appendChild(validation);
        card.appendChild(footer);

        root.appendChild(card);
    }

    function renderThankYou(root) {
        root.innerHTML = '';

        var wrap = document.createElement('div');
        wrap.className = 'artm-cs-thankyou';
        wrap.innerHTML = '<h4>Thank you</h4><p>Your feedback has been recorded.</p>';
        root.appendChild(wrap);
    }

    function placeInline(container, survey, debug) {
        var location = getMatchedLocation(survey);
        var selector = location && location.css_selector ? location.css_selector : (survey.display.inline_selector || '');
        if (!selector) {
            if (debug) {
                debug.set('inline_result', {
                    success: false,
                    reason: 'No inline selector found',
                    location_id: location ? location.location_id || null : null
                });
            }
            return false;
        }

        var target = document.querySelector(selector);
        if (!target) {
            if (debug) {
                debug.set('inline_result', {
                    success: false,
                    reason: 'Selector not found in DOM',
                    selector: selector,
                    location_id: location ? location.location_id || null : null
                });
            }
            return false;
        }

        var position = (location && location.position) || survey.display.inline_position || 'after';
        if (position === 'before') {
            target.parentNode.insertBefore(container, target);
        } else if (position === 'inside') {
            target.appendChild(container);
        } else if (position === 'replace') {
            target.parentNode.replaceChild(container, target);
        } else {
            target.parentNode.insertBefore(container, target.nextSibling);
        }

        if (debug) {
            debug.set('inline_result', {
                success: true,
                selector: selector,
                position: position,
                location_id: location ? location.location_id || null : null
            });
        }

        return true;
    }

    function createPopupFrame() {
        var overlay = document.createElement('div');
        overlay.className = 'artm-cs-popup';
        overlay.innerHTML = '<div class="artm-cs-popup-backdrop"></div><div class="artm-cs-popup-panel"><button type="button" class="artm-cs-popup-close">x</button><div class="artm-cs-popup-content"></div></div>';
        document.body.appendChild(overlay);
        return overlay;
    }

    function isElementVisibleInViewport(element, minRatio) {
        if (!element || typeof element.getBoundingClientRect !== 'function') {
            return false;
        }

        var rect = element.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        if (viewportWidth <= 0 || viewportHeight <= 0 || rect.width <= 0 || rect.height <= 0) {
            return false;
        }

        var visibleWidth = Math.min(rect.right, viewportWidth) - Math.max(rect.left, 0);
        var visibleHeight = Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0);
        if (visibleWidth <= 0 || visibleHeight <= 0) {
            return false;
        }

        var visibleArea = visibleWidth * visibleHeight;
        var totalArea = rect.width * rect.height;
        var ratio = totalArea > 0 ? (visibleArea / totalArea) : 0;

        return ratio >= (typeof minRatio === 'number' ? minRatio : 0.25);
    }

    function watchInlineOpen(container, endpoint, survey, context, debug) {
        var opened = false;
        var timerId = null;
        var observer = null;
        var thresholdMs = 3000;
        var minVisibleRatio = 0.25;

        function clearTimer() {
            if (timerId) {
                clearTimeout(timerId);
                timerId = null;
            }
        }

        function cleanup() {
            clearTimer();
            if (observer) {
                observer.disconnect();
                observer = null;
            }
            window.removeEventListener('scroll', checkVisibility, true);
            window.removeEventListener('resize', checkVisibility, true);
            document.removeEventListener('visibilitychange', checkVisibility, true);
        }

        function trackOpen() {
            if (opened) {
                return;
            }
            opened = true;
            cleanup();

            if (debug) {
                debug.set('inline_result', {
                    success: true,
                    reason: 'Inline survey visible for 3 seconds.'
                });
            }

            postJson(endpoint.popupOpen, {
                survey_id: survey.survey_id,
                visitor_key: context.visitor_key,
                customer_id: context.customer_id
            });
        }

        function scheduleTimer() {
            if (opened || timerId || !isElementVisibleInViewport(container, minVisibleRatio)) {
                return;
            }

            timerId = setTimeout(function () {
                timerId = null;
                if (isElementVisibleInViewport(container, minVisibleRatio)) {
                    trackOpen();
                }
            }, thresholdMs);
        }

        function checkVisibility() {
            if (opened) {
                return;
            }

            if (document.hidden) {
                clearTimer();
                return;
            }

            if (isElementVisibleInViewport(container, minVisibleRatio)) {
                scheduleTimer();
                return;
            }

            clearTimer();
        }

        document.addEventListener('visibilitychange', checkVisibility, true);

        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(function (entries) {
                if (opened || !entries || !entries.length) {
                    return;
                }

                var entry = entries[0];
                if (entry.isIntersecting && entry.intersectionRatio >= minVisibleRatio) {
                    scheduleTimer();
                } else {
                    clearTimer();
                }
            }, {
                threshold: [minVisibleRatio]
            });
            observer.observe(container);
        } else {
            window.addEventListener('scroll', checkVisibility, true);
            window.addEventListener('resize', checkVisibility, true);
            document.addEventListener('visibilitychange', checkVisibility, true);
        }

        checkVisibility();
    }

    function addPopupTriggers(survey, context, openPopup) {
        var triggers = (survey.display.popup_triggers || []).slice();
        if (!triggers.length) {
            triggers = ['page_load'];
        }

        var opened = false;
        function openOnce() {
            if (opened) {
                return;
            }
            opened = true;
            openPopup();
        }

        triggers.forEach(function (trigger) {
            if (trigger === 'page_load') {
                setTimeout(openOnce, 0);
            }

            if (trigger === 'after_seconds') {
                var delay = (survey.display.popup_delay_seconds || 0) * 1000;
                setTimeout(openOnce, Math.max(delay, 0));
            }

            if (trigger === 'after_page_views') {
                var views = parseInt(localStorage.getItem(PAGE_VIEWS_KEY) || '0', 10);
                var minViews = parseInt(survey.display.popup_page_views || 0, 10);
                if (views >= minViews && minViews > 0) {
                    setTimeout(openOnce, 200);
                }
            }

            if (trigger === 'on_cart_page' && context.page_type === 'cart') {
                setTimeout(openOnce, 300);
            }

            if (trigger === 'on_checkout_page' && context.page_type === 'checkout') {
                setTimeout(openOnce, 300);
            }

            if (trigger === 'on_order_success' && context.page_type === 'success') {
                setTimeout(openOnce, 300);
            }

            if (trigger === 'exit_intent') {
                var handled = false;
                document.addEventListener('mouseout', function (event) {
                    if (handled) {
                        return;
                    }
                    if (event.clientY <= 0) {
                        handled = true;
                        openOnce();
                    }
                });
            }

            if (trigger === 'on_add_to_cart') {
                $(document).on('ajaxComplete.artmSurvey', function (event, xhr, settings) {
                    if (!settings || !settings.url) {
                        return;
                    }
                    if (String(settings.url).indexOf('checkout/cart/add') !== -1) {
                        openOnce();
                    }
                });
            }

            if (trigger === 'on_login' || trigger === 'on_logout') {
                var currentState = context.is_logged_in ? '1' : '0';
                var previousState = localStorage.getItem(LOGIN_STATE_KEY);
                localStorage.setItem(LOGIN_STATE_KEY, currentState);
                if (previousState === null) {
                    return;
                }
                if (trigger === 'on_login' && previousState === '0' && currentState === '1') {
                    openOnce();
                }
                if (trigger === 'on_logout' && previousState === '1' && currentState === '0') {
                    openOnce();
                }
            }
        });
    }

    function initSurveyWidget(config, survey, context, debug) {
        var state = {
            currentIndex: 0,
            answers: {},
            response_id: null,
            saving: false,
            firstAnswerTracked: false
        };

        var endpoint = config.api;
        var container = createContainer();
        var isPopup = survey.display && survey.display.display_type === 'popup';
        var matchedLocation = getMatchedLocation(survey);
        context.location_id = matchedLocation && matchedLocation.location_id ? parseInt(matchedLocation.location_id, 10) || null : null;

        if (debug) {
            debug.set('selected_survey', {
                survey_id: survey.survey_id || null,
                name: survey.name || '',
                display_type: survey.display && survey.display.display_type ? survey.display.display_type : '',
                questions_count: Array.isArray(survey.questions) ? survey.questions.length : 0,
                locations_count: Array.isArray(survey.locations) ? survey.locations.length : 0
            });
            debug.set('selected_location', matchedLocation ? {
                location_id: matchedLocation.location_id || null,
                css_selector: matchedLocation.css_selector || '',
                position: matchedLocation.position || ''
            } : null);
        }

        function trackFirstAnswer(question, value) {
            if (state.firstAnswerTracked || !questionHasValue(question, value)) {
                return;
            }

            state.firstAnswerTracked = true;
            postJson(endpoint.answered, {
                survey_id: survey.survey_id,
                visitor_key: context.visitor_key,
                customer_id: context.customer_id,
                location_id: context.location_id || null,
                page_url: window.location.href
            });
        }

        function persistAnswers(answerPayload, isCompleted, onDone) {
            if (!Array.isArray(answerPayload) || !answerPayload.length) {
                if (typeof onDone === 'function') {
                    onDone(true);
                }
                return;
            }

            var payload = {
                survey_id: survey.survey_id,
                response_id: state.response_id || 0,
                is_completed: isCompleted ? 1 : 0,
                visitor_key: context.visitor_key,
                customer_id: context.customer_id,
                location_id: context.location_id || null,
                page_url: window.location.href,
                device: navigator.platform || '',
                browser: navigator.userAgent || '',
                answers: answerPayload
            };

            state.saving = true;
            postJson(endpoint.submit, payload).then(function (response) {
                state.saving = false;
                if (response && response.response_id) {
                    state.response_id = parseInt(response.response_id, 10) || state.response_id;
                }

                var ok = !!(response && response.success !== false);
                if (!ok && debug) {
                    debug.note('Answer save failed for survey ' + String(survey.survey_id));
                }

                if (typeof onDone === 'function') {
                    onDone(ok);
                }
            });
        }

        function renderStep() {
            renderSurveyCard(container, survey, state, {
                onAnswerChange: trackFirstAnswer,
                onPrev: function () {
                    if (state.saving) {
                        return;
                    }
                    state.currentIndex = Math.max(0, state.currentIndex - 1);
                    renderStep();
                },
                onNext: function () {
                    if (state.saving) {
                        return;
                    }

                    var total = (survey.questions || []).length;
                    var currentQuestion = (survey.questions || [])[state.currentIndex] || null;
                    var currentValue = currentQuestion ? state.answers[String(currentQuestion.question_id)] : null;
                    var currentPayload = currentQuestion
                        ? normalizeAnswers((function () {
                            var single = {};
                            single[String(currentQuestion.question_id)] = currentValue;
                            return single;
                        })())
                        : [];

                    if (state.currentIndex >= total - 1) {
                        // Final submit persists all current answers and marks survey completed.
                        persistAnswers(normalizeAnswers(state.answers), true, function (ok) {
                            if (!ok) {
                                renderStep();
                                return;
                            }
                            renderThankYou(container);
                        });
                        return;
                    }

                    // Save current question immediately when moving forward.
                    persistAnswers(currentPayload, false, function (ok) {
                        if (!ok) {
                            renderStep();
                            return;
                        }
                        state.currentIndex += 1;
                        renderStep();
                    });
                }
            });
        }

        if (!isPopup) {
            if (!placeInline(container, survey, debug)) {
                if (debug) {
                    debug.set('should_render', false);
                    debug.set('reason', 'Inline mode selected but target selector not found.');
                }
                return;
            }

            if (debug) {
                debug.set('should_render', true);
                debug.set('reason', 'Inline survey rendered.');
            }

            renderStep();
            postJson(endpoint.impression, {
                survey_id: survey.survey_id,
                visitor_key: context.visitor_key,
                customer_id: context.customer_id
            });
            watchInlineOpen(container, endpoint, survey, context, debug);
            return;
        }

        var popup = createPopupFrame();
        var panel = popup.querySelector('.artm-cs-popup-content');
        var closeBtn = popup.querySelector('.artm-cs-popup-close');
        panel.appendChild(container);

        function openPopup() {
            popup.classList.add('is-open');
            renderStep();

            if (debug) {
                debug.set('popup_result', {
                    opened: true,
                    triggers: (survey.display && survey.display.popup_triggers) || ['page_load']
                });
                debug.set('should_render', true);
                debug.set('reason', 'Popup survey opened by trigger.');
            }

            postJson(endpoint.popupOpen, {
                survey_id: survey.survey_id,
                visitor_key: context.visitor_key,
                customer_id: context.customer_id
            });
            postJson(endpoint.impression, {
                survey_id: survey.survey_id,
                visitor_key: context.visitor_key,
                customer_id: context.customer_id
            });
        }

        function closePopup() {
            popup.classList.remove('is-open');
            postJson(endpoint.closed, {
                survey_id: survey.survey_id,
                visitor_key: context.visitor_key,
                customer_id: context.customer_id
            });
        }

        closeBtn.addEventListener('click', closePopup);
        popup.querySelector('.artm-cs-popup-backdrop').addEventListener('click', closePopup);

        addPopupTriggers(survey, context, openPopup);

        if (debug) {
            debug.set('popup_result', {
                opened: false,
                waiting_for_trigger: true,
                triggers: (survey.display && survey.display.popup_triggers) || ['page_load']
            });
            debug.set('reason', 'Popup configured; waiting for trigger.');
        }
    }

    return function (config) {
        $(function () {
            var debug = createDebugReporter(config || {});
            incrementPageViews();

            var visitorKey = getOrCreateVisitorKey();
            var customerContext = getCustomerContext();

            var context = {
                page_type: detectPageType(),
                product_id: getProductId(),
                category_id: getCategoryId(),
                url: window.location.href,
                store_id: parseInt(config.storeId || 0, 10) || 0,
                visitor_key: visitorKey,
                customer_id: customerContext.customer_id,
                customer_group_id: customerContext.customer_group_id,
                is_logged_in: customerContext.is_logged_in
            };

            debug.set('context', context);
            debug.set('eligible_request', { context: context });
            debug.note('Calling eligible API: ' + String(config && config.api && config.api.eligible ? config.api.eligible : 'N/A'));

            postJsonDetailed(config.api.eligible, { context: context }).then(function (apiResult) {
                debug.set('eligible_http', {
                    ok: !!apiResult.ok,
                    status: apiResult.status,
                    status_text: apiResult.status_text,
                    error_message: apiResult.error_message || ''
                });
                debug.set('eligible_response', apiResult.body);

                if (!apiResult.ok) {
                    debug.set('should_render', false);
                    debug.set('reason', 'Eligible API failed: HTTP ' + String(apiResult.status) + ' ' + String(apiResult.status_text || ''));
                    return;
                }

                var surveys = safeJson(apiResult.body);
                debug.set('eligible_count', surveys.length || 0);
                if (!surveys.length) {
                    debug.set('should_render', false);
                    var traceHint = apiResult.body && apiResult.body.trace && apiResult.body.trace.hint
                        ? String(apiResult.body.trace.hint)
                        : '';
                    debug.set('reason', 'Eligible API returned no surveys.' + (traceHint ? ' ' + traceHint : ''));
                    return;
                }

                initSurveyWidget(config, surveys[0], context, debug);
            });
        });
    };
});
