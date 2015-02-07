window.prev = function (node, selector) {
    var children = node.parentNode.querySelectorAll(selector);
    var index = Array.prototype.indexOf.call(children, node);

    return index > 0 ? children.item(index - 1) : null;
};

window.next = function (node, selector) {
    var children = node.parentNode.querySelectorAll(selector);
    var index = Array.prototype.indexOf.call(children, node);

    return index < children.length - 1 ? children.item(index + 1) : null;
};

window.isTouchDevice = function () {
    return "ontouchstart" in window;
};

window.getSelectionText = function () {
    var text = "";
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
};
