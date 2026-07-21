(function () {
    const POLL_MS = 2000;

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
        const name = escapeHtml(message.attachment_name || 'Attachment');

        if (message.attachment_type === 'video') {
            return `<video src="${url}" class="${className}" controls playsinline preload="metadata"></video>`;
        }

        if (message.attachment_type === 'image') {
            const lightbox = theme === 'vendor' ? ' panel-lightbox-trigger' : '';
            return `<img src="${url}" alt="Attachment" class="${className}${lightbox}">`;
        }

        return `
            <a href="${url}" target="_blank" rel="noopener" download class="${className} ${className}--file vp-chat-file">
                <span class="vp-chat-file-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </span>
                <span class="vp-chat-file-meta">
                    <span class="vp-chat-file-name">${name}</span>
                    <span class="vp-chat-file-action">Download</span>
                </span>
            </a>
        `;
    }

    function renderMessageActions(message, theme) {
        // Edit / Delete temporarily disabled in the UI.
        return '';

        /*
        if (!message.is_mine) {
            return '';
        }

        const editBtn = message.can_edit
            ? `<button type="button" class="${theme === 'vendor' ? 'vp-chat-action' : 'jbw-chat-action'}" data-chat-edit>Edit</button>`
            : '';
        const dangerClass = theme === 'vendor' ? 'vp-chat-action vp-chat-action--danger' : 'jbw-chat-action jbw-chat-action--danger';

        return `
            <div class="${theme === 'vendor' ? 'vp-chat-message-actions' : 'jbw-chat-message-actions'}">
                ${editBtn}
                <button type="button" class="${dangerClass}" data-chat-delete>Delete</button>
            </div>
        `;
        */
    }

    function renderMessageMeta(message, theme) {
        const edited = message.is_edited
            ? `<span class="${theme === 'vendor' ? 'vp-chat-edited' : 'jbw-chat-edited'}">· Edited</span>`
            : '';
        const timeClass = theme === 'vendor' ? 'vp-chat-time' : 'jbw-chat-time';
        const metaClass = theme === 'vendor' ? 'vp-chat-meta' : 'jbw-chat-meta';
        const timeTag = theme === 'vendor' ? 'span' : 'p';

        return `
            <div class="${metaClass}">
                <${timeTag} class="${timeClass}">${escapeHtml(message.sent_at || '')}${edited}</${timeTag}>
                ${renderMessageActions(message, theme)}
            </div>
        `;
    }

    function messageUrlAttrs(message) {
        const update = message.update_url ? ` data-update-url="${escapeHtml(message.update_url)}"` : '';
        const destroy = message.delete_url ? ` data-delete-url="${escapeHtml(message.delete_url)}"` : '';
        return `${update}${destroy}`;
    }

    function renderVendorMessage(message) {
        const isMine = !!message.is_mine;
        const rowClass = isMine ? 'vp-chat-row vp-chat-row--mine' : 'vp-chat-row vp-chat-row--theirs';
        const bubbleClass = isMine ? 'vp-chat-bubble vp-chat-bubble--mine' : 'vp-chat-bubble vp-chat-bubble--theirs';
        const body = message.body ? `<p data-chat-body>${escapeHtml(message.body)}</p>` : '';

        return `
            <div class="${rowClass}" data-message-id="${message.id}"${messageUrlAttrs(message)}>
                <div class="${bubbleClass}" data-chat-bubble>
                    ${body}
                    ${renderAttachment(message, 'vendor')}
                </div>
                ${renderMessageMeta(message, 'vendor')}
            </div>
        `;
    }

    function renderCustomerMessage(message) {
        const wrapperClass = message.is_mine
            ? 'jbw-chat-message-wrapper jbw-chat-message-wrapper--mine'
            : 'jbw-chat-message-wrapper jbw-chat-message-wrapper--theirs';
        const bubbleClass = message.is_mine ? 'jbw-chat-bubble jbw-chat-bubble--mine' : 'jbw-chat-bubble jbw-chat-bubble--theirs';
        const body = message.body ? `<p data-chat-body>${escapeHtml(message.body)}</p>` : '';

        return `
            <div class="${wrapperClass}" data-message-id="${message.id}"${messageUrlAttrs(message)}>
                <div class="${bubbleClass}" data-chat-bubble>
                    ${body}
                    ${renderAttachment(message, 'customer')}
                </div>
                ${renderMessageMeta(message, 'customer')}
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

            const track = getMessageTrack(container);
            const lastMessage = track?.querySelector('[data-message-id]:last-of-type');

            if (lastMessage && typeof lastMessage.scrollIntoView === 'function') {
                lastMessage.scrollIntoView({ block: 'end', inline: 'nearest', behavior: 'auto' });
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }
        };

        scroll();
        requestAnimationFrame(() => {
            scroll();
            requestAnimationFrame(scroll);
        });

        window.setTimeout(scroll, 120);

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

    function updateThreads(container, threads, theme, activeChatId, options = {}) {
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
        threadsBox.scrollTop = options.scrollToTop ? 0 : previousScrollTop;
    }

    function promoteThread(container, thread, theme) {
        const threadsBox = container.querySelector('[data-chat-threads]');
        const chatId = thread?.id ?? container.dataset.chatId;

        if (!threadsBox || !chatId) {
            return;
        }

        let node = threadsBox.querySelector(`[data-thread-id="${chatId}"]`);

        if (!node && thread) {
            const html = theme === 'vendor'
                ? renderVendorThread(thread, chatId)
                : renderCustomerThread(thread, chatId);
            threadsBox.insertAdjacentHTML('afterbegin', html);
            threadsBox.scrollTop = 0;
            return;
        }

        if (!node) {
            node = threadsBox.querySelector('.vp-chat-thread.is-active, .jbw-chat-thread.is-active');
        }

        if (!node) {
            return;
        }

        if (thread) {
            const preview = node.querySelector('.vp-chat-thread-body p, .jbw-chat-thread-body p');
            const time = node.querySelector('.vp-chat-thread-top span');
            const name = node.querySelector('.vp-chat-thread-top strong');

            if (preview && thread.preview != null) {
                preview.textContent = thread.preview;
            }
            if (time && thread.time != null) {
                time.textContent = thread.time;
            }
            if (name && thread.name) {
                name.textContent = thread.name;
            }
        }

        threadsBox.prepend(node);
        threadsBox.scrollTop = 0;
    }

    async function poll(container, options = {}) {
        if (container.dataset.chatPollingInFlight === '1') {
            return;
        }

        const pollUrl = resolveUrl(container.dataset.pollUrl);
        const chatId = container.dataset.chatId;
        const theme = container.dataset.chatTheme || 'vendor';
        const search = container.dataset.chatSearch || '';

        if (!pollUrl) {
            return;
        }

        container.dataset.chatPollingInFlight = '1';

        try {
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

            updateThreads(container, data.threads || [], theme, chatId, options);
        } finally {
            container.dataset.chatPollingInFlight = '0';
        }
    }

    function formatFileSize(bytes) {
        const size = Number(bytes) || 0;
        if (size < 1024) {
            return `${size} B`;
        }
        if (size < 1024 * 1024) {
            return `${(size / 1024).toFixed(1)} KB`;
        }
        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    function clearAttachPreview(form) {
        const preview = form.closest('.vp-chat-compose-stack, .jbw-chat-compose-stack')
            ?.querySelector('[data-chat-attach-preview]')
            || form.parentElement?.querySelector('[data-chat-attach-preview]');
        const body = preview?.querySelector('[data-chat-attach-preview-body]');
        const fileField = form.querySelector('input[type="file"][name="attachment"]');

        if (preview?.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
            delete preview.dataset.objectUrl;
        }

        if (body) {
            body.innerHTML = '';
        }

        if (preview) {
            preview.hidden = true;
        }

        if (fileField) {
            fileField.value = '';
        }
    }

    function renderAttachPreview(form, file) {
        const preview = form.closest('.vp-chat-compose-stack, .jbw-chat-compose-stack')
            ?.querySelector('[data-chat-attach-preview]')
            || form.parentElement?.querySelector('[data-chat-attach-preview]');
        const body = preview?.querySelector('[data-chat-attach-preview-body]');

        if (!preview || !body || !file) {
            return;
        }

        if (preview.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
            delete preview.dataset.objectUrl;
        }

        const name = escapeHtml(file.name || 'Attachment');
        const size = escapeHtml(formatFileSize(file.size));
        const type = file.type || '';
        let mediaHtml = `
            <span class="vp-chat-attach-preview-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </span>
        `;

        if (type.startsWith('image/')) {
            const objectUrl = URL.createObjectURL(file);
            preview.dataset.objectUrl = objectUrl;
            mediaHtml = `<img src="${escapeHtml(objectUrl)}" alt="" class="vp-chat-attach-preview-thumb">`;
        } else if (type.startsWith('video/')) {
            const objectUrl = URL.createObjectURL(file);
            preview.dataset.objectUrl = objectUrl;
            mediaHtml = `<video src="${escapeHtml(objectUrl)}" class="vp-chat-attach-preview-thumb vp-chat-attach-preview-thumb--video" muted playsinline></video>`;
        }

        body.innerHTML = `
            ${mediaHtml}
            <div class="vp-chat-attach-preview-meta">
                <span class="vp-chat-attach-preview-name">${name}</span>
                <span class="vp-chat-attach-preview-size">${size}</span>
            </div>
        `;
        preview.hidden = false;

        const stack = form.closest('.vp-chat-compose-stack, .jbw-chat-compose-stack');
        stack?.scrollIntoView({ block: 'nearest' });
        form.querySelector('[data-chat-input]')?.focus();
    }

    function bindAttachPreview(form) {
        if (!form || form.dataset.chatAttachBound === '1') {
            return;
        }

        const fileField = form.querySelector('input[type="file"][name="attachment"]');
        const clearButton = form.closest('.vp-chat-compose-stack, .jbw-chat-compose-stack')
            ?.querySelector('[data-chat-attach-clear]')
            || form.parentElement?.querySelector('[data-chat-attach-clear]');

        if (!fileField) {
            return;
        }

        form.dataset.chatAttachBound = '1';

        fileField.addEventListener('change', () => {
            const file = fileField.files && fileField.files[0];
            if (!file) {
                clearAttachPreview(form);
                return;
            }
            renderAttachPreview(form, file);
        });

        clearButton?.addEventListener('click', (event) => {
            event.preventDefault();
            clearAttachPreview(form);
        });
    }

    async function sendMessage(form, container) {
        if (form.dataset.chatSending === '1') {
            return;
        }

        if (container.dataset.editingMessageId) {
            await saveComposeEdit(container, form);
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
                let message = 'Could not send message.';
                try {
                    const errorData = await response.json();
                    message = errorData.message
                        || Object.values(errorData.errors || {}).flat()[0]
                        || message;
                } catch (parseError) {
                    // ignore
                }
                window.alert(message);
                return;
            }

            const data = await parseJsonResponse(response);

            if (data.message) {
                appendMessage(container, data.message, theme, true);
            }

            if (data.thread) {
                promoteThread(container, data.thread, theme);
            } else {
                promoteThread(container, {
                    id: container.dataset.chatId,
                    preview: bodyField?.value?.trim() || (hasFile ? 'Attachment' : ''),
                    time: new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }),
                }, theme);
            }

            if (bodyField) {
                bodyField.value = '';
            }
            clearAttachPreview(form);

            await poll(container, { scrollToTop: true });
        } catch (error) {
            window.alert('Could not send message. Please try again.');
        } finally {
            form.dataset.chatSending = '0';
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    function bindMobileAside(container) {
        const sidebar = container.querySelector('[data-chat-aside]');

        if (!sidebar || container.dataset.chatAsideBound === '1') {
            return;
        }

        container.dataset.chatAsideBound = '1';

        const openAside = () => {
            container.classList.add('vp-chat-layout--aside-open');
            sidebar.classList.add('vp-chat-sidebar--mobile-open');
        };

        const closeAside = () => {
            container.classList.remove('vp-chat-layout--aside-open');
            sidebar.classList.remove('vp-chat-sidebar--mobile-open');
        };

        container.querySelectorAll('[data-chat-aside-open]').forEach((button) => {
            button.addEventListener('click', openAside);
        });

        container.querySelectorAll('[data-chat-aside-close]').forEach((button) => {
            button.addEventListener('click', closeAside);
        });

        container.addEventListener('click', (event) => {
            if (event.target.closest('[data-chat-aside-thread]')) {
                closeAside();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAside();
            }
        });
    }

    function syncChatViewportHeight() {
        const viewport = window.visualViewport;
        const height = Math.round(viewport?.height || window.innerHeight || 0);

        if (!height) {
            return;
        }

        document.documentElement.style.setProperty('--jbw-app-height', `${height}px`);

        if (document.body.classList.contains('jbw-body--chat')) {
            const offsetTop = Math.max(0, Math.round(viewport?.offsetTop || 0));
            document.body.style.height = `${height}px`;
            document.body.style.top = `${offsetTop}px`;
        }
    }

    function bindChatViewport(container) {
        if (document.documentElement.dataset.jbwChatViewportBound === '1') {
            syncChatViewportHeight();
            return;
        }

        document.documentElement.dataset.jbwChatViewportBound = '1';

        const onViewportChange = () => {
            syncChatViewportHeight();
            document.querySelectorAll('[data-chat-live]').forEach((node) => {
                scrollMessages(node, node.dataset.chatPinnedBottom !== '0');
            });
        };

        syncChatViewportHeight();

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', onViewportChange);
            window.visualViewport.addEventListener('scroll', onViewportChange);
        }

        window.addEventListener('resize', onViewportChange);
        window.addEventListener('orientationchange', () => {
            window.setTimeout(onViewportChange, 150);
        });

        document.addEventListener('focusin', (event) => {
            if (!event.target.closest('[data-chat-input], [data-chat-compose]')) {
                return;
            }

            window.setTimeout(onViewportChange, 50);
            window.setTimeout(onViewportChange, 300);
        });

        document.addEventListener('focusout', (event) => {
            if (!event.target.closest('[data-chat-input], [data-chat-compose]')) {
                return;
            }

            window.setTimeout(onViewportChange, 50);
            window.setTimeout(onViewportChange, 300);
        });
    }

    function getComposeStack(container) {
        return container.querySelector('.jbw-chat-compose-stack, .vp-chat-compose-stack');
    }

    function clearComposeEdit(container) {
        const form = container.querySelector('[data-chat-compose]');
        const banner = container.querySelector('[data-chat-edit-banner]');
        const preview = banner?.querySelector('[data-chat-edit-preview]');
        const input = form?.querySelector('[data-chat-input]');
        const attach = form?.querySelector('.jbw-chat-attach, .vp-chat-attach');

        container.querySelectorAll('[data-message-id].is-editing').forEach((row) => {
            row.classList.remove('is-editing');
        });

        delete container.dataset.editingMessageId;
        delete container.dataset.editingUpdateUrl;

        if (banner) {
            banner.hidden = true;
        }
        if (preview) {
            preview.textContent = '';
        }
        if (input) {
            input.placeholder = 'Type a message...';
            if (!input.value.trim()) {
                input.value = '';
            }
        }
        if (attach) {
            attach.hidden = false;
        }
        if (form) {
            form.classList.remove('is-editing-message');
        }
    }

    function startMessageEdit(row) {
        const container = row.closest('[data-chat-live]');
        const bodyNode = row.querySelector('[data-chat-body]');
        const updateUrl = row.dataset.updateUrl;
        const form = container?.querySelector('[data-chat-compose]');
        const input = form?.querySelector('[data-chat-input]');
        const banner = container?.querySelector('[data-chat-edit-banner]');
        const preview = banner?.querySelector('[data-chat-edit-preview]');
        const attach = form?.querySelector('.jbw-chat-attach, .vp-chat-attach');

        if (!container || !bodyNode || !updateUrl || !form || !input) {
            return;
        }

        const text = (bodyNode.textContent || '').trim();
        if (!text) {
            return;
        }

        clearComposeEdit(container);

        container.dataset.editingMessageId = String(row.dataset.messageId || '');
        container.dataset.editingUpdateUrl = updateUrl;
        row.classList.add('is-editing');
        form.classList.add('is-editing-message');

        if (attach) {
            attach.hidden = true;
        }
        clearAttachPreview(form);

        input.value = text;
        input.placeholder = 'Edit message...';
        if (banner) {
            banner.hidden = false;
        }
        if (preview) {
            preview.textContent = text.length > 80 ? `${text.slice(0, 80)}…` : text;
        }

        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);

        if (typeof input.scrollIntoView === 'function') {
            getComposeStack(container)?.scrollIntoView({ block: 'nearest' });
        }
    }

    async function saveComposeEdit(container, form) {
        const updateUrl = container.dataset.editingUpdateUrl;
        const messageId = container.dataset.editingMessageId;
        const input = form.querySelector('[data-chat-input]');
        const body = (input?.value || '').trim();
        const theme = container.dataset.chatTheme || 'customer';

        if (!updateUrl || !messageId || !body) {
            return;
        }

        if (form.dataset.chatSending === '1') {
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        form.dataset.chatSending = '1';
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const payload = new FormData();
            payload.append('_token', csrfToken());
            payload.append('_method', 'PATCH');
            payload.append('body', body);

            const response = await fetch(resolveUrl(updateUrl), {
                method: 'POST',
                body: payload,
                headers: jsonHeaders(),
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                let message = 'Could not update message.';
                try {
                    const errorData = await response.json();
                    message = errorData.message
                        || Object.values(errorData.errors || {}).flat()[0]
                        || message;
                } catch (e) {
                    // ignore
                }
                window.alert(message);
                return;
            }

            const data = await parseJsonResponse(response);
            const row = container.querySelector(`[data-message-id="${messageId}"]`);

            if (row && data.message) {
                applyMessageUpdate(row, data.message, theme);
            }

            if (data.thread) {
                promoteThread(container, data.thread, theme);
            }

            if (input) {
                input.value = '';
            }
            clearComposeEdit(container);
            scrollMessages(container, true);
        } catch (error) {
            window.alert('Could not update message. Please try again.');
        } finally {
            form.dataset.chatSending = '0';
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    function applyMessageUpdate(row, message, theme) {
        const html = theme === 'vendor' ? renderVendorMessage(message) : renderCustomerMessage(message);
        row.outerHTML = html;
    }

    async function deleteMessage(row) {
        const deleteUrl = row.dataset.deleteUrl;
        if (!deleteUrl) {
            return;
        }

        if (!window.confirm('Delete this message?')) {
            return;
        }

        const container = row.closest('[data-chat-live]');
        if (container && String(container.dataset.editingMessageId || '') === String(row.dataset.messageId || '')) {
            clearComposeEdit(container);
            const input = container.querySelector('[data-chat-input]');
            if (input) {
                input.value = '';
            }
        }

        const payload = new FormData();
        payload.append('_token', csrfToken());
        payload.append('_method', 'DELETE');

        const response = await fetch(resolveUrl(deleteUrl), {
            method: 'POST',
            body: payload,
            headers: jsonHeaders(),
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            let message = 'Could not delete message.';
            try {
                const errorData = await response.json();
                message = errorData.message || message;
            } catch (e) {
                // ignore
            }
            window.alert(message);
            return;
        }

        const data = await parseJsonResponse(response);
        const theme = container?.dataset.chatTheme || 'customer';
        row.remove();

        if (container && data.thread) {
            promoteThread(container, data.thread, theme);
        }

        const track = container ? getMessageTrack(container) : null;
        if (track && !track.querySelector('[data-message-id]')) {
            const emptyClass = theme === 'vendor' ? 'vp-chat-empty-thread' : 'jbw-chat-empty-thread';
            const emptyText = theme === 'vendor'
                ? 'No messages yet. Say hello to your customer.'
                : 'Say hello to start the conversation.';
            track.innerHTML = `<p class="${emptyClass}">${emptyText}</p>`;
        }
    }

    function bindMessageActions(container) {
        if (container.dataset.chatActionsBound === '1') {
            return;
        }

        container.dataset.chatActionsBound = '1';

        container.addEventListener('click', (event) => {
            if (event.target.closest('[data-chat-edit-cancel]')) {
                event.preventDefault();
                const form = container.querySelector('[data-chat-compose]');
                const input = form?.querySelector('[data-chat-input]');
                clearComposeEdit(container);
                if (input) {
                    input.value = '';
                }
                return;
            }

            const row = event.target.closest('[data-message-id]');
            if (!row || !container.contains(row)) {
                return;
            }

            if (event.target.closest('[data-chat-edit]')) {
                event.preventDefault();
                startMessageEdit(row);
                return;
            }

            if (event.target.closest('[data-chat-delete]')) {
                event.preventDefault();
                deleteMessage(row).catch(() => {});
            }
        });
    }

    function bindContainer(container) {
        const form = container.querySelector('[data-chat-compose]');

        if (form && form.dataset.chatLiveBound !== '1') {
            form.dataset.chatLiveBound = '1';
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                sendMessage(form, container);
            });
            bindAttachPreview(form);
        }

        bindMessageScroll(container);
        bindMobileAside(container);
        bindMessageActions(container);
        bindChatViewport(container);
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
