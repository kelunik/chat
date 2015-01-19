<!doctype html>
<html>
<head>
	<title>t@lkZone</title>
	<meta charset="utf-8">

	<meta name="mobile-web-app-capable" content="yes">
	<link rel="icon" sizes="192x192" href="/img/icon_192x192.png">
	<link rel="icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="apple-touch-icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="apple-touch-icon-precomposed" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="manifest" href="/manifest.json">

	<script>
		var url = "<?=DEPLOY_HTTPS ? "wss" : "ws"?>://<?=DEPLOY_AUTHORITY?>/chat";
	</script>

	<link rel="icon" href="/img/icon.ico" type="image/x-icon">
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Lato:400,700|Source+Code+Pro">
	<link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">
	<script type="text/javascript" src="/js/all.min.js?v=<?= JS_VERSION ?>"></script>

	<script>
		user = {
			id: <?= (int) $session->id ?>,
			name: "<?= htmlspecialchars($session->name) ?>",
			imageUrl: "<?= htmlspecialchars($session->avatar) ?>"
		};

		window.csrfToken = "<?=htmlspecialchars($session->csrfToken)?>";
	</script>
</head>
<body>
<div id="page"></div>
</body>
</html>
