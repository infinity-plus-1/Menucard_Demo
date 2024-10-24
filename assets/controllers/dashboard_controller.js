import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = [
        'dashboard'
    ];

    dashboardTargetConnected() {
        $('#dashboard_main_chart').load('/07e3b1546a627bb4f13a7b70ea00a71b7cd0be0d');
    }
}