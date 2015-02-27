"use strict";

// "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
// ^ smallest valid gif, set when GitHub images is not available or in dev mode without internet connection

require("./extend.js");

var ActivityObserver = require("./ActivityObserver.js"),
    DataHandler = require("./DataHandler.js"),
    Formatter = require("./Formatter.js"),
    Handlebars = require("hbsfy/runtime"),
    Input = require("./Input.js"),
    LongPress = require("./longpress.js"),
    Message = require("./Message.js"),
    MessageList = require("./Messages.js"),
    NotificationCenter = require("./NotificationCenter.js"),
    Remarkable = require("remarkable"),
    Room = require("./Room.js"),
    RoomList = require("./Rooms.js"),
    TimeUpdater = require("./TimeUpdater.js"),
    Util = require("./Util.js"),
    moment = require("moment"),
    key = require("./vendor/keymaster.min.js");

var dataHandler = new DataHandler(config.websocketUrl);
var roomList = new RoomList();
var timeUpdater = new TimeUpdater();
var activityObserver = new ActivityObserver(config, roomList, dataHandler, timeUpdater);
var messageList = new MessageList(roomList);
var notificationCenter = new NotificationCenter(roomList, dataHandler);
var input = new Input(roomList, messageList, dataHandler);
var formatter = new Formatter(messageList, roomList);

var initialLoad = true;

window.sessionStorage.setItem("autologout", "");

dataHandler.on("message", function (type, data) {
    var node = document.getElementById("message-temp-" + data.token);

    if (node) {
        node.parentNode.removeChild(node);
    }

    new Message(data, input, messageList, roomList, activityObserver, dataHandler, notificationCenter);

    if (data.user.id === user.id && data.reply) {
        notificationCenter.clearPing(data.reply.messageId)
    }
});

dataHandler.on("message-edit", function (type, data) {
    var message = messageList.get(data.messageId);
    var text = data.text;

    if (message === null) {
        return; // just ignore that
    }

    message.classList.remove("chat-message-pending");
    message.classList.remove("chat-message-cmd-me");
    message.setAttribute("data-token", "");
    message.setAttribute("data-text", text);

    var roomId = messageList.get(data.messageId).parentNode.getAttribute("data-id") * 1;

    formatter.formatMessage(roomId, message.querySelector(".chat-message-text"), text, data.reply, data.user);

    if (data.error) {
        alert(data.error);
        console.log(data.error);
        return;
    }

    message.querySelector(".chat-message-meta").setAttribute("data-edit", data.time);

    if (data.user.id === user.id && data.reply) {
        notificationCenter.clearPing(data.reply.messageId);
    }

    var starredMessage = document.getElementById("message-starred-" + data.messageId);

    if (starredMessage) {
        var textNode = starredMessage.parentNode.parentNode.querySelector(".starred-message-text");
        formatter.formatMessage(-1, textNode, data.text.replace(/^:\d+ /, ""), null, data.user);
    }
});

dataHandler.on("missed-query", function (type, data) {
    data.messages.forEach(function (o) {
        new Message(o, input, messageList, roomList, activityObserver, dataHandler, notificationCenter);
    });

    if (data.init) {
        roomList.get(data.roomId).scrollToBottom();
    }
});

dataHandler.on("star", function (type, data) {
    var msg = messageList.get(data.messageId);

    if (msg) {
        var node = msg.querySelector(".chat-message-stars");
        node.setAttribute("data-stars", data.stars);

        if (data.user === user.id) {
            node.setAttribute("data-starred", data.action == "star" ? "1" : "0");
        }
    }

    msg = document.getElementById("message-starred-" + data.messageId);

    if (msg) {
        if (data.stars === 0) {
            msg.parentNode.parentNode.parentNode.removeChild(msg.parentNode.parentNode);
        } else {
            msg.setAttribute("data-stars", data.stars);

            if (data.user == user.id) {
                msg.setAttribute("data-starred", data.action == "star" ? "1" : "0");
            }
        }
    } else {
        dataHandler.send("stars", {roomId: roomList.getCurrent().getId()});
    }
});

