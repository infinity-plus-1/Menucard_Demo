import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        groups: Array,
        sizes: Object,
    }

    static targets = [
        'dishTotal',
    ];

    connect() {
        this.groups = {};

        for (const element of this.groupsValue) {
            this.groups[element[0]] = {
                group: element[0],
                name: element[1],
                count: element[2],
                errorId: `select-grouped-extra-error-${element[0]}`,
                selectedExtra: null,
            };
        }

        sessionStorage.setItem('currentDishGroups', JSON.stringify(this.groups));

        const currentDish = sessionStorage.getItem('currentDish');

        try {
            this.currentDish = (currentDish && currentDish !== 'null' && currentDish.trim() !== 'undefined')
            ? JSON.parse(currentDish)
            : {}
            ;
        } catch (e) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
            return;
        }

        this.currentDish.size = {
            size: null,
            price: null,
            sizes: this.sizesValue,
        }

        sessionStorage.setItem('currentDish', JSON.stringify(this.currentDish));
    }

    selectSize(event) {
        if (!this.hasDishTotalTarget) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the extra. Please reload the page and try again`,
            });
            return;
        }

        if (
            !event
            || typeof event === 'undefined'
            || typeof event.params === 'undefined'
            || typeof event.params.size === 'undefined'
            || typeof event.params.price === 'undefined'
            || typeof this.sizesValue[event.params.size] === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `An unknown error occured. Please reload the page and try again.`,
            });
            return;
        }

        if (
            !this.currentDish
            || typeof this.currentDish === 'undefined'
            || typeof this.currentDish.price === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `An unknown error occured. Please reload the page and try again.`,
            });
            return;
        }

        const size = event.params.size;
        const price = event.params.price;

        if (typeof this.currentDish.size !== 'undefined' && typeof this.currentDish.size.price !== 'undefined') {
            this.currentDish.price -= this.currentDish.size.price;
        }

        this.currentDish.size.size = size;
        this.currentDish.size.price = price;

        this.currentDish.price += price;

        sessionStorage.setItem('currentDish', JSON.stringify(this.currentDish));

        this.updateTotal();
    }

    addExtra(event) {
        if (!this.hasDishTotalTarget) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the extra. Please reload the page and try again`,
            });
            return;
        }

        if (
            !event
            || typeof event === 'undefined'
            || typeof event.params === 'undefined'
            || typeof event.params.type === 'undefined'
            || typeof event.params.extra === 'undefined'
            || typeof event.params.extraName === 'undefined'
            || typeof event.params.price === 'undefined'
            || typeof event.params.group === 'undefined'
            || typeof event.params.groupName === 'undefined'
            || typeof event.params.dish === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
            return;
        }

        const type = event.params.type;
        const extra = event.params.extra;
        const extraName = event.params.extraName;
        const price = event.params.price;
        const group = event.params.group;
        const groupName = event.params.groupName;
        const dish = event.params.dish;

        if (
            !this.currentDish
            || typeof this.currentDish === 'undefined'
            || typeof this.currentDish.dish === 'undefined'
            || typeof this.currentDish.price === 'undefined'
            || typeof this.currentDish.extras === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
            return;
        }

        if (dish !== this.currentDish.dish) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
            return;
        }

        if (type === 1) {
            if (event.srcElement.checked) {
                if (typeof this.currentDish.extras[extra] !== 'undefined') {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An extra with the name ${extraName} has been already added to the dish.`,
                    });
                    return;
                }
                this.currentDish.extras[extra] = {
                    extra: extra,
                    extraName: extraName,
                    price: price,
                }
                this.currentDish.price += price;
            } else {
                delete this.currentDish.extras[extra];
                this.currentDish.price -= price;
            }
        } else if (type === 2) {
            if (typeof this.groups[group] === 'undefined') {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `Could not add the dish. Please reload the page and try again.`,
                });
            }
            this.groups[group].selectedExtra = extra;
            if (typeof this.currentDish.groups[group] === 'undefined') {
                this.currentDish.groups[group] = {
                    group: group,
                    groupName: groupName,
                    extra: {
                        extra: extra,
                        extraName: extraName,
                        price: price,
                    }
                };
                this.currentDish.price += price;
            } else {
                this.currentDish.price -= this.currentDish.groups[group].extra.price;
                this.currentDish.groups[group].extra = {
                    extra: extra,
                    extraName: extraName,
                    price: price,
                };
                this.currentDish.price += price;
            }
        } else {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
            return;
        }

        sessionStorage.setItem('currentDish', JSON.stringify(this.currentDish));
        sessionStorage.setItem('currentDishGroups', JSON.stringify(this.groups));

        this.updateTotal();
    }

    updateTotal() {
        $(this.dishTotalTarget).text('$' + Number.parseFloat(this.currentDish.price).toFixed(2));
    }
}


