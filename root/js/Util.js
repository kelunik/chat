"use strict";

module.exports = {
    throttle: throttle,
    html2node: html2node,
    generateToken: generateToken,
    escapeHtml: escapeHtml
};

function generateToken(length) {
    var token = "";

    for (var i = 0; i < length; i += 5) {
        token += Math.floor(10000000 + 89999999 * Math.random()).toString(36).substr(0, 5);
    }

    return token.substr(0, length);
}

function html2node(html) {
    var div = document.createElement("div");
    div.innerHTML = html;
    return div.firstChild;
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// thanks to https://remysharp.com/2010/07/21/throttling-function-calls
function throttle(fn, threshhold, scope) {
    threshhold || (threshhold = 250);
    var last, deferTimer;
    return function () {
        var context = scope || this;

        var now = +new Date,
            args = arguments;
        if (last && now < last + threshhold) {
            // hold on to it
            clearTimeout(deferTimer);
            deferTimer = setTimeout(function () {
                last = now;
                fn.apply(context, args);
            }, threshhold);
        } else {
            last = now;
            fn.apply(context, args);
        }
    };
}
