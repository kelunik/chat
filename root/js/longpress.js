var LongPress = function (element) {
    this.element = element;
    this.target = null;
    this.startX = null;
    this.startY = null;
    this.longpress = false;

    element.addEventListener("touchstart", this, true);
};

LongPress.prototype.handleEvent = function (event) {
    switch (event.type) {
        case "touchstart":
            this.onTouchStart(event);
            break;
        case "touchmove":
            this.onTouchMove(event);
            break;
        case "touchend":
            this.onTouchEnd(event);
            break;
    }
};

LongPress.prototype.onTouchStart = function (event) {
    event.stopPropagation();
    this.target = event.target;

    this.element.addEventListener("touchend", this, true);
    document.body.addEventListener("touchmove", this, true);

    this.startX = event.touches[0].clientX;
    this.startY = event.touches[0].clientY;

    this.timer = setInterval(function () {
        this.longpress = true;
        this.onTouchEnd(new Event("touchend", {
            bubbles: true
        }));
    }.bind(this), 1000);
};

LongPress.prototype.onTouchMove = function (event) {
    if (this.startX === null || this.startY === null) {
        return;
    }

    if (Math.abs(event.touches[0].clientX - this.startX) > 10 ||
        Math.abs(event.touches[0].clientY - this.startY) > 10
    ) {
        this.reset();
    }
};

LongPress.prototype.onTouchEnd = function (event) {
    event.stopPropagation();

    if (this.longpress) {
        var target = event.target || this.target;

        if (target) {
            event.preventDefault();

            this.target.dispatchEvent(new Event("longpress", {
                bubbles: true
            }));
        }
    }

    this.reset();
};

LongPress.prototype.reset = function (event) {
    this.element.removeEventListener("touchend", this, true);
    document.body.removeEventListener("touchmove", this, true);
    clearTimeout(this.timer);
    this.timer = null;
    this.longpress = false;
    this.target = null;
};
