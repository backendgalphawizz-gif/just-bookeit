<script defer>
(function () {
    const DB_NAME = 'jb-admin-form-files';
    const STORE = 'pending';
    const hasValidationErrors = @json($errors->any());
    let restored = false;

    function storageKey() {
        return window.location.pathname;
    }

    function openDb() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, 1);

            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE);
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    function idbGet(key) {
        return openDb().then((db) => new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readonly');
            const req = tx.objectStore(STORE).get(key);
            req.onsuccess = () => resolve(req.result ?? null);
            req.onerror = () => reject(req.error);
        }));
    }

    function idbSet(key, value) {
        return openDb().then((db) => new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).put(value, key);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        }));
    }

    function idbDelete(key) {
        return openDb().then((db) => new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).delete(key);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        }));
    }

    function formHasFiles(form) {
        return Array.from(form.querySelectorAll('input[type="file"]')).some((input) => input.files && input.files.length > 0);
    }

    async function captureFormFiles(form) {
        const fields = {};

        for (const input of form.querySelectorAll('input[type="file"]')) {
            if (!input.name || !input.files || input.files.length === 0) {
                continue;
            }

            fields[input.name] = await Promise.all(
                Array.from(input.files).map(async (file) => ({
                    name: file.name,
                    type: file.type,
                    lastModified: file.lastModified,
                    blob: file,
                }))
            );
        }

        if (Object.keys(fields).length === 0) {
            return;
        }

        await idbSet(storageKey(), {
            savedAt: Date.now(),
            fields,
        });
    }

    function findFileInput(name) {
        return Array.from(document.querySelectorAll('input[type="file"]')).find((input) => input.name === name) ?? null;
    }

    async function restoreFormFiles() {
        if (restored) {
            return;
        }

        restored = true;

        const stored = await idbGet(storageKey());
        if (!stored || !stored.fields) {
            return;
        }

        for (const [name, entries] of Object.entries(stored.fields)) {
            const input = findFileInput(name);
            if (!input || (input.files && input.files.length > 0)) {
                continue;
            }

            const transfer = new DataTransfer();

            entries.forEach((entry) => {
                const file = entry.blob instanceof File
                    ? entry.blob
                    : new File([entry.blob], entry.name, {
                        type: entry.type || 'application/octet-stream',
                        lastModified: entry.lastModified || Date.now(),
                    });

                transfer.items.add(file);
            });

            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function bindForms() {
        document.querySelectorAll('form').forEach((form) => {
            if (form.dataset.jbFilePersistenceBound === '1') {
                return;
            }

            form.dataset.jbFilePersistenceBound = '1';

            form.addEventListener('submit', function (event) {
                if (!formHasFiles(form)) {
                    return;
                }

                if (form.dataset.jbFilePersisting === '1') {
                    return;
                }

                event.preventDefault();
                form.dataset.jbFilePersisting = '1';

                captureFormFiles(form)
                    .catch(() => {})
                    .finally(() => {
                        delete form.dataset.jbFilePersisting;
                        HTMLFormElement.prototype.submit.call(form);
                    });
            });
        });
    }

    function afterAlpineReady(callback) {
        if (window.Alpine) {
            callback();
            return;
        }

        document.addEventListener('alpine:initialized', callback, { once: true });
    }

    function initRestoreOrClear() {
        if (hasValidationErrors) {
            restoreFormFiles().catch(() => {});
            return;
        }

        idbDelete(storageKey()).catch(() => {});
    }

    bindForms();
    afterAlpineReady(initRestoreOrClear);
})();
</script>
