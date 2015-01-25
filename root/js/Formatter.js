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

Formatter.prototype.formatMessage = function (roomId, node, text, reply, user) {
	node.innerHTML = this.md.render(text);

	if (text.indexOf("/me ") === 0) {
		node.parentNode.parentNode.classList.remove("chat-message-followup");
		node.parentNode.parentNode.classList.add("chat-message-cmd-me");
		node.innerHTML = node.innerHTML.replace("/me ", escapeHtml(user.name) + " ");
	}

	else if (reply) {
		node.innerHTML = node.innerHTML.replace(/:\d+/, templateManager.get("reply_to")(reply));

		var replyNode = node.querySelector(".in-reply");

		if (replyNode) {
			replyNode.onclick = function () {
				roomHandler.showMessage(this.getAttribute("data-id"));
			};
		}
	}

	node.parentNode.parentNode.onmouseover = function (e) {
		var m = reply ? messageHandler.getDOM(reply.messageId) : null;

		if (m) {
			m.classList.add("reply");
		}

		m = document.querySelectorAll(".chat-message[data-reply='" + node.parentNode.parentNode.getAttribute("data-id") + "']");

		forEach(m, function (i) {
			i.classList.add("reply");
		});
	};

	node.parentNode.parentNode.onmouseout = function (e) {
		var m = reply ? messageHandler.getDOM(reply.messageId) : null;

		if (m) {
			m.classList.remove("reply");
		}

		m = document.querySelectorAll(".chat-message[data-reply='" + node.parentNode.parentNode.getAttribute("data-id") + "']");

		forEach(m, function (i) {
			i.classList.remove("reply");
		});
	};

	forEach(node.getElementsByTagName("a"), function (o) {
		o.setAttribute("target", "_blank");
	});

	forEach(node.getElementsByTagName("img"), function (img) {
		img.addEventListener("load", function () {
			if (roomId !== -1) {
				if (roomHandler.rooms[roomId].defaultScroll) {
					setTimeout(function () { // we need that timeout, because node is added AFTER this method returns
						var roomNode = roomHandler.getRoom(roomId);
						roomNode.scrollTop = roomNode.scrollHeight;
					}, 100);
				}
			}
		});

		if (img.complete) {
			img.dispatchEvent(new Event('load'));
		}

		img.addEventListener("click", function() {
			var e = img;

			// don't enlarge linked images
			while("hasAttribute" in e.parentNode) {
				if(e.parentNode.hasAttribute("href")) {
					return;
				}

				e = e.parentNode;
			}

			lightBox.showImage(img.src);
		});
	});

	return node;
};
