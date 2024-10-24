const TurboUtil = class {

    constructor() {
        this.pageLoadFade();
    }
    animationDone = false;
    isPreview() {
        return $('html[data-turbo-preview]').length > 0;
    }
    counter = 0;
    pageLoadFade() {
        $(document).on('turbo:before-cache', (e) => {
            $('body').attr('data-is-preview', '1').css('opacity', '.5');
        });
        $(document).on('turbo:before-render', (e) => {
            let intervalID = 0;
            this.animationDone = false;
            $('[data-is-preview]').animate({
                opacity: .5
            }, 50, () => {
                this.animationDone = true;
            });
            if (!this.isPreview()) {
                e.preventDefault();
                let waitForDone = () => {
                    if (this.animationDone) {
                        this.animationDone = false;
                        const newBody = e.detail.newBody;
                        $(newBody).css('opacity', '.5');
                        e.detail.resume();
                        requestAnimationFrame(() => {
                            $(newBody).animate({
                                opacity: 1.0
                            }, 50, () => {
                                if (newBody && newBody.hasAttribute('data-is-preview')) {
                                    newBody.removeAttribute('data-is-preview');
                                }
                                clearInterval(intervalID);
                            });
                        });
                    }
                }
                intervalID = setInterval(waitForDone, 50);
            }
        });
    }
}

export default new TurboUtil();