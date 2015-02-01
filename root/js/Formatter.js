var Formatter = (function (window, document, messages, rooms, templateManager, util) {
	"use strict";

	var md = new Remarkable('full', {
		html: false,
		xhtmlOut: false,
		breaks: true,
		langPrefix: 'language-',
		linkify: true,
		typographer: true,
		quotes: '“”‘’',
		highlight: function (str, lang) {
			if (lang === "text" || lang === "plain" || lang === "nohighlight" || lang === "no-highlight") {
				return "";
			}

			if (lang && hljs.getLanguage(lang)) {
				try {
					return hljs.highlight(lang, str).value;
				} catch (err) {
					// default
				}
			}

			try {
				return hljs.highlightAuto(str).value;
			} catch (err) {
				// default
			}

			return ""; // use external default escaping
		}
	});

	md.use(new IssueLinker());

	return {
		formatMessage: function (roomId, node, text, reply, user) {
			var match = /^(https:\/\/trello\.com\/c\/([0-9a-z]+))(\/.*)?$/i.exec(text);

			if (roomId > 0 && match) {
				var link = document.createElement("a");
				link.href = match[1];
				link.target = "_blank";
				link.textContent = match[1];
				node.appendChild(link);

				var url = "https://api.trello.com/1/card/" + match[2];
				url += "?key=" + window.trelloKey;

				var req = new XMLHttpRequest();
				req.onload = function () {
					if (this.status === 200) {
						try {
							var data = JSON.parse(this.response);
							var html = templateManager.get("trello_card")(data);
							node.parentNode.replaceChild(util.html2node(html), node);

							var room = rooms.get(roomId);
							if (room.isDefaultScroll()) {
								room.scrollToBottom();
							}
						} catch (e) {
							console.log("Couldn't load trello card.", e);
						}
					}
				};

				req.open("GET", url, true);
				req.send();

				return node;
			}

			node.innerHTML = md.render(text);

			node.querySelectorAll("code:not([class])").forEach(function (o) {
				o.classList.add("inline-code");
			});

			node.querySelectorAll("img").forEach(function (img) {
				// currently we can't allow images, because they're mixed content,
				// which we want to avoid, sorry. Just replace those images with a link.
				var link = document.createElement("a");
				link.href = img.src;
				link.textContent = img.src;
				img.parentNode.replaceChild(link, img);
			});

			if (text.indexOf("/me ") === 0) {
				node.parentNode.parentNode.classList.remove("chat-message-followup");
				node.parentNode.parentNode.classList.add("chat-message-cmd-me");
				node.innerHTML = node.innerHTML.replace("/me ", util.escapeHtml(user.name) + " ");
			}

			else if (reply) {
				node.innerHTML = node.innerHTML.replace(/:\d+/, templateManager.get("reply_to")(reply));

				var replyNode = node.querySelector(".in-reply");

				if (replyNode) {
					replyNode.onclick = function () {
						messages.highlight(parseInt(this.getAttribute("data-id")));
					};
				}
			}

			if (roomId === -1) {
				return;
			}

			node.parentNode.parentNode.onmouseover = function (e) {
				var m = reply ? messages.get(reply.messageId) : null;

				if (m) {
					m.classList.add("reply");
				}

				m = document.querySelectorAll(".chat-message[data-reply='" + node.parentNode.parentNode.getAttribute("data-id") + "']");

				m.forEach(function (i) {
					i.classList.add("reply");
				});
			};

			node.parentNode.parentNode.onmouseout = function (e) {
				var m = reply ? messages.get(reply.messageId) : null;

				if (m) {
					m.classList.remove("reply");
				}

				m = document.querySelectorAll(".chat-message[data-reply='" + node.parentNode.parentNode.getAttribute("data-id") + "']");

				m.forEach(function (i) {
					i.classList.remove("reply");
				});
			};

			node.getElementsByTagName("a").forEach(function (o) {
				o.setAttribute("target", "_blank");
			});

			return node;
		}
	}
})(window, document, Messages, Rooms, TemplateManager, Util);
