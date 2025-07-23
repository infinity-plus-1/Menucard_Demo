import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {

    delete() {
        const alert = Swal.mixin({
            customClass: {
                confirmButton: "btn btn-danger",
                cancelButton: "btn btn-primary"
            },
            buttonsStyling: false
        });
        alert.fire({
            title: "Are you sure?",
            text: "Do you really want to delete your account?",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Delete",
            cancelButtonText: "Cancel",
            reverseButtons: true
          }).then((result) => {
            if (result.isConfirmed) {
                alert.fire({
                    title: "Are you really sure?",
                    text: "This action can't be reverted!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Delete",
                    cancelButtonText: "Cancel",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const tokenElement = $('[name="token_delete"]');
                        const oldPasswordElement = $('#user_form_oldPassword');
                        if (tokenElement.length === 1 && oldPasswordElement.length === 1) {
                            const token = tokenElement.val();
                            const oldPassword = oldPasswordElement.val();
                            $.ajax({
                                method: 'POST',
                                url: '/delete_user',
                                data: {userAccepted: true, token_delete: token, oldPassword: oldPassword}
                            }).done((test, test1, test2) => {
                                alert.fire({
                                    title: "Error",
                                    text: errorMsg,
                                    icon: "error"
                                });

                            }).fail((response) => {
                                const errorMsg = getJsonResponseMessage(response);
                                alert.fire({
                                    title: "Error",
                                    text: errorMsg,
                                    icon: "error"
                                });
                            });
                        }
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        alert.fire({
                            title: "Cancelled",
                            text: "Your account has not been deleted.",
                            icon: "info"
                        });
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                    alert.fire({
                    title: "Cancelled",
                    text: "Your account has not been deleted.",
                    icon: "info"
                });
            }
        });
    }
}