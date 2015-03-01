require("./extend.js");

var template = require("../../html/chat_message.handlebars");

var Handlebars = require("hbsfy/runtime");
var Formatter = require("./Formatter.js");
var Util = require("./Util.js");
var moment = require("moment");

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

var formatter = new Formatter;
var content = document.getElementById("transcript");

data.forEach(function (data) {
    var messageNode = Util.html2node(template(data));
    messageNode.setAttribute("data-text", data.messageText);

    if (data.user.id === user.id) {
        messageNode.classList.add("chat-message-me");
    }

    formatter.formatMessage(data.roomId, messageNode.querySelector(".chat-message-text"), data.messageText, data.reply, data.user);
    messageNode.querySelector("time").textContent = moment.unix(data.time).fromNow();
    messageNode.querySelector("time").setAttribute("title", moment.unix(data.time).format("LLL"));
    messageNode.querySelector("time").parentNode.href = "/messages/" + data.messageId + "#" + data.messageId;

    content.appendChild(messageNode);
});
