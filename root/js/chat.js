function init(window, document, activityObserver, dataHandler, formatter, handlebars, messages, notificationCenter, rooms, templateManager, user) {
	"use strict";

	window.sessionStorage.setItem("autologout", "");
	window.devicePixelRatio = window.devicePixelRatio || 1;

	dataHandler.on("message", function (type, data) {
		var node = document.getElementById("message-temp-" + data.token);

		if (node) {
			node.classList.remove("chat-message-pending");
			node.setAttribute("id", "message-" + data.messageId);
			node.setAttribute("data-id", data.messageId);

			formatter.formatMessage(data.roomId, node.querySelector(".chat-message-text"), node.getAttribute("data-text"), data.reply, user);
			var room = rooms.getCurrent();
			room.setLastMessage(Math.max(room.getLastMessage(), data.messageId));

			node.querySelector("time").parentNode.href = "/message/" + data.messageId + "#" + data.messageId;

			node.querySelector(".chat-message-stars").addEventListener("click", function () {
				var star = this.getAttribute("data-starred") == "0";
				this.setAttribute("data-starred", star ? "1" : "0");

				var event = star ? "star" : "unstar";
				DataHandler.send(event, {
					messageId: data.messageId
				});
			});
		} else {
			new Message(data);
		}

		if (data.user.id === user.id && data.reply) {
			notificationCenter.clearPing(data.reply.messageId)
		}
	});

	dataHandler.on("message-edit", function (type, data) {
		var message = messages.get(data.messageId);
		var text = data.text;

		if (message === null) {
			return; // just ignore that
		}

		message.classList.remove("chat-message-pending");
		message.classList.remove("chat-message-cmd-me");
		message.setAttribute("data-token", "");
		message.setAttribute("data-text", text);

		var roomId = messages.get(data.messageId).parentNode.getAttribute("data-id") * 1;

		formatter.formatMessage(roomId, message.querySelector(".chat-message-text"), text, data.reply, data.user);

		if (data.error) {
			alert(data.error);
			console.log(data.error);
			return;
		}

		message.querySelector(".chat-message-meta").setAttribute("data-edit", data.time);

		if (data.user.id === user.id && data.reply) {
			notificationCenter.clearPing(data.reply.messageId);
		}

		var starredMessage = document.getElementById("message-starred-" + data.messageId);

		if (starredMessage) {
			var textNode = starredMessage.parentNode.parentNode.querySelector(".starred-message-text");
			formatter.formatMessage(-1, textNode, data.text.replace(/^:\d+ /, ""), null, data.user);
		}
	});

	dataHandler.on("missed-query", function (type, data) {
		data.messages.forEach(function (o) {
			new Message(o);
		});

		if (data.init) {
			rooms.get(data.roomId).scrollToBottom();
		}
	});

	dataHandler.on("star", function (type, data) {
		var msg = messages.get(data.messageId);

		if (msg) {
			var node = msg.querySelector(".chat-message-stars");
			node.setAttribute("data-stars", data.stars);

			if (data.user === user.id) {
				node.setAttribute("data-starred", data.action == "star" ? "1" : "0");
			}
		}

		msg = document.getElementById("message-starred-" + data.messageId);

		if (msg) {
			if (data.stars === 0) {
				msg.parentNode.parentNode.parentNode.removeChild(msg.parentNode.parentNode);
			} else {
				msg.setAttribute("data-stars", data.stars);

				if (data.user == user.id) {
					msg.setAttribute("data-starred", data.action == "star" ? "1" : "0");
				}
			}
		} else {
			dataHandler.send("stars", {roomId: rooms.getCurrent().getId()});
		}
	});

	dataHandler.on("stars", function (type, data) {
		var stars = document.getElementById("stars-" + data.roomId);
		stars.innerHTML = templateManager.get("starred_messages")(data);

		stars.children.forEach(function (o, i) {
			var node = o.querySelector(".starred-message-text");
			formatter.formatMessage(-1, node, data.messages[i].messageText.replace(/^:\d+ /, ""), null, data.user);

			node = o.querySelector(".starred-message-meta time");
			node.textContent = moment.unix(data.messages[i].time).fromNow();
		});

		stars.querySelectorAll(".star-message").forEach(function (o) {
			o.addEventListener("click", function () {
				var star = this.getAttribute("data-starred") === "0";
				this.setAttribute("data-starred", star ? "1" : "0");

				var event = star ? "star" : "unstar";
				dataHandler.send(event, {
					messageId: +this.getAttribute("data-message-id")
				});
			});
		});
	});

	dataHandler.on("transcript", function (type, data) {
		var room = rooms.get(data.roomId);
		room.setTranscriptPending(false);
		var node = room.getNode();
		var tempScroll = node.scrollHeight - node.scrollTop;

		data.messages.forEach(function (message) {
			new Message(message);
		});

		if (data.messages.length > 0) {
			node.scrollTop = node.scrollHeight - tempScroll;
		} else {
			room.noMoreMessages();
		}
	});

	dataHandler.on("ping", function (type, data) {
		var room = rooms.get(data.roomId);
		room.addPing(data.messageId);
		notificationCenter.showNotification("New Message in " + room.getName(), "You were mentioned by @" + data.user.name + ".", data.user.avatar + "&s=80");
	});

	dataHandler.on("ping-clear", function (type, data) {
		rooms.forEach(function (room) {
			room.clearPing(data.messageId);
		});
	});

	dataHandler.on("whereami", function (type, data) {
		var path = window.location.pathname;
		var roomId = 1 * path.substr(7);

		if (data.length === 0) {
			// TODO: Show room search
			window.location = "/rooms/1";
		} else {
			data.forEach(function (room) {
				if (rooms.has(room.id)) {
					return;
				}

				rooms.add(new Room(room));

				if (roomId === room.id) {
					rooms.focus(roomId);
				}
			}.bind(this));
		}

		notificationCenter.onPingChange();
	});

	dataHandler.on("activity", function (type, data) {
		data.userId = data.userId || 0;
		var elements = document.getElementsByClassName("user-activity-" + data.userId);

		elements.forEach(function (element) {
			element.setAttribute("data-state", data.state);
		});
	});

	dataHandler.on("user-join", function (type, data) {
		var room = rooms.get(data.roomId);

		if (room) {
			room.addUser(data.user);
		}
	});

	dataHandler.on("logout", function () {
		if (window.sessionStorage) {
			window.sessionStorage.setItem("autologout", "1");
		}

		window.location.href = "/auth";
	});

	document.getElementById("logout").addEventListener("click", function () {
		dataHandler.close();
	});

	dataHandler.on("error", function (e) {
		if (document.getElementById("error") === null) {
			document.body.appendChild(Util.html2node(TemplateManager.get('error')("We couldn't establish any WebSocket connection, sorry about that!")));
		}

		console.log(e);
	});

	handlebars.registerHelper('datetime', function (time) {
		return moment.unix(time).toISOString();
	});

	handlebars.registerHelper('dateformat', function (time) {
		return moment.unix(time).format("LLL");
	});

	handlebars.registerHelper('avatar', function (url) {
		return url + "&s=" + Math.round(window.devicePixelRatio * 30);
	});

	var markdown = new Remarkable('full', {
		html: false,
		xhtmlOut: false,
		breaks: true,
		langPrefix: 'language-',
		linkify: true,
		typographer: true,
		quotes: '“”‘’'
	});

	handlebars.registerHelper('markdown', function (text) {
		return new handlebars.SafeString(markdown.render(text));
	});

	dataHandler.on("open", function () {
		dataHandler.send("activity", {
			state: activityObserver.isActive() ? "active" : "inactive"
		});

		var e = document.getElementById("error-overlay");

		if (e !== null) {
			e.parentNode.removeChild(e);
		}

		var path = window.location.pathname;

		if (path.substring(0, 7) === "/rooms/") {
			dataHandler.send("whereami", {
				join: +path.substr(7)
			});
		}
	});

	document.addEventListener("DOMContentLoaded", function () {
		dataHandler.init();

		document.addEventListener("keydown", function (e) {
			if (e.target.nodeName === "TEXTAREA" || e.target.nodeName === "INPUT" || e.target.isContentEditable) {
				return;
			}

			if (e.which === 32) {
				e.preventDefault();
				document.getElementById("input").focus();
			}
		});

		new LongPress(document);
	});


	key("r", function (e) {
		e.preventDefault();
		var input = document.getElementById("input");
		input.value = "> " + getSelectionText().trim().replace(/\n\n/g, "\n").replace(/\n/g, "\n> ") + "\n\n";
		Input.adjust();
		input.focus();
		var caretPos = message.getAttribute("data-text").length;
		if (input.createTextRange) {
			var range = input.createTextRange();
			range.move("character", caretPos);
			range.select();
		} else {
			if (input.selectionStart) {
				input.setSelectionRange(caretPos, caretPos);
			}
		}
	});

	key("shift+?", function () {
		alert("press r to post reply and quote selected text!");
	});

	key("end", function () {
		var node = document.getElementsByClassName("room-current")[0];
		node.scrollTop = node.scrollHeight;
	});

	key("pageup", function () {
		var node = document.getElementsByClassName("room-current")[0];
		node.scrollTop -= node.clientHeight * .2;
	});

	key("pagedown", function () {
		var node = document.getElementsByClassName("room-current")[0];
		node.scrollTop += node.clientHeight * .2;
	});

	setInterval(function () {
		var http = new XMLHttpRequest();
		http.open("GET", "/session/status", true);

		http.onreadystatechange = function () {
			if (http.readyState !== XMLHttpRequest.DONE) {
				return;
			}

			if (http.status === 401) {
				// TODO: Better UX
				alert("Your session expired!");
				window.location = "/auth";
			}
		};

		http.send(null);
	}, 60 * 1000);

	window.addEventListener("resize", function () {
		rooms.forEach(function (room) {
			if (room.isDefaultScroll()) {
				room.scrollToBottom();
			}
		});
	});

	document.addEventListener("submit", function (e) {
		var form = e.target;
		var input = document.createElement('input');
		input.type = "hidden";
		input.name = "csrf-token";
		input.value = window.csrfToken;
		form.appendChild(input);
	}, true);

	document.getElementById("new-messages").addEventListener("click", function () {
		rooms.getCurrent().scrollToBottom();
	});

// "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
// ^ smallest valid gif, set when GitHub images is not available or in dev mode without internet connection
}
