import { Controller } from "@hotwired/stimulus";
import Swal from 'sweetalert2';

export default class extends Controller {
    logout () {
        const csrfTokenElement = $('input[name="_csrf_token"]');
        let csrfToken = csrfTokenElement.get(0).value;
        $.post('/ajax-logout', {
            _csrf_token: csrfToken
        }).done((response) => {
            if (typeof response.status !== 'undefined' && response.status === 1) {
                const frame = $('turbo-frame#navbar_dropdown');
                if (frame && frame.length > 0) {
                    frame.get(0).reload();
                }

                if (typeof response.csrf_token !== 'undefined' && response.csrf_token !== '') {
                    csrfTokenElement.get(0).value = response.csrf_token;
                }
                Swal.fire({
                    icon: 'success',
                    text: "You've been logged out successfuly.",
                    timer: 2000,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                }).then(() => {
                    Turbo.visit('/');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    text: "Log out was unsuccessful. Please reload the page and try again.",
                    timer: 2000,
                    timerProgressBar: true
                });
            }
        }).fail((response) => {
            Swal.fire({
                icon: 'error',
                text: "Log out was unsuccessful. Please reload the page and try again.",
                timer: 2000,
                timerProgressBar: true
            });
        });
    }
}