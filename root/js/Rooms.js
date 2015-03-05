"use strict";

module.exports = function () {
    var rooms = {}, current;

    return {
        add: function (room) {
            rooms[room.getId()] = room;
        },

        getCurrent: function () {
            return current;
        },

        setCurrent: function (id) {
            current = rooms[id];
        },

        forEach: function (callback) {
            for (var id in rooms) {
                if (rooms.hasOwnProperty(id)) {
                    callback(rooms[id], id);
                }
            }
        },

        has: function (id) {
            return id in rooms;
        },

        get: function (id) {
            return rooms[id];
        }
    }
};
