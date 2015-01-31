<!doctype html>
<html>
<head>
	<title>t@lkZone</title>
	<meta charset="utf-8">

	<script>
		console.time("pageload");
	</script>

	<?php require TEMPLATE_DIR . "head_meta.php"; ?>

	<script>
		var url = "<?=DEPLOY_HTTPS ? "wss" : "ws"?>://<?=DEPLOY_AUTHORITY?>/chat";
	</script>

	<link rel="icon" href="/img/icon.ico" type="image/x-icon">
	<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Lato:400,700|Source+Code+Pro">
	<link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">

	<script>
		user = {
			id: <?= (int) $session->id ?>,
			name: "<?= htmlspecialchars($session->name) ?>",
			avatar: "<?= htmlspecialchars($session->avatar) ?>"
		};

		window.csrfToken = "<?=htmlspecialchars($session->csrfToken)?>";
	</script>

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
	<div id="chat">
		<input class="no-display" id="mobile-menu-switch-checkbox" type="checkbox">

		<div id="left-col">
			<div id="user">
				<label class="mobile-only" id="mobile-menu-switch" for="mobile-menu-switch-checkbox">
					<i class="fa fa-fw fa-navicon fa-lg"></i>
				</label>

				<span id="current-user"><?= htmlspecialchars($session->name) ?></span>

				<form id="logout" action="/logout" method="post">
					<button type="submit" class="button-no-style"><i class="fa fa-power-off" title="logout"></i>
					</button>
				</form>
			</div>
			<div id="room-tabs"></div>
		</div>

		<div id="main-col">
			<div id="rooms">

			</div>

			<div id="above_input">
				<div id="autocomplete">

				</div>

				<div id="new-messages-indicator">
					<div id="new-messages">
						<i class="fa fa-inbox"></i> scroll down to see new messages
					</div>
				</div>
			</div>

			<textarea id="input" placeholder="Type here, use markdown to format your text&hellip;"></textarea>
		</div>

		<div id="right-col">
			<div id="room-infos">

			</div>
			<div id="stars">

			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="/js/all.min.js?v=<?= JS_VERSION ?>"></script>
</body>
</html>
