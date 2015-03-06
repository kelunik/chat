"use strict";

var Autocomplete = require("./autocomplete.js"),
    Util = require("./Util.js"),
    Formatter = require("./Formatter.js"),
    moment = require("moment");

var editMessage = 0,
    compose = false,
    currentReplyTo = null,
    input = document.getElementById("input"),
    roomList, dataHandler, formatter;

module.exports = function (_roomList, _dataHandler) {
    roomList = _roomList;
    dataHandler = _dataHandler;
    formatter = new Formatter(roomList);
    setup();

    return {
        newline: newline,
        tab: tab,
        edit: edit,
        submit: submit,
        reset: reset,
        replyTo: replyTo
    }
};

function setup() {
    new Autocomplete("input", "autocomplete", function (name) {
        name = name.toLowerCase();

        if (roomList.getCurrent()) {
            var users = roomList.getCurrent().getUsers();
            var suggestions = [];

            users.forEach(function (user) {
                if (user.name.toLowerCase().startsWith(name)) {
                    suggestions.push(user);
                }
            });

            return suggestions;
        } else {
            return [];
        }
    }, require("../../html/autocomplete.handlebars"));

    input.addEventListener("input", Util.throttle(function () {
        adjust(true);
    }));

    input.addEventListener("keydown", function (e) {
        var message, roomNode, nodes, current;

        if (e.which === 37 || e.which === 39) {
            compose = true;
            return;
        }

        if (e.which === 13 && e.shiftKey) {
            e.preventDefault();
            newline();
            return false;
        }

        if (e.which === 13) {
            e.preventDefault();
            submit();
            return false;
        }

        else if (e.which === 38 && e.ctrlKey) {
            current = replyTo();

            if (current) {
                message = window.prev(roomList.getCurrent().getMessageList().get(current), ".chat-message:not(.chat-message-me)");
            } else {
                nodes = document.querySelectorAll(".room-current .chat-message:not(.chat-message-me)");
                message = nodes.length > 1 ? nodes[nodes.length - 1] : null;
            }

            if (message) {
                replyTo(parseInt(message.getAttribute("data-id")));
            }

            e.preventDefault();
            return false;
        }

        else if (e.which === 40 && e.ctrlKey) {
            current = replyTo();

            if (current) {
                message = window.next(roomList.getCurrent().getMessageList().get(current), ".chat-message:not(.chat-message-me)");

                if (message) {
                    replyTo(parseInt(message.getAttribute("data-id")));
                } else {
                    replyTo(null);
                }
            }

            e.preventDefault();
            return false;
        }

        else if (e.which === 38 && !e.shiftKey) {
            if (compose && input.value !== "") {
                return;
            }

            if (editMessage) {
                var msgNode = null;

                roomList.forEach(function (room) {
                    var msg = room.getMessageList().get(editMessage);

                    if (msg) {
                        msgNode = msg.getNode();
                    }
                });

                message = window.prev(msgNode, ".chat-message-me");
            } else {
                nodes = document.querySelectorAll(".room-current .chat-message-me");
                message = nodes.length > 1 ? nodes[nodes.length - 1] : null;
            }

            if (message) {
                edit(parseInt(message.getAttribute("data-id")));
                e.preventDefault();
                return false;
            }
        }

        else if (e.which === 40 && !e.shiftKey) {
            if (compose && input.value !== "") {
                return;
            }

            var msgNode = null;

            roomList.forEach(function (room) {
                var msg = room.getMessageList().get(editMessage);

                if (msg) {
                    msgNode = msg.getNode();
                }
            });

            message = editMessage ? window.next(msgNode, ".chat-message-me") : null;

            if (message) {
                edit(parseInt(message.getAttribute("data-id")));
            } else {
                reset();
            }

            e.preventDefault();
            return false;
        }

        else if (e.which === 27) { // escape
            reset();
            e.preventDefault();
            return false;
        }

        else if (e.which === 9) { // tab
            tab(e);
            e.preventDefault();
            return false;
        }

        else if (e.which === 33) {
            roomNode = roomList.getCurrent().getNode();
            roomNode.scrollTop -= roomNode.clientHeight * .2;
        }

        else if (e.which === 34) {
            roomNode = roomList.getCurrent().getNode();
            roomNode.scrollTop += roomNode.clientHeight * .2;
        }

        else if (e.which === 35) {
            roomNode = roomList.getCurrent().getNode();
            roomNode.scrollTop = roomNode.scrollHeight;
        }
    });

    reset(window.isTouchDevice());
}

