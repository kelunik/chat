var Messages = (function (document, rooms) {
    "use strict";

    var messages = {};

    return {
        get: function (id) {
            if (id in messages) {
                return messages[id];
            } else {
                return messages[id] = document.getElementById("message-" + id);
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

            var room = rooms.get(message.parentNode.getAttribute("data-id") * 1);
            var roomNode = message.parentNode;
            var roomId = room.getId();
            rooms.focus(roomId);

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
})(document, Rooms);
