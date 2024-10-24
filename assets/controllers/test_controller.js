import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [ 'increment' ];
    static values = {
        counter: Number
    };

    initialize() {
        this.counterValue = 0;
    }

    counterValueChanged() {
        this.incrementTarget.innerText = this.counterValue;
        console.log('Again, you changed this value to ' + this.counterValue);
    }

    incr() {
        this.counterValue = ++this.counterValue;
    }
}