function newline() {
    var start = input.selectionStart;
    var end = input.selectionEnd;
    var value = input.value;
    var indent = "";
    var last_new_line = value.lastIndexOf("\n");

    if (last_new_line > 0) {
        indent = value.substring(last_new_line + 1);
    } else {
        indent = value;
    }

    indent = indent.replace(/(\S.*)/, "");

    if (indent === "\n") {
        indent = "";
    }

    input.value = value.substring(0, start) + "\n" + indent + value.substring(end);
    input.selectionStart = input.selectionEnd = start + 1 + indent.length;

    adjust(true);

    input.parentNode.scrollTop += 20;
}

function tab(e) {
    var start = input.selectionStart;
    var end = input.selectionEnd;
    var value = input.value;
    var before = value.substr(0, start);
    var after = value.substr(end);

    if (start === end) {
        input.value = before + "\t" + after;
        input.selectionStart = input.selectionEnd = start + 1;
    } else {
        var selectStart = start;

        var line_before = before.substr(0, Math.max(0, before.lastIndexOf("\n") + 1));
        var line_after = after.substr(Math.max(0, after.indexOf("\n")));
        var text_to_indent = before.substr(Math.max(0, before.lastIndexOf("\n") + 1))
            + value.substring(start, end)
            + after.substr(0, Math.max(0, after.indexOf("\n")));

        if (e && e.shiftKey) {
            selectStart -= /(^|\n)(\t| {0,4})/g.exec(text_to_indent)[2].length;

            text_to_indent = text_to_indent.replace(/(^|\n)(\t| {0,4})/g, "\n");

            if (text_to_indent.indexOf("\n") === 0) { // TODO: Just get first char and compare
                text_to_indent = text_to_indent.substr(1);
            }

            input.value = line_before + text_to_indent + line_after;
        } else {
            selectStart++;

            input.value = line_before + "\t" + text_to_indent.replace(/\n/g, "\n\t") + line_after;
        }

        input.selectionStart = selectStart;
        input.selectionEnd = input.value.length - after.length;
    }

    adjust(true);
}

function adjust(_compose) {
    var newReplyTo = replyTo();
    var message;

    if (currentReplyTo !== newReplyTo) {
        if (currentReplyTo) {
            message = roomList.getCurrent().getMessageList().get(currentReplyTo);

            if (message) {
                message.classList.remove("input-reply");
            }
        }

        if (newReplyTo) {
            message = roomList.getCurrent().getMessageList().get(newReplyTo);

            if (message) {
                message.classList.add("input-reply");
            }
        }

        currentReplyTo = newReplyTo;
    }

    var toScroll = [];

    document.querySelectorAll(".room").forEach(function (o) {
        toScroll.push(o.scrollHeight - o.scrollTop - o.clientHeight);
    });

    var scroll = input.parentNode.scrollTop;

    input.style.height = "0";

    // TODO: Find out why we need 3px more
    var height = input.scrollHeight;
    input.style.height = Math.max(60, height) + "px";
    input.parentNode.style.height = Math.max(63, height + 3) + "px";

    input.parentNode.scrollTop = scroll;

    document.querySelectorAll(".room").forEach(function (o) {
        var scroll = toScroll.shift();
        o.scrollTop = o.scrollHeight - o.clientHeight - scroll;
    });

    compose = _compose;
}

