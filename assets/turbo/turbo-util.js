const TurboUtil = class {

    constructor() {
        this.pageLoadFade();
    }

    animationDone = false;

    isPreview() {
        return $('html[data-turbo-preview]').length > 0;
    }

    pageLoadFade() {
        // $(document).on('turbo:before-cache', (e) => {
        //     $('body').attr('data-is-preview', '1').css('opacity', '.5');
        // });
        // $(document).on('turbo:before-render', (e) => {
        //     let intervalID = 0;
        //     this.animationDone = false;
        //     $('[data-is-preview]').animate({
        //         opacity: .7
        //     }, 50, () => {
        //         this.animationDone = true;
        //     });
        //     if (!this.isPreview()) {
        //         e.preventDefault();
        //         let waitForDone = () => {
        //             if (this.animationDone) {
        //                 this.animationDone = false;
        //                 const newBody = e.detail.newBody;
        //                 $(newBody).css('opacity', '.7');
        //                 e.detail.resume();
        //                 requestAnimationFrame(() => {
        //                     $(newBody).animate({
        //                         opacity: 1.0
        //                     }, 50, () => {
        //                         if (newBody && newBody.hasAttribute('data-is-preview')) {
        //                             newBody.removeAttribute('data-is-preview');
        //                         }
        //                         clearInterval(intervalID);
        //                     });
        //                 });
        //             }
        //         }
        //         intervalID = setInterval(waitForDone, 50);
        //     }
        // });

        $(document).on('turbo:visit', (e) => {
            $('body').addClass('loadingFade');
        });

        $(document).on('turbo:before-render', (e) => {
            $('body').addClass('loadingFade');
            $(e.detail.newBody).addClass('loadingFade');
        });

        $(document).on('turbo:render', () => {
            if (!this.isPreview()) {
                requestAnimationFrame(() => {
                    $('body').removeClass('loadingFade');
                });
            }
        });

        $(document).on('turbo:post-render', (e) => {
            $('html').removeAttr('aria-busy');
            requestAnimationFrame(() => {
                $('body').removeClass('loadingFade');
            });
        });

        $(document).on('turbo:before-stream-render', (e) => {
            const fallbackToDefaultActions = e.detail.render;
            e.detail.render = function (streamElement) {
                fallbackToDefaultActions(streamElement).then(() => {
                    $(document).trigger('turbo:post-render', [streamElement]);
                });
            }
        });
    }
}

export default new TurboUtil();