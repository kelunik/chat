"use strict";

var Util = require("./Util.js"),
    Remarkable = require("remarkable"),
    hljs = require("./vendor/highlight.js"),
    IssueLinker = require("./vendor/issue-linker.min.js"),
    MessageExpand = require("./MessageExpand.js");

var messageList, md, messageExpand;

module.exports = function (_messageList, roomList) {
    messageList = _messageList;
    messageExpand = new MessageExpand(roomList);

    md = new Remarkable("full", {
        html: false,
        xhtmlOut: false,
        breaks: true,
        langPrefix: "language-",
        linkify: true,
        typographer: true,
        maxNesting: 2,
        quotes: "“”‘’",
        highlight: function (str, lang) {
            if (lang === "text" || lang === "plain" || lang === "nohighlight" || lang === "no-highlight") {
                return "";
            }

            if (lang && hljs.getLanguage(lang)) {
                try {
                    return hljs.highlight(lang, str).value;
                } catch (err) {
                    // default
                }
            }

            try {
                return hljs.highlightAuto(str).value;
            } catch (err) {
                // default
            }

            return ""; // use external default escaping
        }
    });

    md.use(new IssueLinker());

    return {
        formatMessage: formatMessage
    }
};

function formatMessage(roomId, node, text, reply, user) {
    if (roomId > 0) {
        var result = tryExpand(roomId, node, text);

        if (result) {
            return result;
        }
    }

    node.innerHTML = md.render(text);
    node.querySelectorAll("img").forEach(replaceImageWithLink);

    if (text.indexOf("/me ") === 0) {
        initMe(node, user);
    }

    else if (reply) {
        initReplyNode(node, reply);
    }

    node.getElementsByTagName("a").forEach(function (o) {
        o.setAttribute("target", "_blank");
    });

    return node;
}

function initReplyNode(node, reply) {
    node.innerHTML = node.innerHTML.replace(/:\d+/, require("../../html/reply_to.handlebars")(reply));
    var replyNode = node.querySelector(".in-reply");

    if (replyNode) {
        replyNode.onclick = function () {
            messageList.highlight(parseInt(this.getAttribute("data-id")));
        };
    }
}

function initMe(node, user) {
    node.parentNode.parentNode.classList.remove("chat-message-followup");
    node.parentNode.parentNode.classList.add("chat-message-cmd-me");
    node.innerHTML = node.innerHTML.replace("/me ", Util.escapeHtml(user.name) + " ");
}

function replaceImageWithLink(img) {
    // currently we can't allow images, because they're mixed content,
    // which we want to avoid, sorry. Just replace those images with a link.
    var link = document.createElement("a");
    link.href = img.src;
    link.textContent = img.src;
    img.parentNode.replaceChild(link, img);
}

function tryExpand(roomId, node, text) {
    var match;

    match = new RegExp("^(" + RegExp.quote(config.host) + "\/messages\/([0-9]+))(#[0-9]+)?$").exec(text);
    if (match) {
        return messageExpand.expand(roomId, node, match[1], match[1] + ".json", require("../../html/message_card.handlebars"));
    }

    match = /^(https:\/\/trello\.com\/c\/([0-9a-z]+))(\/.*)?$/i.exec(text);
    if (match) {
        var reqUrl = "https://api.trello.com/1/card/" + match[2] + "?key=" + trelloKey;
        return messageExpand.expand(roomId, node, match[1], reqUrl, require("../../html/trello_card.handlebars"));
    }

    return false;
}
