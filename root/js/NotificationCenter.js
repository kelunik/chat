var NotificationCenter = (function (window, document, dataHandler, rooms) {
	"use strict";

	var displayNotification, icons, messageIndicator = null, appIcon, userImages;

	userImages = {};

	appIcon = new Image(80, 80);
	appIcon.src = "/img/logo_40x40x2.png";

	displayNotification = function (title, message, customIcon) {
		var image = new Image(80, 80);
		image.src = customIcon;
		var icon;

		// TODO: Enable again when GitHub allows access to image data
		/* if (customIcon in userImages) {
		 icon = userImages[customIcon];
		 } else if (image.complete || image.readyState === 4 || image.readyState === "complete") {
		 var canvas = document.createElement("canvas");
		 canvas.width = canvas.height = 80;
		 var ctx = canvas.getContext("2d");
		 ctx.drawImage(image, 0, 0, 80, 80);
		 ctx.drawImage(appIcon, 50, 50, 30, 30);
		 icon = userImages[customIcon] = canvas.toDataURL();
		 } else {
		 icon = "/img/logo_40x40x2.png";
		 } */

		icon = "/img/logo_40x40x2.png";

		var notification = new Notification(title, {
			tag: "message",
			icon: icon,
			lang: "en_US",
			dir: "ltr",
			body: message
		});

		// Firefox closes notifications after 4 seconds,
		// let's do this in other browsers, too.
		notification.onshow = function () {
			setTimeout(this.close.bind(this), 5000);
		}.bind(notification);
	}.bind(this);

	icons = {
		default: "/img/icon.ico",
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
			try {
				var icon = document.createElement('link');
				icon.id = "icon";
				icon.rel = "icon";
				icon.href = url;
				var curr = document.getElementById("icon");
				document.head.removeChild(curr);
				document.head.insertBefore(icon, document.getElementsByTagName("link")[0]);
			} catch (e) {
				// you suck, js!
			}
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

		showNotification: function (title, message, customIcon) {
			if (window.Notification && Notification.permission === "granted") {
				displayNotification(title, message, customIcon);
			}

			else if (window.Notification && Notification.permission !== "denied") {
				Notification.requestPermission(function (status) {
					if (Notification.permission !== status) {
						Notification.permission = status;
					}

					displayNotification(title, message, customIcon);
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
						});
					}
				}.bind(this))
			}.bind(this));
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
