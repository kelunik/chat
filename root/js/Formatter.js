var Formatter = function () {
	this.md = new Remarkable('full', {
		html: false,        // Enable HTML tags in source
		xhtmlOut: false,        // Use '/' to close single tags (&lt;br /&gt;)
		breaks: true,        // Convert '\n' in paragraphs into &lt;br&gt;
		langPrefix: 'language-',  // CSS language prefix for fenced blocks
		linkify: true,         // autoconvert URL-like texts to links

		// Enable some language-neutral replacements + quotes beautification
		typographer: true,

		// Double + single quotes replacement pairs, when typographer enabled,
		// and smartquotes on. Set doubles to '«»' for Russian, '„“' for German.
		quotes: '“”‘’',
		highlight: function (str, lang) {
			if (lang === "text" || lang === "plain" || lang === "nohighlight" || lang === "no-highlight") {
				return "";
			}

			if (lang && hljs.getLanguage(lang)) {
				try {
					return hljs.highlight(lang, str).value;
				} catch (err) {
				}
			}

			try {
				return hljs.highlightAuto(str).value;
			} catch (err) {
			}

			return ""; // use external default escaping
		}
	});

	this.md.use(new IssueLinker());
};

Formatter.prototype.formatMessage = function (node, text, reply, user) {
	node.innerHTML = this.md.render(text);

	forEach(node.querySelectorAll("code:not([class])"), function (o) {
		o.innerHTML = hljs.highlightAuto(o.textContent).value;
	});

	if (text.indexOf("/me ") === 0) {
		node.parentNode.parentNode.classList.remove("chat-message-followup");
		node.parentNode.parentNode.classList.add("chat-message-cmd-me");
		node.innerHTML = node.innerHTML.replace("/me ", escapeHtml(user.name) + " ");
	}

	else if (reply !== null) {
		node.innerHTML = node.innerHTML.replace(/:\d+/, templateManager.get("reply_to")(reply));

		node.onmouseover = function (e) {
			var m = messageHandler.getDOM(reply.messageId);

			if (m !== null) {
				m.classList.add("reply");
			}
		};

		node.onmouseout = function (e) {
			var m = messageHandler.getDOM(reply.messageId);

			if (m !== null) {
				m.classList.remove("reply");
			}
		};

		reply = node.querySelector(".in-reply");

		if (reply !== null) {
			reply.onclick = function () {
				roomHandler.showMessage(this.getAttribute("data-id"));
			};
		}
	}

	forEach(node.getElementsByTagName("a"), function (o) {
		o.setAttribute("target", "_blank");
	});

	return node;
};
