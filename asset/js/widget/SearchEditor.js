define(["../notjQuery", "../vendor/Sortable"], function ($, Sortable) {

    "use strict";

    class SearchEditor {
        constructor(form) {
            this.form = form;
        }

        bind() {
            $(this.form).on('end', this.onRuleDropped, this);

            this.form.querySelectorAll('ol').forEach(sortable => {
                let options = {
                    scroll: true,
                    group: 'rules',
                    direction: 'vertical',
                    invertSwap: true,
                    handle: '.drag-initiator'
                };

                Sortable.create(sortable, options);
            });

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

        onRuleDropped(event) {
            if (event.to === event.from && event.newIndex === event.oldIndex) {
                // The user dropped the rule at its previous position
                return;
            }

            let placement = 'before';
            let neighbour = event.to.querySelector(':scope > :nth-child(' + (event.newIndex + 2) + ')');
            if (! neighbour) {
                // User dropped the rule at the end of a group
                placement = 'after';
                neighbour = event.to.querySelector(':scope > :nth-child(' + event.newIndex + ')')
                if (! neighbour) {
                    // User dropped the rule into an empty group
                    placement = 'to';
                    neighbour = event.to.closest('[id]');
                }
            }

            // It's a submit element, the very first one, otherwise Icinga Web 2 sends another "structural-change"
            this.form.insertBefore(
                $.render(
                    '<input type="hidden" name="structural-change[1]" value="' + placement + ':' + neighbour.id + '">'
                ),
                this.form.firstChild
            );
            this.form.insertBefore(
                $.render('<input type="submit" name="structural-change[0]" value="move-rule:' + event.item.id + '">'),
                this.form.firstChild
            );

            $(this.form).trigger('submit');
        }
    }

    return SearchEditor;
});
