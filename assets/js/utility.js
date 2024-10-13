Array.prototype.shuffle = function () {
    const a = [];
    while (this.length) {
        let i = Math.floor(Math.random() * (this.length - 1));
        a.push(this.splice(i, 1));
    }
    return a;
};

/**     Begin inView
 *      Checks, whether a element is visible in the viewport
 *      Requires: JQuery

2024 - Dennis Schwab

Usage example:

function isInView(element, options) {
    element.style.backgroundColor = options.backgroundColor;
    element.style.color = options.color;
}
function isOutView(element, options) {
    element.style.backgroundColor = options.backgroundColor;
    element.style.color = options.color;
}

$(document).ready(() => {

    //Chaining
    const options = {
        callbackExit: isOutView,
        threshold: .1,
        entryOptions: {
            color: "white",
            backgroundColor: "black"
        },
        exitOptions: {
            color: "black",
            backgroundColor: "white"
        },
        callExitOnStop: true,
        execCounter: 1
    };
    $(".content_instance").inView(isInView, options).startView();

    setTimeout(() => {
        $(".content_instance").stopView();
    }, 10000);

    //Set up step by step
    const divElement = $("#divElement");
    if (divElement.length > 0) {
        divElement.inView(isInView);
        divElement[0].inViewObj.callbackExit = isOutView;
        const config = {
            entryOptions: {
                color: "white",
                backgroundColor: "black"
            },
            exitOptions: {
                color: "black",
                backgroundColor: "white"
            }
        };
        divElement.updateConfig(config);
        divElement.startView();
    }
});

*/



/**
 * Creates an InViewObj instance.
 * @param {HTMLElement} e - The element to observe.
 * @param {Function} callbackEntry - The callback function to execute when the element is in view.
 * @param {Object} config - Configuration object. Valid options are: {
 *  element: {HTMLElement},
 *  callbackEntry: {Function},
 *  callbackExit: {Function|null},
 *  interval: {positive Integer},
 *  threshold: {Float between 0.0 and 1.0},
 *  callExitOnStop: {Boolean},
 *  execCounter: {Integer},
 *  entryOptions: {Object|null},
 *  exitOptions: {Object|null}
 * }
 */
