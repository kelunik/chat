var DataHandler = function (url) {
	this.websocketUrl = url;
	this.websocket = null;
	this.handlers = {};
	this.queue = [];
};

DataHandler.prototype.connect = function () {
	if (this.websocket === null || this.websocket.readyState == WebSocket.CLOSED) {
		this.websocket = new WebSocket(this.websocketUrl);

		var handler = this;

		this.websocket.onopen = function () {
			handler.onOpen();

			dataHandler.send("activity", {state: notificationCenter.userActive ? "active" : "inactive"});
		};

		this.websocket.onmessage = function (e) {
			handler.onMessage(e);
		};

		this.websocket.onclose = function () {
			handler.onClose();
		};

		this.websocket.onerror = function (e) {
			handler.onError(e);
		};
	}
};

DataHandler.prototype.onError = function (e) {
	console.log(e);

	if ("error" in this.handlers) {
		this.handlers["error"]();
	}
};

DataHandler.prototype.onOpen = function () {
	console.debug("DataHandler::onOpen");

	var queue = this.queue;
	this.queue = [];

	if (queue.length !== 0) {
		this.send("lost-push", queue);
	}

	if ("open" in this.handlers) {
		this.handlers["open"]();
	}
};

DataHandler.prototype.onMessage = function (e) {
	if (document.getElementById("rooms") === null) { // TODO: Cache this, move away from here...
		document.getElementById("page").innerHTML = templateManager.get("chat")(user);

		var input = document.getElementById("input");
		input.focus();

		autocomplete(input);

		input.addEventListener("input", function (e) {
			var message = parseInt(this.getAttribute("data-message"));

			if (this.value === "" && (message || 0) === 0) {
				this.removeAttribute("data-compose");
				this.setAttribute("data-message", "0");
			} else {
				this.setAttribute("data-compose", "1");
			}

			adjustInput(this);
		});

		input.addEventListener("keydown", function (e) {
			if (e.which == 37 || e.which == 39) {
				input.setAttribute("data-compose", "1");
				return;
			}

			if (e.which == 13 && e.shiftKey) {
				e.preventDefault();

				input.setAttribute("data-compose", "1");

				var start = this.selectionStart;
				var end = this.selectionEnd;
				var value = this.value;
				var indent = "";
				var last_new_line = value.lastIndexOf("\n");

				if (last_new_line > 0) {
					indent = value.substring(last_new_line + 1);
				} else {
					indent = value;
				}

				indent = indent.replace(/(\S.*)/, "");

				this.value = value.substring(0, start) + "\n" + indent + value.substring(end);
				this.selectionStart = this.selectionEnd = start + 1 + indent.length;

				adjustInput(this);

				return false;
			}

			if (e.which == 13) {
				e.preventDefault();

				var text = input.value;
				var room = +document.getElementsByClassName("room-current")[0].getAttribute("data-id");

				if (text === "") {
					return;
				}

				var tempId = generateToken(20);
				var editMessage = parseInt(input.getAttribute("data-message"));
				var node = roomHandler.getRoom(room);

				if (editMessage > 0) {
					var shouldScroll = node.scrollTop === node.scrollHeight - node.clientHeight;

					if (text === messageHandler.getDOM(editMessage).getAttribute("data-text")) {
						input.setAttribute("data-message", "0");
						input.value = "";
						input.focus();
						return;
					}

					var messageNode = messageHandler.getDOM(editMessage);
					messageNode.classList.add("chat-message-pending");
					messageNode.setAttribute("data-edit-id", tempId.toString());

					formatter.formatMessage(room, messageNode.querySelector(".chat-message-text"), text, null, user);
					this.removeAttribute("data-compose");

					dataHandler.send("message-edit", {
						messageId: +editMessage,
						text: text,
						tempId: tempId
					});

					if (shouldScroll) {
						node.scrollTop = node.scrollHeight;
					}
				} else {
					var message = nodeFromHTML(templateManager.get("chat_message")({
						tempId: tempId,
						roomId: room,
						messageText: text,
						user: {
							id: user.id,
							name: user.name,
							avatar: user.imageUrl
						},
						stars: 0,
						starred: false,
						time: moment().unix()
					}));

					formatter.formatMessage(room, message.querySelector(".chat-message-text"), text, null, user);

					message.querySelector("time").textContent = moment().fromNow();
					message.querySelector("time").setAttribute("title", moment().format("LLL"));

					message.classList.add("chat-message-me");
					message.setAttribute("data-text", text);

					var prevNode = node.querySelector(".chat-message:last-of-type");
					node.appendChild(message);

					if (prevNode && +prevNode.getAttribute("data-author") === user.id && moment(prevNode.querySelector("time").getAttribute("datetime")).unix() > moment().unix() - 60) {
						message.classList.add("chat-message-followup");
					}

					node.scrollTop = node.scrollHeight;

					dataHandler.send("message", {
						roomId: room,
						text: text,
						tempId: tempId
					});
				}

				input.removeAttribute("data-compose");
				input.setAttribute("data-message", "0");
				input.value = "";

				adjustInput(input);

				input.focus();

				return false;
			}

			else if (e.which == 38 && !e.shiftKey) {
				if (input.getAttribute("data-compose")) {
					return;
				}

				e.preventDefault();

				var editMessage = +input.getAttribute("data-message");

				var message;

				if (editMessage > 0) {
					message = window.prev(messageHandler.getDOM(editMessage), ".chat-message-me");
				} else {
					var nodes = document.querySelectorAll(".room-current .chat-message-me");
					message = nodes[nodes.length - 1];
				}

				if (message !== null) {
					if (moment(message.querySelector("time").getAttribute("datetime")).unix() > moment().unix() - 5 * 60) {
						input.value = message.getAttribute("data-text");
						input.setAttribute("data-message", message.getAttribute("data-id"));

						var caretPos = message.getAttribute("data-text").length;
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
				}

				adjustInput(input);

				return false;
			}

			else if (e.which == 40 && !e.shiftKey) {
				if (input.hasAttribute("data-compose")) {
					return;
				}

				e.preventDefault();

				var editMessage = parseInt(input.getAttribute("data-message"));

				var message = editMessage > 0
					? window.next(messageHandler.getDOM(editMessage), ".chat-message-me") // FIXME: replace next
					: null;

				if (message !== null) {
					input.value = message.getAttribute("data-text");
					input.setAttribute("data-message", message.getAttribute("data-id"));

					var caretPos = message.getAttribute("data-text").length;
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
					input.removeAttribute("data-compose");
					input.setAttribute("data-message", "0");
					input.value = "";
				}

				adjustInput(input);

				return false;
			}

			else if (e.which == 27) { // escape
				e.preventDefault();
				input.removeAttribute("data-compose");
				input.setAttribute("data-message", "0");
				input.value = "";
				adjustInput(input);

				return false;
			}

			else if (e.which == 9) { // tab
				e.preventDefault();

				var start = this.selectionStart;
				var end = this.selectionEnd;
				var value = this.value;
				var before = value.substr(0, start);
				var after = value.substr(end);

				if (start === end) {
					this.value = before + "\t" + after;
					this.selectionStart = this.selectionEnd = start + 1;
				} else {
					var selectStart = start;

					var line_before = before.substr(0, Math.max(0, before.lastIndexOf("\n") + 1));
					var line_after = after.substr(Math.max(0, after.indexOf("\n")));
					var text_to_indent = before.substr(Math.max(0, before.lastIndexOf("\n") + 1))
						+ value.substring(start, end)
						+ after.substr(0, Math.max(0, after.indexOf("\n")));

					if (e.shiftKey) {
						selectStart -= /(^|\n)(\t| {0,4})/g.exec(text_to_indent)[2].length;

						text_to_indent = text_to_indent.replace(/(^|\n)(\t| {0,4})/g, "\n");

						if (text_to_indent.indexOf("\n") === 0) { // TODO: Just get first char and compare
							text_to_indent = text_to_indent.substr(1);
						}

						this.value = line_before + text_to_indent + line_after;
					} else {
						selectStart++;

						this.value = line_before + "\t" + text_to_indent.replace(/\n/g, "\n\t") + line_after;
					}

					this.selectionStart = selectStart;
					this.selectionEnd = this.value.length - after.length;
				}
			}

			else if (e.which == 33) {
				var roomNode = document.querySelector(".room-current");
				roomNode.scrollTop -= roomNode.clientHeight * .2;
			}

			else if (e.which == 34) {
				var roomNode = document.querySelector(".room-current");
				roomNode.scrollTop += roomNode.clientHeight * .2;
			}

			else if (e.which === 35) {
				var roomNode = document.querySelector(".room-current");
				roomNode.scrollTop = roomNode.scrollHeight;
			}
		});
	}

	var payload = null;

	try {
		payload = JSON.parse(e.data);
	} catch (e) {
		alert(e);
		console.log(e);
	}

	if (payload == null || !"type" in payload || !"data" in payload) {
		return;
	}

	console.log("in", payload.type, payload.data);

	if (payload.type in this.handlers) {
		this.handlers[payload.type](payload.type, payload.data);
	} else {
		console.info("No handler has been registered for that type: " + payload.type);
	}
};

DataHandler.prototype.onClose = function () {
	console.debug("DataHandler::onClose");

	if ("close" in this.handlers) {
		this.handlers["close"]();
	}

	var handler = this;

	setTimeout(function () {
		handler.connect();
	}, 3000);
};

DataHandler.prototype.on = function (type, callback) {
	this.handlers[type] = callback;
};

DataHandler.prototype.send = function (type, data) {
	if (this.websocket.readyState === WebSocket.OPEN) {
		this.websocket.send(JSON.stringify({type: type, data: data}));
		console.log("out", type, data);
	} else {
		var handler = this;

		if (type === "lost-push") {
			forEach(data, function (i, o) {
				handler.queue.push(o);
			});
		} else {
			handler.queue.push({type: type, data: data});
		}
	}
};
