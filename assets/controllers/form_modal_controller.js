import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = [
        'modal',
        'dialog',
        'title',
        'confirmBtn',
        'cancelBtn',
        'closeBtn',
        'content'
    ];

    static values = {
        title: String,
        content: String,
        modalWidth: String,
        confirmBtn: String,
        confirmColor: String,
        cancelBtn: String,
        cancelColor: String,
        colorMultiplier: String,
        confirmFunc: String
    }

    modal = null;
    cacheFunc = null;

    modalTargetConnected() {
        let colorMultiplier = parseFloat(this.colorMultiplierValue) || .75;
        if (this.titleValue) {
            $(this.titleTarget).text(this.titleValue);
        }

        if (this.hasConfirmBtnTarget && this.confirmBtnValue) {
            $(this.confirmBtnTarget).text(this.confirmBtnValue);
        }
        if (this.hasConfirmBtnTarget && this.confirmColorValue) {
            let changedColor = colorBrightness(this.confirmColorValue, colorMultiplier);
            $(this.confirmBtnTarget).css({
                '--bs-btn-bg': this.confirmColorValue,
                '--bs-btn-border-color': this.confirmColorValue,
                '--bs-btn-hover-bg': changedColor,
                '--bs-btn-hover-border-color': changedColor
            });
        }
        if (this.hasCancelBtnTarget && this.cancelBtnValue) {
            $(this.cancelBtnTarget).text(this.cancelBtnValue);
        }
        if (this.hasCancelBtnTarget && this.cancelColorValue) {
            let changedColor = colorBrightness(this.cancelColorValue, colorMultiplier);
            $(this.cancelBtnTarget).css({
                '--bs-btn-bg': this.cancelColorValue,
                '--bs-btn-border-color': this.cancelColorValue,
                '--bs-btn-hover-bg': changedColor,
                '--bs-btn-hover-border-color': changedColor
            });
        }
        if (this.modalWidthValue) {
            $(this.dialogTarget).css('--bs-modal-width', this.modalWidthValue);
        }
        this.cacheFunc = () => {
            if (this.modal) this.modal.hide();
            $('.modal-backdrop.fade.show').remove();
            $('body').removeClass('modal-open').css({
                'padding-right': '',
                'overflow': ''
            });
            $('.modal.fade.show').removeClass('show').css('display', 'none');
            
        }
        $(document).on('turbo:before-cache', this.cacheFunc);
        
    }

    disconnect() {
        $(document).off('turbo:before-cache', this.cacheFunc);
    }

    openModal() {
        if (this.contentValue === '') {
            return;
        }
        const _modal = new Modal(this.modalTarget);
        this.modal = _modal;
        $(this.contentTarget).html('<h3>Loading...</h3>');
        $.ajax({
            method: 'POST',
            url: this.contentValue,
            data: { width: $(window).width() },
        }).done((response) => {
            $(this.contentTarget).html(response);
            this.modal.show();
        }).fail((response, status, xhr) => {
            $(this.contentTarget).html (
                '<h3 class="error">' +
                    'An error occured while trying to fetch the page:<BR />' +
                    xhr.status + " " + xhr.statusText +
                '</h3>'
            );
        });
    }

    // openModal() {
    //     const _modal = new Modal(this.modalTarget);
    //     this.modal = _modal;
    //     $(this.contentTarget).html('<h3>Loading...</h3>')
    //     .load((this.contentValue || ''), {
    //         fetchForm: 1
    //     }, (response, status, xhr) => {
    //         if (status === 'error') {
    //             $(this.contentTarget).html (
    //                 '<h3 class="error">' +
    //                     'An error occured while trying to fetch the page:<BR />' +
    //                     xhr.status + " " + xhr.statusText +
    //                 '</h3>'
    //             );
    //         } else if (status === 'success') {
    //             $(this.contentTarget).html(response);
    //             this.modal.show();
    //         } else {
    //             $(this.contentTarget).html (
    //                 '<h3 class="error">' +
    //                     'An unknown error occured.<BR />' +
    //                     'Please try again later.' +
    //                 '</h3>'
    //             );
    //         }
    //     })
    // }

    closeModal() {
        $('.modal.fade.show').removeClass('show').css('display', 'none');
        $('.modal-backdrop.fade.show').remove();
    }
}