function InViewObj(e, callbackEntry, config) {
    /**
     * The element to observe.
     * @type {HTMLElement}
     */
    this.element = e;
    /**
     * The callback function for entry.
     * @type {Function} - The callback function to execute when the element is in view.
     */
    this.callbackEntry = callbackEntry;
    /**
     * The callback function for exit.
     * @type {Function|null} - The callback function to execute when the element is not in view anymore.
     */
    this.callbackExit = null;
    /**
     * The debouncing interval, only necessary when the
     *  IntersectionObserver is not available in the browser.
     *  Must be set before starting the listener.
     * @type {Integer} - Lower value results in smoother detection, but heavier computation.
     */
    this.interval = 100;
    /**
     * The threshold for firing the events - 1.0 = 100% of the element must be in the viewport.
     * @type {Float} - 1.0 = 100% of the element must be in the viewport - 0.0 = 1. px counts.
     */
    this.threshold = .5;
    /**
     * The exit function will be executed when this variable is set to true.
     * Default: false
     * @type {Boolean} - Will be call stop function, if set.
     */
    this.callExitOnStop = false;
    /**
     * Will decrement by 1 whenever the entry function is called. Executes stop
     *  when reaching zero.
     * Default: -1 (unlimited executions)
     * @type {Integer} - Limit the number of events fired by setting an positive
     *  value. No limitations if a negative number is set.
     */
    this.execCounter = -1;
    /**
     * Options that can be forwarded to the entry callback function.
     * @type {Object|null} - E.g.: {backgroundColor: "black", color: "white"}.
     */
    this.entryOptions = {};
    /**
     * Options that can be forwarded to the exit callback function.
     * @type {Object|null} - E.g.: {backgroundColor: "white", color: "black"}.
     */
    this.exitOptions = {};
    /**
     * !ONLY FOR INTERNAL USAGE!
     * @type {Boolean} - DO NOT CHANGE THIS VALUE MANUALLY!
     */
    this._isInView = false;
    /**
     * The interval function. ONLY FOR INTERNAL USAGE!
     * @type {Number|null} - DO NOT CHANGE THIS MANUALLY!
     */
    this._intervalFn = null;
    /**
     * The observer instance. ONLY FOR INTERNAL USAGE!
     * @type {IntersectionObserver|null} - DO NOT CHANGE THIS MANUALLY!
     */
    this._obs = null;

    /*for (const property in config) {
        if (config.hasOwnProperty(property)) {
            if (this.hasOwnProperty(property)) {
                if (
                    property !== '_isInView' && property !== '_intervalFn' &&
                    property !== '_obs'
                ) {
                    this[property] = config[property];
                }
            }
        }
    }*/

    function updateConfig(instance, config) {
        for (const property in config) {
            if (config.hasOwnProperty(property)) {
                if (instance.hasOwnProperty(property)) {
                    if (
                        property !== '_isInView' && property !== '_intervalFn' &&
                        property !== '_obs'
                    ) {
                        instance[property] = config[property];
                    }
                }
            }
        }
    }

    InViewObj.prototype._updateConfig = function (config) {
        updateConfig(this, config);
    }

    updateConfig(this, config);

    InViewObj.prototype._inViewCalculation = function () {
        const rect = this.element.getBoundingClientRect();
        return (
            ((rect.top - $(window).height() + (rect.height * this.threshold)) <= 0 &&
            (rect.bottom - (rect.height * this.threshold)) >= 0) &&
            ((rect.left - $(window).width() + (rect.width * this.threshold)) <= 0 &&
            (rect.right - (rect.width * this.threshold)) >= 0)
        );
    }

    InViewObj.prototype._outViewCalculation = function () {
        const rect = this.element.getBoundingClientRect();
        return (
            ((rect.top - $(window).height() + (rect.height * this.threshold)) > 0 ||
            (rect.bottom - (rect.height * this.threshold)) < 0) ||
            ((rect.left - $(window).width() + (rect.width * this.threshold)) > 0 ||
            (rect.right - (rect.width * this.threshold)) < 0)
        );
    }

    InViewObj.prototype._inViewListener = function () {
        let intervalFunction = setInterval(() => {
            if (this._isInView === false && this._inViewCalculation()) {
                
                this._internalCallback(true);
            }
            else if (this._isInView === true && this._outViewCalculation()) {
                this._internalCallback(false);
            }
        }, this.interval);
        this._intervalFn = intervalFunction;
    }

    /**
     * 
     * @param {Array|Boolean} param 
     */
    InViewObj.prototype._internalCallback = function (param) {
        if (param instanceof Array) {
            /** * @type {IntersectionObserverEntry} entry */
            const entry = param[0];
            if (entry.isIntersecting) {
                if (this.execCounter > 0 || this.execCounter < 0) {
                    this.callbackEntry(this.element, this.entryOptions);
                    this.execCounter--;
                } else if (this.execCounter === 0) {
                    this.execCounter--;
                    this._stopView();
                }
            } else {
                if (this.callbackExit) {
                    this.callbackExit(this.element, this.exitOptions);
                }
            }
        } else {
            if (param === true) {
                if (this.execCounter > 0 || this.execCounter < 0) {
                    this._isInView = true;
                    this.callbackEntry(this.element, this.entryOptions);
                    this.execCounter--;
                } else if (this.execCounter === 0) {
                    this.execCounter--;
                    this._stopView();
                }
            }
            else if (param === false) {
                this._isInView = false;
                if (this.callbackExit) {
                    this.callbackExit(this.element, this.exitOptions);
                }
            }
        }
    }

    InViewObj.prototype._startView = function () {
        try {
            if (this.threshold < 0.0 || this.threshold > 1.0) this.threshold = 1.0;
            this._obs = new IntersectionObserver((entries) => {
                this._internalCallback(entries);
            }, {threshold: this.threshold});
            this._obs.observe(this.element);
        } catch (err) {
            this._inViewListener();
        }
    }

    InViewObj.prototype._stopView = function () {
        if (this.callExitOnStop === true && typeof this.callbackExit == "function") {
            this.callbackExit(this.element, this.exitOptions);
        }
        if (this._obs) {
            this._obs.unobserve(this.element);
        } else if (this._intervalFn) {
            clearInterval(this._intervalFn);
        } else {
            console.log("Neither an observer, nor an interval has been set.");
        }
    }
    
    return this;
}

