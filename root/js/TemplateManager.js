var TemplateManager = function () {
};

TemplateManager.prototype.get = function (key) {
	if (key in Handlebars.templates) {
		return Handlebars.templates[key];
	} else {
		console.info("No template for key: " + key);
	}
};
