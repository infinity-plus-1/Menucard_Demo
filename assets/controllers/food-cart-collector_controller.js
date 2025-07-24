import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

export default class extends Controller {

    static targets = [
        'cashSum',
        'countSum',
        'orderButton',
        'modalLabel',
        'closeBtn',
        'modalBody',
        'submitBtn',
        'deliveryStreet',
        'deliverySn',
        'deliveryZip',
        'deliveryCity',
    ];

    static values = {
        prepareLink: String,
        finalizeLink: String,
        id: Number,
        changeAddressLink: String,
        addDishToCartLink: String,
        showRatingsLink: String,
        removeDishesFromCartLink: String,
        zip: String,
        userZip: String,
        city: String,
        street: String,
        sn: String,
    }

    connect() {
        this.modal = new Modal('#food-menu-modal', {
            keyboard: false
        });
        if (this.hasOrderButtonTarget) {
            const restaurant = sessionStorage.getItem('restaurant');
            if (Number.parseInt(restaurant) !== this.idValue) {
                sessionStorage.clear();
                this.cart = {};
                this.totalCash = 0.0;
                this.totalCount = 0;
            }

            const cart = sessionStorage.getItem('cart');
            const totalCash = sessionStorage.getItem('totalCash');
            const totalCount = sessionStorage.getItem('totalCount');
            
            try {
                this.cart = (cart && cart !== 'null' && cart.trim() !== 'undefined')
                ? JSON.parse(cart)
                : {}
                ;
                this.totalCash = (totalCash && totalCash !== 'null' && totalCash.trim() !== 'undefined') ? totalCash : 0.0;
                this.totalCount = (totalCount && totalCount !== 'null' && totalCount.trim() !== 'undefined') ? totalCount : 0;
            } catch (e) {
                this.cart = {};
                this.totalCash = 0.0;
                this.totalCount = 0;
            }

            const container = document.getElementById('food-menu-main-container');
            const canvas = document.getElementById('offcanvasBottom');

            if (container && canvas) {
                container.style.paddingBottom = `${canvas.offsetHeight}px`;
                $(window).on('resize', () => {
                    container.style.paddingBottom = `${canvas.offsetHeight}px`;
                });
            }

            this.totalize().then(() => {
                this.cashSumTarget.textContent = this.totalCash.toFixed(2);
                this.countSumTarget.textContent = this.totalCount;
            });
        }

        this.addDishToCart = this._addDishToCart.bind(this);
        this.closeModalFunc = this.closeModal.bind(this);

        this.initialAddressHandler();
    }

    getCart(event) {
        const cartValue = this.cart;
        if (event.detail && typeof event.detail.callback === 'function') {
            event.detail.callback(cartValue);
        }
      }

    setSessionData() {
        sessionStorage.setItem('cart', JSON.stringify(this.cart));
        sessionStorage.setItem('totalCash', this.totalCash + '');
        sessionStorage.setItem('totalCount', this.totalCount + '');
        sessionStorage.setItem('restaurant', this.idValue);
    }

    disconnect() {
        $(window).off('resize');
        this.setSessionData();
        sessionStorage.removeItem('currentDish');
        sessionStorage.removeItem('currentDishGroups');
    }

    async totalize() {
        if (this.hasOrderButtonTarget) {
            this.totalCash = 0.0;
            this.totalCount = 0;
            for (const [id, dishCollection] of Object.entries(this.cart)) {
                for (const [uuid, dish] of Object.entries(dishCollection)) {
                    if (
                        !dish
                        || typeof dish === 'undefined'
                        || typeof dish.price === 'undefined'
                    ) {
                        continue;
                    }
                    this.totalCash += dish.price;
                    this.totalCount++;
                }
            }
            if (this.totalCount > 0) {
                this.orderButtonTarget.disabled = false;
            } else {
                this.orderButtonTarget.disabled = true;
            }
            this.setSessionData();
            this.cashSumTarget.textContent = this.totalCash.toFixed(2);
            this.countSumTarget.textContent = this.totalCount;
        }
    }

