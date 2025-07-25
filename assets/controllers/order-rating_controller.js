import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {

    static targets = [
        'ratingText',
        'charCount',
        'rateButton',
    ];

    static values = {
        rateLink: String,
        order: Number,
    };
    
    connect() {
        const elements = $('i.bi', this.element);
        this.selectedIndex = null;

        if (elements.length !== 5) {
            Swal.fire({
                icon: "error",
                title: "Whoa!",
                text: 'Something went totally wrong. Rating does work as expected.',
            });
            if (this.hasRateButtonTarget) {
                this.rateButtonTarget.disabled = true;
            }
        }

        elements.on('mouseover', (e) => {
            const index = elements.index(e.target);
            for (let i = 0; i <= index; i++) {
                elements.eq(i).removeClass('bi-star').addClass('bi-star-fill');
            }
        }).on('mouseout', () => {
            $('i.bi:not(.selected-star)').removeClass('bi-star-fill').addClass('bi-star');
        }).on('click', (e) => {
            const index = elements.index(e.target);
            $('i.bi').removeClass('bi-star-fill selected-star').addClass('bi-star');
            for (let i = 0; i <= index; i++) {
                elements.eq(i).removeClass('bi-star').addClass('bi-star-fill selected-star');
            }
            this.selectedIndex = index;
        });
    }

    disconnect() {
        const elements = $('i.bi');
        elements.off('mouseover').off('mouseout').off('click');
    }

    charCounter() {
        if (this.hasRatingTextTarget && this.hasCharCountTarget) {
            const len = $(this.ratingTextTarget).val().length;
            if (len <= 300) {
                $(this.charCountTarget).get(0).textContent = (300 - $(this.ratingTextTarget).val().length);
            } else {
                $(this.ratingTextTarget).val($(this.ratingTextTarget).val().slice(0, 300));
            }
        }
    }

    rate() {
        if (this.rateLinkValue !== '' && this.orderValue !== 0 && this.hasRateButtonTarget) {
            const ratingText = this.hasRatingTextTarget ? this.ratingTextTarget.value : '';
            if (this.selectedIndex >= 0 && this.selectedIndex <= 4) {
                this.rateButtonTarget.disabled = true;
                $.ajax({
                    method: 'post',
                    url: `${this.rateLinkValue}/${this.orderValue}`,
                    data: {
                        rating: this.selectedIndex,
                        ratingText: ratingText,
                    },
                }).done(() => {
                    this.rateButtonTarget.style.display = 'none';
                    this.ratingTextTarget.disabled = true;
                    const elements = $('i.bi');
                    elements.off('mouseover').off('mouseout').off('click');
                    $('.star-rating label').css('cursor', 'default');
                    Swal.fire({
                        icon: "success",
                        title: "Review saved",
                        text: 'Thank you for sharing your experience with others!',
                    });
                }).fail((response) => {
                    const errorMsg = getJsonResponseMessage(response);
                    Swal.fire({
                        icon: "error",
                        title: "Order could not be rated",
                        text: errorMsg,
                    });
                    this.rateButtonTarget.disabled = false;
                });
            } else {

            }
        }
    }
}