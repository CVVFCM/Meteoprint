import { Controller } from '@hotwired/stimulus';

/*
 * When the place autocomplete is pre-filled with a raw token ("lat,lng" or "spot:slug")
 * — e.g. after the browser Back button — TomSelect shows the raw value (no option, or an
 * auto-created option whose label IS the token). This resolves the token to its place
 * name and (re)labels the option so the field displays the name. The token stays the
 * submitted value.
 *
 * Usage: data-controller="place-prefill"
 *        data-action="symfony--ux-autocomplete--autocomplete:connect->place-prefill#seed"
 */
export default class extends Controller {
    async seed(event) {
        const tomSelect = event.detail?.tomSelect ?? event.target?.tomselect;
        const value = tomSelect?.getValue();
        if (!tomSelect || !value) {
            return;
        }

        // Already displayed with a real label (text differs from the raw token).
        const existing = tomSelect.options[value];
        if (existing && existing.text && existing.text !== value) {
            return;
        }

        try {
            const response = await fetch(`/places/label?token=${encodeURIComponent(value)}`);
            if (!response.ok) {
                return;
            }
            const { text } = await response.json();
            if (!text || text === value) {
                return;
            }

            if (tomSelect.options[value]) {
                tomSelect.updateOption(value, { value, text });
            } else {
                tomSelect.addOption({ value, text });
            }
            tomSelect.refreshItems();
        } catch {
            // Leave the raw value rather than break the page.
        }
    }
}
