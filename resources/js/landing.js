import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';

/**
 * Prize slider on the public landing page. Pagination/navigation
 * are fully custom-styled (see app.css), so we intentionally skip Swiper's
 * default pagination/navigation CSS modules.
 */
function initPrizeSliders() {
    document.querySelectorAll('.prize-swiper').forEach((container) => {
        if (container.swiper) {
            container.swiper.destroy(true, true);
        }

        const wrapper = container.closest('.prize-slider');
        new Swiper(container, {
            modules: [Navigation, Pagination],
            grabCursor: true,
            centerInsufficientSlides: true,
            spaceBetween: 16,
            breakpoints: {
                640: { slidesPerView: 2 },
                900: { slidesPerView: 3 },
            },
            navigation: {
                nextEl: wrapper?.querySelector('.prize-swiper-next'),
                prevEl: wrapper?.querySelector('.prize-swiper-prev'),
            },
            pagination: {
                el: wrapper?.querySelector('.prize-swiper-pagination'),
                clickable: true,
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', initPrizeSliders);
document.addEventListener('livewire:navigated', initPrizeSliders);
