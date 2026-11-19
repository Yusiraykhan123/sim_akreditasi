document.addEventListener('DOMContentLoaded', () => {
    const topbar = document.querySelector('.topbar');
    const updateTopbarOnScroll = () => topbar?.classList.toggle('is-scrolled', window.scrollY > 24);
    updateTopbarOnScroll();
    window.addEventListener('scroll', updateTopbarOnScroll, { passive: true });

    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const passwordInput = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!(passwordInput instanceof HTMLInputElement)) return;

        toggle.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'text';
            passwordInput.type = isVisible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!isVisible));
            toggle.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    });

    const elementModal = document.querySelector('#element-modal');
    const elementForm = document.querySelector('#element-form');
    const uploadModal = document.querySelector('#upload-modal');
    const uploadForm = document.querySelector('#upload-form');
    const documentPreviewModal = document.querySelector('#document-preview-modal');
    const documentPreviewFrame = documentPreviewModal?.querySelector('iframe');
    const documentPreviewTitle = documentPreviewModal?.querySelector('#document-preview-title');
    const documentPreviewStatus = documentPreviewModal?.querySelector('.document-preview-status');
    let documentPreviewRequest = null;
    let documentPreviewObjectUrl = null;
    const unitPicker = uploadForm?.querySelector('#upload-unit-picker');
    const selectAllUnits = uploadForm?.querySelector('[data-unit-select-all]');
    const unitOptions = Array.from(uploadForm?.querySelectorAll('[data-unit-option]') ?? []);
    const unitSummary = uploadForm?.querySelector('[data-unit-summary]');
    const confirmationDialog = document.querySelector('#confirm-dialog');
    const validationDialog = document.querySelector('#validation-dialog');
    const successDialog = document.querySelector('#success-dialog');
    const confirmForm = confirmationDialog?.querySelector('form');
    const pendingTitle = confirmationDialog?.querySelector('[data-confirm-title]');
    const pendingMessage = confirmationDialog?.querySelector('[data-confirm-message]');
    const pendingConfirmButton = confirmationDialog?.querySelector('[data-confirm-submit]');
    let pendingAction = null;

    validationDialog?.showModal();
    successDialog?.showModal();
    if (successDialog) {
        window.setTimeout(() => {
            if (!successDialog.open) return;

            successDialog.classList.add('is-closing');
            window.setTimeout(() => successDialog.close(), 220);
        }, 2000);
    }
    validationDialog?.addEventListener('click', (event) => {
        if (event.target === validationDialog) validationDialog.close();
    });

    const showElementModal = (mode = 'create', button = null) => {
        if (!elementModal || !elementForm) return;

        const method = elementForm.querySelector('#element-method');
        const elementId = elementForm.querySelector('#element-id');
        const title = elementForm.querySelector('#element-modal-title');
        const submitLabel = elementForm.querySelector('#element-submit-label');

        elementForm.reset();
        elementId.value = '';

        if (mode === 'edit' && button) {
            const id = button.dataset.action.split('/').pop();
            elementForm.action = button.dataset.action;
            method.value = 'PUT';
            elementId.value = id;
            elementForm.querySelector('#modal-category-id').value = button.dataset.category;
            elementForm.querySelector('#modal-standard').value = button.dataset.standard;
            elementForm.querySelector('#modal-ep').value = button.dataset.ep;
            elementForm.querySelector('#modal-rdwos').value = button.dataset.rdwos ?? '';
            elementForm.querySelector('#modal-content').value = button.dataset.content;
            title.textContent = 'Ubah Elemen';
            submitLabel.textContent = 'Simpan Perubahan';
        } else {
            elementForm.action = elementForm.dataset.createAction;
            method.value = 'POST';
            elementForm.querySelector('#modal-category-id').value = elementForm.dataset.category ?? '';
            title.textContent = 'Tambah Elemen';
            submitLabel.textContent = 'Simpan Elemen';
        }

        elementModal.showModal();
        elementForm.querySelector(mode === 'edit' ? '#modal-standard' : '#modal-category-id').focus();
    };

    document.querySelectorAll('[data-open-element-modal]').forEach((button) => {
        button.addEventListener('click', () => showElementModal());
    });

    document.querySelectorAll('[data-edit-element]').forEach((button) => {
        button.addEventListener('click', () => showElementModal('edit', button));
    });

    document.querySelectorAll('[data-open-upload-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!uploadModal || !uploadForm) return;

            uploadForm.reset();
            updateUnitPicker();
            const elementId = button.dataset.elementId;
            uploadForm.action = uploadForm.dataset.uploadTemplate.replace('__ID__', encodeURIComponent(elementId));
            uploadForm.querySelector('#upload-element-id').value = elementId;
            uploadForm.querySelector('#upload-element-label').textContent = `Elemen ${button.dataset.elementLabel} · pilih unit dan lengkapi keterangan dokumen.`;
            uploadModal.showModal();
            unitPicker?.querySelector('summary')?.focus();
        });
    });

    document.querySelectorAll('[data-open-document-list]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.dialogId);
            if (dialog instanceof HTMLDialogElement) dialog.showModal();
        });
    });

    document.querySelectorAll('[data-close-document-list]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelectorAll('.document-detail-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    document.querySelectorAll('[data-open-document-preview]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!documentPreviewModal || !documentPreviewFrame || !documentPreviewTitle || !documentPreviewStatus) return;

            documentPreviewRequest?.abort();
            if (documentPreviewObjectUrl) URL.revokeObjectURL(documentPreviewObjectUrl);
            documentPreviewRequest = new AbortController();
            documentPreviewObjectUrl = null;
            documentPreviewTitle.textContent = button.dataset.previewTitle || 'Preview PDF';
            documentPreviewFrame.src = 'about:blank';
            documentPreviewStatus.textContent = 'Memuat pratinjau PDF...';
            documentPreviewModal.showModal();

            try {
                const response = await fetch(button.dataset.previewUrl, {
                    headers: { Accept: 'application/pdf' },
                    signal: documentPreviewRequest.signal,
                });

                if (!response.ok) throw new Error(`Preview PDF gagal dimuat (${response.status}).`);

                const pdf = new Blob([await response.blob()], { type: 'application/pdf' });
                documentPreviewObjectUrl = URL.createObjectURL(pdf);
                documentPreviewFrame.src = documentPreviewObjectUrl;
                documentPreviewStatus.textContent = '';
            } catch (error) {
                if (error.name === 'AbortError') return;

                documentPreviewStatus.textContent = 'Preview PDF gagal dimuat. Silakan coba lagi.';
                console.error(error);
            }
        });
    });

    document.querySelectorAll('[data-close-document-preview]').forEach((button) => {
        button.addEventListener('click', () => documentPreviewModal?.close());
    });

    documentPreviewModal?.addEventListener('click', (event) => {
        if (event.target === documentPreviewModal) documentPreviewModal.close();
    });

    documentPreviewModal?.addEventListener('close', () => {
        documentPreviewRequest?.abort();
        documentPreviewRequest = null;
        if (documentPreviewObjectUrl) URL.revokeObjectURL(documentPreviewObjectUrl);
        documentPreviewObjectUrl = null;
        if (documentPreviewFrame) documentPreviewFrame.src = 'about:blank';
        if (documentPreviewStatus) documentPreviewStatus.textContent = '';
    });

    const updateUnitPicker = () => {
        if (!selectAllUnits || !unitSummary) return;

        const selectedCount = unitOptions.filter((option) => option.checked).length;
        const allSelected = unitOptions.length > 0 && selectedCount === unitOptions.length;

        selectAllUnits.checked = allSelected;
        selectAllUnits.indeterminate = selectedCount > 0 && !allSelected;
        unitSummary.textContent = selectedCount === 0
            ? 'Pilih satu atau beberapa unit'
            : allSelected
                ? `Semua ${selectedCount} unit dipilih`
                : `${selectedCount} unit dipilih`;
    };

    unitOptions.forEach((option) => option.addEventListener('change', updateUnitPicker));

    selectAllUnits?.addEventListener('change', () => {
        unitOptions.forEach((option) => {
            option.checked = selectAllUnits.checked;
        });
        updateUnitPicker();
    });

    unitPicker?.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.addEventListener('click', (event) => event.stopPropagation());
    });

    unitPicker?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') unitPicker.open = false;
    });

    document.addEventListener('click', (event) => {
        if (unitPicker?.open && !unitPicker.contains(event.target)) unitPicker.open = false;
    });

    document.querySelectorAll('[data-close-upload-modal]').forEach((button) => {
        button.addEventListener('click', () => uploadModal?.close());
    });

    uploadModal?.addEventListener('click', (event) => {
        if (event.target === uploadModal) uploadModal.close();
    });

    document.querySelectorAll('[data-close-element-modal]').forEach((button) => {
        button.addEventListener('click', () => elementModal?.close());
    });

    elementModal?.addEventListener('click', (event) => {
        if (event.target === elementModal) elementModal.close();
    });

    const openConfirmation = (form, title, message, confirmLabel = 'Ya, lanjutkan') => {
        if (!confirmationDialog || !pendingTitle || !pendingMessage || !pendingConfirmButton) {
            form.requestSubmit();
            return;
        }

        pendingAction = form;
        pendingTitle.textContent = title;
        pendingMessage.textContent = message;
        pendingConfirmButton.textContent = confirmLabel;
        confirmationDialog.showModal();
    };

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            openConfirmation(
                form,
                form.dataset.confirmTitle || 'Konfirmasi tindakan',
                form.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan?',
                'Ya, hapus'
            );
        });
    });

    confirmForm?.addEventListener('submit', (event) => {
        if (event.submitter?.value !== 'confirm' || !pendingAction) {
            pendingAction = null;
            return;
        }

        event.preventDefault();
        const form = pendingAction;
        pendingAction = null;
        confirmationDialog.close();
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });

    confirmationDialog?.addEventListener('click', (event) => {
        if (event.target === confirmationDialog) confirmationDialog.close();
    });

    confirmationDialog?.addEventListener('close', () => {
        pendingAction = null;
    });

    if (uploadModal?.open && uploadForm) {
        const elementId = uploadForm.querySelector('#upload-element-id').value;
        if (elementId) {
            uploadForm.action = uploadForm.dataset.uploadTemplate.replace('__ID__', encodeURIComponent(elementId));
        }
        updateUnitPicker();
    }

    const toast = document.querySelector('[data-toast]');
    if (toast) {
        const dismiss = () => {
            toast.classList.add('toast-hiding');
            window.setTimeout(() => toast.remove(), 300);
        };

        toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
        window.setTimeout(dismiss, 6000);
    }

    if (elementModal?.open && elementForm) {
        const id = elementForm.querySelector('#element-id').value;
        const method = elementForm.querySelector('#element-method');

        if (id && method.value === 'PUT') {
            elementForm.action = elementForm.dataset.updateTemplate.replace('__ID__', encodeURIComponent(id));
            elementForm.querySelector('#element-modal-title').textContent = 'Ubah Elemen';
            elementForm.querySelector('#element-submit-label').textContent = 'Simpan Perubahan';
        }
    }
});