(function ($) {
    /**
     * 
     * @param {Function} callbackEntry the function to execute when the element e 
     * is in the viewport. The element e will be forwarded to the function as first parameter.
     * @param {Object} config - Configuration object. Valid options are: {
     *  element: {HTMLElement},
     *  callbackEntry: {Function},
     *  callbackExit: {Function|null},
     *  interval: {positive Integer},
     *  threshold: {Float between 0.0 and 1.0},
     *  callExitOnStop: {Boolean},
     *  execCounter: {Integer},
     *  entryOptions: {Object|null},
     *  exitOptions: {Object|null}
     * }
     */
    $.fn.inView = function (callbackEntry, config = {}) {
        this.each( function () {
            $(this).map(function () {
                this.inViewObj = new InViewObj (this, callbackEntry, config);
            });
            
        });
        return this;
    }

    $.fn.startView = function () {
        $(this).each(function () {
            if (this.inViewObj) this.inViewObj._startView();
        });
    };

    $.fn.stopView = function () {
        $(this).each(function () {
            if (this.inViewObj) this.inViewObj._stopView();
        });
    };

    $.fn.updateConfig = function (config) {
        $(this).each(function () {
            if (this.inViewObj) this.inViewObj._updateConfig(config);
        });
    };
})(jQuery);

/**     End inView */

/**     Begin ThumbnailSlider 
 *
 *      Present images as selectable thumbnails with a hero image at the top
 *      Requires: Bootstrap v4.0-vX.X, JQuery
 */


/**
 * Creates an ThumbnailSlider instance.
 * @param {jQueryElementsObject} container 
 * @param {Array<String>} images 
 */
