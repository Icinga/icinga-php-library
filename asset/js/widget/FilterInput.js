define(["../notjQuery", "BaseInput"], function ($, BaseInput) {

    "use strict";

    class FilterInput extends BaseInput {
        constructor(input) {
            super(input);

            this.termType = 'column';

            /**
             * The negation operator
             *
             * @type {{}}
             */
            this.negationOperator = { label: '!', search: '!', class: 'logical-operator', type: 'negation_operator' };

            /**
             * Supported grouping operators
             *
             * @type {{close: {}, open: {}}}
             */
            this.grouping_operators = {
                open: { label: '(', search: '(', class: 'grouping-operator-open', type: 'grouping_operator' },
                close: { label: ')', search: ')', class: 'grouping-operator-close', type: 'grouping_operator' }
            };

            /**
             * Supported logical operators
             *
             * The first is also the default.
             *
             * @type {{}[]}
             */
            this.logical_operators = [
                { label: '&', search: '&', class: 'logical-operator', type: 'logical_operator', default: true },
                { label: '|', search: '|', class: 'logical-operator', type: 'logical_operator' },
            ];

            /**
             * Supported relational operators
             *
             * The first is also the default.
             *
             * @type {{}[]}
             */
            this.relational_operators = [
                { label: '~', search: '~', class: 'operator', type: 'operator', default: true },
                { label: '!~', search: '!~', class: 'operator', type: 'operator' },
                { label: '=', search: '=', class: 'operator', type: 'operator' },
                { label: '!=', search: '!=', class: 'operator', type: 'operator' },
                { label: '>', search: '>', class: 'operator', type: 'operator' },
                { label: '<', search: '<', class: 'operator', type: 'operator' },
                { label: '>=', search: '>=', class: 'operator', type: 'operator' },
                { label: '<=', search: '<=', class: 'operator', type: 'operator' }
            ];
        }

        bind() {
            $(this.termContainer).on('click', '[data-group-type="condition"] > button', this.onRemoveCondition, this);
            $(this.termContainer).on('click', '[data-index]', this.onTermClick, this);
            $(this.termContainer).on('mouseover', '[data-index]', this.onTermHover, this);
            $(this.termContainer).on('mouseout', '[data-index]', this.onTermLeave, this);
            return super.bind();
        }

        reset() {
            super.reset();

            this.termType = 'column';
        }

        restoreTerms() {
            if (super.restoreTerms()) {
                this.reportValidity(this.input.form);
                return true;
            }

            return false;
        }

        registerTerms() {
            super.registerTerms();

            if (this.hasTerms()) {
                this.termType = this.nextTermType(this.lastTerm());
            }
        }

        registerTerm(termData, termIndex = null) {
            termIndex = super.registerTerm(termData, termIndex);

            if (termData.type === 'grouping_operator' && typeof termData.counterpart === 'undefined') {
                let counterpart;
                if (this.isGroupOpen(termData)) {
                    counterpart = this.nextPendingGroupClose(termIndex);
                } else {
                    counterpart = this.lastPendingGroupOpen(termIndex);
                }

                if (counterpart !== null) {
                    termData.counterpart = counterpart;
                    this.usedTerms[counterpart].counterpart = termIndex;
                }
            }

            return termIndex;
        }

        readFullTerm(input, termIndex = null) {
            let termData = super.readFullTerm(input, termIndex);
            if (termData === false) {
                return false;
            }

            if (! Array.isArray(termData) && ! termData.type) {
                termData.type = this.termType;
            }

            return termData;
        }

        insertTerm(termData, termIndex) {
            let label = super.insertTerm(termData, termIndex);

            if (termIndex === this.usedTerms.length - 1) {
                this.termType = this.nextTermType(termData);
            } else {
                let next = this.termContainer.querySelector(`[data-index="${ termIndex + 1 }"]`);
                this.checkValidity(next.firstChild, next.dataset.type, termIndex + 1);
            }

            return label;
        }

        insertRenderedTerm(label) {
            let termIndex = Number(label.dataset.index);
            if (label.dataset.counterpart >= 0) {
                let otherLabel = this.termContainer.querySelector(`[data-index="${ label.dataset.counterpart }"]`);
                if (otherLabel !== null) {
                    otherLabel.dataset.counterpart = termIndex;
                    this.checkValidity(otherLabel.firstChild);
                }
            }

            let previous = this.termContainer.querySelector(`[data-index="${ termIndex - 1 }"]`);
            switch (label.dataset.type) {
                case 'column':
                    let newCondition = this.renderCondition();
                    newCondition.appendChild(label);

                    if (previous) {
                        previous.parentNode.insertBefore(newCondition, previous.nextSibling);
                    } else {
                        this.termContainer.insertBefore(newCondition, this.termContainer.firstChild);
                    }

                    break;
                case 'operator':
                case 'value':
                    previous.parentNode.appendChild(label);
                    break;
                case 'logical_operator':
                    if (previous) {
                        if (previous.parentNode.dataset.groupType === 'condition') {
                            previous.parentNode.parentNode.insertBefore(label, previous.parentNode.nextSibling);
                        } else {
                            previous.parentNode.insertBefore(label, previous.nextSibling);
                        }
                    } else {
                        this.termContainer.insertBefore(label, this.termContainer.firstChild);
                    }

                    break;
                case 'negation_operator':
                    if (previous) {
                        previous.parentNode.insertBefore(label, previous.nextSibling);
                    } else {
                        this.termContainer.insertBefore(label, this.termContainer.firstChild);
                    }

                    break;
                case 'grouping_operator':
                    if (this.isGroupOpen(label.dataset)) {
                        if (label.dataset.counterpart >= 0) {
                            let counterpart = this.termContainer.querySelector(
                                `[data-index="${ label.dataset.counterpart }"]`
                            );
                            counterpart.parentNode.insertBefore(label, counterpart.parentNode.firstChild);
                        } else {
                            let newGroup = this.renderChain();
                            newGroup.appendChild(label);

                            let sibling = previous ? previous.nextSibling : this.termContainer.firstChild;
                            while (sibling !== null && sibling.dataset.type !== 'grouping_operator') {
                                let nextSibling = sibling.nextSibling;
                                newGroup.appendChild(sibling);
                                sibling = nextSibling;
                            }

                            if (previous) {
                                previous.parentNode.insertBefore(newGroup, previous.nextSibling);
                            } else {
                                // newGroup should be now the only child then
                                this.termContainer.appendChild(newGroup);
                            }
                        }
                    } else {
                        let chain = this.termContainer.querySelector(
                            `[data-index="${ label.dataset.counterpart }"]`
                        ).parentNode;
                        if (previous.parentNode.dataset.groupType && previous.parentNode !== chain) {
                            previous = previous.parentNode;
                        }

                        if (previous.parentNode !== chain) {
                            // The op is being moved by the user again, after it was already moved
                            let sibling = previous;
                            let lastSibling = null;
                            while (sibling !== null && sibling !== chain) {
                                let previousSibling = sibling.previousSibling;
                                chain.insertBefore(sibling, lastSibling);
                                lastSibling = sibling;
                                sibling = previousSibling;
                            }
                        }

                        // There may be terms following in the same level which now should be a level above
                        let sibling = previous.nextSibling;
                        let refNode = chain.nextSibling;
                        while (sibling !== null) {
                            let nextSibling = sibling.nextSibling;
                            chain.parentNode.insertBefore(sibling, refNode);
                            sibling = nextSibling;
                        }

                        chain.appendChild(label);
                    }
            }

            if (termIndex === this.usedTerms.length - 1) {
                this.identifyLastRenderedTerm();
            }

            return label;
        }

        addTerm(termData, termIndex = null) {
            super.addTerm(termData, termIndex);

            if (termData.counterpart >= 0) {
                let otherLabel = this.termContainer.querySelector(`[data-index="${ termData.counterpart }"]`);
                if (otherLabel !== null) {
                    otherLabel.dataset.counterpart = termIndex || this.usedTerms[termData.counterpart].counterpart;
                    this.checkValidity(otherLabel.firstChild);
                }
            }

            this.termType = this.nextTermType(termData);
        }

        addRenderedTerm(label) {
            let newGroup = null;
            let leaveGroup = false;
            let currentGroup = null;

            switch (label.dataset.type) {
                case 'column':
                    newGroup = this.renderCondition();
                    break;
                case 'grouping_operator':
                    if (this.isGroupOpen(label.dataset)) {
                        newGroup = this.renderChain();
                    } else {
                        let termIndex = Number(label.dataset.index);
                        let previous = this.termContainer.querySelector(`[data-index="${ termIndex - 1 }"]`);

                        currentGroup = this.termContainer.querySelector(
                            `[data-index="${ label.dataset.counterpart }"]`
                        ).parentNode;
                        if (previous.parentNode.dataset.groupType && previous.parentNode !== currentGroup) {
                            previous = previous.parentNode;
                        }

                        if (previous.parentNode !== currentGroup) {
                            // The op is being moved by the user again, after it was already moved
                            let sibling = previous;
                            let lastSibling = null;
                            while (sibling !== null && sibling !== currentGroup) {
                                let previousSibling = sibling.previousSibling;
                                currentGroup.insertBefore(sibling, lastSibling);
                                lastSibling = sibling;
                                sibling = previousSibling;
                            }
                        }
                    }

                    break;
                case 'logical_operator':
                    currentGroup = this.currentGroup;
                    leaveGroup = currentGroup.dataset.groupType === 'condition';
            }

            if (currentGroup === null) {
                currentGroup = this.currentGroup;
            }

            if (newGroup !== null) {
                newGroup.appendChild(label);
                currentGroup.appendChild(newGroup);
            } else if (leaveGroup) {
                currentGroup.parentNode.appendChild(label);
            } else {
                currentGroup.appendChild(label);
            }

            this.identifyLastRenderedTerm();
        }

        identifyLastRenderedTerm() {
            let lastTerm = Array.from(this.termContainer.querySelectorAll('[data-index]')).pop();
            if (! lastTerm) {
                return;
            }

            let lastLabel = this.termContainer.querySelector('.last-term');
            if (lastLabel !== null) {
                if (lastLabel === lastTerm) {
                    return;
                }

                lastLabel.classList.remove('last-term');
            }

            lastTerm.classList.add('last-term');
        }

        termsToQueryString(terms) {
            if (! this.input.form.checkValidity()) {
                let filtered = [];
                for (let i = 0; i < terms.length; i++) {
                    const input = this.termContainer.querySelector(`[data-index="${ i }"] > input`);
                    if (input === null || this.isGroupOpen(terms[i]) || input.checkValidity()) {
                        filtered.push(terms[i]);
                    } else if (input) {
                        // Ignore all terms after an invalid one
                        break;
                    }
                }

                terms = filtered;
            }

            return super.termsToQueryString(terms);
        }

        removeTerm(label, updateDOM = true) {
            let termIndex = Number(label.dataset.index);
            if (termIndex < this.usedTerms.length - 1) {
                // It's not the last term
                if (! this.validate(label.firstChild)) {
                    return false;
                }
            }

            let termData = super.removeTerm(label, updateDOM);

            if (this.hasTerms()) {
                if (termIndex === this.usedTerms.length) {
                    // It's been the last term
                    this.termType = this.nextTermType(this.lastTerm());
                }

                if (termData.counterpart >= 0) {
                    let otherLabel = this.termContainer.querySelector(`[data-index="${ termData.counterpart }"]`);
                    delete this.usedTerms[otherLabel.dataset.index].counterpart;
                    delete otherLabel.dataset.counterpart;
                    this.checkValidity(otherLabel.firstChild);
                }
            } else {
                this.termType = 'column';
            }

            return termData;
        }

        removeRange(labels) {
            let removedTerms = super.removeRange(labels);

            if (this.hasTerms()) {
                this.termType = this.nextTermType(this.lastTerm());

                labels.forEach((label) => {
                    if (label.dataset.counterpart >= 0) {
                        let otherLabel = this.termContainer.querySelector(
                            `[data-counterpart="${ label.dataset.index }"]`
                        );
                        if (otherLabel !== null) {
                            delete this.usedTerms[otherLabel.dataset.index].counterpart;
                            delete otherLabel.dataset.counterpart;
                            this.checkValidity(otherLabel.firstChild);
                        }
                    }
                });
            } else {
                this.termType = 'column';
            }

            return removedTerms;
        }

        removeRenderedTerm(label) {
            let parent = label.parentNode;
            let children = parent.querySelectorAll(':scope > [data-index], :scope > [data-group-type]');
            if (parent.dataset.groupType && children.length === 1) {
                // If the parent is a group and the label is the only child, we can remove the entire group
                parent.remove();
            } else {
                super.removeRenderedTerm(label);

                if (parent.dataset.groupType === 'chain') {
                    // Get a new nodes list first, otherwise the removed label is still part of it
                    children = parent.querySelectorAll(':scope > [data-index], :scope > [data-group-type]');
                    let hasNoGroupOperators = children[0].dataset.type !== 'grouping_operator'
                        && children[children.length - 1].dataset.type !== 'grouping_operator';
                    if (hasNoGroupOperators) {
                        // Unwrap remaining terms, remove the resulting empty group
                        Array.from(children).forEach(child => parent.parentNode.insertBefore(child, parent));
                        parent.remove();
                    }
                }
            }

            if (Number(label.dataset.index) >= this.usedTerms.length - 1) {
                this.identifyLastRenderedTerm();
            }
        }

        removeRenderedRange(labels) {
            let to = Number(labels[labels.length - 1].dataset.index);

            while (labels.length) {
                let label = labels.shift();
                let parent = label.parentNode;
                if (parent.dataset.groupType && label === parent.firstChild) {
                    let counterpartIndex = Number(label.dataset.counterpart);
                    if (isNaN(counterpartIndex)) {
                        counterpartIndex = Number(
                            Array.from(parent.querySelectorAll(':scope > [data-index]')).pop().dataset.index
                        );
                    }

                    if (counterpartIndex <= to) {
                        // If the parent's terms are all to be removed, we'll remove the
                        // entire parent to keep the DOM operations as efficient as possible
                        parent.remove();

                        labels.splice(0, counterpartIndex - Number(label.dataset.index));
                        continue;
                    }
                }

                this.removeRenderedTerm(label);
            }
        }

        reIndexTerms(from, howMuch = 1, forward = false) {
            let fromLabel = this.termContainer.querySelector(`[data-index="${ from }"]`);

            super.reIndexTerms(from, howMuch, forward);

            let _this = this;
            this.termContainer.querySelectorAll('[data-counterpart]').forEach(label => {
                let counterpartIndex = Number(label.dataset.counterpart);
                if ((forward && counterpartIndex >= from) || (! forward && counterpartIndex > from)) {
                    counterpartIndex += forward ? howMuch : -howMuch;

                    let termIndex = Number(label.dataset.index);
                    if (
                        (! forward && termIndex > from - howMuch && label !== fromLabel)
                        || (forward && termIndex >= from)
                    ) {
                        // Make sure to use the previous index to access usedTerms, it's not adjusted yet
                        termIndex += forward ? -howMuch : howMuch;
                    }

                    label.dataset.counterpart = `${ counterpartIndex }`;
                    _this.usedTerms[termIndex].counterpart = `${ counterpartIndex }`;
                }
            });
        }

        complete(input, data) {
            let termIndex = Number(input.parentNode.dataset.index);
            if (termIndex >= 0) {
                data.term.type = this.usedTerms[termIndex].type;
            } else {
                termIndex = this.usedTerms.length;
                data.term.type = this.termType;
            }

            // Special cases
            switch (data.term.type) {
                case 'grouping_operator':
                case 'negation_operator':
                    return;
                case 'column':
                    data.showQuickSearch = termIndex === this.usedTerms.length;
                    break;
                case 'value':
                    let terms = [ ...this.usedTerms ];
                    terms.splice(termIndex - 2, 3, { type: 'column', search: '' },
                        { type: 'operator', search: '' }, { type: 'value', search: '' });

                    data.searchFilter = this.termsToQueryString(terms);
                    break;
                case 'operator':
                case 'logical_operator':
                    let suggestions = this.validOperator(
                        data.trigger === 'script' ? '' : data.term.label, data.term.type, termIndex);
                    if (suggestions.exactMatch && ! suggestions.partialMatches) {
                        // User typed a suggestion manually, don't show the same suggestion again
                        return;
                    }

                    data.suggestions = this.renderSuggestions(suggestions);
            }

            // Additional metadata
            switch (data.term.type) {
                case 'value':
                    data.operator = this.usedTerms[--termIndex].search;
                case 'operator':
                    data.column = this.usedTerms[--termIndex].search;
            }

            super.complete(input, data);
        }

        nextTermType(termData) {
            switch (termData.type) {
                case 'column':
                    return 'operator';
                case 'operator':
                    return 'value';
                case 'value':
                    return 'logical_operator';
                case 'logical_operator':
                case 'negation_operator':
                    return 'column';
                case 'grouping_operator':
                    return this.isGroupOpen(termData) ? 'column' : 'logical_operator';
            }
        }

        get currentGroup() {
            let label = Array.from(this.termContainer.querySelectorAll('[data-index]')).pop();
            if (! label) {
                return this.termContainer;
            }

            let termData = this.usedTerms[label.dataset.index];
            switch (termData.type) {
                case 'grouping_operator':
                    if (this.isGroupOpen(termData)) {
                        break;
                    }
                case 'value':
                    return label.parentNode.parentNode;
            }

            return label.parentNode;
        }

        lastPendingGroupOpen(before) {
            let level = 0;
            for (let i = before - 1; i >= 0 && i < this.usedTerms.length; i--) {
                let termData = this.usedTerms[i];

                if (termData.type === 'grouping_operator') {
                    if (this.isGroupOpen(termData)) {
                        if (level === 0) {
                            return typeof termData.counterpart === 'undefined' ? i : null;
                        }

                        level++;
                    } else {
                        if (termData.counterpart >= 0) {
                            i = termData.counterpart;
                        } else {
                            level--;
                        }
                    }
                }
            }

            return null;
        }

        nextPendingGroupClose(after) {
            let level = 0;
            for (let i = after + 1; i < this.usedTerms.length; i++) {
                let termData = this.usedTerms[i];

                if (termData.type === 'grouping_operator') {
                    if (this.isGroupClose(termData)) {
                        if (level === 0) {
                            return typeof termData.counterpart === 'undefined' ? i : null;
                        }

                        level--;
                    } else {
                        if (termData.counterpart >= 0) {
                            i = termData.counterpart;
                        } else {
                            level++;
                        }
                    }
                }
            }

            return null;
        }

        isGroupOpen(termData) {
            return termData.type === 'grouping_operator' && termData.search === this.grouping_operators.open.search;
        }

        isGroupClose(termData) {
            return termData.type === 'grouping_operator' && termData.search === this.grouping_operators.close.search;
        }

        getOperator(value, termType = null) {
            if (termType === null) {
                termType = this.termType;
            }

            let operators;
            switch (termType) {
                case 'operator':
                    operators = this.relational_operators;
                    break;
                case 'logical_operator':
                    operators = this.logical_operators;
                    break;
            }

            value = value.toLowerCase();
            return operators.find((term) => {
                return value === term.label.toLowerCase() || value === term.search.toLowerCase();
            }) || null;
        }

        matchOperators(operators, value) {
            value = value.toLowerCase();

            let exactMatch = false;
            let partialMatch = false;
            let filtered = operators.filter((op) => {
                let label = op.label.toLowerCase();
                let search = op.search.toLowerCase();

                if (
                    (value.length < label.length && value === label.slice(0, value.length))
                    || (value.length < search.length && value === search.slice(0, value.length))
                ) {
                    partialMatch = true;
                    return true;
                }

                if (value === label || value === search) {
                    exactMatch = true;
                    return true;
                }

                return false;
            });

            if (exactMatch || partialMatch) {
                operators = filtered;
            }

            operators.exactMatch = exactMatch;
            operators.partialMatches = partialMatch;

            return operators;
        }

        nextOperator(value, currentValue, termType = null, termIndex = null) {
            let operators = [];

            if (termType === null) {
                termType = this.termType;
            }

            if (termIndex === null && termType === 'column' && ! currentValue) {
                switch (true) {
                    case ! this.hasTerms():
                    case this.lastTerm().type === 'logical_operator':
                    case this.isGroupOpen(this.lastTerm()):
                        operators.push(this.grouping_operators.open);
                        operators.push(this.negationOperator);
                }
            } else if (termIndex === -1) {
                // This is more of a `previousOperator` thing here
                switch (termType) {
                    case 'column':
                        operators = operators.concat(this.logical_operators);
                    case 'logical_operator':
                        operators.push(this.grouping_operators.open);
                        operators.push(this.negationOperator);
                        break;
                    case 'negation_operator':
                        operators = operators.concat(this.logical_operators);
                        operators.push(this.grouping_operators.open);
                        break;
                    case 'grouping_operator':
                        if (this.isGroupOpen(this.usedTerms[0])) {
                            operators.push(this.grouping_operators.open);
                            operators.push(this.negationOperator);
                        }
                }
            } else {
                let nextIndex = termIndex === null ? this.usedTerms.length : termIndex + 1;
                switch (termType) {
                    case 'column':
                        operators = operators.concat(this.relational_operators);

                        if (! currentValue || (termIndex !== null && termIndex < this.usedTerms.length)) {
                            operators.push(this.grouping_operators.open);
                            operators.push(this.negationOperator);
                        }
                    case 'operator':
                    case 'value':
                        operators = operators.concat(this.logical_operators);

                        if (this.lastPendingGroupOpen(nextIndex) !== null) {
                            operators.push(this.grouping_operators.close);
                        }

                        break;
                    case 'logical_operator':
                        if (this.lastPendingGroupOpen(nextIndex) !== null) {
                            operators.push(this.grouping_operators.close);
                        }

                        if (termIndex !== null && termIndex < this.usedTerms.length) {
                            operators.push(this.grouping_operators.open);
                            operators.push(this.negationOperator);
                        }

                        break;
                    case 'negation_operator':
                        operators.push(this.grouping_operators.open);

                        break;
                    case 'grouping_operator':
                        let termData = this.usedTerms[termIndex];
                        if (this.isGroupOpen(termData)) {
                            operators.push(this.grouping_operators.open);
                            operators.push(this.negationOperator);
                        } else {
                            operators = operators.concat(this.logical_operators);

                            if (this.lastPendingGroupOpen(nextIndex)) {
                                operators.push(this.grouping_operators.close);
                            }
                        }
                }
            }

            return value ? this.matchOperators(operators, value) : operators;
        }

        validOperator(value, termType = null, termIndex = null) {
            let operators = [];

            if (termType === null) {
                termType = this.termType;
            }

            switch (termType) {
                case 'operator':
                    operators = operators.concat(this.relational_operators);
                    break;
                case 'logical_operator':
                    operators = operators.concat(this.logical_operators);
                    break;
                case 'negation_operator':
                    operators.push(this.negationOperator);
                    break;
                case 'grouping_operator':
                    let termData = this.usedTerms[termIndex];
                    if (termData.counterpart >= 0) {
                        let counterpart = this.usedTerms[termData.counterpart];
                        if (this.isGroupOpen(counterpart)) {
                            operators.push(this.grouping_operators.close);
                        } else {
                            operators.push(this.grouping_operators.open);
                        }
                    }
            }

            return value ? this.matchOperators(operators, value) : operators;
        }

        checkValidity(input, type = null, termIndex = null) {
            if (! super.checkValidity(input)) {
                return false;
            }

            if (type === null) {
                type = input.parentNode.dataset.type;
            }

            if (! type || type === 'value') {
                // type is undefined for the main input, values have no special validity rules
                return true;
            }

            if (termIndex === null && input.parentNode.dataset.index >= 0) {
                termIndex = Number(input.parentNode.dataset.index);
            }

            let value = this.readPartialTerm(input);

            let options;
            switch (type) {
                case 'operator':
                case 'logical_operator':
                case 'negation_operator':
                case 'grouping_operator':
                    options = this.validOperator(value, type, termIndex);
            }

            let message = '';
            if (type === 'column') {
                let nextTermAt = termIndex + 1;
                if (! value && nextTermAt < this.usedTerms.length && this.usedTerms[nextTermAt].type === 'operator') {
                    message = this.input.dataset.chooseColumn;
                }
            } else {
                let isRequired = ! options.exactMatch;
                if (type === 'negation_operator' && ! value) {
                    isRequired = false;
                } else if (type === 'operator' && ! value) {
                    let nextTermAt = termIndex + 1;
                    isRequired = nextTermAt < this.usedTerms.length && this.usedTerms[nextTermAt].type === 'value';
                } else if (type === 'logical_operator' && ! value) {
                    if (termIndex === 0 || termIndex === this.usedTerms.length - 1) {
                        isRequired = false;
                    } else {
                        isRequired = ! this.isGroupOpen(this.usedTerms[termIndex - 1])
                            && ! this.isGroupClose(this.usedTerms[termIndex + 1])
                            && this.usedTerms[termIndex - 1].type !== 'logical_operator'
                            && this.usedTerms[termIndex + 1].type !== 'logical_operator';
                    }
                } else if (type === 'grouping_operator') {
                    if (typeof this.usedTerms[termIndex].counterpart === 'undefined') {
                        if (value) {
                            message = this.input.dataset.incompleteGroup;
                        }

                        isRequired = false;
                    } else if (! value) {
                        isRequired = false;
                    }
                }

                if (isRequired) {
                    message = this.input.dataset.chooseTemplate.replace(
                        '%s',
                        options.map(e => e.label).join(', ')
                    );
                }
            }

            if (! message && termIndex > 0 && type !== 'logical_operator') {
                let previousTerm = this.usedTerms[termIndex - 1];

                let missingLogicalOp = true;
                switch (type) {
                    case 'column':
                        missingLogicalOp = ! ['logical_operator', 'negation_operator'].includes(previousTerm.type)
                            && ! this.isGroupOpen(previousTerm);
                        break;
                    case 'operator':
                        missingLogicalOp = previousTerm.type !== 'column';
                        break;
                    case 'value':
                        missingLogicalOp = previousTerm.type !== 'operator';
                        break;
                    case 'negation_operator':
                        missingLogicalOp = previousTerm.type !== 'logical_operator'
                            && ! this.isGroupOpen(previousTerm);
                        break;
                    case 'grouping_operator':
                        if (value === this.grouping_operators.open.label) {
                            missingLogicalOp = ! ['logical_operator', 'negation_operator'].includes(previousTerm.type)
                                && ! this.isGroupOpen(previousTerm);
                        } else {
                            missingLogicalOp = false;
                        }
                }

                if (missingLogicalOp) {
                    message = this.input.dataset.missingLogOp;
                }
            }

            input.setCustomValidity(message);
            return input.checkValidity();
        }

        renderSuggestions(suggestions) {
            let itemTemplate = $.render('<li><input type="button" tabindex="-1"></li>');

            let list = document.createElement('ul');

            suggestions.forEach((term) => {
                let item = itemTemplate.cloneNode(true);
                item.firstChild.value = term.label;

                for (let name in term) {
                    if (name === 'default') {
                        if (term[name]) {
                            item.classList.add('default');
                        }
                    } else {
                        item.firstChild.dataset[name] = term[name];
                    }
                }

                list.appendChild(item);
            });

            return list;
        }

        renderPreview(content) {
            return $.render('<span>' + content + '</span>');
        }

        renderCondition() {
            return $.render(
                '<div class="filter-condition" data-group-type="condition">'
                + '<button type="button"><i class="icon fa fa-trash"></i></button>'
                + '</div>'
            );
        }

        renderChain() {
            return $.render('<div class="filter-chain" data-group-type="chain"></div>');
        }

        renderTerm(termData, termIndex) {
            let label = super.renderTerm(termData, termIndex);
            label.dataset.type = termData.type;

            if (! termData.class) {
                label.classList.add(termData.type.replace('_', '-'));
            }

            if (termData.counterpart >= 0) {
                label.dataset.counterpart = termData.counterpart;
            }

            return label;
        }

        autoSubmit(input, changeType, data) {
            if (this.shouldNotAutoSubmit()) {
                return;
            }

            let changedTerms = [];
            if ('terms' in data) {
                changedTerms = data['terms'];
            }

            let changedIndices = Object.keys(changedTerms).sort((a, b) => a - b);
            if (! changedIndices.length) {
                return;
            }

            let lastTermAt;
            switch (changeType) {
                case 'add':
                case 'exchange':
                    lastTermAt = changedIndices.pop();
                    if (changedTerms[lastTermAt].type === 'value') {
                        if (! changedIndices.length) {
                            data['terms'] = {
                                ...{
                                    [lastTermAt - 2]: this.usedTerms[lastTermAt - 2],
                                    [lastTermAt - 1]: this.usedTerms[lastTermAt - 1]
                                },
                                ...changedTerms
                            };
                        }

                        break;
                    } else if (this.isGroupClose(changedTerms[lastTermAt])) {
                        break;
                    }

                    return;
                case 'insert':
                    lastTermAt = changedIndices.pop();
                    if ((changedTerms[lastTermAt].type === 'value' && changedIndices.length)
                        || this.isGroupClose(changedTerms[lastTermAt])
                        || (changedTerms[lastTermAt].type === 'negation_operator'
                            && lastTermAt < this.usedTerms.length - 1
                        )
                    ) {
                        break;
                    }

                    return;
                case 'save':
                    let updateAt = changedIndices[0];
                    let valueAt = updateAt;
                    switch (changedTerms[updateAt].type) {
                        case 'column':
                            if (changedTerms[updateAt].label !== this.usedTerms[updateAt].label) {
                                return;
                            }

                            valueAt++;
                        case 'operator':
                            valueAt++;
                    }

                    if (valueAt === updateAt) {
                        if (changedIndices.length === 1) {
                            data['terms'] = {
                                ...{
                                    [valueAt - 2]: this.usedTerms[valueAt - 2],
                                    [valueAt - 1]: this.usedTerms[valueAt - 1]
                                },
                                ...changedTerms
                            };
                        }

                        break;
                    } else if (this.usedTerms.length > valueAt && this.usedTerms[valueAt].type === 'value') {
                        break;
                    }

                    return;
                case 'remove':
                    let firstTermAt = changedIndices.shift();
                    if (changedTerms[firstTermAt].type === 'column'
                        || this.isGroupOpen(changedTerms[firstTermAt])
                        || changedTerms[firstTermAt].type === 'negation_operator'
                        || (changedTerms[firstTermAt].type === 'logical_operator' && changedIndices.length)
                    ) {
                        break;
                    }

                    return;
            }

            super.autoSubmit(input, changeType, data);
        }

        encodeTerm(termData) {
            if (termData.type === 'column' || termData.type === 'value') {
                termData = super.encodeTerm(termData);
                termData.search = termData.search.replace(
                    /[()]/g,
                    function(c) {
                        return '%' + c.charCodeAt(0).toString(16);
                    }
                );
            }

            return termData;
        }

        isTermDirectionVertical() {
            return false;
        }

        highlightTerm(label, highlightedBy = null) {
            label.classList.add('highlighted');

            let canBeHighlighted = (label) => ! ('highlightedBy' in label.dataset)
                && label.firstChild !== document.activeElement
                && (this.completer === null
                    || ! this.completer.isBeingCompleted(label.firstChild)
                );

            if (highlightedBy !== null) {
                if (canBeHighlighted(label)) {
                    label.dataset.highlightedBy = highlightedBy;
                }
            } else {
                highlightedBy = label.dataset.index;
            }

            let negationAt, previousIndex, nextIndex;
            switch (label.dataset.type) {
                case 'column':
                case 'operator':
                case 'value':
                    label.parentNode.querySelectorAll(':scope > [data-index]').forEach((otherLabel) => {
                        if (otherLabel !== label && canBeHighlighted(otherLabel)) {
                            otherLabel.classList.add('highlighted');
                            otherLabel.dataset.highlightedBy = highlightedBy;
                        }
                    });

                    negationAt = Number(label.dataset.index) - (
                        label.dataset.type === 'column'
                            ? 1 : label.dataset.type === 'operator'
                                ? 2 : 3
                    );
                    if (negationAt >= 0 && this.usedTerms[negationAt].type === 'negation_operator') {
                        let negationLabel = this.termContainer.querySelector(`[data-index="${ negationAt }"]`);
                        if (negationLabel !== null && canBeHighlighted(negationLabel)) {
                            negationLabel.classList.add('highlighted');
                            negationLabel.dataset.highlightedBy = highlightedBy;
                        }
                    }

                    break;
                case 'logical_operator':
                    previousIndex = Number(label.dataset.index) - 1;
                    if (previousIndex >= 0 && this.usedTerms[previousIndex].type !== 'logical_operator') {
                        this.highlightTerm(
                            this.termContainer.querySelector(`[data-index="${ previousIndex }"]`),
                            highlightedBy
                        );
                    }

                    nextIndex = Number(label.dataset.index) + 1;
                    if (nextIndex < this.usedTerms.length && this.usedTerms[nextIndex].type !== 'logical_operator') {
                        this.highlightTerm(
                            this.termContainer.querySelector(`[data-index="${ nextIndex }"]`),
                            highlightedBy
                        );
                    }

                    break;
                case 'negation_operator':
                    nextIndex = Number(label.dataset.index) + 1;
                    if (nextIndex < this.usedTerms.length) {
                        this.highlightTerm(
                            this.termContainer.querySelector(`[data-index="${ nextIndex }"]`),
                            highlightedBy
                        );
                    }

                    break;
                case 'grouping_operator':
                    negationAt = null;
                    if (this.isGroupOpen(label.dataset)) {
                        negationAt = Number(label.dataset.index) - 1;
                    }

                    if (label.dataset.counterpart >= 0) {
                        let otherLabel = this.termContainer.querySelector(
                            `[data-index="${ label.dataset.counterpart }"]`
                        );
                        if (otherLabel !== null) {
                            if (negationAt === null) {
                                negationAt = Number(otherLabel.dataset.index) - 1;
                            }

                            if (canBeHighlighted(otherLabel)) {
                                otherLabel.classList.add('highlighted');
                                otherLabel.dataset.highlightedBy = highlightedBy;
                            }
                        }
                    }

                    if (negationAt >= 0 && this.usedTerms[negationAt].type === 'negation_operator') {
                        let negationLabel = this.termContainer.querySelector(`[data-index="${ negationAt }"]`);
                        if (negationLabel !== null && canBeHighlighted(negationLabel)) {
                            negationLabel.classList.add('highlighted');
                            negationLabel.dataset.highlightedBy = highlightedBy;
                        }
                    }
            }
        }

        deHighlightTerm(label) {
            if (! ('highlightedBy' in label.dataset)) {
                label.classList.remove('highlighted');
            }

            this.termContainer.querySelectorAll(`[data-highlighted-by="${ label.dataset.index }"]`).forEach(
                (label) => {
                    label.classList.remove('highlighted');
                    delete label.dataset.highlightedBy;
                }
            );
        }

        /**
         * Event listeners
         */

        onTermFocusOut(event) {
            let label = event.currentTarget;
            if (this.completer === null || ! this.completer.isBeingCompleted(label.firstChild, event.relatedTarget)) {
                this.deHighlightTerm(label);
            }

            if (['column', 'value'].includes(label.dataset.type) || ! this.readPartialTerm(label.firstChild)) {
                super.onTermFocusOut(event);
            }
        }

        onTermFocus(event) {
            let input = event.target;
            let isTerm = input.parentNode.dataset.index >= 0;
            let termType = input.parentNode.dataset.type || this.termType;

            if (isTerm) {
                this.highlightTerm(input.parentNode);
            }

            let value = this.readPartialTerm(input);
            if (! value && (termType === 'column' || termType === 'value')) {
                if (isTerm) {
                    this.validate(input);
                }

                // No automatic suggestions without input
                return;
            }

            super.onTermFocus(event);
        }

        onTermClick(event) {
            if (this.disabled) {
                return;
            }

            let input = event.target;
            let termType = input.parentNode.dataset.type;

            if (['logical_operator', 'operator'].includes(termType)) {
                this.complete(input, { trigger: 'script', term: { label: this.readPartialTerm(input) } });
            }
        }

        onTermHover(event) {
            if (this.disabled) {
                return;
            }

            let label = event.currentTarget;

            if (['column', 'operator', 'value'].includes(label.dataset.type)) {
                // This adds an attr to delay the remove button. If it's shown instantly upon hover
                // it's too easy to accidentally click it instead of the desired grouping operator.
                label.parentNode.dataset.hoverDelay = "";
                setTimeout(function () {
                    delete label.parentNode.dataset.hoverDelay;
                }, 500);
            }

            this.highlightTerm(label);
        }

        onTermLeave(event) {
            if (this.disabled) {
                return;
            }

            let label = event.currentTarget;
            if (label.firstChild !== document.activeElement
                && (this.completer === null || ! this.completer.isBeingCompleted(label.firstChild))
            ) {
                this.deHighlightTerm(label);
            }
        }

        onRemoveCondition(event) {
            let button = event.target.closest('button');
            let labels = Array.from(button.parentNode.querySelectorAll(':scope > [data-index]'));

            let previous = button.parentNode.previousSibling;
            let next = button.parentNode.nextSibling;

            while (previous !== null || next !== null) {
                if (previous !== null && previous.dataset.type === 'negation_operator') {
                    labels.unshift(previous);
                    previous = previous.previousSibling;
                }

                if (next !== null && next.dataset.type === 'logical_operator') {
                    labels.push(next);
                    next = next.nextSibling;
                } else if (previous !== null && previous.dataset.type === 'logical_operator') {
                    labels.unshift(previous);
                    previous = previous.previousSibling;
                }

                if (
                    previous && previous.dataset.type === 'grouping_operator'
                    && next && next.dataset.type === 'grouping_operator'
                ) {
                    labels.unshift(previous);
                    labels.push(next);
                    previous = next.parentNode !== null ? next.parentNode.previousSibling : null;
                    next = next.parentNode !== null ? next.parentNode.nextSibling : null;
                } else {
                    break
                }
            }

            this.autoSubmit(this.input, 'remove', { terms: this.removeRange(labels) });
            this.togglePlaceholder();
        }

        onCompletion(event) {
            super.onCompletion(event);

            if (event.target.parentNode.dataset.index >= 0) {
                return;
            }

            if (this.termType === 'operator' || this.termType === 'logical_operator') {
                this.complete(this.input, { term: { label: '' } });
            }
        }

        onKeyDown(event) {
            super.onKeyDown(event);
            if (event.defaultPrevented) {
                return;
            }

            let input = event.target;
            let isTerm = input.parentNode.dataset.index >= 0;

            if (this.hasSyntaxError(input)) {
                return;
            }

            let currentValue = this.readPartialTerm(input);
            if (isTerm && ! currentValue) {
                // Switching contexts requires input first
                return;
            } else if (input.selectionStart !== input.selectionEnd) {
                // In case the user selected a range of text, do nothing
                return;
            } else if (/[A-Z]/.test(event.key.charAt(0)) || event.ctrlKey || event.metaKey) {
                // Ignore control keys not resulting in new input data
                // TODO: Remove this and move the entire block into `onInput`
                //       once Safari supports `InputEvent.data`
                return;
            }

            let termIndex = null;
            let termType = this.termType;
            if (isTerm) {
                if (input.selectionEnd === input.value.length) {
                    // Cursor is at the end of the input
                    termIndex = Number(input.parentNode.dataset.index);
                    termType = input.parentNode.dataset.type;
                } else if (input.selectionStart === 0) {
                    // Cursor is at the start of the input
                    termIndex = Number(input.parentNode.dataset.index);
                    if (termIndex === 0) {
                        // TODO: This is bad, if it causes problems, replace it
                        //       with a proper `previousOperator` implementation
                        termType = this.usedTerms[termIndex].type;
                        termIndex -= 1;
                    } else {
                        termIndex -= 1;
                        termType = this.usedTerms[termIndex].type;
                    }
                } else {
                    // In case the cursor is somewhere in between, do nothing
                    return;
                }

                if (termIndex > -1 && termIndex < this.usedTerms.length - 1) {
                    let nextTerm = this.usedTerms[termIndex + 1];
                    if (nextTerm.type === 'operator' || nextTerm.type === 'value') {
                        // In between parts of a condition there's no context switch possible at all
                        return;
                    }
                }
            } else if (input.selectionEnd !== input.value.length) {
                // Main input processing only happens at the end of the input
                return;
            }

            let operators;
            let value = event.key;
            if (! isTerm || termType === 'operator') {
                operators = this.validOperator(
                    termType === 'operator' ? currentValue + value : value, termType, termIndex);
                if (! operators.exactMatch && ! operators.partialMatches) {
                    operators = this.nextOperator(value, currentValue, termType, termIndex);
                }
            } else {
                operators = this.nextOperator(value, currentValue, termType, termIndex);
            }

            if (isTerm) {
                let newTerm = null;
                let exactMatchOnly = operators.exactMatch && ! operators.partialMatches;
                if (exactMatchOnly && operators[0].label.toLowerCase() !== value.toLowerCase()) {
                    // The user completes a partial match
                } else if (exactMatchOnly && (termType !== 'operator' || operators[0].type !== 'operator')) {
                    newTerm = { ...operators[0] };
                } else if (operators.partialMatches && termType !== 'operator') {
                    newTerm = { ...operators[0], label: value, search: value };
                } else {
                    // If no match is found, the user continues typing
                    switch (termType) {
                        case 'operator':
                            newTerm = { label: value, search: value, type: 'value' };
                            break;
                        case 'logical_operator':
                        case 'negation_operator':
                            newTerm = { label: value, search: value, type: 'column' };
                            break;
                    }
                }

                if (newTerm !== null) {
                    let label = this.insertTerm(newTerm, termIndex + 1);
                    this.autoSubmit(label.firstChild, 'insert', { terms: { [termIndex + 1]: newTerm } });
                    this.complete(label.firstChild, { term: newTerm });
                    $(label.firstChild).focus({ scripted: true });
                    event.preventDefault();
                }
            } else {
                if (operators.partialMatches) {
                    this.exchangeTerm();
                    this.togglePlaceholder();
                } else if (operators.exactMatch) {
                    if (termType !== operators[0].type) {
                        this.autoSubmit(input, 'exchange', { terms: this.exchangeTerm() });
                    } else {
                        this.clearPartialTerm(input);
                    }

                    this.addTerm({ ...operators[0] });
                    this.autoSubmit(input, 'add', { terms: { [this.usedTerms.length - 1]: operators[0] } });
                    this.togglePlaceholder();
                    event.preventDefault();
                } else if (termType === 'operator') {
                    let partialOperator = this.getOperator(currentValue);
                    if (partialOperator !== null) {
                        // If no match is found, the user seems to want the partial operator.
                        this.addTerm({ ...partialOperator });
                        this.clearPartialTerm(input);
                    }
                }
            }
        }

        onInput(event) {
            let input = event.target;
            if (this.hasSyntaxError(input)) {
                return super.onInput(event);
            }

            let termIndex = Number(input.parentNode.dataset.index);
            let isTerm = termIndex >= 0;

            if (! isTerm && (this.termType === 'operator' || this.termType === 'logical_operator')) {
                let value = this.readPartialTerm(input);

                if (value && ! this.validOperator(value).partialMatches) {
                    let defaultTerm = this.termType === 'operator'
                        ? { ...this.relational_operators[0] }
                        : { ...this.logical_operators[0] };

                    if (value !== defaultTerm.label) {
                        this.addTerm(defaultTerm);
                        this.togglePlaceholder();
                    } else {
                        this.exchangeTerm();
                        this.togglePlaceholder();
                    }
                }
            }

            super.onInput(event);

            if (isTerm && input.checkValidity()) {
                let value = this.readPartialTerm(input);
                if (value && ! ['column', 'value'].includes(input.parentNode.dataset.type)) {
                    this.autoSubmit(input, 'save', { terms: { [termIndex]: this.saveTerm(input) } });
                }
            }
        }

        onPaste(event) {
            if (! this.hasTerms()) {
                this.submitTerms(event.clipboardData.getData('text/plain'));
                event.preventDefault();
            } else if (! this.input.value) {
                let terms = event.clipboardData.getData('text/plain');
                if (this.termType === 'logical_operator') {
                    if (! this.validOperator(terms[0]).exactMatch) {
                        this.registerTerm({ ...this.logical_operators[0] });
                    }
                } else if (this.termType !== 'column') {
                    return;
                }

                this.submitTerms(this.termsToQueryString(this.usedTerms) + terms);
                event.preventDefault();
            }
        }
    }

    return FilterInput;
});
