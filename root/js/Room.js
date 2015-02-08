"use strict";

var Util = require("./Util");

var rooms, tabs, infos, stars, template;
var messageList, roomList, activityObserver, dataHandler, notificationCenter;

template = {
    content: require("../../html/room.handlebars"),
    tab: require("../../html/room_tab.handlebars"),
    info: require("../../html/room_info.handlebars"),
    stars: require("../../html/stars.handlebars")
};

module.exports = function (data, _messageList, _roomList, _activityObserver, _dataHandler, _notificationCenter) {
    messageList = _messageList;
    roomList = _roomList;
    activityObserver = _activityObserver;
    dataHandler = _dataHandler;
    notificationCenter = _notificationCenter;

    rooms = rooms || document.getElementById("rooms");
    tabs = tabs || document.getElementById("room-tabs");
    infos = infos || document.getElementById("room-infos");
    stars = stars || document.getElementById("stars");

    if (!rooms || !tabs) {
        throw new Error("no parent dom available to add room");
    }

    var id, name, description, users, pings, defaultScroll, firstMessage, lastMessage, scrollTimeout,
        firstLoadableMessage, transcriptPending, contentNode, tabNode, infoNode, starsNode, pingNode,
        initialPayloadSent;

    id = data.id;
    name = data.name;
    description = data.description;
    users = data.users;
    pings = data.pings;
    defaultScroll = true;
    scrollTimeout = null;
    firstMessage = null;
    lastMessage = null;
    firstLoadableMessage = null;
    transcriptPending = false;
    initialPayloadSent = false;

    contentNode = rooms.appendChild(Util.html2node(template.content(id)));
    contentNode = document.getElementById("room-" + id);
    tabNode = tabs.appendChild(Util.html2node(template.tab({
        id: id,
        name: name
    })));
    tabNode = document.getElementById("room-tab-" + id);
    infoNode = infos.appendChild(Util.html2node(template.info(data)));
    infoNode = document.getElementById("room-info-" + id);
    starsNode = stars.appendChild(Util.html2node(template.stars({
        roomId: id
    })));
    pingNode = tabNode.querySelector(".pings");

    infoNode.getElementsByTagName("a").forEach(function (o) {
        o.setAttribute("target", "_blank");
    });

    tabNode.addEventListener("click", function () {
        if (!initialPayloadSent) {
            initialPayloadSent = true;

            dataHandler.send("transcript", {
                roomId: id,
                direction: "older",
                messageId: firstMessage || -1
            });

            dataHandler.send("stars", {
                roomId: id
            });
        }

        document.getElementsByClassName("room-current").forEach(function (o) {
            o.classList.remove("room-current");
        });

        document.getElementsByClassName("room-tab-current").forEach(function (o) {
            o.classList.remove("room-tab-current");
        });

        document.getElementsByClassName("stars-current").forEach(function (o) {
            o.classList.remove("stars-current");
        });

        document.getElementsByClassName("room-info-current").forEach(function (o) {
            o.classList.remove("room-info-current");
        });

        contentNode.classList.add("room-current");
        tabNode.classList.add("room-tab-current");
        starsNode.classList.add("stars-current");
        infoNode.classList.add("room-info-current");

        roomList.focus(id, true);
        tabNode.setAttribute("data-new-messages", "0");

        window.history.replaceState(null, "", "/rooms/" + id);
    }.bind(this));

    pingNode.setAttribute("data-pings", pings.length + "");

    pingNode.addEventListener("click", function () {
        var messageId = exports.popPing();

        if (!messageId) {
            return;
        }

        messageList.highlight(messageId);
        dataHandler.send("ping", {
            messageId: messageId
        });
        pingNode.setAttribute("data-pings", pings.length + "");
        notificationCenter.onPingChange();
    }.bind(this));

    var exports = {
        getId: function () {
            return id;
        },

        getName: function () {
            return name;
        },

        getDescription: function () {
            return description;
        },

        getUsers: function () {
            return users;
        },

        getNode: function () {
            return contentNode;
        },

        getTabNode: function () {
            return tabNode;
        },

        getPings: function () {
            return pings;
        },

        getPingCount: function () {
            return pings.length;
        },

        addPing: function (id) {
            for (var i = pings.length - 1; i >= 0; i--) {
                if (pings[i] === id) {
                    return;
                }
            }

            pings.push(id);
            pingNode.setAttribute("data-pings", pings.length + "");
            notificationCenter.onPingChange();
        },

        clearPing: function (id) {
            for (var i = pings.length - 1; i >= 0; i--) {
                if (pings[i] === id) {
                    pings.splice(i, 1);
                    pingNode.setAttribute("data-pings", pings.length + "");
                    notificationCenter.onPingChange();
                }
            }
        },

        popPing: function () {
            return pings.shift();
        },

        getFirstMessage: function () {
            return firstMessage;
        },

        getLastMessage: function () {
            return lastMessage;
        },

        setFirstMessage: function (value) {
            firstMessage = value;
        },

        setLastMessage: function (value) {
            lastMessage = value;
        },

        noMoreMessages: function () {
            firstLoadableMessage = firstMessage;
        },

        setDefaultScroll: function (value) {
            defaultScroll = value;
        },

        checkDefaultScroll: function () {
            defaultScroll = contentNode.clientHeight === contentNode.scrollHeight || contentNode.clientHeight === contentNode.scrollHeight - contentNode.scrollTop;
        },

        setTranscriptPending: function (value) {
            transcriptPending = value;
        },

        shouldScroll: function () {
            return activityObserver.isActive() && roomList.getCurrent() === this && defaultScroll;
        },

        isDefaultScroll: function () {
            return defaultScroll;
        },

        scrollToBottom: function () {
            contentNode.scrollTop = contentNode.scrollHeight;

            if (roomList.getCurrent() === this) {
                notificationCenter.hideMessageIndicator();
            }
        },

        onComeBack: function () {
            if (defaultScroll) {
                contentNode.scrollTop += contentNode.clientHeight;
                this.checkDefaultScroll();

                if (!defaultScroll) {
                    notificationCenter.showMessageIndicator();
                } else {
                    notificationCenter.hideMessageIndicator();
                }
            }

            tabNode.setAttribute("data-new-messages", "0");
        },

        onScroll: function () {
            if (contentNode.scrollTop > contentNode.scrollHeight - contentNode.clientHeight - 30) {
                defaultScroll = true;
                notificationCenter.hideMessageIndicator();
            } else {
                defaultScroll = false;
            }

            if (transcriptPending || firstLoadableMessage === firstMessage) {
                return;
            }

            if (contentNode.scrollTop < 600) {
                transcriptPending = true;

                dataHandler.send("transcript", {
                    roomId: id,
                    direction: "older",
                    messageId: firstMessage
                });
            }
        },

        addUser: function (data) {
            // TODO: improve implementation
            users.push(data);
            infoNode = infos.replaceChild(infoNode, Util.html2node(template.info(users)));
        }
    };

    contentNode.addEventListener("scroll", function () {
        if (scrollTimeout === null) {
            exports.onScroll();

            scrollTimeout = window.setTimeout(function () {
                exports.onScroll();
            }.bind(exports), 200);
        } else {
            clearTimeout(scrollTimeout);
            scrollTimeout = window.setTimeout(function () {
                exports.onScroll();
                scrollTimeout = null;
            }.bind(exports), 200);
        }
    }.bind(exports));

    return exports;
};
