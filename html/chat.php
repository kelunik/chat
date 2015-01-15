<!doctype html>
<html>
<head>
	<title>t@lkZone</title>
	<meta charset="utf-8">

	<script>
		var url = "<?=DEPLOY_HTTPS ? "wss" : "ws"?>://<?=DEPLOY_AUTHORITY?>/chat";
	</script>

	<link rel="icon" href="/img/icon.ico" type="image/x-icon">
	<!-- <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Lato:400,700|Source+Code+Pro"> -->
	<link rel="stylesheet" href="/css/highlight/railscasts.css">
	<link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">
	<script type="text/javascript" src="/js/all.min.js?v=<?= JS_VERSION ?>"></script>

	<script>
		user.id = <?= (int) $session->id ?>;
		user.name = "<?= htmlspecialchars($session->name) ?>";
		user.imageUrl = "<?= htmlspecialchars($session->avatar) ?>";
	</script>
</head>
<body>
<div id="page"></div>
</body>
</html>
