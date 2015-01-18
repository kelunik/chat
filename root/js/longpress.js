(function () {
	var longPress = false;
	var pressTimer = null;

	var start = function (e) {
		if (e.type === "click" && e.button !== 0) {
			return;
		}

		longPress = false;
		e.target.classList.add("longpress");

		pressTimer = setTimeout(function () {
			e.target.dispatchEvent(new Event("longpress", {bubbles: true}));
			longPress = true;
		}, 1000);

		return false;
	};

	var cancel = function (e) {
		if (pressTimer !== null) {
			clearTimeout(pressTimer);
		}

		e.target.classList.remove("longpress");
	};

	var click = function (e) {
		if (pressTimer !== null) {
			clearTimeout(pressTimer);
		}

		e.target.classList.remove("longpress");

		if (longPress) {
			return false;
		}
	};

	document.addEventListener("mousedown", start);
	document.addEventListener("touchstart", start);
	document.addEventListener("click", click);
	document.addEventListener("mouseout", cancel);
	document.addEventListener("touchend", cancel);
	document.addEventListener("touchleave", cancel);
	document.addEventListener("touchcancel", cancel);
})();
