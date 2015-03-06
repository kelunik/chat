"use strict";

var Formatter = require("./Formatter.js"),
    moment = require("moment"),
    Util = require("./Util.js");

var formatter;
var template = require("../../html/chat_message.handlebars");

module.exports = function (data, input, roomList, activityObserver, dataHandler) {
    formatter = formatter || new Formatter(roomList);

    var id = data.messageId;
    var room = data.roomId;
    var text = data.messageText;
    var starred = data.starred;

    var node = Util.html2node(template(data));

    if (data.user.id === user.id) {
        node.classList.add("chat-message-me");
    }

    formatter.formatMessage(room, node.querySelector(".chat-message-text"), text, data.reply, data.user);

    node.querySelector("time").textContent = moment.unix(data.time).fromNow();
    node.querySelector("time").setAttribute("title", moment.unix(data.time).format("LLL"));
    node.querySelector("time").parentNode.href = "/messages/" + id + "#" + id;

    node.querySelector(".chat-message-stars").addEventListener("click", function () {
        starred = !starred;

        var event = starred ? "star" : "unstar";
        this.setAttribute("data-starred", starred ? "1" : "0");

        dataHandler.send(event, {
            messageId: id
        });
    });

    node.querySelector(".chat-message-reply").addEventListener("click", function () {
        input.replyTo(id);
    });

    if (data.user.id === user.id) {
        node.addEventListener("longpress", function () {
            input.edit(data.messageId);
        });
    }

    node.addEventListener("mouseenter", function () {
        var nodes = document.querySelectorAll(".chat-message[data-reply='" + id + "']");

        nodes.forEach(function (node) {
            node.classList.add("reply");
        });

        if (data.reply) {
            var message = roomList.getCurrent().getMessageList().get(data.reply.messageId);

            if (message) {
                message.getNode().classList.add("reply");
            }
        }
    });

    node.addEventListener("mouseleave", function () {
        var nodes = document.querySelectorAll(".reply");

        nodes.forEach(function (node) {
            node.classList.remove("reply");
        });
    });

    if (window.isTouchDevice()) {
        node.classList.add("unselectable");
    }

    return {
        getId: function () {
            return id;
        },

        getRoom: function () {
            return room;
        },

        getText: function () {
            return text;
        },

        getAuthorId: function () {
            return data.user.id;
        },

        getTime: function () {
            return data.time;
        },

        getNode: function () {
            return node;
        },

        setText: function (value) {
            formatter.formatMessage(room, node.querySelector(".chat-message-text"), value, data.reply, data.user);
            text = value;
        },

        setStars: function (value) {
            var starNode = node.querySelector(".chat-message-stars");
            starNode.setAttribute("data-stars", value);
        },

        setStarred: function (value) {
            var starNode = node.querySelector(".chat-message-stars");
            starNode.setAttribute("data-starred", value ? "1" : "0");
            starred = value;
        },

        highlight: function () {
            var pos = node.offsetTop;
            var height = node.clientHeight;

            if (pos < node.parentNode.scrollTop) {
                node.parentNode.scrollTop = pos;
            }

            else if (pos + height > node.parentNode.scrollTop + node.parentNode.clientHeight) {
                node.parentNode.scrollTop = pos + height - node.parentNode.clientHeight;
            }

            // TODO: use css class

            node.style.backgroundColor = "#ff9";

            setTimeout(function () {
                node.style.backgroundColor = "";
            }, 1000);
        }
    }
};