    prepareOrder() {
        if (this.finalizeLinkValue !== '' && this.prepareLinkValue !== '' && this.idValue !== 0 && !$.isEmptyObject(this.cart)) {
            const address = sessionStorage.getItem('address');
            $.ajax({
                url: `${this.prepareLinkValue}`,
                data: {dishes: JSON.stringify(this.cart), id: this.idValue, address: address},
                method: 'POST'
            }).done((response) => {
                Turbo.visit(`${this.finalizeLinkValue}/${response}`);
            }).fail((response) => {
                if (typeof response.status !== 'undefined' && typeof response.responseJSON !== 'undefined') {
                    Swal.fire({
                        icon: "error",
                        title: `Error ${response.status}`,
                        text: response.responseJSON,
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An unknown error occured.`,
                    });
                }
            });
        }
    }

    changeAddress() {
        if (this.modal) {
            this.changeAddressModalOpened = function () {
                if (typeof this.modal._element !== 'undefined') {
                    this.modal._element.removeEventListener('shown.bs.modal', this.changeAddressModalOpened);
                    if (this.hasModalLabelTarget) {
                        this.modalLabelTarget.innerText = 'Change delivery address';
                    }
                    if (this.hasModalBodyTarget && this.hasSubmitBtnTarget) {
                        this.modalBodyTarget.innerHTML = 'Loading...';
                        if (this.changeAddressLinkValue !== '' && this.zipValue !== '') {
                            $.ajax({
                                method: 'POST',
                                url: this.changeAddressLinkValue,
                                data: { zip: this.zipValue },
                            }).done((response) => {
                                Turbo.renderStreamMessage(response);
                                $(this.submitBtnTarget).on('click', this.saveAddress.bind(this));
                            }).fail(() => {
                                this.modal.hide();
                                Swal.fire({
                                    icon: "error",
                                    title: "Oops...",
                                    text: `An unknown error occured.`,
                                });
                            });
                        } else {
                            this.modal.hide();
                            Swal.fire({
                                icon: "error",
                                title: "Oops...",
                                text: `An unknown error occured.`,
                            });
                        }
                    }
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An unknown error occured.`,
                    });
                }
            }.bind(this);

            this.modal._element.addEventListener('shown.bs.modal', this.changeAddressModalOpened);
            this.modal.show();
        }
    }

    initialAddressHandler() {
        const address = sessionStorage.getItem('address');
        
        const _shouldUseAccAddress = () => {
            if (
                this.zipValue === this.userZipValue
                && this.streetValue !== ''
                && this.snValue !== ''
                && this.cityValue !== ''
            ) {
                this.setAddress(this.streetValue, this.snValue, this.cityValue);
            } else {
                this.setAddress('', '', '');
            }
        }

        if (address) {
            try {
                const addressObj = JSON.parse(address);

                if (typeof addressObj.zip !== 'undefined' && this.zipValue === addressObj.zip) {
                    if (
                        addressObj.city !== ''
                        && addressObj.street !== ''
                        && addressObj.sn !== ''
                        && this.zipValue !== ''
                        && this.zipValue.length === 5
                    ) {
                        this.setAddress(addressObj.street, addressObj.sn, addressObj.city);
                    } else {
                        _shouldUseAccAddress();
                    }
                } else {
                    _shouldUseAccAddress();
                }
            } catch (error) {
                _shouldUseAccAddress();
            }
        } else {
            _shouldUseAccAddress();
        }
    }

    async prepareAddressSet(deliveryStreet, deliverySn, deliveryCity) {
        deliveryStreet.removeClass('red-border');
        deliverySn.removeClass('red-border');
        deliveryCity.removeClass('red-border');
        if (deliveryStreet.val().length < 2) {   
            deliveryStreet.addClass('red-border');
            throw new Error("The street must be at least two characters long.");
        }
        if (deliverySn.val().length < 1) {   
            deliverySn.addClass('red-border');
            throw new Error("Missing street number.");
        }
        if (deliveryCity.val().length < 2) {   
            deliveryCity.addClass('red-border');
            throw new Error("The city must be at least two characters long.");
        }
    }

    setAddress(deliveryStreet, deliverySn, deliveryCity) {
        if (
            this.hasDeliveryStreetTarget
            && this.hasDeliverySnTarget
            && this.hasDeliveryZipTarget
            && this.hasDeliveryCityTarget
        ) {
            $(this.deliveryStreetTarget).text(`${deliveryStreet} `);
            $(this.deliverySnTarget).text(deliverySn ? `${deliverySn}, ` : '');
            $(this.deliveryZipTarget).text(`${this.zipValue} `);
            $(this.deliveryCityTarget).text(`${deliveryCity}`);
            sessionStorage.setItem('address', JSON.stringify({
                street: deliveryStreet,
                sn: deliverySn,
                city: deliveryCity,
                zip: this.zipValue,
            }));
        }
    }

    saveAddress(e) {
        const deliveryStreet = $('#deliveryStreet');
        const deliverySn = $('#deliverySn');
        const deliveryCity = $('#deliveryCity');
        const warning = $('#food-menu-address-form-warning');
        if (
            this.hasSubmitBtnTarget
            && deliveryStreet.length === 1
            && deliverySn.length === 1
            && deliveryCity.length === 1
            && warning.length === 1
            && this.zipValue !== ''
        ) {
            warning.text('');
            warning.addClass('d-none');
            this.prepareAddressSet(deliveryStreet, deliverySn, deliveryCity).then(() => {
                const submitBtn = $(this.submitBtnTarget);
                submitBtn.off('click');
                this.setAddress(deliveryStreet.val(), deliverySn.val(), deliveryCity.val());
                if (this.modal) {
                    this.modal.hide();
                }
            }).catch((error) => {
                warning.text(error);
                warning.removeClass('d-none');
            });
        }
    }

    closeModal() {
        if (this.hasModalLabelTarget) {
            this.modalLabelTarget.innerText = '';
        }
        if (this.hasModalBodyTarget) {
            this.modalBodyTarget.innerHTML = '';
        }
        this.modal._element.removeEventListener('shown.bs.modal', this.changeAddressModalOpened);

        if (this.hasSubmitBtnTarget) {
            $(this.submitBtnTarget).off('click', this.addDishToCart);
            $(this.submitBtnTarget).off('click', this.closeModalFunc);
            $(this.submitBtnTarget).text('OK');
        }

        this.modal.hide();
    }

    async _validatePreDishAction(params, stdErrorText) {
        if (!this.modal || typeof this.modal === 'undefined' || typeof this.modal._element === 'undefined') {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: stdErrorText,
            });
        }

