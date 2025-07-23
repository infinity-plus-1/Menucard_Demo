import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    /**
     * Call this functions by a stimulus event to assign a callback - e.g.:
     * 
     * login_controller.js ->
     * login() {
     *   this.dispatch('login', {
     *       detail: {
     *           callback: this.checkLogin
     *       }
     *   })
     * }
     * 
     * <html element
     * data-controller="login(from example above) submit-modal"
     * {{
     *      stimulus_action(
     *          'login(controller from example above)',
     *          'login(the function to call in the controller)',
     *          'click(the event type)'
     *      )
     *      |
     *      stimulus_action(
     *          'submit-modal(this controller)',
     *          'defaultEmailLoginModal(or any other of the modal functions)',
     *          'login:login(the controller where the callback function is in:the event name)'
     *      )
     *      |
     *      stimulus_controller('submit-modal', {
     *          title: 'One variable to override the default one of this controller',
     *          text: 'Yet another variable to override'
     *          cancelBtnTxt: 'etc...'
     *      })
     * }}
     * >...</html element>
     * 
     * @param {Function} callback Async promise function
     *  (shared by event)
     * @param {Array|Object} callbackParams Parameters of the
     *  callback function (shared by event)
     * @param {String} title Title message of the modal, before confirmation
     * @param {String} text Message of the modal, before confirmation
     * @param {String} icon Icon that shall be displayed in the alert window
     * @param {Number} timer Time in ms for the alert to show up
     * @param {Boolean} showCancelButton True by default (false for disable)
     * @param {String} cancelBtnTxt Text of the cancel button
     * @param {String} cancelButtonColor Color of the cancelation button
     * @param {String} confirmBtnTxt Text of the conformation button
     * @param {String} confirmButtonColor Color of the confirmation button
     * @param {String} successIcon Icon of the success alert window
     * @param {String} successTitle Title message of the modal,
     *  when the confirmation process is successful
     * @param {String} successText Confirmation message of the modal,
     *  when the confirmation process is successful
     * @param {Number} successTimer Time in ms for the success alert to show up
     * @param {Boolean} showAbort If enabled, a succeeding alert window will appear
     *  if the user aborts (false by default)
     * @param {String} abortIcon Icon of the abort alert window
     * @param {String} abortTitle Title message of the modal,
     *  when the action is aborted
     * @param {String} abortText Confirmation message of the modal,
     *  when the action is aborted
     * @param {Number} abortTimer Time in ms for the abort alert to show up
     * @param {String} failureIcon Icon of the failure alert window
     * @param {String} failureTitle Title message of the modal,
     *  when the confirmation process is unsuccessful
     * @param {String} failureText Confirmation message of the modal,
     *  when the confirmation process is unsuccessful
     * @param {Number} failureTimer Time in ms for the failure alert to show up
     * @param {String} enterMailTxt Label for the email address input
     * @param {String} mailPlaceholder Placeholder in the email address input
     * @param {String} enterPasswordTxt Label in the password input
     * @param {String} missingText Error text if not all data are received
     *  the server
     * @param {String} unknownText Error text for unknown responses
     * @param {String} successResponse A custom response can be defined
     *  for the conditional if to trigger the logged in prompt ('Success' is default)
     * @param {String} failureResponse A custom response can be defined
     *  for the conditional if to show the failure error ('Failure' is default)
     * @param {String} incompleteResponse A custom response can be defined for the
     *  conditional if to show the incomplete error ('Incomplete' is default)
     */
    static values = {
        /* Common vars */
        callback: String,
        callbackParams: Object,
        title: String,
        text: String,
        timer: Number,
        showCancelButton: Boolean,
        cancelBtnTxt: String,
        cancelButtonColor: String,
        confirmBtnTxt: String,
        confirmButtonColor: String,
        successTitle: String,
        successText: String,
        successTimer: Number,
        failureIcon: String,
        failureTitle: String,
        failureText: String,
        failureTimer: Number,
        /* Confirm */
        showAbort: Boolean,
        abortIcon: String,
        abortTitle: String,
        abortText: String,
        abortTimer: Number,
        /* Login */ 
        enterMailTxt: String,
        mailPlaceholder: String,
        enterPasswordTxt: String,
        missingText: String,
        unknownText: String,
        successResponse: String,
        failureResponse: String,
        incompleteResponse: String
    };
    
    cacheFunc = null;

    connect() {
        this.cacheFunc = () => {
            $('html').removeClass('swal2-shown swal2-height-auto');
            $('.swal2-container').attr('data-turbo-temporary', '');
            $('body').removeClass('swal2-shown swal2-height-auto');
        }
        $(document).on('turbo:before-cache', this.cacheFunc);
            
    }

    disconnect() {
        $(document).off('turbo:before-cache', this.cacheFunc);
    }
    
    defaultConfirmModal(params) {
        if (params && params.detail && params.detail.callback) {
            this.callback = params.detail.callback;
            this.callbackParams = params.detail.callbackParams || {};
        } else {
            throw ('No callback function defined in defaultConfirmModal');
        }
        Swal.fire({
            title: this.titleValue || 'Are you sure?',
            text: this.text || 'Do you want to proceed?',
            icon: this.iconValue || "info",
            timer: this.timerValue || undefined,
            timerProgressBar: true,
            showCancelButton: this.showCancelButtonValue || true,
            confirmButtonColor: this.confirmButtonColorValue || "#2b800d",
            cancelButtonColor: this.cancelButtonColorValue || "#d33",
            cancelButtonText: this.cancelBtnTxtValue || 'Cancel',
            confirmButtonText: this.confirmBtnTxtValue || 'Confirm',
            preConfirm: () => {
                return this.callback(this.callbackParams);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: this.successTitleValue || 'Done',
                    text: this.successTextValue || 'Action successful!',
                    icon: this.successIconValue || "success",
                    timer: this.successTimerValue || undefined,
                    timerProgressBar: true
                });
            } else {
                if (this.showAbortValue == true) {
                    Swal.fire({
                        title: this.abortTitleValue || 'Aborted',
                        text: this.abortTextValue || 'Action aborted!',
                        icon: this.abortIconValue || "warning",
                        timer: this.abortTimerValue || undefined,
                        timerProgressBar: true
                    });
                }
            }
        }).catch((error) => {
            Swal.fire({
                title: this.failureTitleValue || 'An error occured',
                text: (this.failureTextValue || 'Action unsuccessful:') + ' ' + error,
                icon: this.failureIconValue || "error",
                timer: this.failureTimerValue || undefined,
                timerProgressBar: true
            });
        });
    }

    /**
     * Standard login prompt
     * @param {Array|Object} params Optional options for the callback function
     */

    defaultEmailLoginModal(params) {

        const DEFAULT_LOGIN_ERR_MSG = 5000;

        if (params && params.detail && params.detail.callback) {
            this.callback = params.detail.callback;
            this.callbackParams = params.detail.callbackParams || {};
        } else {
            throw ('No callback function defined in defaultConfirmModal');
        }
        const counter = $('[id^=email-login-input_').length;
        Swal.fire({
            title: this.titleValue || 'Login',
            html:
                `<form>` +
                    `<div>` +
                        `<label for="email-login-input_${counter}">` +
                            `${this.enterMailTxtValue || "Enter your E-Mail address"}` +
                        `</label>` +
                        `<input id="email-login-input_${counter}" ` +
                            `type="email" placeholder="` +
                            `${this.mailPlaceholderValue || "Your mail address"} " ` +
                            `name="email-login-input_${counter}" class="swal2-input" />` +
                    `</div>` +
                    `<BR /><BR />` +
                    `<div>` +
                        `<label for="password-login-input_${counter}">` +
                            `${this.enterPasswordTxtValue || "Enter your password"}` +
                        `</label>` +
                        `<input id="password-login-input_${counter}" ` +
                            `type="password" autocomplete="on" ` +
                            `name="password-login-input_${counter}" class="swal2-input" />` +
                    `</div>` +
                `</form>`, 
            focusConfirm: false,
            showCancelButton: this.showCancelButtonValue || true,
            confirmButtonColor: this.confirmButtonColorValue || "#3085d6",
            cancelButtonColor: this.cancelButtonColorValue || "#d33",
            cancelButtonText: this.cancelBtnTxtValue || 'Cancel',
            confirmButtonText: this.confirmBtnTxtValue || 'Login',
            preConfirm: () => {
                const email = document.getElementById(`email-login-input_${counter}`);
                const emailValue = email.value;
                const password = document.getElementById(`password-login-input_${counter}`);
                const passwordValue = password.value;
                let proceed = false;
                if (!validateEmail(emailValue)) {
                    Swal.showValidationMessage('No valid E-Mail address entered.');
                    return false;
                }
        
                if (!validatePassword(passwordValue)) {
                    Swal.showValidationMessage('No valid password entered.');
                    return false;
                }
        
                const elements = { email, password };
                return this.callback(elements, this.callbackParams).then((response) => {
                    if (response === (this.successResponseValue || 'Success')) {
                        return true;
                    }
                    
                    Swal.showValidationMessage(this.loginErrorMsgTimerValue || 'Unknown error, please try again later.');
                    return false;
                }).catch((response) => {
                    let msg = '';
                    switch(response) {
                        case (this.failureResponseValue || 'Failure'):
                            msg = this.failureTextValue || 'Login data wrong, please try again.';
                            break;
                        case (this.incompleteResponseValue || 'Incomplete'):
                            msg = this.missingTextValue || 'Not all login data sent';
                            break;
                        default:
                            msg = this.loginErrorMsgTimerValue || 'Unknown error, please try again later.';
                    }
                    Swal.showValidationMessage(msg);
                    setTimeout(() => {
                        Swal.resetValidationMessage();
                    }, (this.loginErrorMsgTimerValue || DEFAULT_LOGIN_ERR_MSG));
                    return false;
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((response) => {
            if (response.isConfirmed) {
                Swal.fire({
                    title: this.successTitleValue || 'Login successful',
                    text: this.successTextValue || 'You\'re logged in.',
                    icon: this.successIconValue || "success",
                    confirmButtonColor: this.confirmButtonColorValue || "#3085d6",
                    timer: this.successTimerValue || undefined,
                    timerProgressBar: true
                });
            }
        });
    }
}