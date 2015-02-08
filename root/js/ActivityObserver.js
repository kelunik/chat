"use strict";

var userActive = !document.hidden;

module.exports = function (config, roomList, dataHandler, timeUpdater) {
    setup(config, roomList, dataHandler, timeUpdater);

    return {
        isActive: function () {
            return userActive;
        }
    }
};

function setup(config, roomList, dataHandler, timeUpdater) {
    document.addEventListener("visibilitychange", function () {
        userActive = !document.hidden;

        if (document.hidden) {
            timeUpdater.stop();
            dataHandler.send("activity", {
                state: "inactive"
            });
        } else {
            document.title = config.name;
            timeUpdater.start();
            dataHandler.send("activity", {
                state: "active"
            });

            var current = roomList.getCurrent();

            if (current) {
                current.onComeBack();
            }
        }
    }, false);
}