        if (!this.hasSubmitBtnTarget) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: stdErrorText,
            });
            return;
        }

        if (
            !params
            || typeof params === 'undefined'
            || typeof params.detail === 'undefined'
            || typeof params.detail.content === 'undefined'
            || typeof params.detail.content.dish === 'undefined'
            || typeof params.detail.content.dishPrice === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: stdErrorText,
            });
            return;
        }
    }

    _validateAllGroupRadiosSet(currentDish) {
        let groups = null;
        let errors = 0;

        try {
            groups = JSON.parse(sessionStorage.getItem('currentDishGroups'));
        } catch (error) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not gather selected grouped extras. Please reload the page and try again.`,
            });
            return;
        }

        for (const key in groups) {
            if (Object.prototype.hasOwnProperty.call(groups, key)) {
                const group = groups[key];
                
                if (
                    !group
                    || typeof group.selectedExtra === 'undefined'
                    || typeof group.count === 'undefined'
                    || typeof group.name === 'undefined'
                    || typeof group.group === 'undefined'
                    || typeof group.errorId === 'undefined'
                ) {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An unknown error occured. Please reload the page and try again.`,
                    });
                    return false;
                }

                const errorMsg = $(`#${group.errorId}`);

                if (errorMsg.length !== 1) {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An unknown error occured. Please reload the page and try again.`,
                    });
                    return false;
                }

                errorMsg.addClass('d-none');

                if (group.count > 0 && !group.selectedExtra || group.selectedExtra < 1) {
                    errorMsg.removeClass('d-none');
                    errors++;
                }
            }
        }

        if (
            !currentDish
            || typeof currentDish === 'undefined'
            || typeof currentDish.size === 'undefined'
            || typeof currentDish.size.size === 'undefined'
            || typeof currentDish.size.price === 'undefined'
            || typeof currentDish.size.sizes === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `An unknown error occured. Please reload the page and try again.`,
            });
            return false;
        }

        if (Object.keys(currentDish.size.sizes).length > 0) {
            const sizeErrorMsg = $(`#select-dish-size-error`);

            if (sizeErrorMsg.length !== 1) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `An unknown error occured. Please reload the page and try again.`,
                });
                return false;
            }
    
            sizeErrorMsg.addClass('d-none');
    
            if (!currentDish.size.size || !currentDish.size.price) {
                sizeErrorMsg.removeClass('d-none');
                errors++;
            }
        }

        if (errors > 0) {
            return false;
        }

        return true;
    }

    async addDish(params) {
        
        await this._validatePreDishAction(params, 'Could not add the dish. Please reload the page and try again');

        const dish = params.detail.content.dish;
        const price = params.detail.content.dishPrice;

        if (dish > 0 && this.addDishToCartLinkValue !== '') {
            $.ajax({
                url: this.addDishToCartLinkValue,
                method: 'POST',
                data: { dish: dish },
            }).done((response) => {
                Turbo.renderStreamMessage(response);
                $(this.submitBtnTarget).on('click', this.addDishToCart).text('ADD TO CART');
                this.modal.show();
                sessionStorage.setItem('currentDish', JSON.stringify({dish: dish, price: price, extras: {}, groups: {}, size: {}}));
            }).fail((response) => {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `Could not add the dish. Please reload the page and try again`,
                });
                return;
            });
        }
    }

    _addDishToCart() {
        let currentDish = null;
        
        try {
            currentDish = JSON.parse(sessionStorage.getItem('currentDish'));
        } catch (error) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
            return;
        }

        if (
            !currentDish
            || typeof currentDish === 'undefined'
            || typeof currentDish.dish === 'undefined'
            || typeof currentDish.price === 'undefined'
            || typeof currentDish.extras === 'undefined'
            || typeof currentDish.groups === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
            return;
        }

        if (!this._validateAllGroupRadiosSet(currentDish)) {
            return;
        }
        
        if (typeof this.cart[currentDish.dish] === 'undefined') {
            this.cart[currentDish.dish] = {};
        }

        const uuid = crypto.randomUUID();
        this.cart[currentDish.dish][uuid] = {
            size: currentDish.size,
            price: currentDish.price,
            extras: currentDish.extras,
            groups: currentDish.groups,
        };

        const counterTarget = $(`#dish-counter-${currentDish.dish}`);

        if (counterTarget.length === 1) {
            counterTarget.text(Object.keys(this.cart[currentDish.dish]).length);
        }

        this.totalize();
    }

    async removeDishes(params) {
        await this._validatePreDishAction(params, 'Unable to perform a removal action. Please reload the page.');

        const dish = params.detail.content.dish;

        const dishesObj = this.cart[dish];

        if (dishesObj && Object.keys(dishesObj).length > 0 && this.removeDishesFromCartLinkValue !== '') {
            $.ajax({
                url: this.removeDishesFromCartLinkValue,
                method: 'POST',
                data: { dish: dish, dishes: JSON.stringify(dishesObj) },
            }).done((response) => {
                Turbo.renderStreamMessage(response);
                $(this.submitBtnTarget).on('click', this.closeModalFunc).text('OK');
                this.modal.show();
            }).fail((response) => {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: 'Unable to perform a removal action. Please reload the page.',
                });
                return;
            });
        }
    }

    removeDish(params) {
        if (
            !params
            || typeof params === 'undefined'
            || typeof params.detail === 'undefined'
            || typeof params.detail.content === 'undefined'
            || typeof params.detail.content.uuid === 'undefined'
            || typeof params.detail.content.dish === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not remove the dish. Please reload the page and try again.`,
            });
            return;
        }

        const uuid = params.detail.content.uuid;
        const dish = params.detail.content.dish;

        if (!this.cart || typeof this.cart[dish] === 'undefined' || typeof this.cart[dish][uuid] === 'undefined') {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not remove the dish. Please reload the page and try again.`,
            });
            return;
        }

        delete this.cart[dish][uuid];

        const counterTarget = $(`#dish-counter-${dish}`);

        if (counterTarget.length === 1) {
            counterTarget.text(Object.keys(this.cart[dish]).length);
        }

        const dishElement = $(`#remove-dish-${uuid}`);

        if (dishElement.length !== 1) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not remove the dish. Please reload the page and try again.`,
            });
            return;
        }

        dishElement.remove();

        this.totalize();
    }

    showRatings() {
        if (this.hasModalBodyTarget && this.hasModalLabelTarget && this.showRatingsLinkValue !== '' && this.idValue > 0) {
            $.ajax({
                method: 'POST',
                url: this.showRatingsLinkValue,
                data: { company: this.idValue },
            }).done((response) => {
                Turbo.renderStreamMessage(response);
                if (this.hasModalLabelTarget) {
                    this.modalLabelTarget.innerHTML = '<h4>User ratings</h4>';
                }
                this.modal.show();
            }).fail((response) => {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `Can't show the ratings.`,
                });
            });
        }
    }
}