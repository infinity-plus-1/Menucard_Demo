import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {
    static values = {
        id: String,
        price: Number,
        groups: Array,
    }

    static targets = [
        'counter',
        'dishTotal',
        'sizes',
    ];

    connect() {
        if (
            typeof this.idValue !== 'undefined' &&
            typeof this.priceValue !== 'undefined' &&
            this.hasCounterTarget
        ) {
            const id = this.idValue;
            
            this.getCartFromParent().then(() => {
                for (const [dishId, dish] of Object.entries(this.cart)) {
                    if (id === dishId) {
                        $(this.counterTarget).text(Object.keys(dish).length);
                    }
                }
            });
        }
    }

    async getCartFromParent() {
        this.dispatch('getCartFromParent', {
            detail: {
                callback: (cart) => {
                    this.cart = cart;
                }
            },
            bubbles: true
        });
    }

    disconnect() {
        if (
            typeof this.idValue !== 'undefined' &&
            typeof this.priceValue !== 'undefined' &&
            this.hasCounterTarget
        ) {
            const id = this.idValue;
            sessionStorage.setItem(id, this.count + '');
        }   
    }

    increment() {
        if (
            typeof this.idValue !== 'undefined' &&
            typeof this.priceValue !== 'undefined' &&
            typeof this.count !== 'undefined' &&
            this.hasCounterTarget
        ) {
            if (this.count < 99)
                this.count++;
            
            this.counterTarget.textContent = this.count;
            sessionStorage.setItem(this.idValue, this.count + '');
            this.dispatch("updateDishSelector", { detail: { content: {id: this.idValue, count: this.count, price: this.priceValue} } });
        }
    }

    decrement() {
        if (
            typeof this.idValue !== 'undefined' &&
            typeof this.priceValue !== 'undefined' &&
            typeof this.count !== 'undefined' &&
            this.hasCounterTarget
        ) {
            if (this.count > 0)
                this.count--;
    
            this.counterTarget.textContent = this.count;
            sessionStorage.setItem(this.idValue, this.count + '');
            this.dispatch("updateDishSelector", { detail: { content: {id: this.idValue, count: this.count, price: this.priceValue} } });
        }
    }

    addDish(event) {
        if (
            !event
            || typeof event === 'undefined'
            || typeof event.params === 'undefined'
            || typeof event.params.dish === 'undefined'
            || typeof event.params.dishPrice === 'undefined'
            || !this.hasCounterTarget
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not add the dish. Please reload the page and try again`,
            });
        }

        this.dispatch('addDish', {
            detail: {
                content: {
                    dish: event.params.dish,
                    dishPrice: event.params.dishPrice,
                }
            }
        });
    }

    

    removeDishes(event) {
        if (
            !event
            || typeof event === 'undefined'
            || typeof event.params === 'undefined'
            || typeof event.params.dish === 'undefined'
            || typeof event.params.dishPrice === 'undefined'
            || !this.hasCounterTarget
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not remove the dish. Please reload the page and try again.`,
            });
        }

        this.dispatch('removeDishes', {
            detail: {
                content: {
                    dish: event.params.dish,
                    dishPrice: event.params.dishPrice,
                }
            }
        });
    }

    removeDish(event) {
        if (
            !event
            || typeof event === 'undefined'
            || typeof event.params === 'undefined'
            || typeof event.params.uuid === 'undefined'
            || typeof event.params.dish === 'undefined'
        ) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: `Could not remove the dish. Please reload the page and try again2.`,
            });
            return;
        }

        this.dispatch('removeDish', {
            detail: {
                content: {
                    dish: event.params.dish,
                    uuid: event.params.uuid,
                },
                bubbles: true,
            },
        });
    }

    
}