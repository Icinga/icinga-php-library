define(["../notjQuery"], function ($) {

    "use strict";

    class CopyToClipboard {
        constructor(button)
        {
            button.classList.add('active');
            button.removeAttribute('tabindex');
            $(button).on('click', null, this.onClick, this);
        }

        onClick(event)
        {
            let button = event.currentTarget;
            let clipboardSource = button.parentElement.querySelector("[data-clipboard-source]");
            let copyText;

            if (clipboardSource) {
                copyText = this.collectText(clipboardSource);
            } else {
                throw new Error('Clipboard source is required but not provided');
            }

            if (navigator.clipboard) {
                navigator.clipboard.writeText(copyText).then(() => {
                    let previousHtml = button.innerHTML;
                    button.innerText = button.dataset.copiedLabel;
                    button.classList.add('copied');

                    setTimeout(() => {
                        // after 4 second, reset it.
                        button.classList.remove('copied');
                        button.innerHTML = previousHtml;
                    }, 4000);
                }).catch((err) => {
                    console.error('Failed to copy: ', err);
                });
            } else {
                throw new Error('Copy to clipboard requires HTTPS connection');
            }

            event.stopPropagation();
            event.preventDefault();
        }

        collectText(node)
        {
            let text = '';

            for (const child of node.childNodes) {
                if (child.nodeType === Node.TEXT_NODE) {
                    text += child.nodeValue;
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    if (child.tagName === 'BR') {
                        text += '\n';
                        continue;
                    }

                    const style = window.getComputedStyle(child);
                    if (style.display === 'none' || style.visibility === 'hidden') {
                        continue;
                    }

                    text += this.collectText(child);
                }
            }

            return text;
        }
    }

    return CopyToClipboard;
});
