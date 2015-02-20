"use strict";

var current = 0, suggestions = [], inputNode, displayNode, suggestedElement, template;

module.exports = function (inputId, displayId, suggestCallback, template) {
    setup(inputId, displayId, suggestCallback, template);

    return {};
};

function setup(inputId, displayId, suggestCallback, template) {
    inputNode = document.getElementById(inputId);
    displayNode = document.getElementById(displayId);

    if (!inputNode || !displayNode) {
        throw new Error("Specified node not found!");
    }

    inputNode.addEventListener("keydown", function (e) {
        if (suggestions.length === 0) {
            return;
        }

        if (e.which === 9 || e.which === 13) {
            e.stopImmediatePropagation();
            e.preventDefault();
            onComplete();

            return false;
        }

        if (e.which === 38) {
            e.preventDefault();
            e.stopImmediatePropagation();

            current = --current < 0 ? suggestions.length - 1 : current;
            update();

            return false;
        }

        if (e.which === 40) {
            e.preventDefault();
            e.stopImmediatePropagation();

            current = ++current % suggestions.length;
            update();

            return false;
        }
    });

    inputNode.addEventListener("input", function () {
        var cursor = inputNode.selectionStart;
        var text = inputNode.value.substr(0, cursor);
        var lineBreak = text.lastIndexOf("\n");

        if (lineBreak > -1) {
            var lines = text.split("\n");
            text = lines[lines.length - 1];
        }

        var wordBreak = text.lastIndexOf(" ");

        if (wordBreak > -1) {
            var words = text.split(" ");
            text = words[words.length - 1];
        }

        if (/^@[a-z][a-z-]*$/i.test(text)) {
            var name = text.substr(1);
            suggestions = suggestCallback(name);
            displayNode.innerHTML = template(suggestions);
            update();
        } else {
            displayNode.innerHTML = "";
            suggestions = [];
            update();
        }
    });
}

function onComplete() {
    var text = inputNode.value;
    var cursor = inputNode.selectionStart;

    var before = text.substr(0, cursor);
    var wordBreak = before.lastIndexOf(" ");
    var newText = "";

    var value = suggestions[current].name;

    if (wordBreak > -1) {
        newText = before.substr(0, wordBreak + 1);
    }

    newText += "@" + value + " " + text.substr(cursor);
    inputNode.value = newText;
    inputNode.selectionStart = inputNode.selectionEnd = before.length + value.length - 1;

    displayNode.innerHTML = "";
    suggestions = [];
    update();
}

function update() {
    if (suggestedElement) {
        suggestedElement.removeAttribute("id");
    }

    suggestedElement = displayNode.getElementsByClassName("autocomplete-entry")[current];

    if (suggestedElement) {
        suggestedElement.setAttribute("id", "autocomplete-current");
    }
}
