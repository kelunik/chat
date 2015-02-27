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

document.getElementById("content-fw").innerHTML = require("../../html/room_overview.handlebars")(data);
