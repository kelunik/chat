<!doctype html>
<html>
<head>
	<title>Transcript</title>
	<meta charset="utf-8">

	<meta name="mobile-web-app-capable" content="yes">
	<link rel="icon" sizes="192x192" href="/img/icon_192x192.png">
	<link rel="icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="apple-touch-icon" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="apple-touch-icon-precomposed" sizes="128x128" href="/img/icon_128x128.png">
	<link rel="manifest" href="/manifest.json">

	<link rel="icon" href="/img/icon.ico" type="image/x-icon">
	<link rel="stylesheet" href="/css/all.min.css?v=<?= CSS_VERSION ?>">
</head>
<body>
<div id="transcript">
	<?php foreach ($messages as $message): ?>
		<div id="<?= $message->id ?>" class="chat-message" data-id="<?= $message->id ?>"
			 data-author="<?= $message->userId ?>">
			<div class="chat-message-user">
				<img src="<?= $message->userAvatar ?>"
					 width="30px"
					 height="30px">
			</div>

			<div class="chat-message-content">
				<div class="chat-message-meta">
					<!-- TODO: show edit -->
					<a href="/user/<?= $message->userId ?>">
						<?= htmlentities($message->userName) ?>
					</a> –
					<time class="chat-message-time" datetime="<?= date('c', $message->time) ?>"></time>
				</div>
			<span class="right">
				<i class="chat-message-stars fa" data-stars="<?= $message->stars ?>"></i>
			</span>

				<div class="chat-message-text"><?= $message->messageText ?></div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
</body>
</html>
