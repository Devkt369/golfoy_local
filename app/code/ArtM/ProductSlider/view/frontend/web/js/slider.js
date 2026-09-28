define([], function () {
    'use strict';

    function parseHtml(html) {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html || '';
        return Array.prototype.slice.call(wrapper.children || []);
    }

    function request(url) {
        return fetch(url, { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            });
    }

    function submitPostData(raw) {
        if (!raw) {
            return;
        }

        var payload;
        try {
            payload = JSON.parse(raw);
        } catch (e) {
            return;
        }

        if (!payload.action || !payload.data) {
            return;
        }

        var form = document.createElement('form');
        form.method = 'post';
        form.action = payload.action;
        Object.keys(payload.data).forEach(function (key) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = String(payload.data[key]);
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }

    return function (config, element) {
        var root = element;
        var track = root.querySelector('[data-role="track"]');
        var viewport = root.querySelector('[data-role="viewport"]');
        var dotsWrap = root.querySelector('[data-role="dots"]');
        var prevBtn = root.querySelector('[data-role="prev"]');
        var nextBtn = root.querySelector('[data-role="next"]');
        var debugPanel = root.querySelector('[data-role="ps-debug-panel"]');
        var debugOutput = root.querySelector('[data-role="ps-debug-events"]');
        var debugCopyBtn = root.querySelector('[data-role="ps-debug-copy"]');
        var debugClearBtn = root.querySelector('[data-role="ps-debug-clear"]');

        if (!root || !track || !viewport) {
            return;
        }

        var state = {
            currentIndex: 0,
            currentPage: parseInt(root.getAttribute('data-initial-page') || '1', 10),
            hasMore: root.getAttribute('data-has-more') === '1',
            totalAvailable: parseInt(root.getAttribute('data-total') || '0', 10),
            loading: false,
            autoplayTimer: null,
            cache: {},
            startX: 0,
            dragX: 0,
            dragging: false
        };

        var cfg = config.config || {};
        var pageSize = parseInt(root.getAttribute('data-page-size') || String(cfg.initialPageSize || 4), 10);
        var debugEnabled = false;
        var eventLogs = [];

        function ensureDebugPanel() {
            if (!debugEnabled) {
                return;
            }

            if (debugPanel && debugOutput) {
                return;
            }

            debugPanel = document.createElement('div');
            debugPanel.className = 'artm-productslider-debug artm-productslider-debug--events';
            debugPanel.setAttribute('data-role', 'ps-debug-panel');

            var title = document.createElement('h4');
            title.textContent = 'Slider Event Debug';
            debugPanel.appendChild(title);

            var help = document.createElement('p');
            help.textContent = 'Click arrows/dots and copy this event log.';
            debugPanel.appendChild(help);

            var controls = document.createElement('p');

            debugCopyBtn = document.createElement('button');
            debugCopyBtn.type = 'button';
            debugCopyBtn.className = 'artm-productslider-debug__btn';
            debugCopyBtn.setAttribute('data-role', 'ps-debug-copy');
            debugCopyBtn.textContent = 'Copy Logs';
            controls.appendChild(debugCopyBtn);

            debugClearBtn = document.createElement('button');
            debugClearBtn.type = 'button';
            debugClearBtn.className = 'artm-productslider-debug__btn';
            debugClearBtn.setAttribute('data-role', 'ps-debug-clear');
            debugClearBtn.textContent = 'Clear';
            controls.appendChild(debugClearBtn);

            debugPanel.appendChild(controls);

            debugOutput = document.createElement('pre');
            debugOutput.setAttribute('data-role', 'ps-debug-events');
            debugPanel.appendChild(debugOutput);

            root.appendChild(debugPanel);
        }

        function snapshot() {
            var visible = Math.max(1, getVisibleItems());
            return {
                idx: state.currentIndex,
                visible: visible,
                cards: countCards(),
                maxIdx: Math.max(0, countCards() - visible),
                page: state.currentPage,
                hasMore: state.hasMore,
                loading: state.loading,
                totalAvailable: state.totalAvailable,
                pageSize: pageSize
            };
        }

        function renderDebugLogs() {
            if (!debugEnabled || !debugOutput) {
                return;
            }
            debugOutput.textContent = eventLogs.join('\n');
            debugOutput.scrollTop = debugOutput.scrollHeight;
        }

        function debugLog(label, extra) {
            if (!debugEnabled) {
                return;
            }
            var now = new Date();
            var ts = now.toISOString().substr(11, 12);
            var line = '[' + ts + '] ' + label + ' | ' + JSON.stringify({
                state: snapshot(),
                extra: extra || {}
            });
            eventLogs.push(line);
            if (eventLogs.length > 250) {
                eventLogs.shift();
            }
            renderDebugLogs();
            try {
                window.__artmProductSliderDebug = window.__artmProductSliderDebug || [];
                window.__artmProductSliderDebug.push(line);
            } catch (e) {
            }
        }

        function bindDebugControls() {
            if (!debugEnabled) {
                return;
            }

            ensureDebugPanel();

            if (debugCopyBtn) {
                debugCopyBtn.addEventListener('click', function () {
                    var text = eventLogs.join('\n');
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function () {
                            debugLog('debug.copy.success');
                        }).catch(function () {
                            debugLog('debug.copy.fail');
                        });
                    } else {
                        debugLog('debug.copy.unsupported');
                    }
                });
            }

            if (debugClearBtn) {
                debugClearBtn.addEventListener('click', function () {
                    eventLogs = [];
                    renderDebugLogs();
                    debugLog('debug.clear');
                });
            }

            debugLog('init', {
                cfg: {
                    displayType: cfg.displayType,
                    itemsDesktop: cfg.itemsDesktop,
                    itemsTablet: cfg.itemsTablet,
                    itemsMobile: cfg.itemsMobile,
                    dots: cfg.dots,
                    infinite: cfg.infinite,
                    autoplay: cfg.autoplay,
                    animationSpeed: cfg.animationSpeed
                }
            });
        }

        root.style.setProperty('--ps-space', String(cfg.spaceBetween || 16) + 'px');
        root.style.setProperty('--ps-items', String(cfg.itemsDesktop || 4));
        root.style.setProperty('--ps-tablet-items', String(cfg.displayType === 'grid' ? (cfg.gridTablet || 2) : (cfg.itemsTablet || 2)));
        root.style.setProperty('--ps-mobile-items', String(cfg.displayType === 'grid' ? (cfg.gridMobile || 1) : (cfg.itemsMobile || 1)));

        function getVisibleItems() {
            if (window.matchMedia('(max-width: 767px)').matches) {
                return cfg.displayType === 'grid' ? (cfg.gridMobile || 1) : (cfg.itemsMobile || 1);
            }
            if (window.matchMedia('(max-width: 1024px)').matches) {
                return cfg.displayType === 'grid' ? (cfg.gridTablet || 2) : (cfg.itemsTablet || 2);
            }

            return cfg.displayType === 'grid' ? (cfg.gridDesktop || 4) : (cfg.itemsDesktop || 4);
        }

        function updateSliderPosition() {
            if (cfg.displayType !== 'slider') {
                return;
            }

            var firstCard = track.querySelector('.artm-productcard');
            if (!firstCard) {
                return;
            }

            var cardWidth = firstCard.getBoundingClientRect().width;
            var gap = cfg.spaceBetween || 16;
            var x = state.currentIndex * (cardWidth + gap);
            track.style.transform = 'translateX(' + (-x) + 'px)';
            track.style.transitionDuration = String(cfg.animationSpeed || 300) + 'ms';
            updateDots();
            debugLog('slider.updatePosition', { cardWidth: cardWidth, gap: gap, translateX: -x });
        }

        function countCards() {
            return track.querySelectorAll('.artm-productcard').length;
        }

        function maxIndex() {
            return Math.max(0, countCards() - getVisibleItems());
        }

        function getPageStarts(visible) {
            var starts = [0];
            var max = maxIndex();
            for (var i = visible; i <= max; i += visible) {
                starts.push(i);
            }
            if (starts[starts.length - 1] !== max) {
                starts.push(max);
            }
            return starts;
        }

        function findCurrentStart(starts) {
            var current = starts[0] || 0;
            for (var i = 0; i < starts.length; i += 1) {
                if (starts[i] <= state.currentIndex) {
                    current = starts[i];
                } else {
                    break;
                }
            }
            return current;
        }

        function ensureReachableIndex(targetIndex) {
            if (targetIndex <= maxIndex() || !state.hasMore) {
                return Promise.resolve();
            }

            return loadPage(state.currentPage + 1).then(function () {
                return ensureReachableIndex(targetIndex);
            });
        }

        function updateDots() {
            if (!dotsWrap || cfg.dots !== true || cfg.displayType !== 'slider') {
                return;
            }

            var total = Math.max(state.totalAvailable, countCards());
            var visible = Math.max(1, getVisibleItems());
            var pages = Math.max(1, Math.ceil(total / visible));
            var active = Math.min(pages - 1, Math.round(state.currentIndex / visible));
            debugLog('dots.rebuild', { total: total, pages: pages, active: active, visible: visible });

            dotsWrap.innerHTML = '';
            for (var i = 0; i < pages; i += 1) {
                var dot = document.createElement('button');
                dot.type = 'button';
                if (i === active) {
                    dot.classList.add('is-active');
                }
                dot.dataset.page = String(i);
                dot.addEventListener('click', function (event) {
                    var pageIndex = parseInt(event.currentTarget.dataset.page || '0', 10);
                    var targetIndex = pageIndex * visible;
                    debugLog('dots.click', { pageIndex: pageIndex });
                    ensureReachableIndex(targetIndex).then(function () {
                        state.currentIndex = Math.min(maxIndex(), targetIndex);
                        debugLog('dots.move', { pageIndex: pageIndex, targetIndex: state.currentIndex });
                        updateSliderPosition();
                    });
                });
                dotsWrap.appendChild(dot);
            }
        }

        function appendCards(html) {
            var before = countCards();
            parseHtml(html).forEach(function (node) {
                track.appendChild(node);
            });
            var after = countCards();
            debugLog('cards.append', { before: before, after: after, appended: after - before });
            updateDots();
        }

        function loadPage(page) {
            if (state.loading || !state.hasMore || !cfg.ajaxUrl || !cfg.identifier) {
                debugLog('load.skip', { page: page, reason: state.loading ? 'loading' : (!state.hasMore ? 'no_more' : 'missing_config') });
                return Promise.resolve();
            }

            if (state.cache[page]) {
                debugLog('load.cache.hit', { page: page });
                appendCards(state.cache[page].html);
                state.hasMore = !!state.cache[page].has_more;
                state.currentPage = page;
                return Promise.resolve();
            }

            state.loading = true;
            var url = cfg.ajaxUrl
                + '?identifier=' + encodeURIComponent(cfg.identifier)
                + '&page=' + encodeURIComponent(String(page))
                + '&page_size=' + encodeURIComponent(String(pageSize));
            debugLog('load.start', { page: page, url: url });

            return request(url)
                .then(function (json) {
                    if (!json || !json.success || !json.data) {
                        throw new Error('Invalid response');
                    }

                    state.cache[page] = json.data;
                    appendCards(json.data.html || '');
                    state.totalAvailable = parseInt(json.data.total || '0', 10) || state.totalAvailable;
                    state.hasMore = !!json.data.has_more;
                    state.currentPage = page;
                    debugLog('load.success', {
                        page: page,
                        total: json.data.total,
                        has_more: json.data.has_more,
                        html_len: (json.data.html || '').length
                    });
                })
                .catch(function (error) {
                    debugLog('load.error', { page: page, error: error ? String(error.message || error) : 'unknown' });
                })
                .finally(function () {
                    state.loading = false;
                    debugLog('load.done', { page: page });
                });
        }

        function goNext() {
            if (cfg.displayType !== 'slider') {
                return;
            }
            debugLog('next.click');

            var visible = Math.max(1, getVisibleItems());
            var starts = getPageStarts(visible);
            var currentStart = findCurrentStart(starts);
            var nextIndex = null;

            for (var i = 0; i < starts.length; i += 1) {
                if (starts[i] > currentStart) {
                    nextIndex = starts[i];
                    break;
                }
            }

            if (nextIndex === null) {
                if (state.hasMore) {
                    debugLog('next.needsLoad', { targetPage: state.currentPage + 1, currentStart: currentStart });
                    loadPage(state.currentPage + 1).then(function () {
                        var refreshedStarts = getPageStarts(visible);
                        var refreshedCurrent = findCurrentStart(refreshedStarts);
                        var refreshedNext = null;
                        for (var j = 0; j < refreshedStarts.length; j += 1) {
                            if (refreshedStarts[j] > refreshedCurrent) {
                                refreshedNext = refreshedStarts[j];
                                break;
                            }
                        }
                        state.currentIndex = refreshedNext === null ? maxIndex() : refreshedNext;
                        debugLog('next.afterLoad', { nextIndex: state.currentIndex });
                        updateSliderPosition();
                    });
                    return;
                }

                if (cfg.infinite) {
                    state.currentIndex = 0;
                } else {
                    state.currentIndex = maxIndex();
                }
                debugLog('next.edge', { nextIndex: state.currentIndex, infinite: !!cfg.infinite });
                updateSliderPosition();
                return;
            }

            state.currentIndex = nextIndex;
            debugLog('next.move', { nextIndex: nextIndex });
            updateSliderPosition();
        }

        function goPrev() {
            if (cfg.displayType !== 'slider') {
                return;
            }
            debugLog('prev.click');

            var visible = Math.max(1, getVisibleItems());
            var starts = getPageStarts(visible);
            var currentStart = findCurrentStart(starts);
            var prevIndex = null;

            for (var i = starts.length - 1; i >= 0; i -= 1) {
                if (starts[i] < currentStart) {
                    prevIndex = starts[i];
                    break;
                }
            }

            if (prevIndex === null) {
                state.currentIndex = cfg.infinite ? starts[starts.length - 1] : 0;
            } else {
                state.currentIndex = prevIndex;
            }
            debugLog('prev.move', { prevIndex: state.currentIndex });
            updateSliderPosition();
        }

        function startAutoplay() {
            stopAutoplay();
            if (!cfg.autoplay || cfg.displayType !== 'slider') {
                return;
            }

            state.autoplayTimer = window.setInterval(goNext, Math.max(1200, cfg.autoplayDelay || 3000));
            debugLog('autoplay.start', { delay: Math.max(1200, cfg.autoplayDelay || 3000) });
        }

        function stopAutoplay() {
            if (state.autoplayTimer) {
                window.clearInterval(state.autoplayTimer);
                state.autoplayTimer = null;
                debugLog('autoplay.stop');
            }
        }

        function bindDrag() {
            if (cfg.displayType !== 'slider') {
                return;
            }
            if (!cfg.swipe && !cfg.mouseDrag && !cfg.touchSupport) {
                return;
            }

            function onDown(clientX) {
                state.dragging = true;
                state.startX = clientX;
                state.dragX = clientX;
                stopAutoplay();
                debugLog('drag.start', { x: clientX });
            }

            function onMove(clientX) {
                if (!state.dragging) {
                    return;
                }
                state.dragX = clientX;
            }

            function onUp() {
                if (!state.dragging) {
                    return;
                }

                var delta = state.dragX - state.startX;
                state.dragging = false;
                debugLog('drag.end', { delta: delta });
                if (Math.abs(delta) > 40) {
                    if (delta < 0) {
                        goNext();
                    } else {
                        goPrev();
                    }
                }
                startAutoplay();
            }

            if (cfg.touchSupport || cfg.swipe) {
                viewport.addEventListener('touchstart', function (e) { onDown(e.touches[0].clientX); }, { passive: true });
                viewport.addEventListener('touchmove', function (e) { onMove(e.touches[0].clientX); }, { passive: true });
                viewport.addEventListener('touchend', onUp);
            }

            if (cfg.mouseDrag) {
                viewport.addEventListener('mousedown', function (e) { onDown(e.clientX); });
                window.addEventListener('mousemove', function (e) { onMove(e.clientX); });
                window.addEventListener('mouseup', onUp);
            }
        }

        function bindKeyboard() {
            root.addEventListener('keydown', function (e) {
                if (cfg.displayType !== 'slider') {
                    return;
                }
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    goNext();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    goPrev();
                }
            });
        }

        function bindPostButtons() {
            root.addEventListener('click', function (event) {
                var button = event.target.closest('button[data-post]');
                if (!button) {
                    return;
                }
                submitPostData(button.getAttribute('data-post') || '');
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', goPrev);
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', goNext);
        }

        root.addEventListener('mouseenter', stopAutoplay);
        root.addEventListener('mouseleave', startAutoplay);
        window.addEventListener('resize', function () {
            debugLog('window.resize');
            updateSliderPosition();
        });

        bindDebugControls();
        bindDrag();
        bindKeyboard();
        bindPostButtons();
        updateDots();
        updateSliderPosition();
        startAutoplay();
    };
});
