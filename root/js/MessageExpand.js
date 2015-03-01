"use strict";

var util = require("./Util"),
    roomList;

module.exports = function (_roomList) {
    roomList = _roomList;

    return {
        expand: expand
    };
};

function expand(roomId, node, linkedUrl, requestUrl, template) {
    var link, req;

    link = document.createElement("a");
    link.href = linkedUrl;
    link.target = "_blank";
    link.textContent = linkedUrl;
    node.innerHTML = "";
    node.appendChild(link);

    req = new XMLHttpRequest();
    req.onload = function () {
        if (this.status === 200) {
            try {
                var data = JSON.parse(this.response.toString());
                var html = template(data);
                node.parentNode.replaceChild(util.html2node(html), node);

                if (roomList) {
                    var room = roomList.get(roomId);
                    if (room.isDefaultScroll()) {
                        room.scrollToBottom();
                    }
                }
            } catch (e) {
                // couldn't load card, just skip that
            }
        }
    };

    req.open("GET", requestUrl, true);
    req.send();

    return node;
}
