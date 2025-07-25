import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { Tooltip } from 'bootstrap';
import Swal from 'sweetalert2';

export default class extends Controller {

    static values = {
        openModalLink: String,
        addExtraLink: String,
        manageGroupLink: String,
        deleteGroupLink: String,
        deleteExtraLink: String,
        groups: Object,
        extras: Object,
        dish: Number,
    };

    static targets = [
        'selectedContainer',
        'extras',
        'modalLabel',
        'modalBody',
        'addGroupInput',
        'extraNamesSuggestion',
        'extraNameInput',
        'extraPriceInput',
        'multiExtraType',
        'singleExtraType',
        'groupSelected',
        'selectGroupContainer',
        'addGroupButton',
        'saveGroupButton',
        'abortSaveGroupButton',
        'submitBtn',
        'extrasMultiContainer',
        'extrasSingleContainer',
    ];

    resetModalBody() {
        if (this.hasModalBodyTarget) {
            this.modalBodyTarget.innerHTML = 'Loading...';
        }
    }

    /**
     * this.groups contains all groups and every group contains it's single-select extras
     * this.extras only contains all multi-select extras
     * That said, we need to search every object on it's own to find all extras
     */

    connect() {
        this.modal = new Modal('#dish-extras-modal', {
            keyboard: false
        });
        this.modalElement = $('#dish-extras-modal');
        this.closeModalFunc = this.closeModal.bind(this);
        this.addExtraFunc = this.addExtra.bind(this);
        if (this.modalElement.length === 1) {
            this.modalElement.get(0).addEventListener('hidden.bs.modal', this.resetModalBody.bind(this));
        }
        this.groups = this.groupsValue;
        this.extras = this.extrasValue;

        this._renderExtrasAndGroups();
        this.boundExtraModalHandler = this._extraModalHandler.bind(this);
    }

    disconnect() {
        if (this.modalElement.length === 1) {
            this.modalElement.get(0).removeEventListener('hidden.bs.modal', this.resetModalBody.bind(this));
        }
    }

    /** Obverse extra suggestions */

