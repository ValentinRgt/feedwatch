import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['scroller', 'prev', 'next'];

    connect() {
        this._onResize = () => this.updateArrows();
        this._onScroll = () => this.updateArrows();

        window.addEventListener('resize', this._onResize);
        this.scrollerTarget.addEventListener('scroll', this._onScroll, { passive: true });

        // Centers the active tab before calculating the deflection.
        requestAnimationFrame(() => {
            this.scrollToActive();
            this.updateArrows();
        });
    }

    disconnect() {
        window.removeEventListener('resize', this._onResize);
        this.scrollerTarget.removeEventListener('scroll', this._onScroll);
    }

    /** Control button display based on screen size and scroll */
    updateArrows() {
        const el = this.scrollerTarget;
        const maxScroll = el.scrollWidth - el.clientWidth;

        if (maxScroll <= 1) {
            this.hideArrow(this.prevTarget);
            this.hideArrow(this.nextTarget);
            return;
        }

        const atStart = el.scrollLeft <= 1;
        const atEnd = el.scrollLeft >= maxScroll - 1;

        this.toggleArrow(this.prevTarget, !atStart);
        this.toggleArrow(this.nextTarget, !atEnd);
    }

    /** Keep the active tab in focus so that it remains visible */
    scrollToActive() {
        const el = this.scrollerTarget;
        const active = el.querySelector('[aria-current="page"], .active');

        if (!active || el.scrollWidth - el.clientWidth <= 1) {
            return;
        }

        const target = active.offsetLeft - (el.clientWidth - active.offsetWidth) / 2;
        const maxScroll = el.scrollWidth - el.clientWidth;

        el.scrollLeft = Math.max(0, Math.min(target, maxScroll));
    }

    /** Scroll backward/forward. */
    prev() {
        this.scrollerTarget.scrollBy({ left: -this.step, behavior: 'smooth' });
    }

    /** Scroll forward/backward. */
    next() {
        this.scrollerTarget.scrollBy({ left: this.step, behavior: 'smooth' });
    }

    get step() {
        return Math.max(this.scrollerTarget.clientWidth * 0.8, 120);
    }

    /** Hide the arrow button */
    hideArrow(arrow) {
        arrow.classList.add('hidden');
    }

    /** Enable/disable the arrow button */
    toggleArrow(arrow, enabled) {
        arrow.classList.remove('hidden');
        arrow.disabled = !enabled;
        arrow.classList.toggle('opacity-30', !enabled);
        arrow.classList.toggle('cursor-not-allowed', !enabled);
        arrow.classList.toggle('cursor-pointer', enabled);
    }
}
