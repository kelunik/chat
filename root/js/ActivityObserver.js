var ActivityObserver = (function (document, rooms, timeUpdater, dataHandler) {
	"use strict";

	var current, currentRoom, currentTab, eventName, property, userActive;

	if (typeof document.hidden !== "undefined") {
		property = "hidden";
		eventName = "visibilitychange";
	} else if (typeof document.mozHidden !== "undefined") {
		property = "mozHidden";
		eventName = "mozvisibilitychange";
	} else if (typeof document.msHidden !== "undefined") {
		property = "msHidden";
		eventName = "msvisibilitychange";
	} else if (typeof document.webkitHidden !== "undefined") {
		property = "webkitHidden";
		eventName = "webkitvisibilitychange";
	}

	document.addEventListener(eventName, function () {
		userActive = !document[property];

		if (document[property]) {
			timeUpdater.stop();
			dataHandler.send("activity", {
				state: "inactive"
			});
		} else {
			document.title = "t@lkZone";
			timeUpdater.start();
			dataHandler.send("activity", {
				state: "active"
			});

			current = rooms.getCurrent();
			currentRoom = current.getNode();
			currentTab = current.getTabNode();

			if (!currentRoom || !currentTab) {
				return; // chat probably not yet loaded
			}

			currentTab.setAttribute("data-new-messages", "0");
			current.onComeBack();
		}
	}, false);

	userActive = userActive = !document[property];;

	return {
		isActive: function () {
			return userActive;
		}
	};
})(document, Rooms, TimeUpdater, DataHandler);
