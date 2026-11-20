(function (root, factory) {
    "use strict";

    if (typeof define === "function" && define.icinga) {
        define(["exports"], factory);
    } else {
        factory(root.iplWebFunctions = root.iplWebFunctions || {});
    }
}(self, function (exports) {
    /**
     * Checks if the given keyboard event represents a special key press.
     *
     * @param {KeyboardEvent} event - The keyboard event to check.
     * @returns {boolean} True if the event represents a special key press, false otherwise.
     */
    function isSpecialKeyPress(event) {
        return event.key.length > 1 || event.ctrlKey || event.metaKey;
    }

    exports.isSpecialKeyPress = isSpecialKeyPress;
}));
