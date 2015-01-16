var NotificationCenter = function () {
	this.tags = {
		message: "message"
	};

	window.addEventListener('load', function () {
		if (window.Notification && Notification.permission !== "granted") {
			Notification.requestPermission(function (status) {
				if (Notification.permission !== status) {
					Notification.permission = status;
				}
			});
		}
	});

	var hidden, visibilityChange;

	if (typeof document.hidden !== "undefined") {
		hidden = "hidden";
		visibilityChange = "visibilitychange";
	} else if (typeof document.mozHidden !== "undefined") {
		hidden = "mozHidden";
		visibilityChange = "mozvisibilitychange";
	} else if (typeof document.msHidden !== "undefined") {
		hidden = "msHidden";
		visibilityChange = "msvisibilitychange";
	} else if (typeof document.webkitHidden !== "undefined") {
		hidden = "webkitHidden";
		visibilityChange = "webkitvisibilitychange";
	}

	this.hidden = hidden;
	this.visibilityChange = visibilityChange;
	this.userActive = true;

	document.addEventListener(visibilityChange, this.handleVisibilityChange.bind(this), false);

	window.addEventListener("beforeunload", function () {
		changeFavicon("/img/icon.ico");
	}, false);
};

NotificationCenter.prototype.handleVisibilityChange = function () {
	if (document[this.hidden]) {
		this.userActive = false;
		timeUpdater.stop(); // don't update contents while user is somewhere else
		dataHandler.send("activity", {state: "inactive"});
	} else {
		document.title = "t@lkZone";
		this.userActive = true;
		timeUpdater.update(); // update immediately when user comes back
		timeUpdater.start(); // update contents while user is here

		var current = document.getElementsByClassName("room-tab-current")[0].getAttribute("data-id");
		var tabNode = roomHandler.getTab(current);

		if (tabNode === null) {
			return; // chat probably not yet loaded...
		}

		var newmessages = parseInt(tabNode.getAttribute("data-new-messages"));
		tabNode.setAttribute("data-new-messages", "0");

		var roomNode = roomHandler.getRoom(current);

		if (roomHandler.rooms[current].defaultScroll) {
			roomNode.scrollTop += roomNode.clientHeight;

			if (roomNode.scrollHeight === roomNode.clientHeight || roomNode.scrollTop === roomNode.scrollHeight - roomNode.clientHeight) {
				roomHandler.rooms[current].defaultScroll = true;
				notificationCenter.hideMessageIndicator();
			}
		} else if (newmessages > 0) {
			notificationCenter.showMessageIndicator();
		}

		dataHandler.send("activity", {state: "active"});
	}
};

NotificationCenter.prototype.notifyMessage = function (message) {
	changeFavicon("/img/icon_new.ico");

	if (window.Notification && Notification.permission === "granted") {
		new Notification(message, {tag: this.tags.message});
	}

	else if (window.Notification && Notification.permission !== "denied") {
		Notification.requestPermission(function (status) {
			if (Notification.permission !== status) {
				Notification.permission = status;
			}

			new Notification(message, {tag: this.tags.message});
		});
	}

	else {
		alert(message);
	}
};

NotificationCenter.prototype.checkPings = function () {
	var cnt = 0;

	for (var id in roomHandler.rooms) {
		cnt += roomHandler.rooms[id].pings.length;
	}

	if (cnt == 0) {
		changeFavicon("/img/icon.ico");
	} else {
		changeFavicon("/img/icon_new.ico");
	}
};

NotificationCenter.prototype.showMessageIndicator = function () {
	document.getElementById("new-messages").style.display = "block";
};

NotificationCenter.prototype.hideMessageIndicator = function () {
	document.getElementById("new-messages").style.display = "none";
};


function changeFavicon(url) {
	(function () {
		var link = document.createElement('link');
		link.type = 'image/x-icon';
		link.rel = 'shortcut icon';
		link.href = url;
		document.getElementsByTagName('head')[0].appendChild(link);
	}());
}
