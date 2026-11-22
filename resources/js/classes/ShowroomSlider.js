import Swiper, { Navigation } from 'swiper'

class ShowroomSlider {
    constructor() {
        this.body = document.body;
        this.container = document.getElementById('showroom-slider');

        Swiper.use([Navigation]);

        this.slider = new Swiper(this.container, {
            speed: 400,
            initialSlide: 0,
            autoHeight: false,
            direction: 'horizontal',
            loop: true,
            watchSlidesProgress: true,

            navigation: {
                nextEl: '#btn-showroom-next',
                prevEl: '#btn-showroom-prev',
            },

            effect: 'slide',
            // spaceBetween: 60,
            slidesPerView: 1,
            centeredSlides: true,
            slidesOffsetBefore: 0,
            grabCursor: true,
            breakpoints: {
                320: {
                    slidesPerView: 2,
                },
                640: {
                    slidesPerView: 3,
                },
                767: {
                    slidesPerView: 4,
                }
            },
            on: {
                progress(e) {
                    const t = e.slides.length;
                    for (let r = 0; r < e.slides.length; r += 1) {
                        const slide = e.slides[r].firstElementChild, progress = e.slides[r].progress, absProgress = Math.abs(progress);
                        const scale = 1.8 - .4 * absProgress;
                        let transformY = 380;
                        if(progress >= 0) {
                            transformY = transformY - progress * 70;
                        } else {
                            transformY = transformY + 25 * Math.pow(progress,2);
                        }
                        slide.style.transform = `translate3d(0, ${transformY}px, 0) scale(${scale})`;
                        slide.style.opacity = absProgress > 3 ? 0 : 1;
                    }
                }, setTransition(e, t) {
                    for (let r = 0; r < e.slides.length; r += 1) {
                        const o = e.slides[r].firstElementChild;
                        o.style.transitionDuration = `${t}ms`;
                    }
                }
            }
        });
    }
}

export default ShowroomSlider;
