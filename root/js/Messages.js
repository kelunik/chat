"use strict";

var messages = {};

module.exports = function (roomList) {
    setupEventHandlers();

    return {
        get: function (id) {
            if (id in messages) {
                return messages[id];
            } else {
                var element = document.getElementById("message-" + id);

                if (element) {
                    return messages[id] = element;
                } else {
                    return null;
                }
            }
        },

        forEach: function (callback) {
            for (var id in messages) {
                if (messages.hasOwnProperty(id)) {
                    callback(messages[id], id);
                }
            }
        },

        highlight: function (id) {
            var message = this.get(id);

            if (!message) {
                window.open("/message/" + id + "#" + id, '_blank');
                return;
            }

            var room = roomList.get(message.parentNode.getAttribute("data-id") * 1);
            var roomNode = message.parentNode;
            var roomId = room.getId();
            roomList.focus(roomId);

            var pos = message.offsetTop;
            var height = message.clientHeight;

            if (pos < roomNode.scrollTop) {
                roomNode.scrollTop = pos;
                room.setDefaultScroll(false);
            }

            else if (pos + height > roomNode.scrollTop + roomNode.clientHeight) {
                roomNode.scrollTop = pos + height - roomNode.clientHeight;
                room.setDefaultScroll(false);
            }

            message.style.background = "#ff9";

            setTimeout(function () {
                message.style.background = "";
            }, 1000);
        }
    }
};

function setupEventHandlers() {
    document.addEventListener("mouseover", function (e) {
        if (e.target.classList.contains("chat-message")) {
            var node = e.target;
            var id = node.getAttribute("data-id");
            var nodes = document.querySelectorAll(".chat-message[data-reply='" + id + "']");

            nodes.forEach(function (node) {
                node.classList.add("reply");
                node.classList.add("reply-" + id);
            });
        }
    });

    document.addEventListener("mouseout", function (e) {
        if (e.target.classList.contains("chat-message")) {
            var node = e.target;
            var id = node.getAttribute("data-id");
            var nodes = document.querySelectorAll(".reply-" + id);

            nodes.forEach(function (node) {
                node.classList.remove("reply");
                node.classList.remove("reply-" + id);
            });
        }
    });
}
