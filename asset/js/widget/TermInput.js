define(["BaseInput"], function (BaseInput) {

    "use strict";

    class TermInput extends BaseInput {
        constructor(input) {
            super(input);

            this.separator = ' ';
            this.ignoreSpaceUntil = null;
            this.ignoreSpaceSince = null;
        }

        reset() {
            super.reset();

            this.ignoreSpaceUntil = null;
            this.ignoreSpaceSince = null;
        }

        writePartialTerm(value, input) {
            if (this.ignoreSpaceUntil !== null && this.ignoreSpaceSince === 0) {
                value = this.ignoreSpaceUntil + value;
            }

            super.writePartialTerm(value, input);
        }

        readFullTerm(input, termIndex = null) {
            let termData = super.readFullTerm(input, termIndex);
            if (this.ignoreSpaceUntil !== null && termData.label[this.ignoreSpaceSince] === this.ignoreSpaceUntil) {
                if (termData.label.length - 1 === this.ignoreSpaceSince
                    || termData.label.slice(-1) !== this.ignoreSpaceUntil
                    || (this.ignoreSpaceSince === 0 && (termData.label.length < 2
                        || termData.label.slice(0, 1) !== this.ignoreSpaceUntil)
                    )
                ) {
                    return false;
                }
            }

            return termData;
        }

        addTerm(termData, termIndex = null) {
            if (this.ignoreSpaceUntil !== null) {
                if (this.ignoreSpaceSince === 0 && termData.label[this.ignoreSpaceSince] === this.ignoreSpaceUntil) {
                    termData.label = termData.label.slice(1, -1);
                }

                this.ignoreSpaceUntil = null;
                this.ignoreSpaceSince = null;
            }

            super.addTerm(termData, termIndex);
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
            this.ignoreSpaceSince = null;
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
            if (addedTerms.length) {
                this.togglePlaceholder();
                event.preventDefault();
                this.autoSubmit(this.input, 'exchange', addedTerms);
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
    }

    return TermInput;
});
