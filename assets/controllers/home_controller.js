import { Controller } from '@hotwired/stimulus';
import { useDebounce } from 'stimulus-use'
function animateHomeCarouselText(element) {
    let margin = $(element).parent().css("width");
    $(element).css({
        "margin-left": margin,
        "opacity": "1"
    }).animate({
        "margin-left": 0
    }, 1000, function () {
        if ($(element).index() > 0) {
            $(this).find("span").addClass("home-fade-in-transition-red");
        }
    });
}

export default class extends Controller {

    static targets = [
        'carousalImgs',
        'zipInput'
    ];

    static values = {
        selectedCarousalImg: String,
        selectedCity: String
    };

    static debounces = [{
        name: 'autofillPostalCode',
        wait: 500
    }];

    initialize() {
        this.application.debug = false;
        $(document).ready(function () {
            $(".col-2 .fsize-4vh").inView(animateHomeCarouselText, {
                interval: 100, execCounter: 1
            }).startView();
        });
    }

    connect() {
        useDebounce(this);
    }

    gotoProduct() {
        console.log(this.selectedCarousalImgValue);
    }

    selectCityPostalCode(e) {
        this.selectedCityValue = $(e.currentTarget).attr('data-home-zip-code-value');
        $('#postcode-container').addClass('disabled');
        $('#selected-city-container').removeClass('disabled');
        $('#selected-city-span').text(this.selectedCityValue);
    }

    unselectClicked() {
        this.dispatch('unselectClicked', { detail: {
            callback: this.unselectCityPostalCode
        }});
    }

    unselectCityPostalCode() {
        return new Promise ((resolve, reject) => {
            try {
                $('#selected-city-container').addClass('disabled');
                $('#postcode-container').removeClass('disabled');
                $('#selected-city-span').text('');
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
            /*axios({
                method: 'get',
                url: 'https://api.geoapify.com/v1/geocode/autocomplete',
                responseType: 'json',
                params: {
                    'text': zipInputValue,
                    'limit': '10',
                    'format': 'json',
                    'filter': 'countrycode:de',
                    'apiKey': '0d33941822104371b45012f25db0268f'
                }
              })*/
              
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
                console.log(error);
            });
        }
        else if (zipInputValue == '') {
            $('.city-select-element').addClass('disabled')
            .attr('data-home-zip-code-value', '');
        }
    }
}