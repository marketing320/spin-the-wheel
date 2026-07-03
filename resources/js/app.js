import './echo';
import { fireConfetti } from './spin/confetti-controller';

/**
 * Fire a celebration on pages that declare a prize (e.g. the result page)
 * via a [data-confetti-level] element.
 */
function celebrate() {
    const el = document.querySelector('[data-confetti-level]');
    if (el && !el.dataset.celebrated) {
        el.dataset.celebrated = '1';
        fireConfetti(el.dataset.confettiLevel || 'medium');
    }
}

document.addEventListener('DOMContentLoaded', celebrate);
document.addEventListener('livewire:navigated', celebrate);
