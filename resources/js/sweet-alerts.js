import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const sharedOptions = {
    buttonsStyling: false,
    backdrop: 'rgba(15, 23, 42, 0.5)',
    customClass: {
        popup: 'arcade-swal',
        confirmButton: 'btn-primary',
        cancelButton: 'btn-ghost',
    },
};

export async function confirmAction({
    title = 'Are you sure?',
    message = '',
    confirmText = 'Continue',
    tone = 'danger',
} = {}) {
    const result = await Swal.fire({
        ...sharedOptions,
        icon: 'warning',
        title,
        text: message,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        focusCancel: tone === 'danger',
        customClass: {
            ...sharedOptions.customClass,
            confirmButton: tone === 'danger' ? 'btn-primary swal-confirm-danger' : 'btn-primary',
        },
    });

    return result.isConfirmed;
}

export function warning(message, title = 'Reminder') {
    return Swal.fire({
        ...sharedOptions,
        icon: 'warning',
        title,
        text: message,
        confirmButtonText: 'OK, got it',
    });
}

export function error(message, title = 'Something went wrong') {
    return Swal.fire({
        ...sharedOptions,
        icon: 'error',
        title,
        text: message,
        confirmButtonText: 'Close',
    });
}

window.AppAlert = { confirm: confirmAction, warning, error };

// Replaces synchronous browser confirmations. The first click is intercepted
// during capture; a confirmed action is replayed once with a short-lived
// bypass marker so the existing Livewire click handler receives it unchanged.
document.addEventListener('click', async (event) => {
    const trigger = event.target.closest?.('[data-swal-confirm]');
    if (!trigger) return;

    if (trigger.dataset.swalConfirmed === 'true') {
        delete trigger.dataset.swalConfirmed;
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    if (trigger.dataset.swalPending === 'true') return;
    trigger.dataset.swalPending = 'true';

    const confirmed = await confirmAction({
        title: trigger.dataset.swalConfirmTitle || 'Are you sure?',
        message: trigger.dataset.swalConfirm || '',
        confirmText: trigger.dataset.swalConfirmButton || 'Continue',
        tone: trigger.dataset.swalConfirmTone || 'danger',
    });

    delete trigger.dataset.swalPending;
    if (confirmed && trigger.isConnected) {
        trigger.dataset.swalConfirmed = 'true';
        trigger.click();
    }
}, true);

window.addEventListener('voucher-reminder', (event) => {
    const detail = event.detail || {};
    if (detail.message) warning(detail.message, detail.title || 'Staff redemption reminder');
});
