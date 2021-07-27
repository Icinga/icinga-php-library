define(["../notjQuery", "Completer"], function ($, Completer) {

    "use strict";

    class BaseInput {
        constructor(input) {
            this.input = input;
            this.disabled = false;
            this.separator = '';
            this.usedTerms = [];
            this.completer = null;
            this.lastCompletedTerm = null;
            this._dataInput = null;
            this._termInput = null;
            this._termContainer = null;
        }

        get dataInput() {
            if (this._dataInput === null) {
                this._dataInput = document.querySelector(this.input.dataset.dataInput);
            }

            return this._dataInput;
        }

        get termInput() {
            if (this._termInput === null) {
                this._termInput = document.querySelector(this.input.dataset.termInput);
            }

            return this._termInput;
        }

        get termContainer() {
            if (this._termContainer === null) {
                this._termContainer = document.querySelector(this.input.dataset.termContainer);
            }

            return this._termContainer;
        }

        bind() {
            // Form submissions
            $(this.input.form).on('submit', this.onSubmit, this);
            $(this.input.form).on(
                'click', 'button:not([type]), button[type="submit"], input[type="submit"]', this.onButtonClick, this);

            // User interactions
            $(this.input).on('input', this.onInput, this);
            $(this.input).on('keydown', this.onKeyDown, this);
            $(this.input).on('keyup', this.onKeyUp, this);
            $(this.termContainer).on('input', '[data-label]', this.onInput, this);
            $(this.termContainer).on('keydown', '[data-label]', this.onKeyDown, this);
            $(this.termContainer).on('keyup', '[data-label]', this.onKeyUp, this);
            $(this.termContainer).on('focusout', '[data-index]', this.onTermBlur, this);
            $(this.termContainer).on('focusin', '[data-index]', this.onTermFocus, this);

            // Copy/Paste
            $(this.input).on('paste', this.onPaste, this);
            $(this.input).on('copy', this.onCopyAndCut, this);
            $(this.input).on('cut', this.onCopyAndCut, this);

            // Should terms be completed?
            if (this.input.dataset.suggestUrl) {
                if (this.completer === null) {
                    this.completer = new Completer(this.input, true);
                    this.completer.bind(this.termContainer);
                }

                $(this.input).on('suggestion', this.onSuggestion, this);
                $(this.input).on('completion', this.onCompletion, this);
                $(this.termContainer).on('suggestion', '[data-label]', this.onSuggestion, this);
                $(this.termContainer).on('completion', '[data-label]', this.onCompletion, this);
            }

            return this;
        }

        refresh(input) {
            if (input === this.input) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this._termInput = null;
            this._termContainer = null;

            this.input = input;
            this.bind();

            if (this.completer !== null) {
                this.completer.refresh(input, this.termContainer);
            }

            if (! this.restoreTerms()) {
                this.reset();
            }
        }

        reset() {
            this.usedTerms = [];
            this.lastCompletedTerm = null;

            this.togglePlaceholder();
            this.termInput.value = '';
            this.termContainer.innerHTML = '';
        }

        destroy() {
            this._termContainer = null;
            this._termInput = null;
            this.input = null;

            if (this.completer !== null) {
                this.completer.destroy();
                this.completer = null;
            }
        }

        disable() {
            this.disabled = true;
            this.input.disabled = true;
            this.input.form.classList.add('disabled');
            this.termContainer.querySelectorAll('[data-index]').forEach(el => el.firstChild.disabled = true);

            if (this.completer !== null) {
                this.completer.reset();
            }
        }

        enable() {
            this.input.disabled = false;
            this.input.form.classList.remove('disabled');
            this.termContainer.querySelectorAll('[data-index]').forEach(el => el.firstChild.disabled = false);
            this.disabled = false;
        }

        restoreTerms() {
            if (this.hasTerms()) {
                this.usedTerms.forEach((termData, termIndex) => this.addTerm(termData, termIndex));
                this.togglePlaceholder();
                this.clearPartialTerm(this.input);
            } else {
                this.registerTerms();
                this.togglePlaceholder();
            }

            return this.hasTerms();
        }

        registerTerms() {
            this.termContainer.querySelectorAll('[data-index]').forEach((label) => {
                let termData = { ...label.dataset };
                delete termData.index;

                if (label.className) {
                    termData['class'] = label.className;
                }

                this.registerTerm(this.decodeTerm(termData), label.dataset.index);
            });
        }

        registerTerm(termData, termIndex = null) {
            if (termIndex !== null) {
                this.usedTerms.splice(termIndex, 0, termData);
                return termIndex;
            } else {
                return this.usedTerms.push(termData) - 1;
            }
        }

        updateTerms(changedTerms) {
            for (const termIndex of Object.keys(changedTerms)) {
                let label = this.termContainer.querySelector(`[data-index="${ termIndex }"]`);
                if (! label) {
                    continue;
                }

                let input = label.firstChild;
                let termData = changedTerms[termIndex];

                if (termData.label) {
                    this.writePartialTerm(termData.label, input);
                }

                this.updateTermData(termData, input);
                this.usedTerms[termIndex] = termData;
            }
        }

        clearPartialTerm(input) {
            if (this.completer !== null) {
                this.completer.reset();
            }

            this.writePartialTerm('', input);
        }

        writePartialTerm(value, input) {
            input.value = value;
            this.updateTermData({ label: value }, input);
        }

        readPartialTerm(input) {
            return input.value.trim();
        }

        readFullTerm(input, termIndex = null) {
            let value = this.readPartialTerm(input);
            if (! value) {
                return false;
            }

            let termData = {};

            if (termIndex !== null) {
                termData = { ...this.usedTerms[termIndex] };
            }

            termData.label = value;
            termData.search = value;

            if (this.lastCompletedTerm !== null) {
                if (termData.label === this.lastCompletedTerm.label) {
                    Object.assign(termData, this.lastCompletedTerm);
                }

                this.lastCompletedTerm = null;
            }

            return termData;
        }

        exchangeTerm() {
            if (this.completer !== null) {
                this.completer.reset();
            }

            let termData = this.readFullTerm(this.input);
            if (! termData) {
                return {};
            }

            let addedTerms = {};
            if (Array.isArray(termData)) {
                for (let data of termData) {
                    this.addTerm(data);
                    addedTerms[this.usedTerms.length - 1] = data;
                }
            } else {
                this.addTerm(termData);
                addedTerms[this.usedTerms.length - 1] = termData;
            }

            this.clearPartialTerm(this.input);

            return addedTerms;
        }

        insertTerm(termData, termIndex) {
            this.reIndexTerms(termIndex, 1, true);
            this.registerTerm(termData, termIndex);
            return this.insertRenderedTerm(this.renderTerm(termData, termIndex));
        }

        insertRenderedTerm(label) {
            let next = this.termContainer.querySelector(`[data-index="${ label.dataset.index + 1 }"]`);
            this.termContainer.insertBefore(label, next);
            return label;
        }

        addTerm(termData, termIndex = null) {
            if (termIndex === null) {
                termIndex = this.registerTerm(termData);
            }

            this.addRenderedTerm(this.renderTerm(termData, termIndex));
        }

        addRenderedTerm(label) {
            this.termContainer.appendChild(label);
        }

        hasTerms() {
            return this.usedTerms.length > 0;
        }

        getQueryString() {
            return this.termsToQueryString(this.usedTerms);
        }

        saveTerm(input, updateDOM = true) {
            let termIndex = input.parentNode.dataset.index;
            let termData = this.readFullTerm(input, termIndex);

            // Only save if something has changed
            if (termData === false) {
                return this.removeTerm(input.parentNode, updateDOM);
            } else if (this.usedTerms[termIndex].label !== termData.label) {
                this.usedTerms[termIndex] = termData;
                this.updateTermData(termData, input);

                return termData;
            }

            return false;
        }

        updateTermData(termData, input) {
            let label = input.parentNode;
            label.dataset.label = termData.label;

            if (!! termData.search || termData.search === '') {
                label.dataset.search = termData.search;
            }
        }

        termsToQueryString(terms) {
            return terms.map(e => this.encodeTerm(e).search).join(this.separator).trim();
        }

        lastTerm() {
            if (! this.hasTerms()) {
                return null;
            }

            return this.usedTerms[this.usedTerms.length - 1];
        }

        popTerm() {
            let lastTermIndex = this.usedTerms.length - 1;
            return this.removeTerm(this.termContainer.querySelector(`[data-index="${ lastTermIndex }"]`));
        }

        removeTerm(label, updateDOM = true) {
            if (this.completer !== null) {
                this.completer.reset();
            }

            let termIndex = Number(label.dataset.index);

            // Re-index following remaining terms
            this.reIndexTerms(termIndex);

            // Cut the term's data
            let [termData] = this.usedTerms.splice(termIndex, 1);

            // Avoid saving the term, it's removed after all
            label.firstChild.skipSaveOnBlur = true;

            if (updateDOM) {
                // Remove it from the DOM
                this.removeRenderedTerm(label);
            }

            return termData;
        }

        removeRenderedTerm(label) {
            label.remove();
        }

        removeRange(labels) {
            let from = Number(labels[0].dataset.index);
            let to = Number(labels[labels.length - 1].dataset.index);
            let deleteCount = to - from + 1;

            if (to < this.usedTerms.length - 1) {
                // Only re-index if there's something left
                this.reIndexTerms(to, deleteCount);
            }

            let removedData = this.usedTerms.splice(from, deleteCount);

            this.removeRenderedRange(labels);

            let removedTerms = {};
            for (let i = from; removedData.length; i++) {
                removedTerms[i] = removedData.shift();
            }

            return removedTerms;
        }

        removeRenderedRange(labels) {
            labels.forEach(label => this.removeRenderedTerm(label));
        }

        reIndexTerms(from, howMuch = 1, forward = false) {
            if (forward) {
                for (let i = this.usedTerms.length - 1; i >= from; i--) {
                    let label = this.termContainer.querySelector(`[data-index="${ i }"]`);
                    label.dataset.index = `${ i + howMuch }`;
                }
            } else {
                for (let i = ++from; i < this.usedTerms.length; i++) {
                    let label = this.termContainer.querySelector(`[data-index="${ i }"]`);
                    label.dataset.index = `${ i - howMuch }`;
                }
            }
        }

        complete(input, data) {
            if (this.completer !== null) {
                $(input).trigger('complete', data);
            }
        }

        selectTerms() {
            this.termContainer.querySelectorAll('[data-index]').forEach(el => el.classList.add('selected'));
        }

        deselectTerms() {
            this.termContainer.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));
        }

        clearSelectedTerms() {
            if (this.hasTerms()) {
                let labels = this.termContainer.querySelectorAll('.selected');
                if (labels.length) {
                    return this.removeRange(Array.from(labels));
                }
            }

            return {};
        }

        togglePlaceholder() {
            let placeholder = '';

            if (! this.hasTerms()) {
                if (this.input.dataset.placeholder) {
                    placeholder = this.input.dataset.placeholder;
                } else {
                    return;
                }
            } else if (this.input.placeholder) {
                if (! this.input.dataset.placeholder) {
                    this.input.dataset.placeholder = this.input.placeholder;
                }
            }

            this.input.placeholder = placeholder;
        }

        renderTerm(termData, termIndex) {
            let label = $.render('<label><input type="text"></label>');

            if (termData.class) {
                label.classList.add(termData.class);
            }

            label.dataset.label = termData.label;
            label.dataset.search = termData.search;
            label.dataset.index = termIndex;

            label.firstChild.value = termData.label;

            return label;
        }

        encodeTerm(termData) {
            termData = { ...termData };
            termData.search = encodeURIComponent(termData.search);

            return termData;
        }

        decodeTerm(termData) {
            termData.search = decodeURIComponent(termData.search);

            return termData;
        }

        shouldNotAutoSubmit() {
            return 'noAutoSubmit' in this.input.dataset;
        }

        autoSubmit(input, changeType, changedTerms) {
            if (this.shouldNotAutoSubmit()) {
                return;
            }

            this.dataInput.value = JSON.stringify({
                type: changeType,
                terms: changedTerms
            });

            if (Object.keys(changedTerms).length) {
                $(this.input.form).trigger('submit', { submittedBy: input });
            }
        }

        moveFocusForward(from = null) {
            let toFocus;

            let inputs = Array.from(this.termContainer.querySelectorAll('input'));
            if (from === null) {
                let focused = this.termContainer.querySelector('input:focus');
                from = inputs.indexOf(focused);
            }

            if (from === -1) {
                toFocus = inputs.shift();
            } else if (from + 1 < inputs.length) {
                toFocus = inputs[from + 1];
            } else {
                toFocus = this.input;
            }

            toFocus.selectionStart = toFocus.selectionEnd = 0;
            $(toFocus).focus();

            return toFocus;
        }

        moveFocusBackward(from = null) {
            let toFocus;

            let inputs = Array.from(this.termContainer.querySelectorAll('input'));
            if (from === null) {
                let focused = this.termContainer.querySelector('input:focus');
                from = inputs.indexOf(focused);
            }

            if (from === -1) {
                toFocus = inputs.pop();
            } else if (from > 0 && from - 1 < inputs.length) {
                toFocus = inputs[from - 1];
            } else {
                toFocus = this.input;
            }

            toFocus.selectionStart = toFocus.selectionEnd = toFocus.value.length;
            $(toFocus).focus();

            return toFocus;
        }

        /**
         * Event listeners
         */

        onSubmit(event) {
            // Unset the input's name, to prevent its submission (It may actually have a name, as no-js fallback)
            this.input.name = '';

            // Set the hidden input's value, it's what's sent
            if (event.detail && 'terms' in event.detail) {
                this.termInput.value = event.detail.terms;
            } else {
                this.termInput.value = this.termsToQueryString(this.usedTerms);
            }

            // Enable the hidden input, otherwise it's not submitted
            this.termInput.disabled = false;
        }

        onSuggestion(event) {
            let data = event.detail;
            let input = event.target;

            let termData;
            if (typeof data === 'object') {
                termData = data;
            } else {
                termData = { label: data, search: data };
            }

            this.lastCompletedTerm = termData;
            this.writePartialTerm(termData.label, input);
        }

        onCompletion(event) {
            let input = event.target;
            let termData = event.detail;
            let termIndex = Number(input.parentNode.dataset.index);

            this.lastCompletedTerm = termData;
            this.writePartialTerm(termData.label, input);

            if (termIndex >= 0) {
                this.autoSubmit(input, 'save', { [termIndex]: this.saveTerm(input) });
            } else {
                this.autoSubmit(input, 'exchange', this.exchangeTerm());
                this.togglePlaceholder();
            }
        }

        onInput(event) {
            let input = event.target;
            let isTerm = input.parentNode.dataset.index >= 0;

            let termData = { label: this.readPartialTerm(input) };
            this.updateTermData(termData, input);
            this.complete(input, { term: termData });

            if (! isTerm) {
                this.autoSubmit(this.input, 'remove', this.clearSelectedTerms());
                this.togglePlaceholder();
            }
        }

        onKeyDown(event) {
            let input = event.target;
            let termIndex = Number(input.parentNode.dataset.index);

            let removedTerms;
            switch (event.key) {
                case ' ':
                    if (! this.readPartialTerm(input)) {
                        this.complete(input, { term: { label: '' } });
                        event.preventDefault();
                    }
                    break;
                case 'Backspace':
                    removedTerms = this.clearSelectedTerms();

                    if (termIndex >= 0 && ! input.value) {
                        let removedTerm = this.removeTerm(input.parentNode);
                        if (removedTerm !== false) {
                            input = this.moveFocusBackward(termIndex);
                            if (event.ctrlKey || event.metaKey) {
                                this.clearPartialTerm(input);
                            } else {
                                this.writePartialTerm(input.value.slice(0, -1), input);
                            }

                            removedTerms[termIndex] = removedTerm;
                            event.preventDefault();
                        }
                    } else if (isNaN(termIndex)) {
                        if (! input.value && this.hasTerms()) {
                            let termData = this.popTerm();
                            if (! event.ctrlKey && ! event.metaKey) {
                                // Removing the last char programmatically is not
                                // necessary since the browser default is not prevented
                                this.writePartialTerm(termData.label, input);
                            }

                            removedTerms[this.usedTerms.length] = termData;
                        }
                    }

                    this.togglePlaceholder();
                    this.autoSubmit(input, 'remove', removedTerms);
                    break;
                case 'Delete':
                    removedTerms = this.clearSelectedTerms();

                    if (termIndex >= 0 && ! input.value) {
                        let removedTerm = this.removeTerm(input.parentNode);
                        if (removedTerm !== false) {
                            input = this.moveFocusForward(termIndex - 1);
                            if (event.ctrlKey || event.metaKey) {
                                this.clearPartialTerm(input);
                            } else {
                                this.writePartialTerm(input.value.slice(1), input);
                            }

                            removedTerms[termIndex] = removedTerm;
                            event.preventDefault();
                        }
                    }

                    this.togglePlaceholder();
                    this.autoSubmit(input, 'remove', removedTerms);
                    break;
                case 'Enter':
                    if (termIndex >= 0) {
                        this.saveTerm(input, false);
                    }
                    break;
                case 'ArrowLeft':
                    if (input.selectionStart === 0 && this.hasTerms()) {
                        event.preventDefault();
                        this.moveFocusBackward();
                    }
                    break;
                case 'ArrowRight':
                    if (input.selectionStart === input.value.length && this.hasTerms()) {
                        event.preventDefault();
                        this.moveFocusForward();
                    }
                    break;
            }
        }

        onKeyUp(event) {
            if (event.target.parentNode.dataset.index >= 0) {
                return;
            }

            switch (event.key) {
                case 'End':
                case 'ArrowLeft':
                case 'ArrowRight':
                    this.deselectTerms();
                    break;
                case 'Home':
                    if (this.input.selectionStart === 0 && this.input.selectionEnd === 0) {
                        if (event.shiftKey) {
                            this.selectTerms();
                        } else {
                            this.deselectTerms();
                        }
                    }

                    break;
                case 'Delete':
                    this.autoSubmit(event.target, 'remove', this.clearSelectedTerms());
                    this.togglePlaceholder();
                    break;
                case 'a':
                    if ((event.ctrlKey || event.metaKey) && ! this.readPartialTerm(this.input)) {
                        this.selectTerms();
                    }
            }
        }

        onTermBlur(event) {
            let input = event.target;
            // skipSaveOnBlur is set if the input is about to be removed anyway.
            // If saveTerm would remove the input as well, the other removal will fail
            // without any chance to handle it. (Element.remove() blurs the input)
            if (typeof input.skipSaveOnBlur === 'undefined' || ! input.skipSaveOnBlur) {
                setTimeout(() => {
                    if (this.completer === null || ! this.completer.isBeingCompleted(input)) {
                        let savedTerm = this.saveTerm(input);
                        if (savedTerm !== false) {
                            let termIndex = Number(input.parentNode.dataset.index);
                            this.autoSubmit(input, 'save', { [termIndex]: savedTerm });
                        }
                    }
                }, 0);
            }
        }

        onTermFocus(event) {
            if (event.detail.scripted) {
                // Only request suggestions if the user manually focuses the term
                return;
            }

            this.deselectTerms();

            let input = event.target;
            let value = this.readPartialTerm(input);
            this.complete(input, { trigger: 'script', term: { label: value } });
        }

        onButtonClick(event) {
            // Register current input value, otherwise it's not included
            this.exchangeTerm();

            if (this.hasTerms()) {
                this.input.required = false;

                // This is not part of `onSubmit()` because otherwise it would override what `autoSubmit()` does
                this.dataInput.value = JSON.stringify({ type: 'submit', terms: this.usedTerms });
            } else if (typeof this.input.dataset.manageRequired !== 'undefined') {
                this.input.required = true;
            }
        }

        onPaste(event) {
            if (this.hasTerms()) {
                return;
            }

            $(this.input.form).trigger(
                'submit',
                { terms: event.clipboardData.getData('text/plain') }
            );

            event.preventDefault();
        }

        onCopyAndCut(event) {
            if (! this.hasTerms()) {
                return;
            }

            let data = '';

            let selectedTerms = this.termContainer.querySelectorAll('.selected');
            if (selectedTerms.length) {
                data = Array.from(selectedTerms).map(label => label.dataset.search).join(this.separator);
            }

            if (this.input.selectionStart < this.input.selectionEnd) {
                data += this.separator + this.input.value.slice(this.input.selectionStart, this.input.selectionEnd);
            }

            event.clipboardData.setData('text/plain', data);
            event.preventDefault();

            if (event.type === 'cut') {
                this.clearPartialTerm(this.input);
                this.autoSubmit(this.input, 'remove', this.clearSelectedTerms());
                this.togglePlaceholder();
            }
        }
    }

    return BaseInput;
});
