"use strict";

var Util = require("./Util");

module.exports = function () {
    return {
        expand: expand
    };
};

function expand(node, linkedUrl, requestUrl, template) {
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
                var roomNode = node.parentNode.parentNode.parentNode;
                var shouldScroll = roomNode.scrollTop === roomNode.scrollHeight - roomNode.clientHeight;
                var data = JSON.parse(this.response.toString());
                var html = template(data);

                var replace = Util.html2node(html);
                node.parentNode.replaceChild(replace, node);

                if (shouldScroll) {
                    console.log(roomNode.scrollTop, roomNode.scrollHeight);
                    roomNode.scrollTop = roomNode.scrollHeight;
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
