<!DOCTYPE html>
<html manifest="/manifest.appcache">
<head lang="en">
	<meta charset="UTF-8">
	<title>t@lkZone</title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<meta name="mobile-web-app-capable" content="yes">
	<link rel="icon" sizes="192x192" href="/img/icon_192x192.png">
	<link rel="icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="icon" sizes="40x40" href="/img/logo_40x40.png">
	<link rel="icon" sizes="16x16" href="/img/icon.ico">
	<link rel="apple-touch-icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="apple-touch-icon-precomposed" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="manifest" href="/manifest.json">

	<style>
		* {
			text-size-adjust: none;
		}

		html, body {
			font-size: 15px;
			font-family: Lato, sans-serif;
			margin: 0;
			min-height: 100vh;
			background: #fafafa;
		}

		#page {
			min-height: 100vh;
			display: flex;
			flex-flow: column;
		}

		a {
			font-weight: bold;
			text-decoration: none;
			color: rgba(0, 100, 200, .85);
		}

		a:hover {
			text-decoration: underline;
			color: rgb(0, 100, 200);
		}

		#nav {
			height: 54px;
			background: #111;
			text-align: center;
			line-height: 52px;
			border-bottom: 1px solid rgba(255, 255, 255, .1);
			flex: 0 0 auto;
		}

		#nav img {
			vertical-align: middle;
			transform: translateY(-7px); /* FIXME: position without translate */
		}

		h1 {
			font-family: 'Pacifico', cursive;
			font-weight: normal;
			font-size: 26px;
			line-height: 52px;
			margin: 0 0 0 20px;
			padding: 0;
			display: inline-block;
			color: #fafafa;
		}

		#content {
			display: flex;
			padding: 20px 0;
			flex: 2;
		}

		#about {
			text-align: center;
			padding: 20px;
			margin: auto;
			box-shadow: 0 0 3px rgba(0, 0, 0, .1);
			border-radius: 3px;
			border: 1px solid rgba(0, 0, 0, .1);
			border-bottom: 1px solid rgba(0, 0, 0, .175);
		}

		h2 {
			line-height: 40px;
			font-size: 22px;
			margin: 0 0 10px 0;
		}

		#login {
			text-decoration: none;
			font-weight: bold;
			color: #fff;
			margin: 25px 0 5px 0;
			padding: 20px;
			display: inline-block;
			background: #31a354;
			border-radius: 30px;
			line-height: 20px;
			border: 1px solid rgba(0, 0, 0, .2);
			box-shadow: 0 0 8px rgba(0, 0, 0, .1) inset;
			position: relative;
		}

		.fa-github {
			font-size: 20px;
			line-height: 14px;
		}

		#footer {
			background: rgba(0, 0, 0, .05);
			border-top: 1px solid rgba(0, 0, 0, .1);
			padding: 10px;
			font-size: 13px;
			color: #666;
			font-weight: bold;
			text-align: center;
		}

		.pending {
			opacity: 0.6;
		}
	</style>

	<script>
		(function (i, s, o, g, r, a, m) {
			i['GoogleAnalyticsObject'] = r;
			i[r] = i[r] || function () {
				(i[r].q = i[r].q || []).push(arguments)
			}, i[r].l = 1 * new Date();
			a = s.createElement(o),
				m = s.getElementsByTagName(o)[0];
			a.async = 1;
			a.src = g;
			m.parentNode.insertBefore(a, m)
		})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

		ga('create', '<?= GA_CODE ?>', 'auto');
		ga('send', 'pageview');
	</script>
</head>
<body>
<div id="page">
	<div id="nav">
		<img src="/img/logo_40x40.png" width="40px" height="40px">

		<h1>t@lkZone</h1>
	</div>

	<div id="content">
		<div id="about">
			<h2>What's t@lkZone?</h2>
			t@lkZone is a new chat based on <a href="https://github.com/amphp" target="_blank">amp</a> software stack.
			<br>
			<a href="/auth/github" id="login">
				<i class="fa fa-github"></i>&nbsp;&nbsp;&nbsp;<span class="text">Sign in with GitHub</span>
			</a>

			<script>
				document.getElementById("login").addEventListener("click", function(e) {
					this.querySelector("span").textContent = "Signing in ...";
					this.classList.add("pending");
				});
			</script>
		</div>
	</div>

	<div id="footer">
		© <?= date('Y') ?> amphp and contributors
	</div>
</div>

<script>
	function loadCSS(href, media) {
		"use strict";

		var s = window.document.createElement("link");
		s.rel = "stylesheet";
		s.href = href;
		s.media = "only x"; // don't block rendering
		document.head.appendChild(s);

		setTimeout(function () {
			s.media = media || "all"
		}, 0);
	}

	loadCSS("//fonts.googleapis.com/css?family=Pacifico|Lato");
	loadCSS("//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css");
</script>

</body>
</html>
