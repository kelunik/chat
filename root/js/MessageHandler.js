var MessageHandler = function () {
	this.tempMessageId = 1;
};

MessageHandler.prototype.getDOM = function (id) {
	return document.getElementById("message-" + id);
};

MessageHandler.prototype.handleMessage = function (type, data) {
	var node = document.getElementById("message-temp-" + data.token);

	if (node) {
		if (this.getDOM(data.messageId) !== null) { // to prevent duplicate message in offine mode
			node.parentNode.removeChild(node);
		} else {
			node.classList.remove("chat-message-pending");
			node.setAttribute("id", "message-" + data.messageId);
			node.setAttribute("data-id", data.messageId);

			formatter.formatMessage(data.roomId, node.querySelector(".chat-message-text"), node.getAttribute("data-text"), data.reply, user);
			roomHandler.rooms[data.roomId].lastMessage = data.messageId;

			node.querySelector("time").parentNode.href = "/message/" + data.messageId + "#" + data.messageId;

			node.querySelector(".chat-message-stars").addEventListener("click", function () {
				var star = this.getAttribute("data-starred") == "0";
				this.setAttribute("data-starred", star ? "1" : "0");

				var event = star ? "star" : "unstar";
				dataHandler.send(event, {
					messageId: data.messageId
				});
			});
		}
	} else {
		this.insertMessage(roomHandler.rooms[data.roomId], data);
	}

	if (data.user.id === user.id && data.reply) {
		var room = roomHandler.rooms[data.roomId];

		for (var i = room.pings.length - 1; i >= 0; i--) {
			if (room.pings[i] === data.reply.messageId) {
				dataHandler.send("ping", {messageId: data.reply.messageId});
			}
		}
	}
};

MessageHandler.prototype.handleMissedQuery = function (type, data) {
	var handler = this;

	forEach(data.messages, function (o) {
		handler.insertMessage(roomHandler.rooms[o.roomId], o, null, data.init);
	});
};

MessageHandler.prototype.handleMessageEdit = function (type, data) {
	var message = this.getDOM(data.messageId);
	var text = data.text;

	if (message === null) {
		return; // just ignore that
	}

	message.classList.remove("chat-message-pending");
	message.classList.remove("chat-message-cmd-me");
	message.setAttribute("data-edit-id", "");
	message.setAttribute("data-text", text);

	var roomId = +message.parentNode.getAttribute("data-id");

	formatter.formatMessage(roomId, message.querySelector(".chat-message-text"), text, data.reply, data.user);

	if (data.error) {
		alert(data.error);
		console.log(data.error);
		return;
	}

	message.querySelector(".chat-message-meta").setAttribute("data-edit", data.time);

	if (data.user.id === user.id && data.reply) {
		var room = roomHandler.rooms[roomId];

		for (var i = room.pings.length - 1; i >= 0; i--) {
			if (room.pings[i] === data.reply.messageId) {
				dataHandler.send("ping", {messageId: data.reply.messageId});
			}
		}
	}

	var starredMessage = document.getElementById("message-starred-" + data.messageId);

	if (starredMessage) {
		var textNode = starredMessage.parentNode.parentNode.querySelector(".starred-message-text");
		formatter.formatMessage(-1, textNode, data.text.replace(/^:\d+ /, ""), null, data.user);
	}
};

