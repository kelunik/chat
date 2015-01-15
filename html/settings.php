<!doctype html>
<html>
<head>
	<title>Settings – t@lk</title>
	<meta charset="utf-8">

	<link rel="icon" href="/img/icon.ico" type="image/x-icon">
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Lato:400,700|Source+Code+Pro">
	<link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">
</head>
<body>
	<div id="settings">
		<form id="notifications" method="post" action="/settings">
			<h2>Notification Settings</h2>

			<label>
				notification frequency
				<select name="mail_notifications">
					<?php

					$opts = [
						'never' => 'never',
						'default' => '24h'
					];

					?>

					<?php foreach($opts as $opt => $val): ?>
						<option value="<?= htmlspecialchars($opt) ?>" <?= $opt === $settings["MAIL_NOTIFICATIONS"] ? "selected" : "" ?>><?= htmlspecialchars($val) ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<div>
				<button type="submit">save</button>
			</div>
		</form>
	</div>
</body>
</html>
