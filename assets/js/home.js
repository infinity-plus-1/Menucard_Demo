function animateCarouselText(element) {
    console.log("test");
    $(element).animate({
        "margin-left": 0
    }, 1000, function () {
        if ($(element).index() > 0) {
            console.log($(this).find("span"));
            $(this).find("span").animate({
                "color": "red"
            }, 1500);
        }
    });
}

$(document).ready(function () {
    //console.log($(".col-2 .fsize-4vh"));
    //$(".col-2 .fsize-4vh").inView(animateCarouselText, {interval: 10}).startView();
});