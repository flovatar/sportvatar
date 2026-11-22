import jquery from 'jquery'
window.$ = window.jQuery = jquery

import '../plugins/marquee'
import '../plugins/openClose'
import 'sly-scrolling/src/sly.js'
// import slick from 'slick-carousel'
//import HeroScene from "./HeroScene";
//import DiscoRoom from "./DiscoRoom";
//import ShowroomSlider from "./ShowroomSlider";
// import HeroSceneShowroom from "./HeroSceneShowroom";
// import FlovatestScene from "./FlovatestScene";


export const NavToggle = () => {
    const opener =  $('.nav-opener');
    const navwrap = $('.nav');
    const filterOpener =  $('.filter-opener');

    opener.click(function(e) {
        e.preventDefault();
        $('body').toggleClass('nav-active');
        $('body').removeClass('filter-active');

        $('html').on('click touchstart pointerdown MSPointerDown', function(e) {
            var target = $(e.target);
            if(!target.closest(opener).length && !target.closest(navwrap).length) {
                $('body').removeClass('nav-active');
            }
        });
    });

    filterOpener.click(function(e) {
        e.preventDefault();
        $('body').toggleClass('filter-active');
    });
}

export const Tabs = () => {
    $('[data-tab]').click(function (e) {
        e.preventDefault();
        var tab_id = $(this).data('tab');

        $(this).parent().siblings().find('[data-tab]').removeClass('active');
        $("#" + tab_id).siblings().removeClass('active');
        $("#" + tab_id).siblings().addClass('hidden');

        $(this).addClass('active');
        $("#" + tab_id).addClass('active');
        $("#" + tab_id).removeClass('hidden');
    })

    $('#' + $('[data-tab].active').data('tab')).addClass('active');
}

export const Modal = () => {
    $('[data-modal]').on('click', function () {
        $('body').addClass('modal-active');
        $('.modal').removeClass('show');
        $($(this).attr('href')).addClass('show');
        $(document).trigger("modalOpened", this);
        return false;
    });
    jQuery('.modal .close').click(function (e) {
        e.preventDefault();
        $('body').removeClass('modal-active');
        $('.modal').removeClass('show');
    });
}

export const OpenClose = () => {
    $('[data-more]').next().hide();
    $('[data-more].active').next().show();

    $('[data-more]').click(function(e) {
        e.preventDefault();
        $(this).hasClass('active') ? $(this).removeClass('active').next().slideUp(200) : $(this).addClass('active').next().slideToggle(200);

        if($(this).closest('[data-accordion]').length) {
            $(this).parent().siblings().find('[data-more]').removeClass('active');
            $(this).parent().siblings().find('[data-more]').next().slideUp(200);
        }
    })

    $('[data-outside]').next().find('a:not(.hasdrop-a):not([data-more])').click(function() {
        $('[data-outside]').removeClass('active').next('').slideUp(200);
    });
    $('[data-outside]').click(function(e) {
        $('[data-outside]').not(this).removeClass('active').next().slideUp(200);
    });

    $('html').on('click touchstart pointerdown MSPointerDown', function(e) {
        var target = $(e.target);

        if(!(target.closest('[data-outside]').length) && !(target.closest('[data-outside] + *').length)) {
            setTimeout (function() {
                $('[data-outside]').removeClass('active').next().slideUp(200);
            }, 200)
        }

        if(!(target.closest('[data-outside-1]').length) && !(target.closest('[data-outside-1] + *').length)) {
            setTimeout (function() {
                $('[data-outside-1]').removeClass('active').next().slideUp(200);
            }, 200)
        }
    });
}

export const Marquee = () => {
    $('.smooth-card-slider').each(function () {
        var block = $(this).find('.line')
        var cloneBlock = block.find('.card-slide')
        var multiply = Math.ceil(2100 / cloneBlock.width())

        for (var x = 1; x <= multiply; x++) {
            var cloned = cloneBlock.clone()
            block.append(cloned)
        }
    })

    $('.smooth-card-slider').marquee({
        line: '.line',
        animSpeed: 40,
        mask: '.mask'
    })
}

