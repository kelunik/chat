<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<title>t@lkZone</title>

	<meta name="mobile-web-app-capable" content="yes">
	<link rel="icon" sizes="192x192" href="/img/icon_192x192.png">
	<link rel="icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="apple-touch-icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="apple-touch-icon-precomposed" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="manifest" href="/manifest.json">

	<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Pacifico|Lato">
	<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">

	<style>
		html, body {
			font-size: 14px;
			font-family: Lato, sans-serif;
			margin: 0;
			min-height: 100vh;
		}

		body {
			background: #222228 no-repeat center fixed;
			background-size: cover;
		}

		#welcome {
			margin: 0 auto 50px 0;
			padding-top: 50px;
			padding-bottom: 30px;
			font-size: 20px;
			text-align: center;
			color: white;
			background: rgba(0, 0, 0, .2);
			border-bottom: 1px solid rgba(0, 0, 0, .1);
			box-shadow: 0 -10px 10px -10px rgba(0, 0, 0, .2) inset;
		}

		h1 {
			font-family: 'Pacifico', cursive;
			font-weight: normal;
			font-size: 40px;
			margin: 10px 0 -5px 0;
			padding: 10px 0;
		}

		#login, #about {
			font-size: 18px;
			color: #eee;
			text-align: center;
			margin-top: 50px;
			line-height: 30px;
		}

		#login a {
			text-decoration: none;
			font-weight: bold;
			color: #222228;
			padding: 20px;
			display: inline-block;
			background: #31a354;
			border-radius: 30px;
			line-height: 20px;
			border: 1px solid rgba(255, 255, 255, .5);
			box-shadow: 0 0 8px rgba(0, 0, 0, .3);
			position: relative;
		}

		h2 {
			font-family: 'Pacifico', cursive;
			margin: 10px 0;
			font-weight: normal;
		}
	</style>
</head>
<body>
<div id="page">
	<div id="welcome">
		<h1>t@lkZone</h1>

        <span>
			That's what developers need.
		</span>
	</div>

	<div id="login">
		<a href="/auth/github">
			<i class="fa fa-github"></i>&nbsp;&nbsp;&nbsp;Sign in with GitHub
		</a>
	</div>

	<div id="about">
		<h2>What's t@lkZone?</h2>

		t@lkZone is a new chat based on aerys and amp-mysql.<br>
		Its aim is to bring developers together and integrate GitHub<br>
		and other services directly into the chat.
	</div>
</div>
</body>
</html>
