define(function () {

    "use strict";

    class RelativeTime {

        static DYNAMIC_RELATIVE_TIME_THRESHOLD = 60 * 60;

        constructor(timezone) {
            this.timezone = timezone;
            /**
             * Walked by tick() to update the text on each element
             *
             * @type {Set<WeakRef<Element>>}
             * @private
             */
            this._refsToUpdate = new Set();
            /**
             * Checked by scan() before adding to _refsToUpdate, to avoid tracking the same element twice
             *
             * @type {WeakSet<Element>}
             * @private
             */
            this._knownElements = new WeakSet();
            this._timer = null;
        }

        scan(root) {
            const elements = root.querySelectorAll('time[data-relative-time], .time-ago, .time-since, .time-until');
            if (elements.length === 0) {
                return;
            }

            elements.forEach((el) => {
                if (! this._knownElements.has(el)) {
                    this._knownElements.add(el);
                    this._refsToUpdate.add(new WeakRef(el));
                }
            });

            if (! this._timer) {
                this._timer = setInterval(() => this.tick(), 1000);
            }
        }

        tick() {
            this._refsToUpdate.forEach((ref) => {
                const el = ref.deref();
                if (el) {
                    this.updateElement(el);
                } else {
                    this._refsToUpdate.delete(ref);
                }
            })
        }

        stop() {
            if (this._timer !== null) {
                clearInterval(this._timer);
                this._timer = null;
            }
        }

        updateElement(element) {
            if (element.hidden) {
                return;
            }

            const relativeTimeAgo = this.getType(element);
            if (relativeTimeAgo === 'ago' || relativeTimeAgo === 'since') {
                const diffSeconds = this.getTimeDifferenceInSeconds(element);
                if (diffSeconds == null || diffSeconds >= RelativeTime.DYNAMIC_RELATIVE_TIME_THRESHOLD) {
                    return;
                }

                element.textContent = element.textContent.replace(
                    /-?\d+m \d+s/,
                    this.render(diffSeconds)
                );
            } else if (relativeTimeAgo === 'until') {
                const remainingSeconds = this.getTimeDifferenceInSeconds(element, true);
                if (
                    remainingSeconds == null
                    || Math.abs(remainingSeconds) >= RelativeTime.DYNAMIC_RELATIVE_TIME_THRESHOLD
                ) {
                    return;
                }

                if (remainingSeconds <= 0 && element.dataset.agoLabel) {
                    element.textContent = element.dataset.agoLabel;
                    element.dataset.relativeTime = 'ago';
                }

                element.textContent = element.textContent.replace(
                    /-?\d+m \d+s/,
                    this.render(remainingSeconds)
                );
            }
        }

        getTimeDifferenceInSeconds(element, future = false) {
            const timeString = this.getDateTime(element);
            const isoString = timeString.replace(' ', 'T');

            const offset = this.getOffset();

            const targetTimeUTC = Date.parse(`${isoString}${offset}`);
            const now = Date.now();

            return Math.floor((future ? targetTimeUTC - now : now - targetTimeUTC) / 1000);
        }

        getOffset() {
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: this.timezone,
                timeZoneName: 'longOffset'
            });
            const parts = formatter.formatToParts(new Date());
            return parts.find(p => p.type === 'timeZoneName').value.replace('GMT', '');
        }

        getType(element) {
            if (element.dataset.relativeTime) {
                return element.dataset.relativeTime;
            } else if (element.classList.contains('time-ago')) {
                return 'ago';
            } else if (element.classList.contains('time-since')) {
                return 'since';
            } else if (element.classList.contains('time-until')) {
                return 'until';
            }

            return null;
        }

        getDateTime(element) {
            return element.dateTime
                || element.getAttribute('datetime')
                || element.getAttribute('title');
        }

        render(diffInSeconds) {
            const absDiff = Math.abs(diffInSeconds);
            const sign = diffInSeconds < 0 ? '-' : '';
            return `${sign}${Math.floor(absDiff / 60)}m ${absDiff % 60}s`;
        }
    }

    return RelativeTime;
});
