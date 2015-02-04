var Room = (function (window, document, activityObserver, dataHandler, messages, notificationCenter, roomsObj, templateManager, util) {
    "use strict";

    var rooms, tabs, infos, stars, template;

    template = {
        content: templateManager.get("room"),
        tab: templateManager.get("room_tab"),
        info: templateManager.get("room_info"),
        stars: templateManager.get("stars")
    };

    return function (data) {
        var id, name, description, users, pings, defaultScroll, firstMessage, lastMessage, scrollTimeout,
            firstLoadableMessage, transcriptPending, contentNode, tabNode, infoNode, starsNode, pingNode,
            initialPayloadSent;

        rooms = rooms || document.getElementById("rooms");
        tabs = tabs || document.getElementById("room-tabs");
        infos = infos || document.getElementById("room-infos");
        stars = stars || document.getElementById("stars");

        if (!rooms || !tabs) {
            throw new Error("no parent dom available to add room");
        }

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

        contentNode = rooms.appendChild(util.html2node(template.content(id)));
        contentNode = document.getElementById("room-" + id);
        tabNode = tabs.appendChild(util.html2node(template.tab({
            id: id,
            name: name
        })));
        tabNode = document.getElementById("room-tab-" + id);
        infoNode = infos.appendChild(util.html2node(template.info(data)));
        starsNode = stars.appendChild(util.html2node(template.stars({
            roomId: id
        })));
        pingNode = tabNode.querySelector(".pings");

        this.getId = function () {
            return id;
        };

        this.getName = function () {
            return name;
        };

        this.getDescription = function () {
            return description;
        };

        this.getUsers = function () {
            return users;
        };

        this.getNode = function () {
            return contentNode;
        };

        this.getTabNode = function () {
            return tabNode;
        };

        this.getPings = function () {
            return pings;
        };

        this.getPingCount = function () {
            return pings.length;
        };

        this.addPing = function (id) {
            for (var i = pings.length - 1; i >= 0; i--) {
                if (pings[i] === id) {
                    return;
                }
            }

            pings.push(id);
            pingNode.setAttribute("data-pings", this.getPingCount());
            notificationCenter.onPingChange();
        };

        this.clearPing = function (id) {
            for (var i = pings.length - 1; i >= 0; i--) {
                if (pings[i] === id) {
                    pings.splice(i, 1);
                    pingNode.setAttribute("data-pings", this.getPingCount());
                    notificationCenter.onPingChange();
                }
            }
        };

        this.popPing = function () {
            return pings.shift();
        };

        this.getFirstMessage = function () {
            return firstMessage;
        };

        this.getLastMessage = function () {
            return lastMessage;
        };

        this.setFirstMessage = function (value) {
            firstMessage = value;
        };

        this.setLastMessage = function (value) {
            lastMessage = value;
        };

        this.noMoreMessages = function () {
            firstLoadableMessage = firstMessage;
        };

        this.setDefaultScroll = function (value) {
            defaultScroll = value;
        };

        this.checkDefaultScroll = function () {
            defaultScroll = contentNode.clientHeight === contentNode.scrollHeight || contentNode.clientHeight === contentNode.scrollHeight - contentNode.scrollTop;
        };

        this.setTranscriptPending = function (value) {
            transcriptPending = value;
        };

        this.shouldScroll = function () {
            return activityObserver.isActive() && roomsObj.getCurrent() === this && defaultScroll;
        };

        this.isDefaultScroll = function () {
            return defaultScroll;
        };

        this.scrollToBottom = function () {
            contentNode.scrollTop = contentNode.scrollHeight;

            if (roomsObj.getCurrent() === this) {
                notificationCenter.hideMessageIndicator();
            }
        };

        this.onComeBack = function () {
            if (defaultScroll) {
                contentNode.scrollTop += contentNode.clientHeight;
                this.checkDefaultScroll();

                if (!defaultScroll) {
                    notificationCenter.showMessageIndicator();
                } else {
                    notificationCenter.hideMessageIndicator();
                }
            }
        };

        this.onScroll = function () {
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
        };

        this.addUser = function (data) {
            // TODO: improve implementation
            users.push(data);
            infoNode = infos.replaceChild(infoNode, util.html2node(template.info(users)));
        };

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

            roomsObj.focus(id, true);
            tabNode.setAttribute("data-new-messages", "0");

            window.history.replaceState(null, "", "/rooms/" + id);
        }.bind(this));

        pingNode.setAttribute("data-pings", this.getPingCount());

        pingNode.addEventListener("click", function () {
            var messageId = this.popPing();

            if (!messageId) {
                return;
            }

            messages.highlight(messageId);
            dataHandler.send("ping", {
                messageId: messageId
            });
            pingNode.setAttribute("data-pings", this.getPingCount());
            notificationCenter.onPingChange();
        }.bind(this));

        contentNode.addEventListener("scroll", function () {
            if (scrollTimeout === null) {
                this.onScroll();

                scrollTimeout = window.setTimeout(function () {
                    this.onScroll();
                }.bind(this), 200);
            } else {
                clearTimeout(scrollTimeout);
                scrollTimeout = window.setTimeout(function () {
                    this.onScroll();
                    scrollTimeout = null;
                }.bind(this), 200);
            }
        }.bind(this));
    };
})(window, document, ActivityObserver, DataHandler, Messages, NotificationCenter, Rooms, TemplateManager, Util);
