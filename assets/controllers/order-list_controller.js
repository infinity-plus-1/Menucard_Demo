import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {

    async initialize() {
        this.component = await getComponent($('[data-live-name-value="OrderView"]').get(0));
        this.cancelButtonTimer();
        this.completeButtonTimer();
        this.observeChanges();
    }


    static targets = [
        'leftButton',
        'cancelButton',
        'completeButton',
        'datetime',
        'orderElement',
        'header',
    ];

    static values = {
        order: Number,
        cancelLink: String,
        completeLink: String,
    }

    observeChanges() {
        if (this.hasHeaderTarget) {
            this.observer = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    if (mutation.type === 'subtree' || mutation.type === 'childList' || mutation.type === 'characterData') {
                        this.cancelButtonTimer();
                        this.completeButtonTimer();
                    }
                }
            });

            //If components reload the content the headers will be replaced by the corresponding order id
            this.observer.observe(this.headerTarget, {
                subtree: true,  
                childList: true,
                characterData: true,
            });
        }
    }

    disconnect() {
        if (this.cancelTimer) {
            clearInterval(this.cancelTimer);
        }
    }

    cancelButtonTargetConnected() {
        //console.log('test');
    }

    changeMaxRes({params}) {
        if (typeof params.mRes !== 'undefined') {
            if (this.hasLeftButtonTarget) {
                this.leftButtonTarget.innerText = params.mRes;
            }
        }
    }

    sendRequest(url, linkElement) {
        const id = Number.parseInt(this.orderValue);
        if (Number.isInteger(id)) {
            const link = $(`#${linkElement}`);
            if (link.length > 0) {
                link.attr('href', `${url}${id}`);
                link[0].click();
            }
        }
    }

    view() {
        if (this.orderValue !== 0) {
            this.sendRequest('/order_view/', 'order-link');
            $('#order-frame').html('');
            $('#order-modal-header-content').html(`Order #${this.orderValue}`);
        }
    }

    cancelButtonTimer() {
        if (this.hasCancelButtonTarget && this.hasDatetimeTarget) {
            const orderCreated = new Date(this.datetimeTarget.innerText.replace(' ', 'T') + ':00Z');
            const now = new Date(Date.now());
            let remainingTime = 300 - Math.ceil((now - orderCreated) / 1000);
            
            if (remainingTime < 0) remainingTime = 0;
            clearTimeout(this.cancelTimer);
            this.cancelButtonTarget.disabled = false;

            if (this.hasCompleteButtonTarget) {
                this.completeButtonTarget.disabled = true;
            }
            
            console.log(remainingTime);
            this.cancelTimer = setTimeout(() => {
                this.cancelButtonTarget.disabled = true;
                console.log('IN HERE');
                console.log(this.cancelButtonTarget);
                if (this.hasCompleteButtonTarget) {
                    this.completeButtonTarget.disabled = false;
                }
            }, remainingTime*1000);
            console.log(this.cancelButtonTarget);
        }
        
    }

    completeButtonTimer() {
        if (this.hasCompleteButtonTarget && this.hasDatetimeTarget) {
            const orderCreated = new Date(this.datetimeTarget.innerText.replace(' ', 'T') + ':00Z');
            const now = new Date(Date.now());
            if (Math.ceil((now - orderCreated) / 1000) >= 300) {
                this.completeButtonTarget.disabled = false;
            }
        }
    }

    //type: 'complete' or 'cancel'
    handleAction(type) {
        const cancelButtonTheme = type === 'complete' ? 'danger' : 'success';
        const confirmButtonTheme = type === 'complete' ? 'success' : 'danger';
        const confirmButtonText = type === 'complete' ? 'Complete' : 'Cancel';
        const successTitleText = type === 'complete' ? 'Completed' : 'Cancelled';
        const infoText = type === 'complete' ? 'completed' : 'cancelled';
        const pathSegment = type === 'complete' ? this.completeLinkValue : this.cancelLinkValue;
        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
              cancelButton: `btn btn-${cancelButtonTheme}`,
              confirmButton: `btn btn-${confirmButtonTheme}`,
            },
            buttonsStyling: false
        });
        swalWithBootstrapButtons.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: `${confirmButtonText}`,
            cancelButtonText: "Abort",
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const url = `${pathSegment}/${this.orderValue}`;
                $.ajax({
                    method: 'post',
                    url: url,
                }).done((response, msg, xhr) => {
                    swalWithBootstrapButtons.fire({
                        title: `${successTitleText}`,
                        text: `Your order has been ${infoText}.`,
                        icon: "success"
                    });
                    const filterInput = $('#filterOrdersInput');
                    if (filterInput.length > 0) {
                        filterInput.get(0).dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }).fail((response) => {
                    if (
                        typeof response.status !== 'undefined'
                        && typeof response.responseJSON !== 'undefined'
                        && typeof response.responseJSON.message !== 'undefined'
                        && response.responseJSON.message !== ''
                    ) {
                        swalWithBootstrapButtons.fire({
                            title: `Error ${response.status}`,
                            text: response.responseJSON.message,
                            icon: "error"
                        });
                    } else {
                        swalWithBootstrapButtons.fire({
                            title: `Error 500`,
                            text: 'An unknown error occured.',
                            icon: "error"
                        });
                    }
                });
                
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                swalWithBootstrapButtons.fire({
                    title: "Aborted",
                    text: `Order has not been ${infoText}.`,
                    icon: "error"
                });
            }
        });
    }

    cancel() {
        if (this.cancelLinkValue !== '' && this.orderValue !== 0) {
            this.handleAction('cancel');
        }
    }

    complete() {
        if (this.completeLinkValue !== '' && this.orderValue !== 0) {
            this.handleAction('complete');
        }
    }

    rate() {
        if (this.orderValue !== 0) {
            this.sendRequest('/rateOrder/', 'order-rate');
            $('#order-rate-frame').html('');
            $('#order-rate-modal-header-content').html(`order #${this.orderValue}`);
        }
    }
}