    extraNamesSuggestionTargetConnected() {
        this.extraSuggestionsObserver = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === 'subtree' || mutation.type === 'childList') {
                    if ($(this.extraNamesSuggestionTarget).children().length > 0) {
                        $(this.extraNamesSuggestionTarget).removeClass('d-none');
                    } else {
                        $(this.extraNamesSuggestionTarget).addClass('d-none');
                    }
                }
            }
        });
        this.extraSuggestionsObserver.observe(this.extraNamesSuggestionTarget, { subtree: true, childList: true });
    }

    extraNamesSuggestionTargetDisconnected() {
        if (this.extraSuggestionsObserver) {
            this.extraSuggestionsObserver.disconnect();
        }
    }

    /** Close modal */

    closeModal() {
        if (this.modal) {
            const modal = $('#dish-modal');
            const extrasModal = $('#dish-extras-modal');
            if (modal.length === 1 && extrasModal.length === 1) {
                modal.removeClass('invisible');
                extrasModal.removeClass('visible');
            }
            this.modal.hide();
        }
        if (this.hasSubmitBtnTarget) {
            $(this.submitBtnTarget).off('click', this.closeModalFunc).off('click', this.addExtraFunc).text('OK');
        }
    }

    /** Managing groups */

    openGroupModal(edited = false) {
        if (this.modal && this.manageGroupLinkValue !== '') {
            $.ajax({
                method: 'POST',
                url: this.manageGroupLinkValue,
                data: {dish: this.dishValue, groups: JSON.stringify(this.groups) },
            }).then((response) => {
                $(document).on('turbo:post-render', this.boundExtraModalHandler);
                Turbo.renderStreamMessage(response);
                if (this.hasModalLabelTarget) {
                    this.modalLabelTarget.innerText = 'Manage groups';
                }
                if (this.hasSubmitBtnTarget) {
                    $(this.submitBtnTarget).on('click', this.closeModalFunc).text('Confirm');
                }

                const modal = $('#dish-modal');
                const extrasModal = $('#dish-extras-modal');
                if (modal.length === 1 && extrasModal.length === 1) {
                    modal.addClass('invisible');
                    extrasModal.addClass('visible');
                }
            }).then((response) => {
    
            });
        }
    }

    manageGroups() {
        if (
            this.hasAddGroupInputTarget
            && this.hasAddGroupButtonTarget
            && this.hasSaveGroupButtonTarget
            && this.hasAbortSaveGroupButtonTarget
        ) {
            this.addGroupButtonTarget.style.display = 'none';
            this.addGroupInputTarget.style.display = 'inline';
            this.saveGroupButtonTarget.style.display = 'inline';
            this.abortSaveGroupButtonTarget.style.display = 'inline';
        }
    }

    abortAddGroup() {
        if (
            this.hasAddGroupInputTarget
            && this.hasAddGroupButtonTarget
            && this.hasSaveGroupButtonTarget
            && this.hasAbortSaveGroupButtonTarget
        ) {
            this.addGroupButtonTarget.style.display = 'inline';
            this.addGroupInputTarget.style.border = '';
            this.addGroupInputTarget.style.display = 'none';
            this.saveGroupButtonTarget.style.display = 'none';
            this.abortSaveGroupButtonTarget.style.display = 'none';
        }
    }

    saveGroup() {
        this.addGroup().then((state) => {
            if (state === true) {
                this.abortAddGroup();
                Swal.fire({
                    icon: "success",
                    title: "Group saved",
                    text: `The group has been successfully created. It will be saved together with the dish or removed if you leave the page.`,
                    timer: 5000,
                    timerProgressBar: true,
                });
                this.openGroupModal(true);
                this._renderExtrasAndGroups();
            }
        });
    }

    async addGroup() {
        if (this.hasAddGroupInputTarget) {
            if (this.groups.length >= 4) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `Only four groups at once can be created per dish.`,
                });
                return false;
            }

            const name = this.addGroupInputTarget.value;
            if (name !== '' && name.length <= 30) {
                if (typeof this.groups[name] === 'undefined') {
                    const group = {
                        group: {
                            name: this.addGroupInputTarget.value,
                            identifier: 0,
                        },
                        extras: {},
                    };
                    this.groups[name] = group;
                    this._updateExtraInput();
                    return true;
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `A group with the name ${name} already exists.`,
                    });
                    return false;
                }
            } else {
                if (name === '') {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `The name field can't be empty.`,
                    });
                } else if (name.length > 30) {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `The value of the name field can't be longer than 30 characters.`,
                    });
                }
                this.addGroupInputTarget.style.border = '1px solid red';
                return false;
            }
        }
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: `An unknown error occured.`,
        });
        return false;
    }

    editGroup(params) {
        if (params && typeof params.params !== 'undefined' && typeof params.params.editGroupContainer !== 'undefined') {
            const container = $(`#${params.params.editGroupContainer}`);
            if (container.length === 1) {
                container.removeClass('d-none');
            }
        }
    }

    saveEditGroup(params) {
        if (
            params
            && typeof params.params !== 'undefined'
            && typeof params.params.editGroupInput !== 'undefined'
            && typeof params.params.identifier !== 'undefined'
        ) {
            const input = $(`#${params.params.editGroupInput}`);
            const identifier = params.params.identifier;
            if (input.length === 1 && identifier !== '') {
                const name = input.val();
                if (name === '') {
                    input.addClass('red-border');
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `The name field can't be empty.`,
                    });
                    return false;
                }
                if (name.length > 30) {
                    input.addClass('red-border');
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `The value of the name field can't be longer than 30 characters.`,
                    });
                    return false;
                }

                if (typeof this.groups[identifier] !== 'undefined') {
                    this.groups[name] = this.groups[identifier];
                    this.groups[name].group.name = name;
                    delete this.groups[identifier];
                    this._updateExtraInput();
                    this._renderExtrasAndGroups();
                    Swal.fire({
                        icon: "success",
                        title: "Group saved",
                        text: `The group has been successfully renamed.`,
                        timer: 5000,
                        timerProgressBar: true,
                    });
                    this.openGroupModal();
                    return true;
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `A group with the name ${identifier} has not been found.`,
                    });
                    return false;
                }
            }
        }
    }

    cancelEditGroup(params) {
        if (
            params
            && typeof params.params !== 'undefined'
            && typeof params.params.editGroupContainer !== 'undefined'
            && typeof params.params.editGroupInput !== 'undefined'
        ) {
            const container = $(`#${params.params.editGroupContainer}`);
            const input = $(`#${params.params.editGroupInput}`);
            if (container.length === 1 && input.length === 1) {
                container.addClass('d-none');
                input.val('');
                input.removeClass('red-border');
            }
        }
        
        
    }

    deleteGroup(params) {
        if (
            params
            && typeof params.params !== 'undefined'
            && typeof params.params.identifier !== 'undefined'
            && typeof params.params.name !== 'undefined'
        ) {
            const identifier = params.params.identifier;
            const name = params.params.name;
            const dish = typeof params.params.dish !== 'undefined' ? params.params.dish : null;

            function _deleteLocally() {
                Swal.fire({
                    icon: "success",
                    title: "Group removed",
                    text: `The group has been successfully deleted.`,
                    timer: 5000,
                    timerProgressBar: true,
                });
                delete this.groups[name];
                this._renderExtrasAndGroups();
                this.openGroupModal();
            }

            const deleteLocally = _deleteLocally.bind(this);

            if (typeof this.groups[name] === 'undefined') {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `A group with the name ${identifier} has not been found.`,
                });
                return false;
            }

            Swal.fire({
                title: "Are you sure?",
                text: "This will delete all extras associated with that group automatically.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    if (
                        dish
                        && identifier
                        && this.deleteGroupLinkValue !== ''
                        && typeof this.groups[name] !== 'undefined'
                    ) {
                        $.ajax({
                            method: 'POST',
                            url: this.deleteGroupLinkValue,
                            data: {group: identifier, dish: dish},
                        }).done(() => {
                            deleteLocally();
                            return true;
                        }).fail((response) => {
                            if (
                                response
                                && typeof response.status !== 'undefined'
                                && typeof response.responseJSON !== 'undefined'
                                && typeof response.responseJSON.message !== 'undefined'
                                && response.responseJSON.message !== ''
                            ) {
                                Swal.fire({
                                    title: `Error ${response.status}`,
                                    text: response.responseJSON.message,
                                    icon: "error"
                                });
                            } else {
                                Swal.fire({
                                    title: "Error",
                                    text: "An unknown error occured while trying to delete the group.",
                                    icon: "error"
                                });
                            }
                        });
                    } else if (
                        identifier === 0
                        && name !== ''
                        && typeof this.groups[name] !== 'undefined'
                    ) {
                        deleteLocally();
                        return true;
                    } else {
                        Swal.fire({
                            title: "Error",
                            text: "The group could not be deleted due to an unknown error.",
                            icon: "error"
                        });
                    }
                  
                }
            });
        }
    }

    /** End managing groups */

    _extraModalHandler() {
        const tooltipTriggerList = $('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length > 0) {
            if (this.tooltipListconst && Array.isArray(this.tooltipListconst) && this.tooltipListconst.length > 0) {
                this.tooltipListconst.forEach((el => el.dispose()));
            }
            
            this.tooltipListconst = [...tooltipTriggerList].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl));
        }
        this.modal.show();
        $(document).off('turbo:post-render', this.boundExtraModalHandler);
    }

    openExtraModal() {
        if (this.modal && this.addExtraLinkValue !== '') {
            $.ajax({
                method: 'POST',
                url: this.addExtraLinkValue,
                data: {dish: this.dishValue, groups: JSON.stringify(this.groups) },
            }).then((response) => {
                this.extraName = '';
                $(document).on('turbo:post-render', this.boundExtraModalHandler);
                Turbo.renderStreamMessage(response);
                if (this.hasModalLabelTarget) {
                    this.modalLabelTarget.innerText = 'Add or remove extras';
                }
                if (this.hasSubmitBtnTarget) {
                    $(this.submitBtnTarget).on('click', this.addExtraFunc).text('Add');
                }

                const modal = $('#dish-modal');
                const extrasModal = $('#dish-extras-modal');
                if (modal.length === 1 && extrasModal.length === 1) {
                    modal.addClass('invisible');
                    extrasModal.addClass('visible');
                }
            }).then((response) => {
    
            });
        }
        if (this.hasAddGroupInputTarget) {
            this.addGroupInputTarget.value = '';
            this.groupModal.show();
        }
    }

    closeGroupModal() {
        if (this.groupModal) {
            this.groupModal.hide();
        }
    }

    

    setExtraType(event) {
        if (event && typeof event.params !== 'undefined' && typeof event.params.type !== 'undefined') {
            if (event.params.type === 'single' && (typeof this.groups === 'undefined' || Object.keys(this.groups) < 1)) {
                Swal.fire({
                    title: "Error",
                    text: "No available groups for this dish. Please close the modal and add a group first.",
                    icon: "error"
                });
                event.preventDefault();
                if (this.hasMultiExtraTypeTarget) {
                    this.multiExtraTypeTarget.checked = true;
                }
                return;
            }
            this.extraType = event.params.type === 'single' ? 'single' : 'multi';
            if (this.hasSelectGroupContainerTarget) {
                if (this.extraType === 'single') {
                    this.selectGroupContainerTarget.style.display = 'inline';
                } else {
                    this.selectGroupContainerTarget.style.display = 'none';
                }
            }
        }
    }

    autoFillExtraName(params) {
        if (params && typeof params.params !== 'undefined' && typeof params.params.name !== 'undefined') {
            this.extraName = params.params.name;
            if (this.hasExtraNamesSuggestionTarget) {
                this.extraNamesSuggestionTarget.innerHTML = '';
            }
            if (this.hasExtraNameInputTarget) {
                this.extraNameInputTarget.value = this.extraName;
            }
        }
    }

    selectGroup(params) {
        if (params && typeof params.params !== 'undefined' && typeof params.params.group !== 'undefined') {
            this.selectedGroup = params.params.group;
            if (this.hasGroupSelectedTarget) {
                this.groupSelectedTarget.innerText = params.params.group;
            }
        }
        if (params && typeof params.params !== 'undefined' && typeof params.params.id !== 'undefined') {
            const dropdownElement = $(`#${params.params.id}`);
            if (dropdownElement.length === 1) {
                const dropdownElements = $('.groupSelectElement');
                if (dropdownElements.length > 0) {
                    dropdownElements.removeClass('active');
                }
                dropdownElement.addClass('active');
            }
        }
    }

    _updateExtraInput() {
        if (this.hasExtrasTarget) {
            const allExtras = {
                multiExtras: {},
                singleExtras: {},
            };
            allExtras.multiExtras = this.extras;
            allExtras.singleExtras = this.groups;
            this.extrasTarget.focus();
            $(this.extrasTarget).val(JSON.stringify(allExtras));
            this.extrasTarget.dispatchEvent(new Event('input', { bubbles: true }));
            this.extrasTarget.dispatchEvent(new Event('change', { bubbles: true }));
            this.extrasTarget.blur();
        }
    }

    deleteExtra(extra, container, divElement, removeBtn, isMulti, group) {
        const _removeExtraLocally = () => {
            if (isMulti) {
                if (typeof this.extras !== 'undefined' && typeof this.extras[extra.name] !== 'undefined') {
                    delete this.extras[extra.name];
                    container.removeChild(divElement);
                }
            } else {
                if (
                    typeof this.groups !== 'undefined'
                    && typeof this.groups[group] !== 'undefined'
                    && typeof this.groups[group].extras !== 'undefined'
                    && typeof this.groups[group].extras[extra.name] !== 'undefined'
                ) {
                    delete this.groups[group].extras[extra.name];
                    container.removeChild(divElement);
                }
            }
            this._updateExtraInput();
        }
        if (this.dishValue > 0 && extra.identifier > 0 && this.deleteExtraLinkValue !== '') {
            return $.ajax({
                url: this.deleteExtraLinkValue,
                method: 'POST',
                data: { dish: this.dishValue, extra: extra.identifier },
            }).done(() => {
                $(removeBtn).off('click');
                _removeExtraLocally();
            }).fail((response) => {
                if (
                    response
                    && typeof response.status !== 'undefined'
                    && typeof response.responseJSON !== 'undefined'
                    && typeof response.responseJSON.message !== 'undefined'
                    && response.responseJSON.message !== ''
                ) {
                    Swal.fire({
                        title: `Error ${response.status}`,
                        text: response.responseJSON.message,
                        icon: "error"
                    });
                } else {
                    Swal.fire({
                        title: "Error",
                        text: "An unknown error occured while trying to delete the extra.",
                        icon: "error"
                    });
                }
                return false;
            });
        } else if (extra.identifier === 0) {
            _removeExtraLocally();
        }
        return false;
    }

    _createExtraElement(extra, container, isMulti, group = '') {
        const divElement = document.createElement('DIV');
        const textElement = document.createElement('SPAN');
        const removeBtn = document.createElement('SPAN');
        textElement.innerText = `${extra.name} : +${extra.price}$`;
        removeBtn.innerText = 'X';

        $(divElement).addClass('mb-1');
        $(removeBtn).addClass('ml-1');

        $(removeBtn).on('click', () => {
            if (container.contains(divElement)) {
                this.deleteExtra(extra, container, divElement, removeBtn, isMulti, group);
            }
        });
        $(divElement).addClass('selectedZipElement');
        $(textElement).addClass('selectedZipElementText');
        $(removeBtn).addClass('selectedZipElementRemoveButton');
        divElement.appendChild(textElement);
        divElement.appendChild(removeBtn);
        container.appendChild(divElement);
    }

    _addExtraToContainer(extra, isMulti, group = '') {
        if (isMulti) {
            if (this.hasExtrasMultiContainerTarget) {
                this._createExtraElement(extra, this.extrasMultiContainerTarget, true);
            }
        } else {
            const cleanedGroupName = group.replace(' ', '');
            const container = $(`#extras-single-group-${cleanedGroupName}`);
            if (container.length === 1) {
                this._createExtraElement(extra, container.get(0), false, group);
            } else {
                if (this.hasExtrasSingleContainerTarget) {
                    const div = document.createElement('DIV');
                    div.id = `extras-single-group-${cleanedGroupName}`;
                    $(div).addClass('mb-3');
                    const header = document.createElement('H5');
                    header.innerText = group;
                    $(header).addClass('text-center');
                    $(header).addClass('fw-bold');
                    const hr = document.createElement('HR');
                    div.appendChild(header);
                    div.appendChild(hr);
                    this._createExtraElement(extra, div, false, group);
                    this.extrasSingleContainerTarget.appendChild(div);
                }
            }
        }
    }

    _countExtras(extras) {
        let extrasCount = 0;

        for (const key in extras) {
            if (Object.prototype.hasOwnProperty.call(extras, key)) {
                const extra = extras[key];
                if (typeof extra.deleted === 'undefined' || extra.deleted !== true) {
                    extrasCount++;
                }
            }
        }

        return extrasCount;
    }

    addExtra() {
        if (
            this.hasExtraNameInputTarget
            && this.hasExtraPriceInputTarget
            && this.hasMultiExtraTypeTarget
            && this.hasSingleExtraTypeTarget
        ) {
            const name = $(this.extraNameInputTarget).val();
            const price = Number.parseFloat($(this.extraPriceInputTarget).val());
            const multiRadioBtn = this.multiExtraTypeTarget;
            const singleRadioBtn = this.singleExtraTypeTarget;
            let extra = {
                price: 0.0,
                name: '',
                identifier: 0,
                deleted: false,
            };

            if (Number.isNaN(price)) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `Price must represent a number between 0 and 99.99.`,
                });
                return false;
            }

            price.toPrecision(2);

            if (price < 0 || price > 99.99) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `Price must represent a number between 0 and 99.99.`,
                });
                return false;
            }

            $(this.extraNameInputTarget).removeClass('red-border');
            if (name === '') {
                $(this.extraNameInputTarget).addClass('red-border');
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `The value of the name field can't be empty.`,
                });
                return false;
            } else if (name.length > 30) {
                $(this.extraNameInputTarget).addClass('red-border');
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `The value of the name field can't be longer than 30 characters.`,
                });
                return false;
            }

            extra.price = price.toFixed(2);
            extra.name = name;



            if (multiRadioBtn.checked) {
                if (typeof this.extras[name] !== 'undefined') {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An extra with the name ${name} already exists.`,
                    });
                    return false;
                }

                if (this._countExtras(this.extras) > 40) {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `You can't have more than 40 multi-selectable extras per dish.`,
                    });
                    return false;
                }

                this.extras[name] = extra;
                this._addExtraToContainer(extra, true);
                this._updateExtraInput();

                return true;
            } else if (singleRadioBtn.checked && this.hasGroupSelectedTarget) {
                const selectedGroup = $(this.groupSelectedTarget).text();
                if (typeof this.groups[selectedGroup] === 'undefined') {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `The group with the name ${selectedGroup} could not be found.`,
                    });
                    return false;
                }
                const group = this.groups[selectedGroup];
                if (typeof group.extras === 'undefined') {
                    group.extras = {};
                }
                if (typeof group.extras[name] !== 'undefined') {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `An extra with the name ${name} already exists.`,
                    });
                    return false;
                }

                if (this._countExtras(group.extras) > 10) {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: `You can't have more than 10 single-selectable extras per group.`,
                    });
                    return false;
                }

                group.extras[name] = extra;
                this._addExtraToContainer(extra, false, selectedGroup);
                this.groups[selectedGroup] = group;
                this._updateExtraInput();
                return true;
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: `An unknown error occured.`,
                });
                return false;
            }
        }
    }

    _renderExtrasAndGroups() {
        if (this.hasExtrasMultiContainerTarget && this.hasExtrasSingleContainerTarget) {
            this.extrasMultiContainerTarget.innerHTML = '';
            this.extrasSingleContainerTarget.innerHTML = '';
            for (const key in this.extras) {
                if (Object.prototype.hasOwnProperty.call(this.extras, key)) {
                    const extra = this.extras[key];
                    this._addExtraToContainer(extra, true);
                    this._updateExtraInput();
                }
            }
    
            for (const key in this.groups) {
                if (Object.prototype.hasOwnProperty.call(this.groups, key)) {
                    const group = this.groups[key];
                    if (typeof group.group !== 'undefined' && typeof group.group.name !== 'undefined') {
                        const name = group.group.name;
                        if (typeof group.extras !== 'undefined') {
                            for (const _key in group.extras) {
                                if (Object.prototype.hasOwnProperty.call(group.extras, _key)) {
                                    const extra = group.extras[_key];
                                    this._addExtraToContainer(extra, false, name);
                                    this._updateExtraInput();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}