dataHandler.on("stars", function (type, data) {
    var stars = document.getElementById("stars-" + data.roomId);
    stars.innerHTML = require("../../html/starred_messages.handlebars")(data);

    stars.children.forEach(function (o, i) {
        var node = o.querySelector(".starred-message-text");
        formatter.formatMessage(-1, node, data.messages[i].messageText.replace(/^:\d+ /, ""), null, data.user);

        node = o.querySelector(".starred-message-meta time");
        node.textContent = moment.unix(data.messages[i].time).fromNow();
    });

    stars.querySelectorAll(".star-message").forEach(function (o) {
        o.addEventListener("click", function () {
            var star = this.getAttribute("data-starred") === "0";
            this.setAttribute("data-starred", star ? "1" : "0");

            var event = star ? "star" : "unstar";
            dataHandler.send(event, {
                messageId: +this.getAttribute("data-message-id")
            });
        });
    });
});

dataHandler.on("transcript", function (type, data) {
    var room = roomList.get(data.roomId);
    room.setTranscriptPending(false);
    var node = room.getNode();
    var tempScroll = node.scrollHeight - node.scrollTop;

    data.messages.forEach(function (message) {
        new Message(message, input, messageList, roomList, activityObserver, dataHandler, notificationCenter);
    });

    if (data.messages.length > 0) {
        node.scrollTop = node.scrollHeight - tempScroll;
    } else {
        room.noMoreMessages();
    }

    if (initialLoad) {
        initialLoad = false;

        if (!window.performance) {
            return;
        }

        // see https://developer.mozilla.org/en-US/docs/Navigation_timing
        if (window.performance.navigation.type === window.performance.navigation.TYPE_NAVIGATE) { // we only want to measure real navigation
            var now = new Date().getTime();
            var overallLoadTime = now - window.performance.timing.navigationStart;
            var requestTime = window.performance.timing.responseEnd - window.performance.timing.requestStart;

            console.log("Overall load time: " + overallLoadTime);
            console.log("Request time: " + requestTime);

            ga("send", "event", "performance", "load", "overall load time", overallLoadTime);
            ga("send", "event", "performance", "load", "request time", requestTime);
        }
    }
});

dataHandler.on("ping", function (type, data) {
    var room = roomList.get(data.roomId);
    room.addPing(data.messageId);
    notificationCenter.showNotification("New Message in " + room.getName(), "You were mentioned by @" + data.user.name + ".", data.user.avatar + "&s=80");
});

dataHandler.on("ping-clear", function (type, data) {
    roomList.forEach(function (room) {
        room.clearPing(data.messageId);
    });
});

dataHandler.on("whereami", function (type, data) {
    var path = window.location.pathname;
    var roomId = 1 * path.substr(7);

    if (data.length === 0) {
        // TODO: Show room search
        window.location = "/rooms/1";
    } else {
        data.forEach(function (room) {
            if (roomList.has(room.id)) {
                return;
            }

            roomList.add(new Room(room, messageList, roomList, activityObserver, dataHandler, notificationCenter));

            if (roomId === room.id) {
                roomList.focus(roomId);
            }
        }.bind(this));
    }

    notificationCenter.onPingChange();
});

dataHandler.on("activity", function (type, data) {
    data.userId = data.userId || 0;
    var elements = document.getElementsByClassName("user-activity-" + data.userId);

    elements.forEach(function (element) {
        element.setAttribute("data-state", data.state);
    });
});

dataHandler.on("user-join", function (type, data) {
    var room = roomList.get(data.roomId);

    if (room) {
        room.addUser(data.user);
    }
});

dataHandler.on("user-leave", function (type, data) {
    var room = roomList.get(data.roomId);

    if (room) {
        room.removeUser(data.userId);
    }
});

dataHandler.on("logout", function () {
    if (window.sessionStorage) {
        window.sessionStorage.setItem("autologout", "1");
    }

    window.location.href = "/login";
});

document.getElementById("logout").addEventListener("click", function () {
    dataHandler.close();
});

dataHandler.on("error", function (e) {
    console.log(e);

    if (document.getElementById("error") === null) {
        document.body.appendChild(Util.html2node(require("../../html/error.handlebars")("We couldn't establish any WebSocket connection, sorry about that!")));
    }
});

dataHandler.on("close", function () {
    console.log("dataHandler::close");
});

