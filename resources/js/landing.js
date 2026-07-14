import Swiper from 'swiper';
import { Autoplay, EffectCoverflow, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-coverflow';

/**
 * Coverflow prize slider on the public landing page. Pagination/navigation
 * are fully custom-styled (see app.css), so we intentionally skip Swiper's
 * default pagination/navigation CSS modules.
 */
function initPrizeSliders() {
    document.querySelectorAll('.prize-swiper').forEach((container) => {
        if (container.swiper) {
            container.swiper.destroy(true, true);
        }

        const wrapper = container.closest('.prize-slider');
        const slideCount = container.querySelectorAll('.swiper-slide').length;

        new Swiper(container, {
            modules: [EffectCoverflow, Navigation, Pagination, Autoplay],
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            spaceBetween: 24,
            loop: slideCount > 4,
            coverflowEffect: {
                rotate: 35,
                stretch: 0,
                depth: 160,
                modifier: 1,
                slideShadows: false,
            },
            autoplay: slideCount > 1 ? {
                delay: 2600,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            } : false,
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
