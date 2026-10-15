const checkingText = document.querySelector('template[data-checking-text]')?.content.textContent.trim();

// Tab "Cek link" / "Cek file APK".
document.querySelectorAll('[data-tabs]').forEach((tablist) => {
    const tabs = [...tablist.querySelectorAll('[role="tab"]')];

    const select = (tab) => {
        tabs.forEach((other) => {
            const selected = other === tab;
            other.setAttribute('aria-selected', String(selected));
            other.tabIndex = selected ? 0 : -1;
            document.getElementById(other.getAttribute('aria-controls')).toggleAttribute('data-hidden', !selected);
        });
    };

    tabs.forEach((tab, index) => {
        tab.tabIndex = tab.getAttribute('aria-selected') === 'true' ? 0 : -1;
        tab.addEventListener('click', () => select(tab));
        tab.addEventListener('keydown', (event) => {
            const step = { ArrowRight: 1, ArrowLeft: -1 }[event.key];
            if (!step) return;
            const next = tabs[(index + step + tabs.length) % tabs.length];
            select(next);
            next.focus();
        });
    });
});

// Area unggah APK: seret-lepas, nama file, dan cek ukuran sebelum dikirim.
// File sampai batas server diunggah utuh. File lebih besar dibaca di browser,
// lalu yang dikirim hanya AndroidManifest.xml dan SHA-256 (lihat apk-manifest.js).
document.querySelectorAll('[data-apk-form]').forEach((form) => {
    const input = form.querySelector('input[type="file"]');
    const zone = form.querySelector('[data-dropzone]');
    const nameEl = form.querySelector('[data-file-name]');
    const errorEl = form.querySelector('[data-apk-error]');
    const button = form.querySelector('button[type="submit"]');
    const label = button.querySelector('[data-label]');
    const manifestForm = document.querySelector('[data-apk-manifest-form]');
    const serverMax = Number(form.dataset.serverMaxBytes);
    const browserMax = Number(form.dataset.browserMaxBytes);
    const originalLabel = label.textContent;

    const showError = (message) => {
        errorEl.textContent = message;
        errorEl.classList.toggle('hidden', !message);
    };

    const setBusy = (busy) => {
        button.disabled = busy;
        button.toggleAttribute('aria-busy', busy);
        label.textContent = busy && checkingText ? checkingText : originalLabel;
    };

    const update = () => {
        const file = input.files[0];
        nameEl.classList.toggle('hidden', !file);
        nameEl.textContent = file ? `${button.dataset.chosen} ${file.name}` : '';
        showError(file && file.size > browserMax ? button.dataset.tooLarge : '');
    };

    const submitManifest = async (file) => {
        const { canReadInBrowser, readApk } = await import('./apk-manifest.js');

        if (!canReadInBrowser()) {
            showError(form.dataset.unsupported);
            return false;
        }

        try {
            const { manifest, sha256 } = await readApk(file);
            const transfer = new DataTransfer();
            transfer.items.add(new File([manifest], 'AndroidManifest.xml', { type: 'application/octet-stream' }));
            manifestForm.querySelector('[name="manifest"]').files = transfer.files;
            manifestForm.querySelector('[name="sha256"]').value = sha256;
            manifestForm.querySelector('[name="file_name"]').value = file.name;
            manifestForm.querySelector('[name="file_size"]').value = String(file.size);
            manifestForm.submit();
            return true;
        } catch {
            showError(form.dataset.unreadable);
            return false;
        }
    };

    input.addEventListener('change', update);

    ['dragenter', 'dragover'].forEach((type) =>
        zone.addEventListener(type, (event) => {
            event.preventDefault();
            zone.classList.add('is-dragging');
        }),
    );

    ['dragleave', 'drop'].forEach((type) =>
        zone.addEventListener(type, () => zone.classList.remove('is-dragging')),
    );

    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        if (event.dataTransfer.files.length) {
            input.files = event.dataTransfer.files;
            update();
        }
    });

    form.addEventListener('submit', async (event) => {
        const file = input.files[0];

        if (!file) return;

        if (file.size > browserMax) {
            event.preventDefault();
            showError(button.dataset.tooLarge);
            return;
        }

        setBusy(true);

        if (file.size <= serverMax) return;

        event.preventDefault();

        if (!(await submitManifest(file))) setBusy(false);
    });
});

// Tombol kirim berubah jadi "Sedang memeriksa…" dan tidak bisa ditekan dua kali.
document.querySelectorAll('[data-checking-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        if (checkingText) button.querySelector('[data-label]').textContent = checkingText;
    });
});

// Bagikan hasil: pakai menu bagikan bawaan HP, atau salin tautan.
document.querySelectorAll('[data-share]').forEach((button) => {
    button.addEventListener('click', async () => {
        const data = { title: 'SayNO', text: button.dataset.shareText, url: button.dataset.shareUrl };

        if (navigator.share) {
            try {
                await navigator.share(data);
            } catch {
                // Pengguna membatalkan.
            }
            return;
        }

        try {
            await navigator.clipboard.writeText(`${data.text} ${data.url}`);
            const label = button.querySelector('[data-label]');
            const original = label.textContent;
            label.textContent = button.dataset.copied;
            setTimeout(() => (label.textContent = original), 2000);
        } catch {
            // Clipboard tidak tersedia; alamat halaman tetap bisa disalin dari browser.
        }
    });
});
