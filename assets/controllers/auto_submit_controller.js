import { Controller } from '@hotwired/stimulus';

/*
 * Submits the host form as soon as a value is chosen (e.g. a TomSelect / UX Autocomplete
 * selection), so the user doesn't have to click the submit button.
 *
 * Usage: data-controller="auto-submit" data-action="change->auto-submit#submit"
 */
export default class extends Controller {
    submit(event) {
        // Ignore clears/empties — only navigate once an actual place is selected.
        if (event.target.value !== '') {
            this.element.requestSubmit();
        }
    }
}
