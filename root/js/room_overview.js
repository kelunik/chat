"use strict";

require("./extend.js");

var Handlebars = require("hbsfy/runtime"),
    Remarkable = require("remarkable");

Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    switch (operator) {
        case '==':
            return (v1 == v2) ? options.fn(this) : options.inverse(this);
        case '===':
            return (v1 === v2) ? options.fn(this) : options.inverse(this);
        case '<':
            return (v1 < v2) ? options.fn(this) : options.inverse(this);
        case '<=':
            return (v1 <= v2) ? options.fn(this) : options.inverse(this);
        case '>':
            return (v1 > v2) ? options.fn(this) : options.inverse(this);
        case '>=':
            return (v1 >= v2) ? options.fn(this) : options.inverse(this);
        case '&&':
            return (v1 && v2) ? options.fn(this) : options.inverse(this);
        case '||':
            return (v1 || v2) ? options.fn(this) : options.inverse(this);
        default:
            return options.inverse(this);
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

Handlebars.registerHelper("str2color", function (str) {
    var hash = 3;

    for (var i = 0; i < str.length; i++) {
        hash = hash * 3 + str.charCodeAt(i);
    }

    return "hsl(" + (hash % 360) + ", 90%, 75%)";
});

document.getElementById("content-fw").innerHTML = require("../../html/room_overview.handlebars")(data);