MessageHandler.prototype.insertMessage = function (room, message, node, init) {
	node = node || roomHandler.getRoom(room.id);
	init = init || false;
	message.messageId = +message.messageId;
	var shouldScroll = node.scrollTop > node.scrollHeight - node.offsetHeight - 5;
	var messageNode = nodeFromHTML(templateManager.get("chat_message")(message));
	messageNode.setAttribute("data-text", message.messageText);

	if (message.user.id == user.id) {
		messageNode.classList.add("chat-message-me");
	}

	formatter.formatMessage(room.id, messageNode.querySelector(".chat-message-text"), message.messageText, message.reply, message.user);
	messageNode.querySelector("time").textContent = moment.unix(message.time).fromNow();
	messageNode.querySelector("time").setAttribute("title", moment.unix(message.time).format("LLL"));
	messageNode.querySelector("time").parentNode.href = "/message/" + message.messageId + "#" + message.messageId;

	if (room.firstMessage == -1 || room.lastMessage == -1) {
		roomHandler.rooms[room.id].firstMessage = message.messageId;
		roomHandler.rooms[room.id].lastMessage = message.messageId;
		node.appendChild(messageNode);
	}

	else if (message.messageId > room.lastMessage) {
		var prev = messageHandler.getDOM(room.lastMessage);

		if (prev !== null && !prev.classList.contains("chat-message-cmd-me") && prev.getAttribute("data-author") == message.user.id
			&& moment(prev.querySelector("time").getAttribute("datetime")).unix() > message.time - 60) {
			messageNode.classList.add("chat-message-followup");
		}

		roomHandler.rooms[room.id].lastMessage = message.messageId;
		node.appendChild(messageNode);
	}

	else if (message.messageId < room.firstMessage) {
		roomHandler.rooms[room.id].firstMessage = message.messageId;
		node.insertBefore(messageNode, node.firstChild);
	}

	else {
		// TODO: Insert in between (search for right position)
	}

	if (init || shouldScroll && node.classList.contains("room-current") && notificationCenter.userActive) {
		node.scrollTop = node.scrollHeight;
	}

	if (!init && node.classList.contains("room-current") && node.scrollTop !== node.scrollHeight - node.clientHeight && roomHandler.rooms[room.id].lastMessage === message.messageId) {
		notificationCenter.showMessageIndicator();
	}

	if (!"seen" in message || !message.seen) {
		var tabNode = roomHandler.getTab(room.id);

		if (tabNode.classList.contains("room-tab-current") && notificationCenter.userActive) {
			tabNode.setAttribute("data-new-messages", "0");
		} else {
			tabNode.setAttribute("data-new-messages", ((+tabNode.getAttribute("data-new-messages") || 0) + 1).toString());
		}

		var newMessages = 0;
		forEach(document.getElementsByClassName("room-tab"), function (o) {
			newMessages += (+o.getAttribute("data-new-messages") || 0);
		});

		if (!notificationCenter.userActive) {
			var title = newMessages == 0 ? "" : "(" + newMessages + ") ";
			document.title = title + " Aerys Chat";
		}
	}

	messageNode.querySelector(".chat-message-stars").addEventListener("click", function () {
		var star = this.getAttribute("data-starred") == "0";
		this.setAttribute("data-starred", star ? "1" : "0");

		var event = star ? "star" : "unstar";
		dataHandler.send(event, {
			messageId: message.messageId
		});
	});

	messageNode.querySelector(".chat-message-reply").addEventListener("click", function () {
		var node = document.getElementById("input");
		var value = node.value;

		if (value.match(/:(\d+)( |$)/)) {
			// TODO: Add helper function to become DOM independent
			node.value = value.replace(/:(\d+)( |$)/, ":" + message.messageId + " ");
		} else {
			node.value = ":" + message.messageId + " " + node.value;
		}

		node.focus();
		node.selectionStart = node.selectionEnd = node.value.length;
	});

	if (isTouchDevice()) {
		messageNode.classList.add("unselectable");
	}

	if (message.user.id === user.id) {
		messageNode.addEventListener("longpress", function () {
			var input = document.getElementById("input");

			if (moment(messageNode.querySelector("time").getAttribute("datetime")).unix() > moment().unix() - 5 * 60) {
				input.value = messageNode.getAttribute("data-text");
				input.setAttribute("data-message", messageNode.getAttribute("data-id"));

				var caretPos = messageNode.getAttribute("data-text").length;
				if (input.createTextRange) {
					var range = input.createTextRange();
					range.move('character', caretPos);
					range.select();
				} else {
					if (input.selectionStart) {
						input.setSelectionRange(caretPos, caretPos);
					}
				}
			} else {
				alert('Previous message is older than 5 minutes and cannot be edited!');
			}

			adjustInput(input);
		});
	}
};

MessageHandler.prototype.handleStar = function (type, data) {
	var msg = this.getDOM(data.messageId);

	if (msg !== null) {
		var node = msg.querySelector(".chat-message-stars");
		node.setAttribute("data-stars", data.stars);

		if (data.user == user.id) {
			node.setAttribute("data-starred", data.action == "star" ? "1" : "0");
		}
	}

	msg = document.getElementById("message-starred-" + data.messageId);

	if (msg !== null) {
		if (data.stars === 0) {
			msg.parentNode.parentNode.parentNode.removeChild(msg.parentNode.parentNode);
		} else {
			msg.setAttribute("data-stars", data.stars);

			if (data.user == user.id) {
				msg.setAttribute("data-starred", data.action == "star" ? "1" : "0");
			}
		}
	} else {
		// TODO: Add API for that
		dataHandler.send("stars", {roomId: +document.getElementsByClassName("room-current")[0].getAttribute("data-id")});
	}
};
