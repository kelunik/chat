var NotificationCenter = (function (window, document, dataHandler, rooms) {
	"use strict";

	var displayNotification, favicon, icons, messageIndicator = null;

	displayNotification = function (title, message) {
		var notification = new Notification(title, {
			tag: "message",
			icon: "/img/logo_40x40x2.png",
			body: message
		});

		// Firefox closes notifications after 4 seconds,
		// let's do this in other browsers, too.
		notification.onshow = function () {
			setTimeout(this.close.bind(this), 5000);
		}.bind(notification);
	}.bind(this);

	icons = {
		default: "/favicon.ico",
		ping: "/img/icon_new.ico"
	};

	// Chrome might need a user action for that
	window.addEventListener("load", function () {
		if (window.Notification && Notification.permission !== "granted") {
			Notification.requestPermission(function (status) {
				if (Notification.permission !== status) {
					Notification.permission = status;
				}
			});
		}
	});

	var exports = {
		setIcon: function (url) {
			if (favicon === null) {
				favicon = document.getElementById("favicon");
			}

			if (!favicon) {
				return;
			}

			favicon.href = url;
		},

		showMessageIndicator: function () {
			if (messageIndicator === null) {
				messageIndicator = document.getElementById("new-messages");
			}

			if (messageIndicator) {
				messageIndicator.style.display = "block";
			}
		},

		hideMessageIndicator: function () {
			if (messageIndicator === null) {
				messageIndicator = document.getElementById("new-messages");
			}

			if (messageIndicator) {
				messageIndicator.style.display = "none";
			}
		},

		showNotification: function (title, message) {
			if (window.Notification && Notification.permission === "granted") {
				displayNotification(title, message);
			}

			else if (window.Notification && Notification.permission !== "denied") {
				Notification.requestPermission(function (status) {
					if (Notification.permission !== status) {
						Notification.permission = status;
					}

					displayNotification(title, message);
				}.bind(this));
			}
		},

		onPingChange: function () {
			var cnt = 0;

			rooms.forEach(function (room) {
				cnt += room.getPingCount();
			});

			this.setIcon(cnt === 0 ? icons.default : icons.ping);
		},

		clearPing: function (id) {
			rooms.forEach(function (room) {
				var pings = room.getPings();

				pings.forEach(function (ping) {
					if (ping === id) {
						dataHandler.send("ping", {
							messageId: id
						})
					}
				})
			});
		}
	};

	window.addEventListener("beforeunload", function () {
		// close this explicitly, because browsers may not directly close connections on unload
		// and further payloads would be processed
		dataHandler.close();

		// restore default favicon, so user's bookmarks show the correct one
		this.setIcon(icons.default);
	}.bind(exports), false);

	return exports;
})
(window, document, DataHandler, Rooms);