function edit(id) {
    var msg = roomList.getCurrent().getMessageList().get(id);
    var message = msg ? msg.getNode() : null;

    if (!message) {
        return;
    }

    if (moment(message.querySelector("time").getAttribute("datetime")).unix() < moment().unix() - 300) {
        alert("You can't edit messages older than 5 minutes!");
        return;
    }

    editMessage = id;

    input.value = msg.getText();

    var caretPos = input.value.length;
    if (input.createTextRange) {
        var range = input.createTextRange();
        range.move("character", caretPos);
        range.select();
    } else {
        if (input.selectionStart) {
            input.setSelectionRange(caretPos, caretPos);
        }
    }

    adjust(false);
    input.focus();
    input.parentNode.setAttribute("data-edit", "true");
}

function submit() {
    var text = input.value.trim();
    var roomId = roomList.getCurrent().getId();

    if (text === "") {
        reset();
        return;
    } else if (text === "/leave") {
        reset();
        window.location = window.location + "/leave";
        return;
    }

    var tempId = Util.generateToken(20);
    var roomNode = roomList.getCurrent().getNode();

    if (editMessage) {
        var msg = roomList.getCurrent().getMessageList().get(editMessage);
        var messageNode = msg.getNode();

        if (text === msg.getText()) {
            reset();
            return;
        }

        messageNode.classList.add("chat-message-pending");
        messageNode.setAttribute("data-token", tempId);

        formatter.formatMessage(roomId, messageNode.querySelector(".chat-message-text"), text, null, user);

        dataHandler.send("message-edit", {
            messageId: editMessage,
            text: text,
            tempId: tempId
        });

        ga('send', 'event', 'chat', 'edit');
    } else {
        var message = Util.html2node(require("../../html/chat_message.handlebars")({
            tempId: tempId,
            roomId: roomId,
            messageText: text,
            user: {
                id: user.id,
                name: user.name,
                avatar: user.avatar
            },
            stars: 0,
            starred: false,
            time: moment().unix()
        }));

        formatter.formatMessage(roomId, message.querySelector(".chat-message-text"), text, null, user);

        message.querySelector("time").textContent = moment().fromNow();
        message.querySelector("time").setAttribute("title", moment().format("LLL"));

        message.classList.add("chat-message-me");
        message.setAttribute("data-text", text);

        var prevNode = roomNode.querySelector(".chat-message:last-of-type");
        roomNode.appendChild(message);

        if (prevNode && +prevNode.getAttribute("data-author") === user.id && moment(prevNode.querySelector("time").getAttribute("datetime")).unix() > moment().unix() - 60) {
            message.classList.add("chat-message-followup");
        }

        dataHandler.send("message", {
            roomId: roomId,
            text: text,
            tempId: tempId
        });

        var pings = roomList.getCurrent().getPings();
        var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

        pings.forEach(function (messageId) {
            var msg = roomList.getCurrent().getMessageList().get(messageId);

            if (!msg) {
                return;
            }

            var rec = msg.getBoundingClientRect();

            if (rec.top >= 0 && rec.top < h || rec.bottom > 0 && rec.bottom <= h) {
                dataHandler.send("ping", {
                    messageId: messageId
                });
            }
        });

        if (window.isTouchDevice()) {
            message.classList.add("unselectable");
        }

        message.addEventListener("longpress", function () {
            edit(parseInt(message.getAttribute("data-id")));
        });

        ga('send', 'event', 'chat', 'create');
    }

    reset();

    if (roomList.getCurrent().shouldScroll()) {
        roomList.getCurrent().scrollToBottom();
    }
}

function reset(nofocus) {
    editMessage = 0;
    input.value = "";
    input.parentNode.removeAttribute("data-edit");
    adjust(false);

    if (!nofocus) {
        input.focus()
    }
}

function replyTo(id) {
    var value = input.value;

    if (arguments.length === 0) {
        var match = /:(\d+)( |$)/.exec(value);
        return match ? +match[1] : null;
    } else {
        if (id) {
            var reply = replyTo();

            if (reply) {
                input.value = value.replace(":" + reply, ":" + id);
            } else {
                input.value = ":" + id + " " + input.value;
            }
        } else {
            input.value = value.replace(/:(\d+)( |$)/, "");
        }


        input.focus();
        input.selectionStart = input.selectionEnd = input.value.length;
        adjust(true);
    }
}
