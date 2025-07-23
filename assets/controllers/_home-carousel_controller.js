import { Controller } from '@hotwired/stimulus';
import { Carousel } from 'bootstrap';


function animateHomeCarouselText(element) {
    let margin = $(element).parent().css("width");
    $(element).css({
        "margin-left": margin,
        "opacity": "1"
    }).animate({
        "margin-left": 0
    }, 1000, function () {
        if ($(element).index() > 0) {
            $(this).find("p").addClass("home-fade-in-transition-red");
        }
    });
}


export default class extends Controller {
    static targets = [
        'carouselImgs'
    ];

    static values = {
        selectedCarouselImg: String
    };

    
    

    initialize() {
        this.application.debug = false;
        $(document).ready(function () {
            $(".col-4 .fsize-2-5vw").inView(animateHomeCarouselText, {
                interval: 100, execCounter: 1
            }).startView();
        });
    }

    connect() {
        const carouselElement = document.querySelector('#home-carousel')

        const carousel = new Carousel(carouselElement, {
        interval: 2000,
        touch: false
        });
    }

    gotoProduct() {
        console.log(this.selectedCarousalImgValue);
    }
}