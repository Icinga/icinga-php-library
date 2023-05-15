define(["../notjQuery", "BaseInput"], function ($, BaseInput) {

    "use strict";

    class TermInput extends BaseInput {
        constructor(input) {
            super(input);

            this.separator = this.input.dataset.termSeparator || ' ';
            this.ignoreSpaceUntil = null;
        }

        bind() {
            super.bind();

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

        termsToQueryString(terms) {
            let quoted = [];
            for (const termData of terms) {
                if (termData.search.indexOf(this.separator) >= 0) {
                    quoted.push({ ...termData, search: '"' + termData.search + '"' });
                } else {
                    quoted.push(termData);
                }
            }

            return super.termsToQueryString(quoted);
        }

        complete(input, data) {
            data.exclude = this.usedTerms.map(termData => termData.search);

            super.complete(input, data);
        }

        /**
         * Event listeners
         */

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

            if (event.key !== this.separator) {
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
            if (! this.hasSyntaxError()) {
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
