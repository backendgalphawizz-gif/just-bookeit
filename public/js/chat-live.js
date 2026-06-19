(function () {
    const POLL_MS = 1000;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function jsonHeaders() {
        return {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        };
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resolveUrl(path) {
        if (!path) {
            return '';
        }

        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }

        return `${window.location.origin}${path.startsWith('/') ? path : `/${path}`}`;
    }

    function getMessagesBox(container) {
        return container.querySelector('[data-chat-messages]');
    }

    function getMessageTrack(container) {
        const box = getMessagesBox(container);

        if (!box) {
            return null;
        }

        return box.querySelector('[data-chat-messages-track]') || box;
    }

    async function parseJsonResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('invalid_response');
        }

        const json = await response.json();

        if (json && typeof json === 'object' && json.data !== undefined && json.messages === undefined && json.message === undefined) {
            return json.data;
        }

        return json;
    }

    function renderAttachment(message, theme) {
        if (!message.attachment_url) {
            return '';
        }

        const className = theme === 'vendor' ? 'vp-chat-attachment' : 'jbw-chat-attachment';
        const url = escapeHtml(message.attachment_url);

        if (message.attachment_type === 'video') {
            return `<video src="${url}" class="${className}" controls playsinline preload="metadata"></video>`;
        }

        if (message.attachment_type === 'image') {
            const lightbox = theme === 'vendor' ? ' panel-lightbox-trigger' : '';
            return `<img src="${url}" alt="Attachment" class="${className}${lightbox}">`;
        }

        return `<a href="${url}" target="_blank" rel="noopener" class="${className}">View attachment</a>`;
    }

    function renderVendorMessage(message) {
        const rowClass = message.is_mine ? 'vp-chat-row vp-chat-row--mine' : 'vp-chat-row';
        const bubbleClass = message.is_mine ? 'vp-chat-bubble vp-chat-bubble--mine' : 'vp-chat-bubble vp-chat-bubble--theirs';
        const body = message.body ? `<p>${escapeHtml(message.body)}</p>` : '';

        return `
            <div class="${rowClass}" data-message-id="${message.id}">
                <div class="${bubbleClass}">
                    ${body}
                    ${renderAttachment(message, 'vendor')}
                </div>
                <span class="vp-chat-time">${escapeHtml(message.sent_at)}</span>
            </div>
        `;
    }

    function renderCustomerMessage(message) {
        const wrapperClass = message.is_mine
            ? 'jbw-chat-message-wrapper jbw-chat-message-wrapper--mine'
            : 'jbw-chat-message-wrapper jbw-chat-message-wrapper--theirs';
        const bubbleClass = message.is_mine ? 'jbw-chat-bubble jbw-chat-bubble--mine' : 'jbw-chat-bubble jbw-chat-bubble--theirs';
        const body = message.body ? `<p>${escapeHtml(message.body)}</p>` : '';

        return `
            <div class="${wrapperClass}" data-message-id="${message.id}">
                <div class="${bubbleClass}">
                    ${body}
                    ${renderAttachment(message, 'customer')}
                </div>
                <p class="jbw-chat-time">${escapeHtml(message.sent_at)}</p>
            </div>
        `;
    }

    function renderVendorThread(thread, activeChatId) {
        const activeClass = Number(activeChatId) === Number(thread.id) ? ' is-active' : '';
        const avatar = thread.avatar_url
            ? `<img src="${escapeHtml(thread.avatar_url)}" alt="" class="vp-chat-avatar">`
            : `<span class="vp-chat-avatar vp-chat-avatar--fallback">${escapeHtml(thread.initial)}</span>`;

        return `
            <a href="${escapeHtml(thread.url)}" class="vp-chat-thread${activeClass}" data-thread-id="${thread.id}">
                ${avatar}
                <div class="vp-chat-thread-body">
                    <div class="vp-chat-thread-top">
                        <strong>${escapeHtml(thread.name)}</strong>
                        <span>${escapeHtml(thread.time || '')}</span>
                    </div>
                    <p>${escapeHtml(thread.preview)}</p>
                </div>
            </a>
        `;
    }

    function renderCustomerThread(thread, activeChatId) {
        const activeClass = Number(activeChatId) === Number(thread.id) ? ' is-active' : '';
        const avatar = thread.avatar_url
            ? `<img src="${escapeHtml(thread.avatar_url)}" alt="" class="jbw-chat-thread-avatar">`
            : `<span class="jbw-chat-thread-avatar jbw-chat-thread-avatar--fallback">${escapeHtml(thread.initial)}</span>`;

        return `
            <a href="${escapeHtml(thread.url)}" class="jbw-chat-thread${activeClass}" data-thread-id="${thread.id}">
                ${avatar}
                <div class="jbw-chat-thread-body">
                    <div class="vp-chat-thread-top">
                        <strong>${escapeHtml(thread.name)}</strong>
                        <span>${escapeHtml(thread.time || '')}</span>
                    </div>
                    <p>${escapeHtml(thread.preview)}</p>
                </div>
            </a>
        `;
    }

    function getLastMessageId(container) {
        const stored = Number(container.dataset.lastMessageId || 0);
        const track = getMessageTrack(container);

        if (!track) {
            return Number.isFinite(stored) ? stored : 0;
        }

        const ids = Array.from(track.querySelectorAll('[data-message-id]'))
            .map((node) => Number(node.dataset.messageId))
            .filter((id) => Number.isFinite(id));

        const maxDom = ids.length ? Math.max(...ids) : 0;

        return Math.max(stored, maxDom);
    }

    function setLastMessageId(container, messageId) {
        const current = getLastMessageId(container);
        const next = Math.max(current, Number(messageId) || 0);
        container.dataset.lastMessageId = String(next);
    }

    function isNearBottom(element, threshold = 140) {
        if (!element) {
            return true;
        }

        return element.scrollHeight - element.scrollTop - element.clientHeight <= threshold;
    }

    function scrollMessages(container, force = false) {
        const messagesBox = getMessagesBox(container);

        if (!messagesBox) {
            return;
        }

        if (!force && container.dataset.chatPinnedBottom === '0') {
            return;
        }

        const scroll = () => {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        };

        scroll();
        requestAnimationFrame(() => {
            scroll();
            requestAnimationFrame(scroll);
        });

        messagesBox.querySelectorAll('img, video').forEach((media) => {
            const handler = () => scroll();
            media.addEventListener('load', handler, { once: true });
            media.addEventListener('loadedmetadata', handler, { once: true });
        });

        container.dataset.chatPinnedBottom = '1';
    }

    function bindMessageScroll(container) {
        const messagesBox = getMessagesBox(container);

        if (!messagesBox || messagesBox.dataset.chatScrollBound === '1') {
            return;
        }

        messagesBox.dataset.chatScrollBound = '1';
        messagesBox.addEventListener('scroll', () => {
            container.dataset.chatPinnedBottom = isNearBottom(messagesBox) ? '1' : '0';
        }, { passive: true });
    }

    function appendMessage(container, message, theme, forceScroll = false) {
        const track = getMessageTrack(container);

        if (!track || track.querySelector(`[data-message-id="${message.id}"]`)) {
            return false;
        }

        const empty = track.querySelector('.vp-chat-empty-thread, .jbw-chat-empty-thread');
        if (empty) {
            empty.remove();
        }

        const html = theme === 'vendor' ? renderVendorMessage(message) : renderCustomerMessage(message);
        track.insertAdjacentHTML('beforeend', html);
        setLastMessageId(container, message.id);

        if (forceScroll || container.dataset.chatPinnedBottom !== '0') {
            scrollMessages(container, true);
        }

        return true;
    }

    function updateThreads(container, threads, theme, activeChatId) {
        const threadsBox = container.querySelector('[data-chat-threads]');
        if (!threadsBox || !Array.isArray(threads) || threads.length === 0) {
            return;
        }

        const previousScrollTop = threadsBox.scrollTop;

        const html = threads
            .map((thread) => (theme === 'vendor'
                ? renderVendorThread(thread, activeChatId)
                : renderCustomerThread(thread, activeChatId)))
            .join('');

        threadsBox.innerHTML = html;
        threadsBox.scrollTop = previousScrollTop;
    }

    async function poll(container) {
        const pollUrl = resolveUrl(container.dataset.pollUrl);
        const chatId = container.dataset.chatId;
        const theme = container.dataset.chatTheme || 'vendor';
        const search = container.dataset.chatSearch || '';

        if (!pollUrl) {
            return;
        }

        const params = new URLSearchParams();
        if (chatId) {
            params.set('chat_id', chatId);
            params.set('after_message_id', String(getLastMessageId(container)));
        }
        if (search) {
            params.set('search', search);
        }

        const response = await fetch(`${pollUrl}?${params.toString()}`, {
            headers: jsonHeaders(),
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            return;
        }

        const data = await parseJsonResponse(response);

        (data.messages || []).forEach((message) => {
            appendMessage(container, message, theme);
        });

        updateThreads(container, data.threads || [], theme, chatId);
    }

    async function sendMessage(form, container) {
        if (form.dataset.chatSending === '1') {
            return;
        }

        const theme = container.dataset.chatTheme || 'vendor';
        const bodyField = form.querySelector('[data-chat-input]');
        const fileField = form.querySelector('input[type="file"][name="attachment"]');
        const hasText = bodyField && bodyField.value.trim().length > 0;
        const hasFile = fileField && fileField.files && fileField.files.length > 0;

        if (!hasText && !hasFile) {
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        form.dataset.chatSending = '1';

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(resolveUrl(form.getAttribute('action')), {
                method: 'POST',
                body: new FormData(form),
                headers: jsonHeaders(),
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('send_failed');
            }

            const data = await parseJsonResponse(response);

            if (data.message) {
                appendMessage(container, data.message, theme, true);
            }

            if (bodyField) {
                bodyField.value = '';
            }
            if (fileField) {
                fileField.value = '';
            }

            await poll(container);
        } catch (error) {
            form.removeAttribute('data-chat-live-bound');
            form.submit();
        } finally {
            form.dataset.chatSending = '0';
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    function bindContainer(container) {
        const form = container.querySelector('[data-chat-compose]');

        if (form && form.dataset.chatLiveBound !== '1') {
            form.dataset.chatLiveBound = '1';
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                sendMessage(form, container);
            });
        }

        bindMessageScroll(container);
        container.dataset.chatPinnedBottom = '1';
        scrollMessages(container, true);

        if (container.dataset.chatLivePolling === '1') {
            return;
        }

        container.dataset.chatLivePolling = '1';

        poll(container).catch(() => {});

        window.setInterval(() => {
            if (document.hidden) {
                return;
            }

            poll(container).catch(() => {});
        }, POLL_MS);
    }

    function init() {
        document.querySelectorAll('[data-chat-live]').forEach(bindContainer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
