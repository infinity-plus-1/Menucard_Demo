import { Controller } from '@hotwired/stimulus';
import { useDebounce } from 'stimulus-use';
import Swal from 'sweetalert2';

export default class extends Controller {

    static targets = [
        'zipInput'
    ];

    static values = {
        selectedCity: String
    };

    static debounces = [{
        name: 'autofillPostalCode',
        wait: 500
    }];

    initialize() {
        this.application.debug = false;
    }

    connect() {
        useDebounce(this);
        this.restaurantSuggestionsSnapshot = '';
        this.restaurantsSnapshot = '';
        const restaurantSuggestionsContainer = $('#restaurantSuggestions');
        if (restaurantSuggestionsContainer.length === 1) {
            this.restaurantSuggestionsSnapshot = restaurantSuggestionsContainer.html();
        }

        const restaurants = $('#restaurants');
        if (restaurants.length === 1) {
            this.restaurantsSnapshot = restaurants.html();
        }
    }

    gotoProduct() {
        console.log(this.selectedCarousalImgValue);
    }

    selectCityPostalCode(e) {
        
        const link = $('#restaurantSuggestionsLink');
        const turboFrame = $('#restaurantSuggestions');
        if (link.length === 1 && turboFrame.length === 1) {
            this.selectedCityValue = $(e.currentTarget).attr('data-home-zip-code-value');
            const selectedSplittedValue = this.selectedCityValue.split(' ');
            if (Array.isArray(selectedSplittedValue) && selectedSplittedValue.length > 1) {
                const zip = selectedSplittedValue[0];
                link.attr('href', `/listSuggestedRestaurants/${zip}`);
                link.get(0).click();
                $('#postcode-container').addClass('disabled');
                $('#selected-city-container').removeClass('disabled');
                $('#selected-city-span').text(this.selectedCityValue);
                turboFrame.removeClass('d-none');
                $.ajax({
                    method: 'GET',
                    url: `/restaurants/${zip}`
                }).done((response) => {
                    Turbo.renderStreamMessage(response);
                }).fail((response) => {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An unknown error occured.`,
                    });
                });
            }
        }
    }

    unselectClicked() {
        this.dispatch('unselectClicked', { detail: {
            callback: this.unselectCityPostalCode,
            callbackParams: this,
        }});
    }

    unselectCityPostalCode(_this) {
        return new Promise ((resolve, reject) => {
            try {
                $('#selected-city-container').addClass('disabled');
                $('#postcode-container').removeClass('disabled');
                $('#selected-city-span').text('');

                const restaurantSuggestionsContainer = $('#restaurantSuggestions');
                if (restaurantSuggestionsContainer.length === 1) {
                    restaurantSuggestionsContainer.html(_this.restaurantSuggestionsSnapshot);
                }

                const restaurants = $('#restaurants');
                if (restaurants.length === 1) {
                    restaurants.html(_this.restaurantsSnapshot);
                }
                resolve();
            } catch (error) {
                reject(error)
            }
        });
    }

    adaptFontSize() {
        const zipInputValue = this.zipInputTarget.value;
        this.zipInputTarget.style.fontSize = zipInputValue != '' ?
        '1.25em' : '1em';
    }

    autofillPostalCode() {
        const zipInputValue = this.zipInputTarget.value;
        if (zipInputValue != '' && zipInputValue.length < 6) {
            axios({
                method: 'get',
                url: '/zips',
                responseType: 'json',
                params: {
                    'zip': zipInputValue
                }
            })
            .then((response) => {
                const matches = JSON.parse(response.request.response);
                let citySelectElements = $('.city-select-element'), i = 0;
                $(citySelectElements).addClass('disabled')
                .attr('data-home-zip-code-value', '');
                matches.forEach((result) => {
                    for (const key in result) {
                        if (Object.hasOwnProperty.call(result, key)) {
                            const element = result[key];
                            citySelectElements.eq(i++)
                            .attr('data-home-zip-code-value', key + ' ' + element)
                            .removeClass('disabled').children().first()
                            .text(key + ' ' + element);
                        }
                    }
                });
                $('#zipcode-error').addClass('disabled');
            })
            .catch(function (error) {
                $('#zipcode-error').removeClass('disabled');
            });
        }
        else if (zipInputValue == '') {
            $('.city-select-element').addClass('disabled')
            .attr('data-home-zip-code-value', '');
        }
    }
}