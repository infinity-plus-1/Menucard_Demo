function animateCarouselText(element) {
    $(element).animate({
        "margin-left": 0
    }, 1000, function () {
        if ($(element).index() > 0) {
            $(this).find("span").animate({
                "color": "red"
            }, 1500);
        }
    });
}