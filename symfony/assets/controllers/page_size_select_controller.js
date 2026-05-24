import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    change(event) {
        const url = new URL(window.location.href);
        url.searchParams.set('pageSize', event.target.value);
        window.location.assign(url.toString());
    }
}
