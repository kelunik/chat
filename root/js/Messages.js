var Messages = (function (document, rooms) {
	"use strict";

	var messages = {};

	return {
		get: function (id) {
			if (id in messages) {
				return messages[id];
			} else {
				return messages[id] = document.getElementById("message-" + id);
			}
		},

		forEach: function (callback) {
			for (var id in messages) {
				if (messages.hasOwnProperty(id)) {
					callback(messages[id], id);
				}
			}
		},

		highlight: function (id) {
			var message = this.get(id);

			if (!message) {
				window.open("/message/" + id + "#" + id, '_blank');
				return;
			}

			var messageNode = message.getNode();
			var room = message.getRoom();
			var roomNode = room.getNode();
			var roomId = room.getId();
			rooms.focus(roomId);

			var pos = messageNode.offsetTop;
			var height = messageNode.clientHeight;

			if (pos < roomNode.scrollTop) {
				roomNode.scrollTop = pos;
				room.setDefaultScroll(false);
			}

			else if (pos + height > roomNode.scrollTop + roomNode.clientHeight) {
				roomNode.scrollTop = pos + height - roomNode.clientHeight;
				room.setDefaultScroll(false);
			}

			messageNode.style.background = "#ff9";

			setTimeout(function () {
				messageNode.style.background = "";
			}, 1000);
		}
	}
})(document, Rooms);
