var RoomHandler = function () {
	this.rooms = {};
};

RoomHandler.prototype.handleWhereAmI = function (type, data) {
	var handler = this;

	var path = window.location.pathname;
	var roomId = +path.substr(7);

	if (data.length === 0) {
		// TODO: Show room search
		window.location = "/rooms/1";
	} else {
		forEach(data, function (room) {
			handler.add(room);

			if (roomId == room.id) {
				roomHandler.focus(roomId);
			}
		});
	}

	notificationCenter.checkPings();
};

RoomHandler.prototype.handleTranscript = function (type, data) {
	var room = this.rooms[data.roomId];
	room.infiniteScroll = false;

	var node = this.getRoom(room.id);

	var tempScroll = node.scrollHeight - node.scrollTop;

	forEach(data.messages, function (message) {
		messageHandler.insertMessage(room, message, node);
	});

	if (data.messages.length > 0) {
		node.scrollTop = node.scrollHeight - tempScroll;
	} else {
		room.firstLoadableMessage = +node.children.item(0).getAttribute("data-id");
	}
};

RoomHandler.prototype.handleStars = function (type, data) {
	var stars = document.getElementById('stars-' + data.roomId);
	stars.innerHTML = templateManager.get("starred_messages")(data);

	forEach(stars.children, function (o, i) {
		var node = o.querySelector(".starred-message-text");
		formatter.formatMessage(node, data.messages[i].messageText.replace(/^:\d+ /, ""), null, data.user);

		node = o.querySelector(".starred-message-meta time");
		node.textContent = moment.unix(data.messages[i].time).fromNow();
	});

	forEach(stars.querySelectorAll(".star-message"), function (o) {
		o.addEventListener("click", function () {
			var star = this.getAttribute("data-starred") === "0";
			this.setAttribute("data-starred", star ? "1" : "0");

			var event = star ? "star" : "unstar";
			dataHandler.send(event, {
				messageId: +this.getAttribute("data-message-id")
			});
		});
	});
};

RoomHandler.prototype.handlePing = function (type, data) {
	var node = this.getTab(data.roomId).querySelector(".pings");
	var room = this.rooms[data.roomId];

	room.pings.push(data.messageId);
	node.setAttribute("data-pings", room.pings.length);
	notificationCenter.checkPings();

	notificationCenter.notifyMessage("Ping from " + data.user.name + " in " + room.name);
};

RoomHandler.prototype.add = function (data) {
	var handler = this;

	if (data.id in this.rooms) {
		dataHandler.send("missed-query", {
			roomId: data.id,
			last: this.rooms[data.id].lastMessage
		});

		return;
	}

	this.rooms[data.id] = data;
	this.rooms[data.id].firstMessage = -1;
	this.rooms[data.id].lastMessage = -1;
	this.rooms[data.id].firstLoadableMessage = null;
	this.rooms[data.id].infiniteScroll = false;
	this.rooms[data.id].defaultScroll = true;

	var roomNode = nodeFromHTML(templateManager.get("room")(data));
	var roomTabNode = nodeFromHTML(templateManager.get("room_tab")(data));
	var starsNode = nodeFromHTML(templateManager.get("stars")({roomId: data.id}));
	var infoNode = nodeFromHTML(templateManager.get("room_info")(data));

	document.getElementById("rooms").appendChild(roomNode);
	document.getElementById("room-tabs").appendChild(roomTabNode);
	document.getElementById("stars").appendChild(starsNode);
	document.getElementById("room-infos").appendChild(infoNode);

	roomTabNode.addEventListener("click", function () {
		forEach(document.getElementsByClassName("room-current"), function (o) {
			o.classList.remove("room-current");
		});

		forEach(document.getElementsByClassName("room-tab-current"), function (o) {
			o.classList.remove("room-tab-current");
		});

		forEach(document.getElementsByClassName("stars-current"), function (o) {
			o.classList.remove("stars-current");
		});

		forEach(document.getElementsByClassName("room-info-current"), function (o) {
			o.classList.remove("room-info-current");
		});

		roomNode.classList.add("room-current");
		roomTabNode.classList.add("room-tab-current");
		starsNode.classList.add("stars-current");
		infoNode.classList.add("room-info-current");

		var newmessages = parseInt(roomTabNode.getAttribute("data-new-messages"));
		roomTabNode.setAttribute("data-new-messages", "0");

		history.replaceState(null, "", "/rooms/" + data.id);

		if (handler.rooms[data.id].defaultScroll) {
			roomNode.scrollTop += roomNode.clientHeight;
		}

		if ((newmessages || 0) === 0 || roomNode.scrollHeight === roomNode.clientHeight) {
			notificationCenter.hideMessageIndicator();
		} else {
			notificationCenter.showMessageIndicator();
		}
	});

	var pingNode = roomTabNode.querySelector(".pings");
	pingNode.setAttribute("data-pings", this.rooms[data.id].pings.length.toString());

	pingNode.addEventListener("click", function () {
		var messageId = roomHandler.rooms[data.id].pings.shift();

		if (messageId === "undefined") {
			return;
		}

		roomHandler.showMessage(messageId);
		dataHandler.send("ping", {messageId: messageId});
		this.setAttribute("data-pings", roomHandler.rooms[data.id].pings.length.toString());
		notificationCenter.checkPings();
	});

	roomNode.addEventListener("scroll", function () {
		clearTimeout(handler.rooms[data.id].scrollTimeout);

		var node = this;

		handler.rooms[data.id].scrollTimeout = setTimeout(function () {
			handler.onScroll(node, data);
		}, 200);
	});

	dataHandler.send("missed-query", {
		roomId: data.id,
		last: -1
	});

	dataHandler.send("stars", {roomId: data.id});
};

