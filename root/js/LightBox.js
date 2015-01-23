var LightBox = function () {
	var obj = this;

	var overlay = document.createElement("div");
	overlay.id = "lightbox";

	var box = document.createElement("div");
	box.id = "lightbox-dialog";

	overlay.appendChild(box);

	window.addEventListener("DOMContentLoaded", function() {
		document.body.appendChild(overlay);
	});

	overlay.addEventListener("click", function () {
		obj.close();
	});
};

LightBox.prototype.showImage = function (src) {
	var lightbox = document.getElementById("lightbox");
	lightbox.style.display = "flex";

	var box = document.getElementById("lightbox-dialog");

	var img = document.createElement("img");
	img.src = src;

	box.innerHTML = img.outerHTML;

	ga('send', 'event', 'chat', 'lightbox-image');
};

LightBox.prototype.close = function() {
	var box = document.getElementById("lightbox-dialog");
	box.innerHTML = "";

	var lightbox = document.getElementById("lightbox");
	lightbox.style.display = "none";
};
