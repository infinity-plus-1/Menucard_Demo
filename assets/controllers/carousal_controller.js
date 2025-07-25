import { Controller } from '@hotwired/stimulus';
import { Carousel } from 'bootstrap';

export default class extends Controller {

    connect() {
        const carouselElement = $('#suggestedRestaurantsSlide');

        if (carouselElement.length > 0) {
            const carousel = new Carousel(carouselElement.get(0), {
                interval: 2000,
                touch: false
            });
        }
        
    }

    // disconnect() {
    //     if (this._carousel) {
    //         $(this.element).carousel('dispose');
    //       }
    // }

}