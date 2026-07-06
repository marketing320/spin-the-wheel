import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

/**
 * Camera-based voucher scanner for the staff redemption page. Reads both QR
 * codes and Code128 barcodes. On a successful decode it dispatches a
 * `voucher-scanned` window event — a small Alpine listener in the blade view
 * forwards the code into the Livewire component ($wire.set + $wire.call),
 * the same bridge pattern used by the admin-toast notifications.
 *
 * The scanner UI lives inside a `wire:ignore` block so Livewire re-renders
 * (after each lookup/confirm/cancel) never tear down the active camera feed.
 */
function boot() {
    const toggleButton = document.getElementById('redeem-scanner-toggle');
    const videoRegion = document.getElementById('redeem-scanner-region');

    if (!toggleButton || !videoRegion || toggleButton.dataset.scannerBound) {
        return;
    }
    toggleButton.dataset.scannerBound = '1';

    let scanner = null;
    let active = false;

    async function start() {
        if (active) return;

        scanner = new Html5Qrcode('redeem-scanner-region', {
            formatsToSupport: [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
            ],
            verbose: false,
        });

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 260, height: 260 } },
                (decodedText) => {
                    window.dispatchEvent(new CustomEvent('voucher-scanned', { detail: { code: decodedText } }));
                    stop();
                },
                () => {
                    /* per-frame decode misses are expected; ignore */
                },
            );
            active = true;
            videoRegion.classList.remove('hidden');
            toggleButton.textContent = 'Stop camera';
        } catch (error) {
            console.warn('Camera scan unavailable:', error);
            window.dispatchEvent(new CustomEvent('admin-toast', {
                detail: { message: 'Could not access the camera — type or scan the code below instead.' },
            }));
        }
    }

    async function stop() {
        if (scanner && active) {
            try {
                await scanner.stop();
            } catch (error) {
                /* already stopped */
            }
            scanner.clear();
        }
        active = false;
        videoRegion.classList.add('hidden');
        toggleButton.textContent = 'Scan with camera';
    }

    toggleButton.addEventListener('click', () => {
        active ? stop() : start();
    });
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
if (document.readyState !== 'loading') boot();
