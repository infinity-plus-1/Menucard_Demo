import { Controller } from '@hotwired/stimulus';

export default class extends Controller
{
    static values = {
        modalId: String,
        timeout: Number
    };
    connect() {
        if (this.modalIdValue !== '') {
            if ($(`#${this.modalIdValue}`).length > 0) {
                const timeout = this.timeoutValue ? this.timeoutValue : 0;
                setTimeout(() => {
                    $(`#${this.modalIdValue}`).modal('hide');
                }, timeout);
                
            }
        }
    }
}