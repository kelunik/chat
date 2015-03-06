"use strict";

var moment = require("moment");

module.exports = function (node, newMessageNode, activityObserver) {
    var firstMessage, lastMessage, messages = {}, messageCount = 0, newMessages = 0;

    return {
        getFirstMessage: function () {
            return firstMessage;
        },

        getLastMessage: function () {
            return lastMessage;
        },

        get: function (id) {
            if (id in messages) {
                return messages[id];
            } else {
                return null;
            }
        },

        forEach: function (callback) {
            for (var id in messages) {
                if (messages.hasOwnProperty(id)) {
                    callback(messages[id], id);
                }
            }
        },

        insert: function (message) {
            var shouldScroll = node.scrollTop === node.scrollHeight - node.clientHeight && node.classList.contains("room-current");

            var id = message.getId();

            if (id in messages) {
                return;
            }

            if (messageCount === 0) {
                node.appendChild(message.getNode());
                firstMessage = id;
                lastMessage = id;
            }

            else if (id > lastMessage) {
                var prev = node.children[node.children.length - 1];

                if (prev && !prev.classList.contains("chat-message-cmd-me") &&
                    prev.getAttribute("data-author") == message.getAuthorId() &&
                    moment(prev.querySelector("time").getAttribute("datetime")).unix() > message.getTime() - 60
                ) {
                    message.getNode().classList.add("chat-message-followup");
                }

                node.appendChild(message.getNode());
                lastMessage = id;
            }

            else if (id < firstMessage) {
                var nodeBefore = node.children[0];
                node.insertBefore(message.getNode(), nodeBefore);
                firstMessage = id;

                if (!nodeBefore.classList.contains("chat-message-cmd-me") &&
                    nodeBefore.getAttribute("data-author") * 1 === message.getAuthorId() &&
                    moment(nodeBefore.querySelector("time").getAttribute("datetime")).unix() - 60 < message.getTime()
                ) {
                    nodeBefore.classList.add("chat-message-followup");
                }
            }

            else {
                var curr = node.children.length - 1;

                while (curr - 1 >= 0) {
                    if (node.children[curr - 1].getAttribute("data-id") * 1 < id) {
                        node.insertBefore(message.getNode(), node.children[curr]);
                        break;
                    }

                    curr--;
                }
            }

            if (shouldScroll) {
                node.scrollTop = node.scrollHeight;
            } else {
                newMessageNode.setAttribute("data-new-messages", ++newMessages);
                activityObserver.onNewMessageChange();
            }

            messages[id] = message;

            messageCount++;
        },

        getNewMessageCount: function () {
            return newMessages;
        }
    }
};
