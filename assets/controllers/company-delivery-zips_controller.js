import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'resultContainer',
        'selectedContainer',
        'deliveryZips',
        'zipInput',
        'errorMsg',
    ];

    connect() {
        if (this.hasDeliveryZipsTarget) {
            const zips = this.deliveryZipsTarget.value;
            this.selectedZips = zips !== '[]' ? JSON.parse(zips) : {};
            for (const zip in this.selectedZips) {
                this.addSelectedZipElement(zip);
            }
        }
    }

    disconnect() {

    }

    clearResults() {
        const children = this.resultContainerTarget.children;
        let child = null;
        while ((child = children.item(0))) {
            $(child).off('click');
            this.resultContainerTarget.removeChild(child);
        }
    }

    updateDeliveryZipsInput() {
        this.deliveryZipsTarget.focus();
        this.deliveryZipsTarget.value = JSON.stringify(this.selectedZips);
        this.deliveryZipsTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.deliveryZipsTarget.dispatchEvent(new Event('change', { bubbles: true }));
        this.deliveryZipsTarget.blur();
    }

    addSelectedZipElement(zip) {
        this.selectedZips[zip] = zip;
        const selectedZipElement = document.createElement('DIV');
        const selectedZipElementText = document.createElement('SPAN');
        const selectedZipElementRemoveButton = document.createElement('SPAN');
        selectedZipElementText.innerText = zip;
        selectedZipElementRemoveButton.innerText = 'X';

        $(selectedZipElementRemoveButton).addClass('ml-1');

        $(selectedZipElementRemoveButton).on('click', () => {
            if (this.selectedContainerTarget.contains(selectedZipElement)) {
                $(selectedZipElementRemoveButton).off('click');
                this.selectedContainerTarget.removeChild(selectedZipElement);
                delete this.selectedZips[zip];
                this.updateDeliveryZipsInput();
            }
        });
        $(selectedZipElement).addClass('selectedZipElement');
        $(selectedZipElementText).addClass('selectedZipElementText');
        $(selectedZipElementRemoveButton).addClass('selectedZipElementRemoveButton');
        selectedZipElement.appendChild(selectedZipElementText);
        selectedZipElement.appendChild(selectedZipElementRemoveButton);
        this.selectedContainerTarget.appendChild(selectedZipElement);
    }

    selectResult(node) {
        if (node && node.nodeType === Node.ELEMENT_NODE) {
            const zip = $(node).attr('data-zip');
            if (typeof this.selectedZips[zip] === 'undefined') {
                this.addSelectedZipElement(zip);
                this.updateDeliveryZipsInput();
            }
        }
    }

    queryZips() {
        if (
            this.hasResultContainerTarget
            && this.hasSelectedContainerTarget
            && this.hasZipInputTarget
            && this.hasErrorMsgTarget
            && this.hasDeliveryZipsTarget
        ) {
            const zipValue = this.zipInputTarget.value;
            if (zipValue.length > 2) {
                const csrfTokenElement = $('[name="_csrf_token"]');
                if (csrfTokenElement.length > 0) {
                    const csrfToken = csrfTokenElement.first().val();
                    $.ajax({
                        url: '/zips',
                        data: {
                            zip: zipValue
                        },
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }).then((response) => {
                        try {
                            const suggests = JSON.parse(response);
                            this.clearResults();
                            suggests.forEach(match => {
                                const resElement = document.createElement('DIV');
                                const entry = Object.entries(match)[0];
                                if (entry) {
                                    const [k, v] = entry;
                                    resElement.innerText = `${k}: ${v}`;
                                    $(resElement).attr('data-zip', k);
                                    $(resElement).on('click', () => {
                                        this.selectResult(resElement);
                                        this.clearResults();
                                        this.zipInputTarget.value = '';
                                    })
                                }
                                this.resultContainerTarget.appendChild(resElement);
                                $(resElement).addClass('zip-dropdown-item');
                            });
                        } catch (e) {
                            this.errorMsgTarget.innerText = 'An error occured, please reload the page and try again';
                            $(this.errorMsgTarget).removeClass('d-none');
                            setTimeout(() => {
                                $(this.errorMsgTarget).addClass('d-none');
                            }, 5000);
                        }
                    });
                }
            } else {
                this.clearResults();
            }
        }
    }
}