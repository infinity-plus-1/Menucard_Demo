import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'button',
        'cuisines',
        'showFilterBtn',
        'hideFilterBtn',
        'restaurantsMainContainer',
        'restaurantFilter',
        'restaurantFilterBar',
        'restaurantsList',
        'restaurants',
    ];

    static values = {
        zip: Number
    };

    listRestaurants(page) {
        if (this.zipValue && this.zipValue > 0 && this.filters) {
            let config = {};
            for (const [key, value] of Object.entries(this.filters)) {
                config[key] = value;
            }
            if (typeof page !== 'undefined') {
                config['page'] = page;
            }
            config['proximity'] = this._getProximity();
            $.ajax({
                method: 'GET',
                url: `/listRestaurants/${this.zipValue}`,
                data: config
            }).done((response) => {
                Turbo.renderStreamMessage(response);
            }).fail((response) => {
            });
        }
    }

    restaurantsTargetConnected(element) {
        if (typeof element !== 'undefined' && element.nodeType === Node.ELEMENT_NODE) {
            const page = $(element).attr('data-current-page');
            if (typeof page !== 'undefined') {
                this.currentPage = Number.parseInt(page);
            }
        }
    }

    restaurantsTargetDisconnected(element) {
        this.currentPage = 0;
    }

    connect() {
        this.filters = {};
        this.listRestaurants();
        this.observeWindowSize();
        this.restaurantsContainerWidth = 0;
        this.currentPage = 0;
    }

    selectCuisine(cuisine) {
        if (
            this.hasButtonTarget
            && this.hasCuisinesTarget
            && cuisine
            && typeof cuisine.params !== 'undefined'
            && typeof cuisine.params.cuisine !== 'undefined'
            && typeof cuisine.params.elementId !== 'undefined'
        ) {
            this.buttonTarget.innerText = cuisine.params.cuisine;
            for (const [key, value] of this.cuisinesTargets.entries()) {
                if (key === cuisine.params.elementId) {
                    value.classList.add('active');
                    this.filters.cuisine = value.innerText;
                } else {
                    value.classList.remove('active');
                }
            }
            this.listRestaurants();
        }
    }

    selectRating(rating) {
        if (
            rating
            && typeof rating.params !== 'undefined'
            && typeof rating.params.rating !== 'undefined'
        ) {
            this.filters.rating = rating.params.rating;
            this.listRestaurants();
        }
    }

    gotoRestaurant(restaurant) {
        if (restaurant && typeof restaurant.params !== 'undefined' && typeof restaurant.params.restaurant !== 'undefined') {
            Turbo.visit(`/company/${restaurant.params.restaurant}`);
        }
    }

    observeWindowSize() {
        if (this.hasRestaurantsMainContainerTarget) {
            this.observer = new ResizeObserver((entries) => {
                if (entries && typeof entries[0] !== 'undefined') {
                    if (entries[0]['contentRect']['width'] !== this.restaurantsContainerWidth) {
                        this.modifyFilterByWindowSize();
                        this.restaurantsContainerWidth = entries[0]['contentRect']['width'];
                        this.adjustPaginationProximity();
                    }
                }
                
            }).observe(this.restaurantsMainContainerTarget);
        }
    }

    unobserveWindowSize() {
        if (this.hasRestaurantsMainContainerTarget) {
            this.observer.unobserve(this.restaurantsMainContainerTarget);
        }
    }

    _getProximity() {
        const width = $(window).width();
        if (width >= 1400) {
            return 5;
        } else if (width >= 1200 && width < 1400) {
            return 4;
        } else if (width >= 992 && width < 1200) {
            return 3;
        } else if (width >= 768 && width < 992) {
            return 2;
        } else if (width >= 576 && width < 768) {
            return 1;
        }
        return 0;
    }

    adjustPaginationProximity() {
        if (this.currentPage !== 0) {
            this.listRestaurants(this.currentPage);
        }
    }

    modifyFilterByWindowSize() {
        if (
            this.hasShowFilterBtnTarget
            && this.hasHideFilterBtnTarget
            && this.hasRestaurantFilterTarget
            && this.hasRestaurantFilterBarTarget
            && this.hasRestaurantsListTarget
        ) {
            if ($(window).width() >= 768) {
                $(this.restaurantFilterBarTarget).css({
                    width: '16.66%',
                });
                $(this.restaurantsListTarget).css({
                    width: '83.33%',
                });
                $(this.hideFilterBtnTarget).addClass('d-none');
                $(this.showFilterBtnTarget).addClass('d-none');
                $(this.restaurantFilterTarget).removeClass('d-none');
            } else {
                $(this.restaurantFilterBarTarget).css({
                    width: '20%',
                });
                $(this.restaurantsListTarget).css({
                    width: '80%',
                });
                $(this.hideFilterBtnTarget).addClass('d-none');
                $(this.showFilterBtnTarget).removeClass('d-none');
                $(this.restaurantFilterTarget).addClass('d-none');
            }
        }
    }

    showFilter() {
        if (
            this.hasShowFilterBtnTarget
            && this.hasHideFilterBtnTarget
            && this.hasRestaurantFilterTarget
            && this.hasRestaurantFilterBarTarget
            && this.hasRestaurantsListTarget
            && this.restaurantFilterBarTarget.style.width === '20%'
            && $(window).width() < 768
        ) {
            this.showFilterBtnTarget.disabled = true;
            $(this.restaurantsListTarget).animate(
                {
                    width: 0,
                },
                450,
                () => {
                    $(this.restaurantFilterTarget).removeClass('d-none');
                    $(this.restaurantFilterBarTarget).animate(
                        {
                            width: '100%',
                        },
                        450,
                        () => {
                            $(this.showFilterBtnTarget).addClass('d-none');
                            this.showFilterBtnTarget.disabled = false;
                            $(this.hideFilterBtnTarget).removeClass('d-none');
                        },
                    );
                },
            );
        }
    }

    hideFilter() {
        if (
            this.hasShowFilterBtnTarget
            && this.hasHideFilterBtnTarget
            && this.hasRestaurantFilterTarget
            && this.hasRestaurantFilterBarTarget
            && this.hasRestaurantsListTarget
            && this.restaurantFilterBarTarget.style.width === '100%'
        ) {
            this.hideFilterBtnTarget.disabled = true;
            $(this.restaurantFilterBarTarget).animate(
                {
                    width: '20%',
                },
                450,
                () => {
                    $(this.restaurantFilterTarget).addClass('d-none');
                    $(this.restaurantsListTarget).animate(
                        {
                            width: '80%',
                        },
                        450,
                        () => {
                            $(this.hideFilterBtnTarget).addClass('d-none');
                            this.hideFilterBtnTarget.disabled = false;
                            $(this.showFilterBtnTarget).removeClass('d-none');
                        },
                    );
                },
            );
        }
    }
}