var Message = (function (window, document, activityObserver, dataHandler, formatter, messages, moment, notificationCenter, rooms, templateManager, util) {
	"use strict";

	var template = {
		chat: templateManager.get("chat_message")
	};

	return function (data) {
		var node, room = rooms.get(data.roomId);

		var messageNode = util.html2node(template.chat(data));
		messageNode.setAttribute("data-text", data.messageText);

		node = document.getElementById("room-" + data.roomId);

		if (data.user.id === user.id) {
			messageNode.classList.add("chat-message-me");
		}

		formatter.formatMessage(room.id, messageNode.querySelector(".chat-message-text"), data.messageText, data.reply, data.user);
		messageNode.querySelector("time").textContent = moment.unix(data.time).fromNow();
		messageNode.querySelector("time").setAttribute("title", moment.unix(data.time).format("LLL"));
		messageNode.querySelector("time").parentNode.href = "/message/" + data.messageId + "#" + data.messageId;

		if (room.getFirstMessage() === null || room.getLastMessage() === null) {
			room.setFirstMessage(data.messageId);
			room.setLastMessage(data.messageId);
			messageNode = node.appendChild(messageNode);
		}

		else if (data.messageId > room.getLastMessage()) {
			var prev = messages.get(room.getLastMessage());

			if (prev && !prev.classList.contains("chat-message-cmd-me") &&
				prev.getAttribute("data-author") == data.user.id &&
				moment(prev.querySelector("time").getAttribute("datetime")).unix() > data.time - 60
			) {
				messageNode.classList.add("chat-message-followup");
			}

			room.setLastMessage(data.messageId);
			messageNode = node.appendChild(messageNode);
		}

		else if (data.messageId < room.getFirstMessage()) {
			room.setFirstMessage(data.messageId);
			messageNode = node.insertBefore(messageNode, node.firstChild);
		}

		else {
			// TODO: Test this code...

			var last = node.lastChild;

			while (last.previousElementSibling) {
				if (last.previousElementSibling.getAttribute("data-id") * 1 < data.id) {
					last.parentNode.insertBefore(messageNode, last);
					break;
				}

				last = last.previousElementSibling;
			}
		}

		if (room.shouldScroll()) {
			room.scrollToBottom();
		} else {
			notificationCenter.showMessageIndicator();
		}

		if (rooms.getCurrent() !== room) {
			var tab = document.getElementById("room-tab-" + data.roomId);
			tab.setAttribute("data-new-messages", "" + (1 * tab.getAttribute("data-new-messages") + 1));
		}

		var messageCount = 0;
		document.getElementsByClassName("room-tab").forEach(function (o) {
			messageCount += (+o.getAttribute("data-new-messages") || 0);
		});

		if (!activityObserver.isActive()) {
			var title = messageCount === 0 ? "" : "(" + messageCount + ") ";
			document.title = title + " t@lkZone";
		}

		messageNode.querySelector(".chat-message-stars").addEventListener("click", function () {
			var star = this.getAttribute("data-starred") === "0";
			this.setAttribute("data-starred", star ? "1" : "0");

			var event = star ? "star" : "unstar";
			dataHandler.send(event, {
				messageId: data.messageId
			});
		});

		messageNode.querySelector(".chat-message-reply").addEventListener("click", function () {
			var node = document.getElementById("input");
			var value = node.value;

			if (value.match(/:(\d+)( |$)/)) {
				// TODO: Add helper function to become DOM independent
				node.value = value.replace(/:(\d+)( |$)/, ":" + data.messageId + " ");
			} else {
				node.value = ":" + data.messageId + " " + node.value;
			}

			node.focus();
			node.selectionStart = node.selectionEnd = node.value.length;
		});

		if (window.isTouchDevice()) {
			messageNode.classList.add("unselectable");
		}

		if (data.user.id === user.id) {
			messageNode.addEventListener("longpress", function () {
				Input.edit(data.messageId);
			});
		}

		this.getId = function () {
			return data.messageId;
		};

		this.getRoom = function () {
			return room;
		};
	};
})(window, document, ActivityObserver, DataHandler, Formatter, Messages, moment, NotificationCenter, Rooms, TemplateManager, Util);
