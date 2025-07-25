import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        'restaurant': Number,
        'zip': Number,
    };

    openRestaurant() {
        if (this.restaurantValue > 0 && this.zipValue > 0) {
            //window.location.href = `/company/${this.restaurantValue}`;
            Turbo.visit(`/company/${this.restaurantValue}?zip=${this.zipValue}`);
        }
    }
}