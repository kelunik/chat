"use strict";

var element, target, startX, startY, longpress, timer;

module.exports = function (_element) {
    element = _element;
    target = null;
    startX = null;
    startY = null;
    longpress = false;

    element.addEventListener("touchstart", onTouchStart, true);
};

function onTouchStart(event) {
    event.stopPropagation();
    target = event.target;

    element.addEventListener("touchend", onTouchEnd, true);
    document.body.addEventListener("touchmove", onTouchMove, true);

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
        var _target = event.target || target;

        if (_target) {
            event.preventDefault();

            _target.dispatchEvent(new Event("longpress", {
                bubbles: true
            }));
        }
    }

    reset();
}

function reset() {
    element.removeEventListener("touchend", onTouchEnd, true);
    document.body.removeEventListener("touchmove", onTouchMove, true);
    clearTimeout(timer);
    timer = null;
    longpress = false;
    target = null;
}
