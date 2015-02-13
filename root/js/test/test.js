var expect = require("chai").expect;
require("mocha-jsdom")();

describe('String', function () {
    describe('#startsWith()', function () {
        it('should return true for "foobar".startsWith("foo")', function () {
            require("../extend.js");
            expect("foobar".startsWith("foo")).to.be.true;
        });

        it('should return false for "foobar".startsWith("bar")', function () {
            require("../extend.js");
            expect("foobar".startsWith("foo")).to.be.true;
        });
    })
});
