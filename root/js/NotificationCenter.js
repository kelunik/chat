"use strict";

var Favico = require("./vendor/favico.min.js");

var displayNotification, icons, messageIndicator = null, appIcon, userImages;

module.exports = function (roomList, dataHandler) {
    var favicon = new Favico({
        type: "circle",
        animation: "none",
        bgColor: "#d00",
        textColor: "#eee",
        fontFamily: "Lato"
    });

    userImages = {};

    appIcon = new Image(80, 80);
    appIcon.src = "/img/logo_40x40x2.png";

    displayNotification = function (title, message, customIcon) {
        var image = new Image(80, 80);
        image.crossOrigin = "Anonymous";

        var timeout = setTimeout(function () {
            image.onload = function () {

            };

            var notification = new Notification(title, {
                tag: "message",
                icon: "/img/logo_40x40x2.png",
                lang: "en_US",
                dir: "ltr",
                body: message
            });

            // Firefox closes notifications after 4 seconds,
            // let's do this in other browsers, too.
            notification.onshow = function () {
                setTimeout(notification.close.bind(notification), 5000);
            };
        }, 3000);

        image.onload = function () {
            clearTimeout(timeout);

            var icon;

            if (customIcon in userImages) {
                icon = userImages[customIcon];
            } else if (image.complete || image.readyState === 4 || image.readyState === "complete") {
                var canvas = document.createElement("canvas");
                canvas.width = canvas.height = 80;
                var ctx = canvas.getContext("2d");
                ctx.drawImage(image, 0, 0, 80, 80);
                ctx.drawImage(appIcon, 40, 40, 35, 35);
                icon = userImages[customIcon] = canvas.toDataURL();
            }

            var notification = new Notification(title, {
                tag: "message",
                icon: icon,
                lang: "en_US",
                dir: "ltr",
                body: message
            });

            // Firefox closes notifications after 4 seconds,
            // let's do this in other browsers, too.
            notification.onshow = function () {
                setTimeout(notification.close.bind(notification), 5000);
            };
        };

        image.src = customIcon;
    }.bind(this);

    icons = {
        default: "/img/icon.ico",
        ping: "/img/icon_new.ico"
    };

    // Chrome might need a user action for that
    window.addEventListener("load", function () {
        if (window.Notification && Notification.permission !== "granted") {
            Notification.requestPermission(function (status) {
                if (Notification.permission !== status) {
                    Notification.permission = status;
                }
            });
        }
    });

    var exports = {
        showMessageIndicator: function () {
            if (messageIndicator === null) {
                messageIndicator = document.getElementById("new-messages");
            }

            if (messageIndicator) {
                messageIndicator.style.display = "block";
            }
        },

        hideMessageIndicator: function () {
            if (messageIndicator === null) {
                messageIndicator = document.getElementById("new-messages");
            }

            if (messageIndicator) {
                messageIndicator.style.display = "none";
            }
        },

        showNotification: function (title, message, customIcon) {
            if (window.Notification && Notification.permission === "granted") {
                displayNotification(title, message, customIcon);
            }

            else if (window.Notification && Notification.permission !== "denied") {
                Notification.requestPermission(function (status) {
                    if (Notification.permission !== status) {
                        Notification.permission = status;
                    }

                    displayNotification(title, message, customIcon);
                }.bind(this));
            }
        },

        onPingChange: function () {
            var cnt = 0;

            roomList.forEach(function (room) {
                cnt += room.getPingCount();
            });

            favicon.badge(cnt);
        },

        clearPing: function (id) {
            roomList.forEach(function (room) {
                var pings = room.getPings();

                pings.forEach(function (ping) {
                    if (ping === id) {
                        dataHandler.send("ping", {
                            messageId: id
                        });
                    }
                }.bind(this))
            }.bind(this));
        }
    };

    window.addEventListener("beforeunload", function () {
        // close this explicitly, because browsers may not directly close connections on unload
        // and further payloads would be processed
        dataHandler.close();

        // restore default favicon, so user's bookmarks show the correct one
        favicon.reset();
    }.bind(exports), false);

    return exports;
};
