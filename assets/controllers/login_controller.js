import { Controller } from "@hotwired/stimulus";
import Swal from 'sweetalert2';

export default class extends Controller {

    login() {
        this.dispatch('login', {
            detail: {
                callback: this.checkLogin
            }
        })
    }

    checkLogin(elements) {
        return new Promise((resolve, reject) => {
            let csrfToken = $('input[name="_csrf_token"]').first().val();
            $.post('/ajax-login', {
                email: elements.email.value,
                password: elements.password.value,
                _csrf_token: csrfToken
            }).done((response) => {
                if (response.status === 1) {
                    const frame = $('turbo-frame#navbar_dropdown');
                    if (frame && frame.length > 0) {
                        frame.get(0).reload();
                        window.location.href = window.location.href;
                    }
                }
                resolve('login_success');
            }).fail((response) => {
                reject('login_unsuccessful');
            });
        });
    }
}