export const CustomNumber = () => {
    (function( $ ) {

        $.fn.numberstyle = function(options) {

            /*
             * Default settings
             */
            var settings = $.extend({
                value: 0,
                step: undefined,
                min: undefined,
                max: undefined
            }, options );

            /*
             * Init every element
             */
            return this.each(function(i) {

                /*
                 * Base options
                 */
                var input = $(this);

                /*
           * Add new DOM
           */
                var container = document.createElement('div'),
                    btnAdd = document.createElement('div'),
                    btnRem = document.createElement('div'),
                    min = (settings.min) ? settings.min : input.attr('min'),
                    max = (settings.max) ? settings.max : input.attr('max'),
                    value = (settings.value) ? settings.value : parseFloat(input.val());
                container.className = 'numberstyle-qty';
                btnAdd.className = (max && value >= max ) ? 'qty-btn qty-add disabled' : 'qty-btn qty-add';
                btnAdd.innerHTML = '<span class="icon-chevron-up text-xxs"></span>';
                btnRem.className = (min && value <= min) ? 'qty-btn qty-rem  disabled' : 'qty-btn qty-rem ';
                btnRem.innerHTML = '<span class="icon-chevron-down text-xxs"></span>';
                input.wrap(container);
                input.closest('.numberstyle-qty').prepend(btnRem).append(btnAdd);

                /*
                 * Attach events
                 */
                // use .off() to prevent triggering twice
                $(document).off('click','.qty-btn').on('click','.qty-btn',function(e){

                    var input = $(this).siblings('input'),
                        sibBtn = $(this).siblings('.qty-btn'),
                        step = (settings.step) ? parseFloat(settings.step) : parseFloat(input.attr('step')),
                        min = (settings.min) ? settings.min : ( input.attr('min') ) ? input.attr('min') : undefined,
                        max = (settings.max) ? settings.max : ( input.attr('max') ) ? input.attr('max') : undefined,
                        oldValue = parseFloat(input.val()),
                        newVal;

                    //Add value
                    if ( $(this).hasClass('qty-add') ) {

                        newVal = (oldValue >= max) ? oldValue : oldValue + step,
                            newVal = (newVal > max) ? max : newVal;

                        if (newVal == max) {
                            $(this).addClass('disabled');
                        }
                        sibBtn.removeClass('disabled');

                        //Remove value
                    } else {

                        newVal = (oldValue <= min) ? oldValue : oldValue - step,
                            newVal = (newVal < min) ? min : newVal;

                        if (newVal == min) {
                            $(this).addClass('disabled');
                        }
                        sibBtn.removeClass('disabled');

                    }

                    //Update value
                    input.val(newVal).trigger('change');

                });

                input.on('change',function(){

                    const val = parseFloat(input.val()),
                        min = (settings.min) ? settings.min : ( input.attr('min') ) ? input.attr('min') : undefined,
                        max = (settings.max) ? settings.max : ( input.attr('max') ) ? input.attr('max') : undefined;

                    if ( val > max ) {
                        input.val(max);
                    }

                    if ( val < min ) {
                        input.val(min);
                    }
                });

            });
        };


        /*
         * Init
         */

        $('.custom-number').numberstyle();

    }( jQuery ));


}

export const slickCarousel = () => {
    $('.parts-slider').slick({
        infinite: false,
        slidesToShow: 4,
        slidesToScroll: 4,
        rows: 3,
        touchThreshold: 70,
        prevArrow: '<button class="slick-prev" aria-label="Previous" type="button"></button>',
        nextArrow: '<button class="slick-next" aria-label="Next" type="button"></button>',
        responsive: [
            {
                breakpoint: 1600,
                settings: {
                  rows: 2,
                  slidesToShow: 3,
                  slidesToScroll: 3,
                }
            },
            {
              breakpoint: 1024,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                rows: 1,
                arrows: false,
                variableWidth: true
              }
            }
        ]
    });
}

export const galleryHeightCounter = () => {
    $(document).on('modalOpened', function() {
    });
}

