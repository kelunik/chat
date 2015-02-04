var Rooms = (function () {
    "use strict";

    var rooms, current;

    rooms = {};

    return {
        add: function (room) {
            rooms[room.getId()] = room;
        },

        getCurrent: function () {
            return current;
        },

        forEach: function (callback) {
            for (var id in rooms) {
                if (rooms.hasOwnProperty(id)) {
                    callback(rooms[id], id);
                }
            }
        },

        focus: function (id, skipEvent) {
            skipEvent = skipEvent || false;

            var room = rooms[id];
            current = room;

            if (!room) {
                return;
            }

            if (!skipEvent) {
                room.getTabNode().dispatchEvent(new Event('click'));
            }

            room.onComeBack();
        },

        has: function (id) {
            return id in rooms;
        },

        get: function (id) {
            return rooms[id];
        }
    }
})();
