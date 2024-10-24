import { Controller } from "@hotwired/stimulus";
import Swal from 'sweetalert2';

export default class extends Controller{

    login() {
        this.dispatch('login', {
            detail: {
                callback: this.checkLogin
            }
        })
    }

    checkLogin(elements) {
        return new Promise((resolve, reject) => {
            $.post('/validatelogin', {
                email: elements.email.value,
                password: elements.password.value
            }).done((response) => {
                resolve(response);
            }).fail((response) => {
                reject(response);
            });
        });
    }
}