export const jsOpenClose = () => {
    $('.js-open-close').openClose({
        activeClass: 'active',
        opener: '.js-opener',
        slider: '.js-slide',
        animSpeed: 400,
        effect: 'slide'
    });
}

export const slyScrolling = () => {
    var options = {
        horizontal: 1,
        itemNav: 'forceCentered',
        smart: 1,
        activateMiddle: 1,
        activateOn: 'click',
        mouseDragging: 1,
        touchDragging: 1,
        releaseSwing: 1,
        scrollBar: '.scrollbar',
        scrollBy: 1,
        speed: 300,
        elasticBounds: 1,
        dragHandle: 1,
        dynamicHandle: 0,
        clickBar: 1,
        keyboardNavBy: 'item',
    };

    var frame = new Sly('.gallery-nav', options).init();

    frame.on('move', function() {
        var active = $(this)[0].rel.activeItem;
        $('.main-gallery .slide').addClass('hidden').removeClass('active');
        $('.main-gallery #gtab-' + (active+1)).removeClass('hidden').addClass('active');
    })
}

export const memeSlider = () => {
    var el = $('.meme-slider');
    window.memeFrameSlider = new Sly('.meme-slider',{
        horizontal: 1,
        itemNav: 'basic',
        smart: 1,
        activateOn: 'click',
        mouseDragging: 1,
        touchDragging: 1,
        releaseSwing: 1,
        moveBy: 3000,
        backward: el.parent().find('.btn-prev'),
        forward: el.parent().find('.btn-next'),
    }).init();

    $(window).on('resize', function () {
        window.memeFrameSlider.reload();
    });
}

export const widgetScroll = () => {
    $('.scrl-up').on({
        'mousedown touchstart': function () {
            $(".core-parts .scroll-y").animate({scrollTop: 0}, 500);
        },
        'mouseup touchend': function () {
            $(".core-parts .scroll-y").stop(true);
        }
    });

    $('.scrl-down').on({
        'mousedown touchstart': function () {
            $(".core-parts .scroll-y").animate({
                scrollTop: $(".core-parts .scroll-y")[0].scrollHeight
            }, 500);
        },
        'mouseup touchend': function () {
            $(".core-parts .scroll-y").stop(true);
        }
    });

    $('.filters-scroll-wrapper .scroll-left').on({
        'mousedown touchstart': function () {
            $(".filters-scroll-wrapper .scroll-x").animate({scrollLeft: 0}, 500);
        },
        'mouseup touchend': function () {
            $(".filters-scroll-wrapper .scroll-x").stop(true);
        }
    });

    $('.filters-scroll-wrapper .scroll-right').on({
        'mousedown touchstart': function () {
            $(".filters-scroll-wrapper .scroll-x").animate({
                scrollLeft: $(".filters-scroll-wrapper .scroll-x")[0].scrollWidth
            }, 500);
        },
        'mouseup touchend': function () {
            $(".filters-scroll-wrapper .scroll-x").stop(true);
        }
    });

    $('.categories-scroll-wrapper .scroll-left').on({
        'mousedown touchstart': function () {
            $(".categories-scroll-wrapper .scroll-x").animate({scrollLeft: 0}, 500);
        },
        'mouseup touchend': function () {
            $(".categories-scroll-wrapper .scroll-x").stop(true);
        }
    });

    $('.categories-scroll-wrapper .scroll-right').on({
        'mousedown touchstart': function () {
            $(".categories-scroll-wrapper .scroll-x").animate({
                scrollLeft: $(".categories-scroll-wrapper .scroll-x")[0].scrollWidth
            }, 500);
        },
        'mouseup touchend': function () {
            $(".categories-scroll-wrapper .scroll-x").stop(true);
        }
    });

}

