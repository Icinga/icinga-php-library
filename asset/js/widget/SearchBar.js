define(["../notjQuery"], function ($) {

    "use strict";

    class SearchBar {
        constructor(form) {
            this.form = form;
            this.filterInput = null;
        }

        bind() {
            $(this.form.parentNode).on('click', '[data-search-editor-url]', this.onOpenerClick, this);

            return this;
        }

        refresh(form) {
            if (form === this.form) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this.form = form;
            this.bind();
        }

        destroy() {
            this.form = null;
            this.filterInput = null;
        }

        setFilterInput(filterInput) {
            this.filterInput = filterInput;

            return this;
        }

        onOpenerClick(event) {
            let opener = event.currentTarget;
            let editorUrl = opener.dataset.searchEditorUrl;
            let filterQueryString = this.filterInput.getQueryString();

            editorUrl += (editorUrl.indexOf('?') > -1 ? '&' : '?') + filterQueryString;

            // The search editor should open in a modal. We simulate a click on an anchor
            // appropriately prepared so that Icinga Web 2 will handle it natively.
            let a = document.createElement('a');
            a.classList.add('modal-opener');
            a.href = editorUrl;
            a.dataset.noIcingaAjax = '';
            a.dataset.icingaModal = '';

            opener.parentNode.insertBefore(a, opener.nextSibling);
            a.click();
            a.remove();
        }
    }

    return SearchBar;
});
