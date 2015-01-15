function forEach(list, callback) {
	Array.prototype.forEach.call(list, callback);
}

function nodeFromHTML(html) {
	var div = document.createElement("div");
	div.innerHTML = html;
	return div.firstChild;
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

function escapeHtml(str) {
	var div = document.createElement('div');
	div.appendChild(document.createTextNode(str));
	return div.innerHTML;
}

function generateToken(length) {
	var token = "";

	for (var i = 0; i < length; i += 5) {
		token += Math.floor(10000000 + 89999999 * Math.random()).toString(36).substr(0, 5);
	}

	return token.substr(0, length);
}
