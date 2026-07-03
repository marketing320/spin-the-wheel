import '../echo';
import { initPlayerPage } from './player-sync';

/**
 * Player spin-page entry. Boots once per rendered page, whether the page
 * arrived via a full load or a Livewire SPA navigation.
 */
function boot() {
    const root = document.querySelector('[data-spin-root]');
    if (!root || root.dataset.spinInited) return;
    root.dataset.spinInited = '1';
    initPlayerPage();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
if (document.readyState !== 'loading') boot();
