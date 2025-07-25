import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {
    static targets = [
        'timer',
        'timerHeader',
        'submitButton',
        'specialReqs',
        'specialReqsCount',
    ];

    static values = {
        alignTimerLink: String,
        submitOrderLink: String,
        orderConfirmedLink: String,
        orderId: String,
    };

    clearIntervals() {
        if (typeof this.timerInterval !== 'undefined') clearInterval(this.timerInterval);
        if (typeof this.alignTimer !== 'undefined') clearInterval(this.alignTimer);
    }

    setExpired() {
        if (this.hasTimerHeaderTarget) {
            this.timerHeaderTarget.style.color = 'red';
            this.timerHeaderTarget.textContent = 'Order expired';
        }
    }

    connect() {
        if (this.hasTimerTarget) {
            this.timer = Number.parseFloat(this.timerTarget.textContent);
            this.timer *= 60;
            this.timerInterval = setInterval(() => {
                this.timer -= 5;
                if (this.timer >= 5) {
                    this.timerTarget.textContent = (this.timer / 60).toFixed(2);
                } else {
                    this.setExpired();
                    this.clearIntervals();
                }
            }, 5000);
            this.alignTimer = setInterval(() => {
                if (this.alignTimerLinkValue !== '') {
                    let orderId = this.orderIdValue !== ''
                        ? this.orderIdValue
                        :window.location.href.split('/').pop()
                        ;
                    $.ajax(`${this.alignTimerLinkValue}/${orderId}`)
                    .done((response) => {
                        this.timer = response;
                        this.timerTarget.textContent = (this.timer / 60).toFixed(2);
                    })
                    .fail(() => {
                        this.setExpired();
                        this.clearIntervals();
                    });
                }
                
            }, 60000);
        }
    }

    specialReqsCount() {
        if (this.hasSpecialReqsTarget && this.hasSpecialReqsCountTarget) {
            const len = $(this.specialReqsTarget).val().length;
            if (len <= 200) {
                $(this.specialReqsCountTarget).get(0).textContent = (200 - $(this.specialReqsTarget).val().length);
            } else {
                $(this.specialReqsTarget).val($(this.specialReqsTarget).val().slice(0, 200));
            }
        }
    }

    disconnect() {
        this.clearIntervals();
    }

    submitOrder() {
        const specialRequest = this.hasSpecialReqsTarget ? this.specialReqsTarget.value : '';
        $(this.submitButtonTarget).prop('disabled', true);
        let orderId = this.orderIdValue !== ''
                ? this.orderIdValue
                :window.location.href.split('/').pop()
                ;
        if (this.submitOrderLinkValue !== '' && orderId !== '') {
            $.ajax({
                url: this.submitOrderLinkValue,
                method: 'POST',
                data: {
                    orderId: orderId,
                    specialRequest: specialRequest,
                },
            }).done((responseData, textStatus, jqXHR) => {
                if (jqXHR.status === 200 && this.orderConfirmedLinkValue !== '') {
                    sessionStorage.clear();
                    Turbo.visit(`${this.orderConfirmedLinkValue}/${responseData}/${orderId}`);
                } else {
                    Swal.fire({
                        icon: "error",
                        title: 'Error: ' + jqXHR.status,
                        text: 'An unknown error occurred, please try again',
                    });
                }
            }).fail((jqXHR) => {
                const errorCode = (typeof jqXHR.status !== 'undefined' && jqXHR.status > 200)
                    ? jqXHR.status
                    : 500
                    ;
                const errorMsg = (typeof jqXHR.responseJSON !== 'undefined' && jqXHR.responseJSON !== '')
                    ? jqXHR.responseJSON
                    : 'An unknown error occured, please try again'
                    ; 
                Swal.fire({
                    icon: "error",
                    title: `Error: ${errorCode}`,
                    text: errorMsg,
                });
                $(this.submitButtonTarget).prop('disabled', false);
            });
            
        }
        
    }
}