import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['select', 'block'];
    static values = {
        match: String,
    };

    connect() {
        this.toggle();
    }

    toggle() {
        const isMatch = this.selectTarget.value === this.matchValue;
        this.blockTarget.classList.toggle('hidden', !isMatch);
        this.blockTarget.querySelectorAll('input, select, textarea').forEach((element) => {
            element.required = isMatch && element.getAttribute('data-required') === 'true';
        });
    }
}
