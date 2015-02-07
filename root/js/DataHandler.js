"use strict";

var websocket, handlers, queue, connect, invokeHandlers, explicitClose;

module.exports = function () {
    explicitClose = false;
    handlers = {};
    queue = [];

    connect = function () {
        if (!websocket || websocket.readyState === WebSocket.CLOSED) {
            websocket = new WebSocket(url);

            websocket.addEventListener("open", function () {
                var tempQueue = queue;
                queue = [];

                if (tempQueue.length !== 0) {
                    DataHandler.send("lost-push", tempQueue);
                }

                invokeHandlers("open");
            });

            websocket.addEventListener("message", function (e) {
                if (explicitClose) {
                    return;
                }

                var payload = null;

                try {
                    payload = JSON.parse(e.data);
                } catch (ex) {
                    throw new Error("invalid json", ex);
                }

                if (payload === null || !"type" in payload) {
                    throw new Error("invalid websocket payload");
                }

                console.log(" ←  in   ", payload.type, payload.data);
                invokeHandlers(payload.type, payload.data);
            });

            websocket.addEventListener("close", function (e) {
                invokeHandlers("close", e);

                if (!explicitClose) {
                    window.setTimeout(function () {
                        connect();
                    }, 3000);
                }
            });

            websocket.addEventListener("error", function (e) {
                invokeHandlers("error", e);
            });
        }
    };

    invokeHandlers = function (type, data) {
        if (type in handlers) {
            handlers[type].forEach(function (callback) {
                if (type in ["open", "close", "error"]) {
                    callback(data);
                } else {
                    callback(type, data);
                }
            });
        } else {
            console.info("No handler has been registered for that type: " + type);
        }
    };

    return {
        init: function () {
            connect();
        },

        on: function (type, callback) {
            if (type in handlers === false) {
                handlers[type] = [];
            }

            handlers[type].push(callback);
        },

        send: function (type, data) {
            if (websocket && websocket.readyState === WebSocket.OPEN) {
                websocket.send(JSON.stringify({
                    type: type,
                    data: data
                }));

                console.log(" →  out  ", type, data);
            } else {
                if (type === "lost-push") {
                    data.forEach(function (o) {
                        queue.push(o);
                    });
                } else {
                    queue.push({
                        type: type,
                        data: data
                    })
                }
            }
        },

        close: function () {
            if (websocket) {
                websocket.close();
            }

            explicitClose = true;
        }
    }
};
