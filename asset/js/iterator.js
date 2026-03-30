(function (root, factory) {
    "use strict";

    if (typeof define === "function" && define.icinga) {
        define(["exports"], factory);
    } else {
        factory(root.icingaIteratorPolyfill = root.icingaIteratorPolyfill || {});
    }
}(self, function (exports) {
    /**
     * Polyfill for `Iterator.filter`
     *
     * @param {Symbol.iterator} iterator
     * @param {function} callback
     * @returns {Generator<*, void, *>}
     */
    function* filter(iterator, callback) {
        if (typeof iterator.filter === "function") {
            yield* iterator.filter(callback);
        }

        for (const item of iterator) {
            if (callback(item)) {
                yield item;
            }
        }
    }

    /**
     * Polyfill for `Iterator.find`
     *
     * @param {Symbol.iterator} iterator
     * @param {function} callback
     * @returns {*}
     */
    function find(iterator, callback) {
        if (typeof iterator.find === "function") {
            return iterator.find(callback);
        }

        for (const item of iterator) {
            if (callback(item)) {
                return item;
            }
        }
    }

    /**
     * Polyfill for `Iterator.map`
     *
     * @param {Symbol.iterator} iterator
     * @param {function} callback
     * @returns {Generator<*, void, *>}
     */
    function* map(iterator, callback) {
        if (typeof iterator.map === "function") {
            yield* iterator.map(callback);
        }

        for (const item of iterator) {
            yield callback(item);
        }
    }

    /**
     * Find the first key in the map whose value satisfies the provided testing function.
     * @param {Map} map
     * @param {function} callback Passed arguments are: value, key, map
     * @returns {*} Returns undefined if no key satisfies the testing function.
     */
    function findKey(map, callback) {
        for (const key of findKeys(map, callback)) {
            return key;
        }
    }

    /**
     * Find all keys in the map whose value satisfies the provided testing function.
     * @param {Map} map
     * @param {function} callback Passed arguments are: value, key, map
     * @returns {Generator<*, void, *>}
     */
    function* findKeys(map, callback) {
        for (const [ key, value ] of map) {
            if (callback(value, key, map)) {
                yield key;
            }
        }
    }

    exports.findKeys = findKeys;
    exports.findKey = findKey;
    exports.filter = filter;
    exports.find = find;
    exports.map = map;
}));
