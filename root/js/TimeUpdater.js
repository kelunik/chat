var TimeUpdater = function (interval) {
	this.interval = interval;
	this.intervalId = null;
	this.start();
};

TimeUpdater.prototype.start = function () {
	var updater = this;
	updater.stop();
	updater.intervalId = setInterval(function () {
		updater.update();
	}, updater.interval);
};

TimeUpdater.prototype.stop = function () {
	if (this.intervalId != null) {
		window.clearInterval(this.intervalId);
		this.intervalId = null;
	}
};

TimeUpdater.prototype.setInterval = function (interval) {
	this.interval = interval;
	this.start();
};

TimeUpdater.prototype.update = function () {
	forEach(document.getElementsByTagName("time"), function (o) {
		o.textContent = moment(o.getAttribute("datetime")).fromNow();
	});
};
