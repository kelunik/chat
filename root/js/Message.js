"use strict";

var Formatter = require("./Formatter.js"),
    moment = require("moment"),
    Util = require("./Util.js");

var template, formatter;

module.exports = function (data, input, messageList, roomList, activityObserver, dataHandler, notificationCenter) {
    var id, room, roomNode, messageNode;

    template = {
        chat: require("../../html/chat_message.handlebars")
    };

    formatter = formatter || new Formatter(messageList, roomList);

    var showIndicator = false;

    id = data.messageId;

    room = roomList.get(data.roomId);
    roomNode = room.getNode();

    messageNode = Util.html2node(template.chat(data));
    messageNode.setAttribute("data-text", data.messageText);

    if (data.user.id === user.id) {
        messageNode.classList.add("chat-message-me");
    }

    formatter.formatMessage(room.getId(), messageNode.querySelector(".chat-message-text"), data.messageText, data.reply, data.user);
    messageNode.querySelector("time").textContent = moment.unix(data.time).fromNow();
    messageNode.querySelector("time").setAttribute("title", moment.unix(data.time).format("LLL"));
    messageNode.querySelector("time").parentNode.href = "/messages/" + data.messageId + "#" + data.messageId;

    if (room.getFirstMessage() === null || room.getLastMessage() === null) {
        room.setFirstMessage(data.messageId);
        room.setLastMessage(data.messageId);
        messageNode = roomNode.appendChild(messageNode);
        showIndicator = true;
    }

    else if (data.messageId > room.getLastMessage()) {
        var prev = messageList.get(room.getLastMessage());

        if (prev && !prev.classList.contains("chat-message-cmd-me") &&
            prev.getAttribute("data-author") == data.user.id &&
            moment(prev.querySelector("time").getAttribute("datetime")).unix() > data.time - 60
        ) {
            messageNode.classList.add("chat-message-followup");
        }

        room.setLastMessage(data.messageId);
        messageNode = roomNode.appendChild(messageNode);

        showIndicator = true;
    }

    else if (data.messageId < room.getFirstMessage()) {
        room.setFirstMessage(data.messageId);
        messageNode = roomNode.insertBefore(messageNode, roomNode.firstChild);
    }

    else {
        // TODO: Test this code...

        var last = roomNode.lastChild;

        while (last.previousSibling) {
            if (last.previousSibling.getAttribute("data-id") * 1 < data.id) {
                roomNode.insertBefore(messageNode, last);
                break;
            }

            last = last.previousSibling;
        }

        showIndicator = true;
    }

    if (room.shouldScroll()) {
        room.scrollToBottom();
    } else if (room === roomList.getCurrent() && showIndicator) {
        notificationCenter.showMessageIndicator();
    }

    if (roomList.getCurrent() !== room) {
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
            input.edit(data.messageId);
        });
    }

    messageNode.addEventListener("mouseenter", function () {
        var nodes = document.querySelectorAll(".chat-message[data-reply='" + id + "']");

        nodes.forEach(function (node) {
            node.classList.add("reply");
        });

        if (data.reply) {
            var message = messageList.get(data.reply.messageId);

            if (message) {
                message.classList.add("reply");
            }
        }
    });

    messageNode.addEventListener("mouseleave", function () {
        var nodes = document.querySelectorAll(".reply");

        nodes.forEach(function (node) {
            node.classList.remove("reply");
        });

        if (data.reply) {
            var message = messageList.get(data.reply.messageId);

            if (message) {
                message.classList.remove("reply");
            }
        }
    });

    return {
        getId: function () {
            return id;
        },

        getRoom: function () {
            return room;
        }
    }
};