export const videoModal = () => {
    function videoId(button) {
        var $videoUrl = button.attr("data-video");
        if ($videoUrl !== undefined) {
            var $videoUrl = $videoUrl.toString();
            var srcVideo;

            if ($videoUrl.indexOf("youtube") !== -1) {
                var et = $videoUrl.lastIndexOf("&");
                if (et !== -1) {
                    $videoUrl = $videoUrl.substring(0, et);
                }
                var embed = $videoUrl.indexOf("embed");
                if (embed !== -1) {
                    $videoUrl =
                    "https://www.youtube.com/watch?v=" +
                    $videoUrl.substring(embed + 6, embed + 17);
                }

                srcVideo =
                "https://www.youtube.com/embed/" +
                $videoUrl.substring($videoUrl.length - 11, $videoUrl.length) +
                "?autoplay=1&mute=1&loop=1&playlist=" +
                $videoUrl.substring($videoUrl.length - 11, $videoUrl.length) +
                "";
            } else if ($videoUrl.indexOf("youtu") !== -1) {
                var et = $videoUrl.lastIndexOf("&");
                if (et !== -1) {
                    $videoUrl = $videoUrl.substring(0, et);
                }
                var embed = $videoUrl.indexOf("embed");
                if (embed !== -1) {
                    $videoUrl =
                    "https://youtu.be/" +
                    $videoUrl.substring(embed + 6, embed + 17);
                }

                srcVideo =
                "https://www.youtube.com/embed/" +
                $videoUrl.substring($videoUrl.length - 11, $videoUrl.length) +
                "?autoplay=1&mute=1&loop=1&playlist=" +
                $videoUrl.substring($videoUrl.length - 11, $videoUrl.length) +
                "";

            } else if ($videoUrl.indexOf("vimeo") !== -1) {
                srcVideo =
                "https://player.vimeo.com/video/" +
                $videoUrl
                .substring($videoUrl.indexOf(".com") + 5, $videoUrl.length)
                .replace("/", "") +
                "?autoplay=1";


            } else if ($videoUrl.indexOf("mp4") !== -1) {
                return (
                    '<video loop playsinline autoplay controls><source src="' +
                    $videoUrl +
                    '" type="video/mp4"></video>'
                    );
            } else {
                alert(
                    "The video assigned is not from Youtube, Vimeo or MP4, remember to enter the correct complete video link .\n - Youtube: https://www.youtube.com/watch?v=43ngkc2Ejgw\n - Vimeo: https://vimeo.com/111939668\n - MP4 https://server.com/file.mp4"
                    );
                return false;
            }
            return (
                '<iframe src="' +
                srcVideo +
                '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                );
        } else {
            alert("No video assigned.");
            return false;
        }
    }

    $(".play-video").click(function (e) {
        e.preventDefault();
        $("body").append('<div id="video-wrap"></div>');
        var $theVideo = videoId($(this));
        if ($theVideo) {
            $("#video-wrap")
            .html(
                '<span class="video-overlay"></span><div class="video-container"><div class="video-wrap"><div class="video-frame"><button class="close-video"></button>' +
                $theVideo +
                '</div></div></div>'
                )
            .addClass("active");
            $('body').addClass('video-active');
        }
    });

    $(document).on("click", ".close-video, .video-overlay", function () {
        $("#video-wrap").empty().removeClass("active");
        $('body').removeClass('video-active');
        $('#video-wrap').remove();
    });
}



$(document).ready(function ($) {
    NavToggle();
    Tabs();
    Modal();
    Marquee();
    OpenClose();
    CustomNumber();
    galleryHeightCounter();
    jsOpenClose();
    widgetScroll();
    videoModal();
    //slickCarousel();
    if($('.gallery-nav').length > 0) {
        slyScrolling();
    }

    if($('.meme-slider').length > 0) {
        memeSlider();
    }

});

jQuery(document).ready(function(){
    if(document.getElementById('hero-scene-home')) {
        //new HeroScene();
    }

    if(document.getElementById('showroom-slider')) {
        //new ShowroomSlider();
    }

    if(document.getElementById('scene-disco-room')) {
        //new DiscoRoom();
    }

    /*if(document.getElementById('hero-scene-showroom')) {
        new HeroSceneShowroom();
    }*/

    /*if(document.getElementById('flovatest-scene')) {
        new FlovatestScene();
    }*/
});
