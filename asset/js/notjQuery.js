define(function () {

    "use strict";

    class notjQuery {
        /**
         * Create a new notjQuery object
         *
         * @param {Element} element
         */
        constructor(element) {
            if (! element) {
                throw new Error("Can't create a notjQuery object for `" + element + "`");
            }

            this.element = element;
        }

        /**
         * Add an event listener to the element
         *
         * @param {string} type
         * @param {string} selector
         * @param {function} handler
         * @param {object} context
         */
        on(type, selector, handler, context = null) {
            if (typeof selector === 'function') {
                context = handler;
                handler = selector;
                selector = null;
            }

            if (selector === null) {
                this.element.addEventListener(type, e => {
                    if (context === null) {
                        handler.apply(e.currentTarget, [e]);
                    } else {
                        handler.apply(context, [e]);
                    }
                });
            } else {
                this.element.addEventListener(type, e => {
                    if (type === 'focusin' && e.target.receivesCustomFocus) {
                        // Ignore native focus event if a custom one follows
                        if (e instanceof FocusEvent) {
                            delete e.target.receivesCustomFocus;
                            e.stopImmediatePropagation();
                            return;
                        }
                    }

                    Object.defineProperty(e, 'currentTarget', { value: e.currentTarget, writable: true });

                    let currentParent = e.currentTarget.parentNode;
                    for (let target = e.target; target && target !== currentParent; target = target.parentNode) {
                        if (target.matches(selector)) {
                            e.currentTarget = target;
                            if (context === null) {
                                handler.apply(target, [e]);
                            } else {
                                handler.apply(context, [e]);
                            }

                            break;
                        }
                    }
                }, false);
            }
        }

        /**
         * Trigger a custom event on the element, asynchronously
         *
         * The event will bubble and is not cancelable.
         *
         * @param {string} type
         * @param {{}} detail
         */
        trigger(type, detail = null) {
            setTimeout(() => {
                this.element.dispatchEvent(new CustomEvent(type, {
                    cancelable: true, // TODO: this should depend on whether it's a native or custom event
                    bubbles: true,
                    detail: detail
                }));
            }, 0);
        }

        /**
         * Focus the element
         *
         * Any other option than `preventScroll` is used as `event.detail`.
         *
         * @param {{}} options
         */
        focus(options = {}) {
            let { preventScroll = false, ...data } = options;

            const hasData = Object.keys(data).length > 0;
            if (hasData) {
                this.element.receivesCustomFocus = true;
            }

            // Put separately on the event loop because focus() forces layout.
            setTimeout(() => this.element.focus({ preventScroll: preventScroll }), 0);

            if (hasData) {
                this.trigger('focusin', data);
            }
        }

        /**
         * Render the element string as DOM Element
         *
         * @param {string} html
         * @return {Element}
         */
        static render(html) {
            if (typeof html !== 'string') {
                throw new Error("Can\'t render `" + html + "`");
            }

            let template = document.createElement('template');
            template.innerHTML = html;
            return template.content.firstChild;
        }
    }

    /**
     * Return a notjQuery object for the given element
     *
     * @param {Element} element
     * @return {notjQuery}
     */
    let factory = function (element) {
        return new notjQuery(element);
    }

    // Define the static methods on the factory
    for (let name of Object.getOwnPropertyNames(notjQuery)) {
        if (['length', 'prototype', 'name'].includes(name)) {
            continue;
        }

        Object.defineProperty(factory, name, {
            value: notjQuery[name]
        });
    }

    return factory;
});
