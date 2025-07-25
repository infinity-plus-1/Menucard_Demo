import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {

    async initialize() {
        this.component = await getComponent($('[data-live-name-value="OrderView"]').get(0));
        this.checkProximity();
        
        $(window).on('resize', () => {
            this.checkProximity();
        });
    }

    static targets = [
        'proximityValue',
    ];

    chopTextToFit(element) {
        if (element.target === 'undefined') {
            return;
        }

        const node = element.target;
        const id = node.id;
        /**
         * Binary search
         * https://www.geeksforgeeks.org/binary-search/
         */
        node.textContent = this.originalTexts[id];
        let width = node.offsetWidth;
        let outerWidth = node.scrollWidth;
        let text = node.textContent;
        let right = text.length;
        let left = 0;
        let mid = 0;

        if (outerWidth > width) {
            while (left < right) {
                mid = Math.floor((right+left) >> 1);
                node.textContent = text.slice(0, mid) + '...';
                if (outerWidth > width) {
                    right = mid - 1;
                } else {
                    left = mid + 1;
                    
                }
                width = node.offsetWidth;
                outerWidth = node.scrollWidth;
            }
        }

        let height = node.offsetHeight;
        let outerHeight = node.scrollHeight;

        if (outerHeight > height) {
            text = node.textContent;
            right = text.length;
            left = 0;
            mid = 0;
            while (left < right) {
                mid = Math.floor((right+left) >> 1);
                node.textContent = text.slice(0, mid) + '...';
                if (outerHeight > height) {
                    right = mid - 1;
                } else {
                    left = mid + 1; 
                }
                height = node.offsetHeight;
                outerHeight = node.scrollHeight;
            }
        }
    }

    connect() {

        this.originalTexts = [];

        this.resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                this.chopTextToFit(entry);
            }
        });

        const resizeMonitorObserver = (node) => {
            if (node && node.nodeType === Node.ELEMENT_NODE) {
                const textElement = $(node).children('p.dish-card-desc');
                if (textElement.length > 0) {
                    const node = textElement.get(0);
                    this.originalTexts[node.id] = node.textContent;
                    this.resizeObserver.observe(node);
                }
            }
        }

        Array.from(this.element.children).forEach((node) => {
            this.originalTexts[node.id] = node.textContent;
            resizeMonitorObserver(node);
        });

        this.mutationObserver = new MutationObserver((entries) => {
            for (const entry of entries) {
                resizeMonitorObserver(entry);
            }
        });

        this.mutationObserver.observe(this.element, {childList: true});
        
    }

    disconnect() {
        if (this.mutationObserver !== 'undefined') {
            this.mutationObserver.disconnect();
        }
        if (this.mutationObserver !== 'undefined') {
            this.resizeObserver.disconnect();
        }

        $(window).off('resize');
    }

    checkProximity() {
        if (this.hasProximityValueTarget) {
            const width = $(window).width();
            if (width >= 1400) {
                this.proximityValueTarget.value = 6;
            } else if (width >= 1200 && width < 1400) {
                this.proximityValueTarget.value = 5;
            } else if (width >= 992 && width < 1200) {
                this.proximityValueTarget.value = 4;
            } else if (width >= 768 && width < 992) {
                this.proximityValueTarget.value = 3;
            } else if (width >= 576 && width < 768) {
                this.proximityValueTarget.value = 2;
            } else {
                this.proximityValueTarget.value = 1;
            }

            this.proximityValueTarget.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}