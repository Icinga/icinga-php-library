(function () {

    "use strict";

    document.addEventListener('DOMContentLoaded', function () {

        class RelativeTimeBehavior extends Icinga.EventListener {
            constructor(icinga) {
                super(icinga);

                const RelativeTime = require('icinga/icinga-php-library/RelativeTime');

                // Icinga Web <= 2.13 support
                if (typeof icinga.ui.disableTimeCounters === 'function') {
                    this.on('icinga-init', null, () => icinga.ui.disableTimeCounters());
                }

                this.on('rendered', '.container', this.onTimeRendered, this);

                /**
                 * RelativeTime instance
                 *
                 * @type {RelativeTime}
                 * @private
                 */
                this._relativeTime = new RelativeTime(icinga.config.timezone);
            }

            onTimeRendered(event) {
                if (event.target.closest('.container') === event.currentTarget) {
                    event.data.self._relativeTime.scan(event.target);
                }
            }

            unbind(emitter) {
                super.unbind(emitter);
                this._relativeTime.stop();
            }
        }

        Icinga.Behaviors = Icinga.Behaviors || {};

        Icinga.Behaviors.RelativeTime = RelativeTimeBehavior;
    });
})();