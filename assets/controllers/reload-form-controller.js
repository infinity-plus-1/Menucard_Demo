import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = [
        'reloadLink'
    ];

    timerFunc = null;

    connect() {
        $('button[data-button-type="formSubmit"]').addClass('disabled-btn').attr('disabled', '');
        this.timerFunc = setTimeout(() => {
            this.reloadLinkTarget.click();
        }, 5000);
    }

    disconnect() {
        clearTimeout(this.timerFunc);
    }
}