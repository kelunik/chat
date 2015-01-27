<!DOCTYPE html>
<html manifest="/manifest.appcache">
<head lang="en">
	<meta charset="UTF-8">
	<title>t@lkZone</title>

	<?php require TEMPLATE_DIR . "head_meta.php"; ?>

	<style>
		* {
			-webkit-text-size-adjust: none;
			-ms-text-size-adjust: none;
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
			display: -webkit-box;
			display: -webkit-flex;
			display: -ms-flexbox;
			display: flex;
			-webkit-flex-flow: column;
			-ms-flex-flow: column;
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
			-webkit-box-flex: 0;
			-webkit-flex: 0 0 auto;
			-ms-flex: 0 0 auto;
			flex: 0 0 auto;
		}

		#nav img {
			vertical-align: middle;
			-webkit-transform: translateY(-7px);
			-ms-transform: translateY(-7px);
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
			display: -webkit-box;
			display: -webkit-flex;
			display: -ms-flexbox;
			display: flex;
			padding: 20px 0;
			-webkit-box-flex: 2;
			-webkit-flex: 2;
			-ms-flex: 2;
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
		<picture>
			<source srcset="img/logo_40x40.png, img/logo_40x40x2.png 2x">
			<img src="img/logo_40x40.png" alt="logo">
		</picture>

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
				document.getElementById("login").addEventListener("click", function (e) {
					this.querySelector("span").innerHTML = "Signing in&hellip;";
					this.classList.add("pending");
				});
			</script>
		</div>
	</div>

	<div id="footer">
		© <?= date('Y') ?> <a href="https://github.com/amphp">amphp</a> and <a href="https://github.com/rdlowrey/amp-chat/graphs/contributors">contributors</a>
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

	(function() {
		"use strict";

		var interval = setInterval(function () {
			window.applicationCache.update();
		}, 300000);

		window.addEventListener('load', function (e) {
			window.applicationCache.addEventListener('updateready', function (e) {
				if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
					if (confirm('A new version of this site is available. Load it?')) {
						window.location.reload();
					} else {
						window.clearInterval(interval);
					}
				}
			}, false);
		}, false);
	})();
</script>

</body>
</html>
