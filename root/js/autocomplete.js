var autocomplete = function (node) {
	var currentUsers = [];
	var current = 0;

	node.addEventListener("keydown", function (e) {
		if (currentUsers.length === 0) {
			return;
		}

		if (e.which === 9 || e.which === 13) {
			e.preventDefault();
			e.stopImmediatePropagation();

			var text = this.value.lastIndexOf(" ");
			this.value = (text > -1 ? this.value.substr(0, text + 1) : "") + "@" + currentUsers[current].name + " ";
			adjustInput(this);
			document.getElementById("autocomplete").innerHTML = "";
			currentUsers = [];
			current = 0;

			return false;
		}

		if (e.which === 38) {
			e.preventDefault();
			e.stopImmediatePropagation();

			current = --current < 0 ? currentUsers.length - 1 : current;

			var el = document.querySelector("#autocomplete-current");
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

			var el = document.querySelector("#autocomplete-current");
			if (el) {
				el.id = "";
			}

			document.querySelectorAll(".autocomplete-entry")[current].id = "autocomplete-current";

			return false;
		}
	});

	node.addEventListener("input", function (e) {
		var pos = this.selectionStart;
		var word = this.value;
		var pre = this.value.substr(0, pos);

		if (pre.indexOf(" ") > -1) {
			var words = pre.split(" ");
			word = words[words.length - 1];
		}

		if (/^@[a-z][a-z-]*$/i.test(word)) {
			var name = word.substr(1);
			var room = roomHandler.getCurrentRoom();
			currentUsers = [];

			forEach(room.users, function (user) {
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
