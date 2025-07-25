import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    
    static targets = [
        'submitButton',
        'email',
        'firstname',
        'lastname',
        'street',
        'sn',
        'zip',
        'city',
        'at',
        'pw'
    ];

    submitButtonTargetConnected() {
        $(this.submitButtonTarget).on('click', () => {
            this.register_submit();
        });
    }

    register_submit() {
        
    }
}