RoomHandler.prototype.showMessage = function (id) {
	var messageNode = messageHandler.getDOM(id);

	if (messageNode === null) {
		window.open("/message/" + id + "#" + id, '_blank');
		return;
	}

	var roomNode = messageNode.parentNode;
	var roomId = +roomNode.getAttribute("data-id");
	this.focus(roomId);

	var pos = messageNode.offsetTop;
	var height = messageNode.clientHeight;

	if (pos < roomNode.scrollTop) {
		roomNode.scrollTop = pos;
		this.rooms[roomId].defaultScroll = false;
	}

	else if (pos + height > roomNode.scrollTop + roomNode.clientHeight) {
		roomNode.scrollTop = pos + height - roomNode.clientHeight;
		this.rooms[roomId].defaultScroll = false;
	}

	messageNode.style.background = "#ff9";

	setTimeout(function () {
		messageNode.style.background = "";
	}, 1000);
};

RoomHandler.prototype.focus = function (id) {
	this.getTab(id).dispatchEvent(new Event('click'));
	var roomNode = this.getRoom(id);

	if (roomNode.scrollHeight === roomNode.clientHeight) {
		this.rooms[id].defaultScroll = true;
		notificationCenter.hideMessageIndicator();
	}
};

RoomHandler.prototype.getTab = function (id) {
	return document.getElementById("room-tab-" + id);
};

RoomHandler.prototype.getRoom = function (id) {
	return document.getElementById("room-" + id);
};

RoomHandler.prototype.getCurrentRoom = function () {
	var id = +document.querySelector(".room-current").getAttribute("data-id");
	return this.rooms[id];
};

RoomHandler.prototype.onScroll = function (node, data) {
	var room = this.rooms[data.id];
	var roomNode = this.getRoom(data.id);

	if (roomNode.scrollTop === roomNode.scrollHeight - roomNode.clientHeight) {
		this.rooms[data.id].defaultScroll = true;
		notificationCenter.hideMessageIndicator();
	} else {
		this.rooms[data.id].defaultScroll = false;
	}

	if (room.infiniteScroll || room.firstLoadableMessage === this.rooms[room.id].firstMessage) {
		return;
	}

	if (node.scrollTop < 600) {
		room.infiniteScroll = true;

		dataHandler.send("transcript", {
			roomId: room.id,
			direction: "older",
			messageId: this.rooms[room.id].firstMessage
		});
	}
};
