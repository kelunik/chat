var Util = (function () {
	return {
		html2node: function (html) {
			var div = document.createElement("div");
			div.innerHTML = html;
			return div.firstChild;
		},

		generateToken: function (length) {
			var token = "";

			for (var i = 0; i < length; i += 5) {
				token += Math.floor(10000000 + 89999999 * Math.random()).toString(36).substr(0, 5);
			}

			return token.substr(0, length);
		},

		escapeHtml: function (str) {
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}
	}
})();
