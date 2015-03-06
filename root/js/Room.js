"use strict";

var Util = require("./Util");
var MessageList = require("./MessageList");

var roomList, activityObserver, dataHandler, notificationCenter;

var template = {
    content: require("../../html/room.handlebars"),
    tab: require("../../html/room_tab.handlebars"),
    stars: require("../../html/stars.handlebars"),
    title: require("../../html/header_title.handlebars"),
    users: require("../../html/header_users.handlebars")
};

module.exports = function (data, _roomList, _activityObserver, _dataHandler, _notificationCenter) {
    roomList = _roomList;
    activityObserver = _activityObserver;
    dataHandler = _dataHandler;
    notificationCenter = _notificationCenter;

    var id, name, description, users, pings, defaultScroll, firstMessage, lastMessage, scrollTimeout = 0,
        firstLoadableMessage, transcriptPending, contentNode, tabNode, infoNode, starsNode, pingNode,
        initialPayloadSent, messageList;

    id = data.id;
    name = data.name;
    description = data.description;
    users = data.users;
    pings = data.pings;
    defaultScroll = true;
    scrollTimeout = null;
    firstLoadableMessage = null;
    transcriptPending = false;
    initialPayloadSent = false;

    contentNode = Util.html2node(template.content(id));
    document.getElementById("rooms").appendChild(contentNode);

    messageList = new MessageList(contentNode);

    tabNode = Util.html2node(template.tab({
        id: id,
        name: name
    }));
    document.getElementById("room-tabs").appendChild(tabNode);

    starsNode = stars.appendChild(Util.html2node(template.stars({
        roomId: id
    })));
    pingNode = tabNode.querySelector(".pings");

    tabNode.addEventListener("click", function () {
        exports.focus();
    }.bind(this));

    pingNode.setAttribute("data-pings", pings.length + "");

    pingNode.addEventListener("click", function () {
        var messageId = exports.popPing();

        if (!messageId) {
            return;
        }

        var message = messageList.get(messageId);

        if (message) {
            message.highlight();
        } else {
            window.open("/messages/" + messageId + "#" + messageId);
        }

        dataHandler.send("ping", {
            messageId: messageId
        });
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
            return pings.length > 0 ? pings[0] : null;
        },

        getMessageList: function () {
            return messageList;
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

        addUser: function (userdata) {
            users.unshift(userdata);

            if (roomList.getCurrent() === this) {
                var u = document.getElementById("header-users");
                u.innerHTML = template.users(users);
            }
        },

        removeUser: function (userId) {
            for (var i = users.length - 1; i >= 0; i--) {
                if (users[i].id === userId) {
                    users.splice(i, 1);
                    break;
                }
            }

            if (roomList.getCurrent() === this) {
                var u = document.getElementById("header-users");
                u.innerHTML = template.users(users);
            }
        },

        focus: function () {
            document.getElementsByClassName("room-current").forEach(function (o) {
                o.classList.remove("room-current");
            });

            document.getElementsByClassName("room-tab-current").forEach(function (o) {
                o.classList.remove("room-tab-current");
            });

            document.getElementsByClassName("stars-current").forEach(function (o) {
                o.classList.remove("stars-current");
            });

            contentNode.classList.add("room-current");
            tabNode.classList.add("room-tab-current");
            starsNode.classList.add("stars-current");

            tabNode.setAttribute("data-new-messages", "0");
            window.history.replaceState(null, "", "/rooms/" + id);

            roomList.setCurrent(id);

            var title = document.getElementById("header-title");
            title.innerHTML = template.title({
                name: name,
                memberinfo: {
                    online: 0,
                    count: 42
                }
            });

            var users = document.getElementById("header-users");
            users.innerHTML = template.users(data.users);

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

            this.onComeBack();
        }
    };

    contentNode.addEventListener("scroll", function () {
        if (scrollTimeout === null) {
            exports.onScroll(); // fire once immediately
            scrollTimeout = window.setTimeout(exports.onScroll.bind(exports), 200);
        } else {
            clearTimeout(scrollTimeout);
            scrollTimeout = window.setTimeout(function () {
                exports.onScroll();
                scrollTimeout = null;
            }, 200);
        }
    }.bind(exports));

    return exports;
};
