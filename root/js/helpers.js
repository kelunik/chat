HTMLCollection.prototype.forEach = function (callback) {
	Array.prototype.forEach.call(this, callback);
};

NodeList.prototype.forEach = function (callback) {
	Array.prototype.forEach.call(this, callback);
};

RegExp.quote = function(str) {
	return (str+'').replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&");
};

if (typeof String.prototype.startsWith !== 'function') {
	String.prototype.startsWith = function (str) {
		return this.slice(0, str.length) === str;
	};
}

if (!Math.sign) {
	Math.sign = function (x) {
		x = +x;

		if (x === 0 || isNaN(x)) {
			return x;
		}

		return x > 0 ? 1 : -1;
	}
}

function prev(node, selector) {
	var children = node.parentNode.querySelectorAll(selector);
	var index = Array.prototype.indexOf.call(children, node);

	return index > 0 ? children.item(index - 1) : null;
}

function next(node, selector) {
	var children = node.parentNode.querySelectorAll(selector);
	var index = Array.prototype.indexOf.call(children, node);

	return index < children.length - 1 ? children.item(index + 1) : null;
}

function isTouchDevice() {
	return "ontouchstart" in window;
}

function getSelectionText() {
	var text = "";
	if (window.getSelection) {
		text = window.getSelection().toString();
	} else if (document.selection && document.selection.type != "Control") {
		text = document.selection.createRange().text;
	}
	return text;
}
