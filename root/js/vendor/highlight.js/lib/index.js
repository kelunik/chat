var hljs = require('./highlight');

hljs.registerLanguage('apache', require('./languages/apache'));
hljs.registerLanguage('bash', require('./languages/bash'));
hljs.registerLanguage('coffeescript', require('./languages/coffeescript'));
hljs.registerLanguage('cpp', require('./languages/cpp'));
hljs.registerLanguage('cs', require('./languages/cs'));
hljs.registerLanguage('css', require('./languages/css'));
hljs.registerLanguage('diff', require('./languages/diff'));
hljs.registerLanguage('fortran', require('./languages/fortran'));
hljs.registerLanguage('http', require('./languages/http'));
hljs.registerLanguage('ini', require('./languages/ini'));
hljs.registerLanguage('java', require('./languages/java'));
hljs.registerLanguage('javascript', require('./languages/javascript'));
hljs.registerLanguage('json', require('./languages/json'));
hljs.registerLanguage('makefile', require('./languages/makefile'));
hljs.registerLanguage('xml', require('./languages/xml'));
hljs.registerLanguage('markdown', require('./languages/markdown'));
hljs.registerLanguage('nginx', require('./languages/nginx'));
hljs.registerLanguage('objectivec', require('./languages/objectivec'));
hljs.registerLanguage('perl', require('./languages/perl'));
hljs.registerLanguage('php', require('./languages/php'));
hljs.registerLanguage('python', require('./languages/python'));
hljs.registerLanguage('ruby', require('./languages/ruby'));
hljs.registerLanguage('sql', require('./languages/sql'));

module.exports = hljs;