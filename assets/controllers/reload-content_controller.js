import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = [
        'content'
    ];
    static values = {
        url: String,
        method: String,
        params: String,
        loader: String,
        display: String,
        errorMsgPrefix: String
    }

    connect()
    {
        
    }

    /**
     * Load the content from the value url
     *  and insert the response in the target content
     */

    reload() {
        let loader = null;
        if (this.loaderValue && this.loaderValue != '') {
            loader = $('#' + this.loaderValue);
            if (loader.length > 0) {
                loader.css('display', this.displayValue);
            }
        }
        $(this.contentTarget).css('opacity', '.5');
        
        let overflowBefore = $('body').css('overflow');
        $('body').css('overflow', 'hidden');
        $.ajax({
            type: this.methodValue || 'post',
            url: this.urlValue || '/',
            data: this.paramsValue || '',
            dataType: 'html'
        })
        .done((response) => {
            $(this.contentTarget).html(response);
        })
        .fail((response) => {
            const errorMsgPrefix = this.errorMsgPrefixValue || 'An error occured: ';
            $(this.contentTarget).html(
                `<h3 style="color: red;">` +
                    `${errorMsgPrefix} ${response}` +
                `</h3>`
            );
        });
        if (loader != null) loader.css('display', 'none');
        $('body').css('overflow', overflowBefore);
        $(this.contentTarget).css('opacity', '1.0');
    }
}