Handlebars.registerHelper('activity-state', function (state) {
    switch (state) {
        case "offline":
            return "this user is offline";
        case "active":
            return "this user is viewing " + config.name;
        case "inactive":
            return "this user has " + config.name + " open, but in background";
        default:
            return "the user's state is unknown, please report this as a bug";
    }
});

Handlebars.registerHelper('datetime', function (time) {
    return moment.unix(time).toISOString();
});

Handlebars.registerHelper('dateformat', function (time) {
    return moment.unix(time).format("LLL");
});

Handlebars.registerHelper('avatar', function (url) {
    if (isNaN(url)) {
        return url + "&s=" + Math.round(window.devicePixelRatio * 30);
    } else {
        return "https://avatars.githubusercontent.com/u/" + url + "?v=3&s=" + Math.round(window.devicePixelRatio * 30);
    }
});

Handlebars.registerHelper('pluralize', function (number, single, plural) {
    if (number === 1) {
        return single;
    } else {
        return plural;
    }
});

Handlebars.registerHelper('pluralizeCount', function (number, single, plural) {
    if (number === 1) {
        return number + " " + single;
    } else {
        return number + " " + plural;
    }
});

var markdown = new Remarkable("full", {
    html: false,
    xhtmlOut: false,
    breaks: true,
    langPrefix: "language-",
    linkify: true,
    typographer: true,
    quotes: "“”‘’"
});

Handlebars.registerHelper('markdown', function (text) {
    return new Handlebars.SafeString(markdown.render(text));
});

dataHandler.on("open", function () {
    console.log("dataHandler::open");

    dataHandler.send("activity", {
        state: activityObserver.isActive() ? "active" : "inactive"
    });

    var e = document.getElementById("error-overlay");

    if (e !== null) {
        e.parentNode.removeChild(e);
    }

    var path = window.location.pathname;

    if (path.substring(0, 7) === "/rooms/") {
        dataHandler.send("whereami", {
            join: +path.substr(7)
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    dataHandler.init();

    document.addEventListener("keydown", function (e) {
        if (e.target.nodeName === "TEXTAREA" || e.target.nodeName === "INPUT" || e.target.isContentEditable) {
            return;
        }

        if (e.which === 32) {
            e.preventDefault();
            document.getElementById("input").focus();
        }
    });

    new LongPress(document);
});

key("end", function () {
    var node = document.getElementsByClassName("room-current")[0];
    node.scrollTop = node.scrollHeight;
});

key("pageup", function () {
    var node = document.getElementsByClassName("room-current")[0];
    node.scrollTop -= node.clientHeight * .2;
});

key("pagedown", function () {
    var node = document.getElementsByClassName("room-current")[0];
    node.scrollTop += node.clientHeight * .2;
});

setInterval(function () {
    var http = new XMLHttpRequest();
    http.open("GET", "/session/status", true);

    http.onreadystatechange = function () {
        if (http.readyState !== XMLHttpRequest.DONE) {
            return;
        }

        if (http.status === 401) {
            // TODO: Better UX
            alert("Your session expired!");
            window.location = "/auth";
        }
    };

    http.send(null);
}, 60 * 1000);

window.addEventListener("resize", function () {
    roomList.forEach(function (room) {
        if (room.isDefaultScroll()) {
            room.scrollToBottom();
        }
    });
});

document.addEventListener("submit", function (e) {
    var form = e.target;
    var input = document.createElement('input');
    input.type = "hidden";
    input.name = "csrf-token";
    input.value = window.csrfToken;
    form.appendChild(input);
}, true);

document.getElementById("new-messages").addEventListener("click", function () {
    roomList.getCurrent().scrollToBottom();
});

document.getElementById("ping-clear-all").addEventListener("click", function () {
    roomList.forEach(function (room) {
        room.getPings().forEach(function (ping) {
            dataHandler.send("ping", {messageId: ping});
        });
    });
});

document.getElementById("room-search").addEventListener("click", function () {
    this.parentNode.classList.add("room-search-open");
    this.querySelector("#room-search-input").focus();
});

document.getElementById("room-search-input").addEventListener("keydown", function (e) {
    if (e.which === 27) {
        document.getElementById("room-search").parentNode.classList.remove("room-search-open");
        setTimeout(function () {
            this.value = "";
        }.bind(this), 250);
    } else if (e.which === 13) {
        window.location = "/search/rooms?q=" + encodeURIComponent(this.value);
    }
});
