"use strict";

var messages = {};

module.exports = function (roomList) {
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
                window.open("/messages/" + id + "#" + id, '_blank');
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
