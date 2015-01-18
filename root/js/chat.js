var timeUpdater = new TimeUpdater(5000);
var formatter = new Formatter();
var templateManager = new TemplateManager();
var notificationCenter = new NotificationCenter();
var dataHandler = new DataHandler(url);
var messageHandler = new MessageHandler();
var roomHandler = new RoomHandler();
var user = new User();

dataHandler.on("message", messageHandler.handleMessage.bind(messageHandler));
dataHandler.on("message-edit", messageHandler.handleMessageEdit.bind(messageHandler));
dataHandler.on("missed-query", messageHandler.handleMissedQuery.bind(messageHandler));
dataHandler.on("star", messageHandler.handleStar.bind(messageHandler));
dataHandler.on("stars", roomHandler.handleStars.bind(roomHandler));
dataHandler.on("transcript", roomHandler.handleTranscript.bind(roomHandler));
dataHandler.on("ping", roomHandler.handlePing.bind(roomHandler));
dataHandler.on("ping-clear", roomHandler.handlePingClear.bind(roomHandler));
dataHandler.on("whereami", roomHandler.handleWhereAmI.bind(roomHandler));
dataHandler.on("activity", roomHandler.handleActivity.bind(roomHandler));
dataHandler.on("user-join", roomHandler.handleUserJoin.bind(roomHandler));

Handlebars.registerHelper('datetime', function (time) {
	return moment.unix(time).toISOString();
});

Handlebars.registerHelper('dateformat', function (time) {
	return moment.unix(time).format("LLL");
});

dataHandler.on("open", function () {
	var path = window.location.pathname;

	if (path.substring(0, 7) === "/rooms/") {
		dataHandler.send("whereami", {join: +path.substr(7)});
	}
});

console.log("App::addDOMContentLoader");

document.addEventListener("DOMContentLoaded", function () {
	console.log("DOMContentLoaded");

	dataHandler.connect();

	document.addEventListener("keydown", function (e) {
		if (e.target.nodeName === "TEXTAREA" || e.target.nodeName === "INPUT" || e.target.isContentEditable) {
			return;
		}

		if (e.which == 32) {
			e.preventDefault();
			document.getElementById("input").focus();
			return false;
		}
	});
});

console.log("App::addDOMContentLoader...done");

if (!Math.sign) {
	Math.sign = function (x) {
		x = +x;

		if (x === 0 || isNaN(x)) {
			return x;
		}

		return x > 0 ? 1 : -1;
	}
}


function adjustInput(node) {
	var heightBefore = node.clientHeight;
	var toScroll = [];

	forEach(document.querySelectorAll(".room"), function (o) {
		toScroll.push(o.scrollHeight - o.scrollTop - o.clientHeight);
	});

	node.style.height = 0;
	node.style.height = Math.max(40, node.scrollHeight - 20) + "px";

	forEach(document.querySelectorAll(".room"), function (o) {
		var scroll = toScroll.shift();
		o.scrollTop = o.scrollHeight - o.clientHeight - scroll;
	});
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

key("r", function (e) {
	e.preventDefault();

	var input = document.getElementById("input");
	input.value = "> " + getSelectionText().trim().replace(/\n\n/g, "\n").replace(/\n/g, "\n> ") + "\n\n";
	adjustInput(input);
	input.focus();
	var caretPos = message.getAttribute("data-text").length;
	if (input.createTextRange) {
		var range = input.createTextRange();
		range.move('character', caretPos);
		range.select();
	} else {
		if (input.selectionStart) {
			input.setSelectionRange(caretPos, caretPos);
		}
	}
});

key("shift+?", function () {
	alert("press r to post reply and quote selected text!");
});

key("end", function () {
	var node = document.getElementsByClassName("room-current")[0];
	node.scrollTop = node.scrollHeight;
});

key("pageup", function () {
	var node = document.getElementsByClassName("room-current")[0];
	node.scrollTop -= node.clientHeight * .5;
});

key("pagedown", function () {
	var node = document.getElementsByClassName("room-current")[0];
	node.scrollTop += node.clientHeight * .5;
});

var sessionCheck = setInterval(function () {
	var http = new XMLHttpRequest();
	http.open("GET", "/session/status", true);

	http.onreadystatechange = function () {
		if (http.readyState != 4) {
			return;
		}

		if (http.status === 401) {
			// TODO: Better UX
			alert("Your session expired!");
			window.location = "/auth";
		}
	};

	http.send(null);
}, 60 * 1000);

window.addEventListener("resize", function () {
	for (var id in roomHandler.rooms) {
		if (roomHandler.rooms.hasOwnProperty(id) && roomHandler.rooms[id].defaultScroll) {
			var node = roomHandler.getRoom(id);
			node.scrollTop = node.scrollHeight;
		}
	}
});

// "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
// ^ smallest valid gif, set when GitHub images is not available or in dev mode without internet connection