function ThumbnailSlider(container, images = []) {
    /**
     * 
     * @type {jQueryElementsObject}
     */
    this.container = container;
    /**
     * Hold an array of paths to the images
     * @type {Array<String>}
     */
    this.images = images;

    /**
     * The id of the main container. ONLY FOR INTERNAL USAGE!
     * @type {String} - DO NOT CHANGE THIS MANUALLY!
     */
    this._containerId = '';
    /**
     * The hero image element (upper big one). ONLY FOR INTERNAL USAGE!
     * @type {jQueryElementsObject} - DO NOT CHANGE THIS MANUALLY!
     */
    this._heroImage = null;
    /**
     * The current displayed thumbs). ONLY FOR INTERNAL USAGE!
     * @type {Object} - DO NOT CHANGE THIS MANUALLY!
     */
    this._currentRange = {active: 0, start: 0, end: 0};
    /**
     * The size of the total given images - 1. ONLY FOR INTERNAL USAGE!
     * @type {Integer} - DO NOT CHANGE THIS MANUALLY!
     */
    this._maxRange = images.length - 1;
    /**
     * If the container is fully builded. ONLY FOR INTERNAL USAGE!
     * @type {Boolean} - DO NOT CHANGE THIS MANUALLY!
     */
    this._builded = false;

    ThumbnailSlider.prototype._buildSlider = function () {
        if (this.container == null) throw("Given main container is null");
        if (this.images.length === 0) throw("No image paths are provided");

        const range = (this.images.length < 5 ? this.images.length : 5);
        this._currentRange.end = 4;
        const imageHeroDiv =
            '<div class="imageSliderHeroDiv" ' +
                'style="overflow: hidden; width: calc(50vw + ' +
                ((range - 1) * 5) + 'px);">' +
                '<img class="imageSliderHeroImage" src="' + this.images[0] + '" />' +
                '<svg width="60" height="100" class="imageSliderLeftArrow"' +
                    'xmlns="http://www.w3.org/2000/svg">' +
                    '<polygon points="55 5 55 90 5 45" stroke="black"></polygon>' +
                '</svg>' +
                '<svg width="60" height="100" class="imageSliderRightArrow"' +
                    'xmlns="http://www.w3.org/2000/svg">' +
                    '<polygon points="5 5 5 90 55 45" stroke="black"></polygon>' +
                '</svg>' +
            '</div>';
        let thumbnailsDiv =
            '<div id="image_slider_thumbnails">';

        for (let i = 0; i < range; i++) {
            thumbnailsDiv += 
                '<img id="image_slider_thumbnail_' + i +
                '" class="imageSliderThumbnail"' +
                ' src="' + this.images[i] + '" />';
        }
        
        thumbnailsDiv += '</div>';

        let thumbnailModel =

        '<div class="thumbnailModel">' +
            '<span class="thumbnailModelClose">X</span>' +
            '<img class="thumbnailModelImg" />' +
        '</div>';
                

        this._containerId = "#" + this.container.id + " ";
        this.container = $(this._containerId);
        this.container.addClass("container unselectable")
        .css({
            "display": "flex",
            "flex-wrap": "wrap",
            "justify-content": "center",
            "align-content": "center",
            "align-items": "center"
        }).append(thumbnailModel).append(imageHeroDiv).append(thumbnailsDiv);
        $(".imageSliderThumbnail").eq(0).css("opacity", "1.0");

        this._heroImage = $(this._containerId + ".imageSliderHeroDiv img").eq(0);
        this._builded = true;
    }

    function _renderHeroImage(_this) {
        $(_this._containerId + ".imageSliderHeroDiv img").eq(0)
            .attr("src", _this.images[_this._currentRange.start]);
        _this._currentRange.active = 0;
    }

    function _renderThumbs(_this) {
        let i = _this._currentRange.start;
        $(_this._containerId + ".imageSliderThumbnail").each(function () {
            $(this).attr("src", _this.images[i]).css("opacity", ".4");
            if (i === _this._currentRange.start) {
                $(this).attr("src", _this.images[i]).css("opacity", "1.0");
            }
            i++;
        });
    }

    function _renderModel(_this, heroImage) {
        $(_this._containerId + ".thumbnailModelImg").eq(0)
            .attr("src", heroImage.attr("src"));
        $(_this._containerId + ".thumbnailModel").eq(0).css("display", "flex");
    }

    function _closeModel(_this) {
        $(_this._containerId + ".thumbnailModel").eq(0).css("display", "none");
    }

    function _slideThumbnails(_this, direction) {
        let bias = 0;
        if (direction === 0) {
            bias = _this._currentRange.start > 4 ? 4 : _this._currentRange.start;
            _this._currentRange.start -= bias;
            _this._currentRange.end -= bias;
            _renderThumbs(_this, direction);
        } else if (direction === 1) {
            bias = _this._maxRange - _this._currentRange.end;
            bias = bias > 4 ? 4 : bias;
            _this._currentRange.start += bias;
            _this._currentRange.end += bias;
            _renderThumbs(_this);
        }
        _renderHeroImage(_this);
    }

    function _startSlider(interval, _this) {
        $(_this._containerId + ".imageSliderThumbnail").on("click", function () {
            $(_this._containerId + ".imageSliderThumbnail")
                .eq(_this._currentRange.active).css("opacity", "");
            $(this).css("opacity", "1.0");
            _this._currentRange.active = $(this).index();
            _this._heroImage.attr("src", $(this).attr("src"));
        });
        $(_this._containerId + ".imageSliderLeftArrow").on("click", function () {
            if (_this._currentRange.start > 0) {
                _slideThumbnails(_this, 0);
            }
        });

        $(_this._containerId + ".imageSliderRightArrow").on("click", function () {
            if (_this._currentRange.end < _this._maxRange) {
                _slideThumbnails(_this, 1);
            }
        });

        $(_this._containerId + ".thumbnailModelClose").on("click", function () {
            _closeModel(_this);
        });

        $(_this._containerId + ".imageSliderHeroImage").on("click", function () {
            _renderModel(_this, $(this));
        });

        $(_this._containerId + ".thumbnailModel").on("click", function () {
            _closeModel(_this);
        });

        clearInterval(interval);
    }

    ThumbnailSlider.prototype._startSlider = function () {
        let _this = this;
        let interval = setInterval(() => {
            if (this._builded === true) {
                _startSlider(interval, _this)
            }
        }, 50);
    }


    ThumbnailSlider.prototype._getImage = function (onlyPath) {
        const img = $(this._containerId + ".imageSliderHeroDiv img").eq(0);
        if (onlyPath === true) {
            return img.attr("src");
        }
        return img;
    };

    return this;
}

(function ($) {
    /**
     * 
     * @param {Array<String>} images 
     * @returns {jQueryElementsObject}
     */
    $.fn.buildSlider = function (images = []) {
        this.each (function () {
            $(this).map(function () {
                this.thumbnailSlider = new ThumbnailSlider(this, images);
                this.thumbnailSlider._buildSlider();
            });
        });
        return this;
    };

    /**
     * 
     * @returns {jQueryElementsObject}
     */
    $.fn.startSlider = function () {
        $(this).each(function () {
            if (this.thumbnailSlider) this.thumbnailSlider._startSlider();
        });
        return $(this);
    };

    /**
     * 
     * @param {Boolean} onlyPath
     */
    $.fn.getImage = function (onlyPath = true) {
        const images = [];
        if (onlyPath === true) {
            $(this).each(function () {
                if (this.thumbnailSlider) {
                    images.push(this.thumbnailSlider._getImage(onlyPath));
                }
            });
            return images;
        }
        return $(".imageSliderHeroImage");

    };
})(jQuery)

/**     End ThumbnailSlider */