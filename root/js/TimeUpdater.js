var TimeUpdater = (function (window, document, moment) {
    "use strict";

    var interval = 10000, intervalId, update;

    update = function () {
        document.getElementsByClassName("relative-time").forEach(function (o) {
            o.textContent = moment(o.getAttribute("datetime")).fromNow();
        });
    };

    return {
        start: function () {
            this.stop();

            intervalId = window.setInterval(update, interval);
            update();
        },

        stop: function () {
            if (intervalId !== null) {
                window.clearInterval(intervalId);
                intervalId = null;
            }
        },

        setInterval: function (value) {
            interval = value;
            this.start();
        }
    };
})(window, document, moment);
