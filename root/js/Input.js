var ga = ga || function () {
        console.log("ga not yet loaded");
    }, user = user || {};

var Input = (function (window, document, dataHandler, formatter, messages, moment, rooms, templateManager, user, util, ga) {
    "use strict";

    var compose, edit, input;

    edit = 0;
    compose = false;
    input = document.getElementById("input");

    if (!window.isTouchDevice()) {
        input.focus();
    }

    autocomplete(input, rooms, templateManager);

    input.addEventListener("input", throttle(function () {
        compose = true;
        Input.adjust();
    }));

    input.addEventListener("keydown", function (e) {
        var message, roomNode;

        if (e.which === 37 || e.which === 39) {
            compose = true;
            return;
        }

        if (e.which === 13 && e.shiftKey) {
            e.preventDefault();
            compose = true;
            Input.newline();
            Input.adjust();
            return false;
        }

        if (e.which === 13) {
            e.preventDefault();
            Input.submit();
            input.focus();
            return false;
        }

        else if (e.which == 38 && !e.shiftKey) {
            if (compose) {
                return;
            }

            e.preventDefault();

            if (edit) {
                message = window.prev(messages.get(edit), ".chat-message-me");
            } else {
                var nodes = document.querySelectorAll(".room-current .chat-message-me");
                message = nodes[nodes.length - 1];
            }

            if (message) {
                Input.edit(parseInt(message.getAttribute("data-id")));
            }

            Input.adjust();
            return false;
        }

        else if (e.which == 40 && !e.shiftKey) {
            if (compose) {
                return;
            }

            e.preventDefault();

            message = edit ? window.next(messages.get(edit), ".chat-message-me") : null;

            if (message) {
                Input.edit(parseInt(message.getAttribute("data-id")));
            } else {
                Input.reset();
            }

            Input.adjust();
            return false;
        }

        else if (e.which == 27) { // escape
            e.preventDefault();
            Input.reset();
            return false;
        }

        else if (e.which == 9) { // tab
            e.preventDefault();
            Input.tab();
            Input.adjust();
            return false;
        }

        else if (e.which == 33) {
            roomNode = rooms.getCurrent().getNode();
            roomNode.scrollTop -= roomNode.clientHeight * .2;
        }

        else if (e.which == 34) {
            roomNode = rooms.getCurrent().getNode();
            roomNode.scrollTop += roomNode.clientHeight * .2;
        }

        else if (e.which === 35) {
            roomNode = rooms.getCurrent().getNode();
            roomNode.scrollTop = roomNode.scrollHeight;
        }
    });

    return {
        newline: function () {
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

            input.value = value.substring(0, start) + "\n" + indent + value.substring(end);
            input.selectionStart = input.selectionEnd = start + 1 + indent.length;
        },

        tab: function () {
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

                if (e.shiftKey) {
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
        },

        adjust: function () {
            var toScroll = [];

            document.querySelectorAll(".room").forEach(function (o) {
                toScroll.push(o.scrollHeight - o.scrollTop - o.clientHeight);
            });

            input.style.height = "0";
            input.style.height = Math.max(40, input.scrollHeight - 20) + "px";

            document.querySelectorAll(".room").forEach(function (o) {
                var scroll = toScroll.shift();
                o.scrollTop = o.scrollHeight - o.clientHeight - scroll;
            });
        },

        edit: function (id) {
            var message = messages.get(id);

            if (moment(message.querySelector("time").getAttribute("datetime")).unix() < moment().unix() - 300) {
                alert("You can't edit messages older than 5 minutes!");
                return;
            }

            edit = id;

            input.value = message.getAttribute("data-text");

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

            input.focus();
            input.setAttribute("data-edit", "true");
        },

        submit: function () {
            var text = input.value.trim();
            var roomId = rooms.getCurrent().getId();

            if (text === "") {
                Input.reset();
                return;
            }

            var tempId = util.generateToken(20);
            var roomNode = rooms.getCurrent().getNode();

            if (edit) {
                var messageNode = messages.get(edit);

                if (text === messageNode.getAttribute("data-text")) {
                    Input.reset();
                    return;
                }

                messageNode.classList.add("chat-message-pending");
                messageNode.setAttribute("data-token", tempId);

                formatter.formatMessage(roomId, messageNode.querySelector(".chat-message-text"), text, null, user);

                dataHandler.send("message-edit", {
                    messageId: edit,
                    text: text,
                    tempId: tempId
                });

                if (rooms.getCurrent().shouldScroll()) {
                    rooms.getCurrent().scrollToBottom();
                }

                ga('send', 'event', 'chat', 'edit');
            } else {
                var message = util.html2node(templateManager.get("chat_message")({
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

                var pings = rooms.getCurrent().getPings();
                var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

                pings.forEach(function (messageId) {
                    var msg = messages.get(messageId);

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
                    Input.edit(parseInt(message.getAttribute("data-id")));
                    Input.adjust();
                });

                ga('send', 'event', 'chat', 'create');
            }

            Input.reset();

            if (rooms.getCurrent().shouldScroll()) {
                rooms.getCurrent().scrollToBottom();
            }
        },

        reset: function () {
            edit = 0;
            compose = false;
            input.value = "";
            input.removeAttribute("data-edit");
            Input.adjust();
            input.focus();
        }
    }
})(window, document, DataHandler, Formatter, Messages, moment, Rooms, TemplateManager, user, Util, ga);
