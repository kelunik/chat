var autocomplete = function (node, rooms, templateManager) {
	"use strict";

	var currentUsers = [];
	var current = 0;

	node.addEventListener("keydown", function (e) {
		var el;

		if (currentUsers.length === 0) {
			return;
		}

		// tab, enter, comma, dot, question mark, exclamation mark, colon
		var keys = [9, 13, 188, 190, 219, 49];

		if (keys.indexOf(e.which) > -1) {
			if (e.which === 9 || e.which === 13) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}

			var text = node.value.lastIndexOf(" ");
			node.value = (text > -1 ? node.value.substr(0, text + 1) : "") + "@" + currentUsers[current].name + (e.which === 9 || e.which === 13 ? " " : "");
			Input.adjust();
			document.getElementById("autocomplete").innerHTML = "";
			currentUsers = [];
			current = 0;

			return false;
		}

		if (e.which === 38) {
			e.preventDefault();
			e.stopImmediatePropagation();

			current = --current < 0 ? currentUsers.length - 1 : current;
			el = document.querySelector("#autocomplete-current");

			if (el) {
				el.id = "";
			}

			document.querySelectorAll(".autocomplete-entry")[current].id = "autocomplete-current";

			return false;
		}

		if (e.which === 40) {
			e.preventDefault();
			e.stopImmediatePropagation();

			current = ++current % currentUsers.length;
			el = document.querySelector("#autocomplete-current");

			if (el) {
				el.id = "";
			}

			document.querySelectorAll(".autocomplete-entry")[current].id = "autocomplete-current";

			return false;
		}
	});

	node.addEventListener("input", function () {
		var pos = this.selectionStart;
		var word = this.value;
		var pre = this.value.substr(0, pos);

		if (pre.indexOf(" ") > -1) {
			var words = pre.split(" ");
			word = words[words.length - 1];
		}

		if (/^@[a-z][a-z-]*$/i.test(word)) {
			var name = word.substr(1);
			var room = rooms.getCurrent();
			currentUsers = [];

			room.getUsers().forEach(function (user) {
				if (user.name.toLowerCase().startsWith(name.toLowerCase())) {
					currentUsers.push(user);
				}
			});

			document.getElementById("autocomplete").innerHTML = templateManager.get("autocomplete")(currentUsers);

			if (currentUsers.length > 0) {
				document.querySelector(".autocomplete-entry").id = "autocomplete-current";
			}
		} else {
			document.getElementById("autocomplete").innerHTML = "";
			currentUsers = [];
		}
	});
};
