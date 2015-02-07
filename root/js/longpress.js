"use strict";

var element, target, startX, startY, longpress, timer;

module.exports = function (_element) {
    element = _element;
    target = null;
    startX = null;
    startY = null;
    longpress = false;

    element.addEventListener("touchstart", handleEvent, true);
};

function handleEvent(event) {
    switch (event.type) {
        case "touchstart":
            onTouchStart(event);
            break;
        case "touchmove":
            onTouchMove(event);
            break;
        case "touchend":
            onTouchEnd(event);
            break;
    }
}

function onTouchStart(event) {
    event.stopPropagation();
    target = event.target;

    element.addEventListener("touchend", handleEvent, true);
    document.body.addEventListener("touchmove", handleEvent, true);

    startX = event.touches[0].clientX;
    startY = event.touches[0].clientY;

    timer = setInterval(function () {
        longpress = true;
        onTouchEnd(new Event("touchend", {
            bubbles: true
        }));
    }, 1000);
}

function onTouchMove(event) {
    if (startX === null || startY === null) {
        return;
    }

    if (Math.abs(event.touches[0].clientX - startX) > 10 ||
        Math.abs(event.touches[0].clientY - startY) > 10
    ) {
        reset();
    }
}

function onTouchEnd(event) {
    event.stopPropagation();

    if (longpress) {
        var target = event.target || target;

        if (target) {
            event.preventDefault();

            target.dispatchEvent(new Event("longpress", {
                bubbles: true
            }));
        }
    }

    reset();
}

function reset() {
    element.removeEventListener("touchend", handleEvent, true);
    document.body.removeEventListener("touchmove", handleEvent, true);
    clearTimeout(timer);
    timer = null;
    longpress = false;
    target = null;
}
