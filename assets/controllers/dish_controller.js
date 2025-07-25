import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {

    static values = {
        id: Number,
    };

    sendRequest(url) {
        if (this.idValue) {
            const id = Number.parseInt(this.idValue);
            if (Number.isInteger(id)) {
                const link = $('#dish-link');
                if (link.length > 0) {
                    link.attr('href', `${url}${id}`);
                    link[0].click();
                }
            }
        }
    }

    view() {
        this.sendRequest('/dish/view/');
        $('#dish-frame').html('');
        $('#dish-modal-header-content').html('Preview dish');
    }

    edit() {
        this.sendRequest('/dish/edit/');
        $('#dish-modal-header-content').html('Edit dish');
    }
    
    delete() {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $(`#delete-dish-${this.idValue}`);
                if (btn.length > 0) {
                    btn[0].click();
                    $(window).on('dish_deleted', function (response) {
                        let message = 'Internal server error';
                        let status = 2;
                        if (response && response.originalEvent) {
                            if (response.originalEvent.detail) {
                                if (response.originalEvent.detail.message && response.originalEvent.detail.status) {
                                    message = response.originalEvent.detail.message;
                                    status = response.originalEvent.detail.status;
                                }
                            }
                        }
                        const icon = status === 1 ? 'success' : 'error';
                        const title = status === 1 ? 'Deleted' : 'Error';
                        Swal.fire({
                            title: title,
                            text: message,
                            icon: icon
                        });
                    });
                }
            }
        });
    }
}