var TemplateManager = (function (handlebars) {
    "use strict";

    return {
        get: function (key) {
            if (key in handlebars.templates) {
                return handlebars.templates[key];
            }

            throw new Error("no template with key '" + key + "'");
        }
    }
})(Handlebars);
