define(["../notjQuery", "../vendor/Sortable", "BaseInput"], function ($, Sortable, BaseInput) {

    "use strict";

    class TermInput extends BaseInput {
        constructor(input) {
            super(input);

            this.separator = this.input.dataset.termSeparator || ' ';
            this.ordered = 'maintainTermOrder' in this.input.dataset;
            this.readOnly = 'readOnlyTerms' in this.input.dataset;
            this.ignoreSpaceUntil = null;
        }

        bind() {
            super.bind();

            if (this.readOnly) {
                $(this.termContainer).on('click', '[data-index] > input', this.onTermClick, this);
            }

            if (this.ordered) {
                $(this.termContainer).on('end', this.onDrop, this);

                Sortable.create(this.termContainer, {
                    scroll: true,
                    direction: 'vertical',
                    handle: '[data-drag-initiator]'
                });
            }

            // TODO: Compatibility only. Remove as soon as possible once Web 2.12 (?) is out.
            //       Or upon any other update which lets Web trigger a real submit upon auto submit.
            $(this.input.form).on('change', 'select.autosubmit', this.onSubmit, this);
            $(this.input.form).on('change', 'input.autosubmit', this.onSubmit, this);

            return this;
        }

        reset() {
            super.reset();

            this.ignoreSpaceUntil = null;
        }

        registerTerm(termData, termIndex = null) {
            termIndex = super.registerTerm(termData, termIndex);

            if (this.readOnly) {
                const label = this.termContainer.querySelector(`[data-index="${ termIndex }"]`);
                if (label) {
                    // The label only exists in DOM at this time if it was transmitted
                    // by the server. So it's safe to assume that it needs validation
                    this.validate(label.firstChild);
                }
            }

            return termIndex;
        }

        readPartialTerm(input) {
            let value = super.readPartialTerm(input);
            if (value && this.ignoreSpaceUntil && value[0] === this.ignoreSpaceUntil) {
                value = value.slice(1);
                if (value.slice(-1) === this.ignoreSpaceUntil) {
                    value = value.slice(0, -1);
                }
            }

            return value;
        }

        writePartialTerm(value, input) {
            if (this.ignoreSpaceUntil !== null && this.ignoreSpaceSince === 0) {
                value = this.ignoreSpaceUntil + value;
            }

            super.writePartialTerm(value, input);
        }

        readFullTerm(input, termIndex = null) {
            let termData = super.readFullTerm(input, termIndex);
            if (termData && this.ignoreSpaceUntil !== null && input.value[0] === this.ignoreSpaceUntil) {
                if (input.value.slice(-1) !== this.ignoreSpaceUntil || input.value.length < 2) {
                    return false;
                }

                this.ignoreSpaceUntil = null;
            }

            return termData;
        }

        hasSyntaxError(input) {
            if ((typeof input === 'undefined' || input === this.input) && this.ignoreSpaceUntil !== null) {
                if (this.input.value === this.ignoreSpaceUntil) {
                    return true;
                }
            }

            return super.hasSyntaxError(input);
        }

        checkValidity(input) {
            if (! this.readOnly) {
                return super.checkValidity(input);
            }

            // Readonly terms don't participate in constraint validation, so we have to do it ourselves
            return ! (input.pattern && ! input.value.match(input.pattern));
        }

        reportValidity(element) {
            if (! this.readOnly) {
                return super.reportValidity(element);
            }

            // Once invalid, it stays invalid since it's readonly
            element.classList.add('invalid');
            if (element.dataset.invalidMsg) {
                const reason = element.parentNode.querySelector(':scope > .invalid-reason');
                if (! reason.matches('.visible')) {
                    element.title = element.dataset.invalidMsg;
                    reason.textContent = element.dataset.invalidMsg;
                    reason.classList.add('visible');
                    setTimeout(() => reason.classList.remove('visible'), 5000);
                }
            }
        }

        termsToQueryString(terms) {
            let quoted = [];
            for (const termData of terms) {
                let search = this.encodeTerm(termData).search;
                if (search.indexOf(this.separator) >= 0) {
                    search = '"' + termData.search + '"';
                }

                quoted.push(search);
            }

            return quoted.join(this.separator).trim();
        }

        addRenderedTerm(label) {
            if (! this.ordered) {
                return super.addRenderedTerm(label);
            }

            const listItem = document.createElement('li');
            listItem.appendChild(label);
            listItem.appendChild($.render('<i data-drag-initiator class="icon fa-bars fa"></i>'));
            this.termContainer.appendChild(listItem);
        }

        insertRenderedTerm(label) {
            if (! this.ordered) {
                return super.insertRenderedTerm(label);
            }

            const termIndex = Number(label.dataset.index);
            const nextListItemLabel = this.termContainer.querySelector(`[data-index="${ termIndex + 1 }"]`);
            const nextListItem = nextListItemLabel?.parentNode || null;
            const listItem = document.createElement('li');
            listItem.appendChild(label);
            listItem.appendChild($.render('<i data-drag-initiator class="icon fa-bars fa"></i>'));
            this.termContainer.insertBefore(listItem, nextListItem);

            return label;
        }

        removeRenderedTerm(label) {
            if (! this.ordered) {
                return super.removeRenderedTerm(label);
            }

            label.parentNode.remove();
        }

        complete(input, data) {
            data.exclude = this.usedTerms.map(termData => termData.search);

            super.complete(input, data);
        }

        renderTerm(termData, termIndex) {
            const label = super.renderTerm(termData, termIndex);

            if (this.readOnly) {
                const removeLabel = this.termContainer.dataset.removeActionLabel;
                label.firstChild.readOnly = true;
                label.appendChild(
                    $.render(
                        `<div class="remove-action" title="${ removeLabel }">` +
                            '<i class="icon fa-trash fa"></i>' +
                            `<span class="remove-action-label">${ removeLabel }</span>` +
                        '</div>'
                    )
                );
                label.appendChild($.render('<span class="invalid-reason"></span>'));
            }

            return label;
        }

        /**
         * Event listeners
         */

        onTermClick(event) {
            let termIndex = Number(event.target.parentNode.dataset.index);
            this.removeTerm(event.target.parentNode);
            this.moveFocusForward(termIndex - 1);
        }

        onDrop(event) {
            if (event.to === event.from && event.newIndex === event.oldIndex) {
                // The user dropped the term at its previous position
                return;
            }

            // The item is the list item, not the term's label
            const label = event.item.firstChild;

            // Remove the term from the internal map, but not the DOM, as it's been moved already
            const termData = this.removeTerm(label, false);
            delete label.dataset.index; // Which is why we have to take it out of the equation for now

            let newIndex = 0; // event.newIndex is intentionally not used, as we have our own indexing
            if (event.item.previousSibling) {
                newIndex = Number(event.item.previousSibling.firstChild.dataset.index) + 1;
            }

            // This is essentially insertTerm() with custom DOM manipulation
            this.reIndexTerms(newIndex, 1, true); // Free up the new index
            this.registerTerm(termData, newIndex); // Re-register the term with the new index
            label.dataset.index = `${ newIndex }`; // Update the DOM, we didn't do that during removal
        }

        onSubmit(event) {
            super.onSubmit(event);

            this.ignoreSpaceUntil = null;
        }

        onInput(event) {
            let label = event.target.parentNode;
            if (label.dataset.index >= 0) {
                super.onInput(event);
                return;
            }

            let input = event.target;
            let firstChar = input.value[0];

            if (this.ignoreSpaceUntil !== null) {
                // Reset if the user changes/removes the source char
                if (firstChar !== this.ignoreSpaceUntil) {
                    this.ignoreSpaceUntil = null;
                }
            }

            if (this.ignoreSpaceUntil === null && (firstChar === "'" || firstChar === '"')) {
                this.ignoreSpaceUntil = firstChar;
            }

            super.onInput(event);
        }

        onKeyDown(event) {
            super.onKeyDown(event);
            if (event.defaultPrevented) {
                return;
            }

            let label = event.target.parentNode;
            if (label.dataset.index >= 0) {
                return;
            }

            if (event.key !== this.separator && event.key !== 'Enter') {
                return;
            }

            let addedTerms = this.exchangeTerm();
            if (Object.keys(addedTerms).length) {
                this.togglePlaceholder();
                event.preventDefault();
                this.autoSubmit(this.input, 'exchange', { terms: addedTerms });
            }
        }

        onKeyUp(event) {
            super.onKeyUp(event);

            let label = event.target.parentNode;
            if (label.dataset.index >= 0) {
                return;
            }

            if (this.ignoreSpaceUntil !== null) {
                // Reset if the user changes/removes the source char
                let value = event.target.value;
                if (value[this.ignoreSpaceSince] !== this.ignoreSpaceUntil) {
                    this.ignoreSpaceUntil = null;
                    this.ignoreSpaceSince = null;
                }
            }

            let input = event.target;
            switch (event.key) {
                case '"':
                case "'":
                    if (this.ignoreSpaceUntil === null) {
                        this.ignoreSpaceUntil = event.key;
                        this.ignoreSpaceSince = input.selectionStart - 1;
                    }
            }
        }

        onButtonClick(event) {
            // Exchange terms only when an Enter key is pressed while not in the term input.
            // If the pointerType is not empty, the click event is triggered by clicking the Submit button in the form,
            // and the default submit event should not be prevented.
            // The below solution does not work if the click event is triggered by pressing Space while on the Submit button.
            // In which case the Submit button needs to be clicked again to trigger the form submission.
            if (! this.hasSyntaxError() && event.pointerType === '') {
                let addedTerms = this.exchangeTerm();
                if (Object.keys(addedTerms).length) {
                    this.togglePlaceholder();
                    event.preventDefault();
                    this.autoSubmit(this.input, 'exchange', { terms: addedTerms });
                    this.ignoreSpaceUntil = null;

                    return;
                }
            }

            super.onButtonClick(event);
        }
    }

    return TermInput;
});
