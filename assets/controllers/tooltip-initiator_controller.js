import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    connect() {
        const tooltipTriggerList = $('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length > 0) {
            if (this.tooltipListconst && Array.isArray(this.tooltipListconst) && this.tooltipListconst.length > 0) {
                this.tooltipListconst.forEach((el => el.dispose()));
            }
            
            this.tooltipListconst = [...tooltipTriggerList].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl));
        }
    }
}