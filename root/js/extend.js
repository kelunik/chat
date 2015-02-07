HTMLCollection.prototype.forEach = Array.prototype.forEach;
NodeList.prototype.forEach = Array.prototype.forEach;

RegExp.quote = function (str) {
    return (str + '').replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&");
};

if (typeof String.prototype.startsWith !== 'function') {
    String.prototype.startsWith = function (str) {
        return this.slice(0, str.length) === str;
    };
}

if (!Math.sign) {
    Math.sign = function (x) {
        x = +x;

        if (x === 0 || isNaN(x)) {
            return x;
        }

        return x > 0 ? 1 : -1;
    }
}

require("./helpers.js");
