define(["../notjQuery"], function ($) {

    "use strict";

    class Completer {
        constructor(input, instrumented = false) {
            this.input = input;
            this.instrumented = instrumented;
            this.nextSuggestion = null;
            this.activeSuggestion = null;
            this.suggestionKiller = null;
            this.completedInput = null;
            this.completedValue = null;
            this.completedData = null;
            this._termSuggestions = null;
        }

        get termSuggestions() {
            if (this._termSuggestions === null) {
                this._termSuggestions = document.querySelector(this.input.dataset.termSuggestions);
            }

            return this._termSuggestions;
        }

        bind(to = null) {
            // Form submissions
            $(this.input.form).on('submit', this.onSubmit, this);

            // User interactions
            $(this.termSuggestions).on('focusout', '[type="button"]', this.onFocusOut, this);
            $(this.termSuggestions).on('click', '[type="button"]', this.onSuggestionClick, this);
            $(this.termSuggestions).on('keydown', '[type="button"]', this.onSuggestionKeyDown, this);

            if (this.instrumented) {
                if (to !== null) {
                    $(to).on('focusout', 'input[type="text"]', this.onFocusOut, this);
                    $(to).on('keydown', 'input[type="text"]', this.onKeyDown, this);
                    $(to).on('complete', 'input[type="text"]', this.onComplete, this);
                }

                $(this.input).on('complete', this.onComplete, this);
            } else {
                $(this.input).on('input', this.onInput, this);
            }

            $(this.input).on('focusout', this.onFocusOut, this);
            $(this.input).on('keydown', this.onKeyDown, this);

            return this;
        }

        refresh(input, bindTo = null) {
            if (input === this.input) {
                // If the DOM node is still the same, nothing has changed
                return;
            }

            this._termSuggestions = null;
            this.abort();

            this.input = input;
            this.bind(bindTo);
        }

        reset() {
            this.abort();
            this.hideSuggestions();
        }

        destroy() {
            this._termSuggestions = null;
            this.input = null;
        }

        renderSuggestions(html) {
            let template = document.createElement('template');
            template.innerHTML = html;

            return template.content;
        }

        showSuggestions(suggestions, input) {
            this.termSuggestions.innerHTML = '';
            this.termSuggestions.appendChild(suggestions);
            this.termSuggestions.style.display = '';

            let containingBlock = this.termSuggestions.offsetParent || document.body;
            let containingBlockRect = containingBlock.getBoundingClientRect();
            let inputRect = input.getBoundingClientRect();
            let inputPosX = inputRect.left - containingBlockRect.left;
            let inputPosY = inputRect.bottom - containingBlockRect.top;
            let suggestionWidth = this.termSuggestions.offsetWidth;

            let maxAvailableHeight = document.body.clientHeight - inputRect.bottom;
            let localMarginBottom = window.getComputedStyle(this.termSuggestions).marginBottom;

            this.termSuggestions.style.top = `${ inputPosY }px`;
            this.termSuggestions.style.maxHeight = `calc(${maxAvailableHeight}px - ${localMarginBottom})`;
            if (inputPosX + suggestionWidth > containingBlockRect.right - containingBlockRect.left) {
                this.termSuggestions.style.left =
                    `${ containingBlockRect.right - containingBlockRect.left - suggestionWidth }px`;
            } else {
                this.termSuggestions.style.left = `${ inputPosX }px`;
            }
        }

        hasSuggestions() {
            return this.termSuggestions.childNodes.length > 0;
        }

        hideSuggestions() {
            if (this.nextSuggestion !== null || this.activeSuggestion !== null) {
                return;
            }

            if (this.suggestionKiller !== null) {
                // onFocusOut initiates this timer in order to hide the suggestions if the user
                // doesn't navigate them. Since it does this by checking after a short interval
                // if the focus is inside the suggestions, the interval has to be long enough to
                // have a chance to detect the focus. `focusout` runs before `blur` and `focus`,
                // so this may lead to a race condition which is addressed by the timeout. Though,
                // to not close the newly opened suggestions of the next input the timer has to
                // be cancelled here since it's purpose is already fulfilled.
                clearTimeout(this.suggestionKiller);
                this.suggestionKiller = null;
            }

            this.termSuggestions.style.display = 'none';
            this.termSuggestions.innerHTML = '';

            this.completedInput = null;
            this.completedValue = null;
            this.completedData = null;
        }

        prepareCompletionData(input, data = null) {
            if (data === null) {
                data = { term: { ...input.dataset } };
                data.term.label = input.value;
            }

            let value = data.term.label;
            data.term.search = value;
            data.term.label = this.addWildcards(value);

            if (input.parentElement instanceof HTMLFieldSetElement) {
                for (let element of input.parentElement.elements) {
                    if (element !== input
                        && element.name !== input.name + '-search'
                        && (element.name.substr(-7) === '-search'
                            || typeof input.form[element.name + '-search'] === 'undefined')
                    ) {
                        // Make sure we'll use a key that the server can understand..
                        let dataName = element.name;
                        if (dataName.substr(-7) === '-search') {
                            dataName = dataName.substr(0, dataName.length - 7);
                        }
                        if (dataName.substr(0, input.parentElement.name.length) === input.parentElement.name) {
                            dataName = dataName.substr(input.parentElement.name.length);
                        }

                        if (! dataName in data || element.value) {
                            data[dataName] = element.value;
                        }
                    }
                }
            }

            return [value, data];
        }

        addWildcards(value) {
            if (! value) {
                return '*';
            }

            if (value.slice(0, 1) !== '*' && value.slice(-1) !== '*') {
                return '*' + value + '*';
            }

            return value;
        }

        abort() {
            if (this.activeSuggestion !== null) {
                this.activeSuggestion.abort();
                this.activeSuggestion = null;
            }

            if (this.nextSuggestion !== null) {
                clearTimeout(this.nextSuggestion);
                this.nextSuggestion = null;
            }
        }

        requestCompletion(input, data, trigger = 'user') {
            this.abort();

            this.nextSuggestion = setTimeout(() => {
                let req = new XMLHttpRequest();
                req.open('POST', this.input.dataset.suggestUrl, true);
                req.setRequestHeader('Content-Type', 'application/json');

                if (typeof icinga !== 'undefined') {
                    let windowId = icinga.ui.getWindowId();
                    let containerId = icinga.ui.getUniqueContainerId(this.termSuggestions);
                    if (containerId) {
                        req.setRequestHeader('X-Icinga-WindowId', windowId + '_' + containerId);
                    } else {
                        req.setRequestHeader('X-Icinga-WindowId', windowId);
                    }
                }

                req.addEventListener('loadend', () => {
                    if (req.readyState > 0) {
                        if (req.responseText) {
                            let suggestions = this.renderSuggestions(req.responseText);
                            if (trigger === 'script') {
                                // If the suggestions are to be displayed due to a scripted event,
                                // show them only if the completed input is still focused..
                                if (document.activeElement === input) {
                                    this.showSuggestions(suggestions, input);
                                }
                            } else {
                                this.showSuggestions(suggestions, input);
                            }
                        } else {
                            this.hideSuggestions();
                        }
                    }

                    this.activeSuggestion = null;
                    this.nextSuggestion = null;
                });

                req.send(JSON.stringify(data));

                this.activeSuggestion = req;
            }, 200);
        }

        suggest(input, value, data = {}) {
            if (this.instrumented) {
                if (! Object.keys(data).length) {
                    data = value;
                }

                $(input).trigger('suggestion', data);
            } else {
                input.value = value;
            }
        }

        complete(input, value, data) {
            $(input).focus({ scripted: true });

            if (this.instrumented) {
                if (! Object.keys(data).length) {
                    data = value;
                }

                $(input).trigger('completion', data);
            } else {
                input.value = value;

                for (let name in data) {
                    let dataElement = input.form[input.name + '-' + name];
                    if (typeof dataElement !== 'undefined') {
                        if (dataElement instanceof RadioNodeList) {
                            dataElement = dataElement[dataElement.length - 1];
                        }

                        dataElement.value = data[name];
                    } else if (name === 'title') {
                        input.title = data[name];
                    }
                }
            }

            this.hideSuggestions();
        }

        moveToSuggestion(backwards = false) {
            let focused = this.termSuggestions.querySelector('[type="button"]:focus');
            let inputs = Array.from(this.termSuggestions.querySelectorAll('[type="button"]'));

            let input;
            if (focused !== null) {
                let sibling = inputs[backwards ? inputs.indexOf(focused) - 1 : inputs.indexOf(focused) + 1];
                if (sibling) {
                    input = sibling;
                } else {
                    input = this.completedInput;
                }
            } else {
                input = inputs[backwards ? inputs.length - 1 : 0];
            }

            $(input).focus();

            if (this.completedValue !== null) {
                if (input === this.completedInput) {
                    this.suggest(this.completedInput, this.completedValue);
                } else {
                    this.suggest(this.completedInput, input.value, { ...input.dataset });
                }
            }

            return input;
        }

        isBeingCompleted(input, activeElement = null) {
            if (activeElement === null) {
                activeElement = document.activeElement;
            }

            return input === this.completedInput && (
                (! activeElement && this.hasSuggestions())
                    || (activeElement && this.termSuggestions.contains(activeElement))
            );
        }

        /**
         * Event listeners
         */

        onSubmit(event) {
            // Reset all states, the user is about to navigate away
            this.reset();
        }

        onFocusOut(event) {
            if (this.completedInput === null) {
                // If there are multiple instances of Completer bound to the same suggestion container
                // all of them try to handle the event. Though, only one of them is responsible and
                // that's the one which has a completed input set.
                return;
            }

            let input = event.target;
            let completedInput = this.completedInput;
            this.suggestionKiller = setTimeout(() => {
                if (completedInput !== this.completedInput) {
                    // Don't hide another input's suggestions
                } else if (document.activeElement !== completedInput
                    && ! this.termSuggestions.contains(document.activeElement)
                ) {
                    // Hide the suggestions if the user doesn't navigate them
                    if (input !== completedInput) {
                        // Restore input if a suggestion lost focus
                        this.suggest(completedInput, this.completedValue);
                    }

                    this.hideSuggestions();
                }
            }, 250);
        }

        onSuggestionKeyDown(event) {
            if (this.completedInput === null) {
                return;
            }

            switch (event.key) {
                case 'Escape':
                    $(this.completedInput).focus({ scripted: true });
                    this.suggest(this.completedInput, this.completedValue);
                    break;
                case 'Tab':
                    event.preventDefault();
                    this.moveToSuggestion(event.shiftKey);
                    break;
                case 'ArrowLeft':
                case 'ArrowUp':
                    event.preventDefault();
                    this.moveToSuggestion(true);
                    break;
                case 'ArrowRight':
                case 'ArrowDown':
                    event.preventDefault();
                    this.moveToSuggestion();
                    break;
            }
        }

        onSuggestionClick(event) {
            if (this.completedInput === null) {
                return;
            }

            let input = event.currentTarget;

            this.complete(this.completedInput, input.value, { ...input.dataset });
        }

        onKeyDown(event) {
            let suggestions;

            switch (event.key) {
                case ' ':
                    if (this.instrumented) {
                        break;
                    }

                    let input = event.target;

                    if (! input.value) {
                        if (input.minLength <= 0) {
                            let [value, data] = this.prepareCompletionData(input);
                            this.completedInput = input;
                            this.completedValue = value;
                            this.completedData = data;
                            this.requestCompletion(input, data);
                        }

                        event.preventDefault();
                    }

                    break;
                case 'Tab':
                    suggestions = this.termSuggestions.querySelectorAll('[type="button"]');
                    if (suggestions.length === 1) {
                        event.preventDefault();
                        let input = event.target;
                        let suggestion = suggestions[0];

                        this.complete(input, suggestion.value, { ...suggestion.dataset });
                    }

                    break;
                case 'Enter':
                    let defaultSuggestion = this.termSuggestions.querySelector('.default > [type="button"]');
                    if (defaultSuggestion !== null) {
                        event.preventDefault();
                        let input = event.target;

                        this.complete(input, defaultSuggestion.value, { ...defaultSuggestion.dataset });
                    }

                    break;
                case 'Escape':
                    if (this.hasSuggestions()) {
                        this.hideSuggestions()
                        event.preventDefault();
                    }

                    break;
                case 'ArrowUp':
                    suggestions = this.termSuggestions.querySelectorAll('[type="button"]');
                    if (suggestions.length) {
                        event.preventDefault();
                        this.moveToSuggestion(true);
                    }

                    break;
                case 'ArrowDown':
                    suggestions = this.termSuggestions.querySelectorAll('[type="button"]');
                    if (suggestions.length) {
                        event.preventDefault();
                        this.moveToSuggestion();
                    }

                    break;
                default:
                    if (/[A-Z]/.test(event.key.charAt(0)) || event.key === '"') {
                        // Ignore control keys not resulting in new input data
                        break;
                    }

                    let typedSuggestion = this.termSuggestions.querySelector(`[value="${ event.key }"]`);
                    if (typedSuggestion !== null) {
                        this.hideSuggestions();
                    }
            }
        }

        onInput(event) {
            let input = event.target;

            if (input.minLength > 0 && input.value.length < input.minLength) {
                return;
            }

            // Set the input's value as search value. This ensures that if the user doesn't
            // choose a suggestion, an up2date contextual value will be transmitted with
            // completion requests and the server can properly identify a new value upon submit
            input.dataset.search = input.value;
            if (typeof input.form[input.name + '-search'] !== 'undefined') {
                let dataElement = input.form[input.name + '-search'];
                if (dataElement instanceof RadioNodeList) {
                    dataElement = dataElement[dataElement.length - 1];
                }

                dataElement.value = input.value;
            }

            let [value, data] = this.prepareCompletionData(input);
            this.completedInput = input;
            this.completedValue = value;
            this.completedData = data;
            this.requestCompletion(input, data);
        }

        onComplete(event) {
            let input = event.target;
            let { trigger = 'user' , ...detail } = event.detail;

            let [value, data] = this.prepareCompletionData(input, detail);
            this.completedInput = input;
            this.completedValue = value;
            this.completedData = data;

            if (typeof data.suggestions !== 'undefined') {
                this.showSuggestions(data.suggestions, input);
            } else {
                this.requestCompletion(input, data, trigger);
            }
        }
    }

    return Completer;
});
