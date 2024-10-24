import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    klicken() {
        $("body").append('<div data-controller="test" data-action="click->test#incr">You have clicked <span data-test-target="increment">0</span> times!</div>');
    }
}