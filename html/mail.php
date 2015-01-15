<?php

$css["body"] = "background:#eee;font-size:1.1em;padding:20px";
$css["all"] = "background:#fff;border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,.2);padding:0 0 10px 0";
$css["link"] = "color:rgb(0,100,200);text-decoration:none";
$css["title"] = "font-size:1.3em;background:rgba(0,100,200,.85);margin:0 0 10px 0;border-bottom:1px solid rgba(0,0,0,.1);padding:10px 20px;color:#fff;border-radius:3px 3px 0 0";
$css["notification"] = "padding:15px 20px;border-top:1px solid rgba(0,0,0,.1);border-bottom:1px solid rgba(0,0,0,.1);margin:10px 0 0 0";
$css["notification_meta"] = "margin-bottom:10px;color:#888";
$css["footer"] = $css["header"] = "font-size:.9em;color:#777;text-align:center;padding:15px";
$css["footer_link"] = "color:#666;text-decoration:none";

?><!doctype html>
<html>
<meta charset="utf-8">
<body style="<?= $css["body"] ?>">
<div style="<?= $css["header"] ?>">
	You're receiving this e-mail because you were active in t@lk.<br>You can change your notification settings by
	clicking the link at the end of this e-mail.
</div>
<div style="<?= $css["all"] ?>">
	<div style="<?= $css["title"] ?>">
		Here's what you missed ...
	</div>

	<div>
		<?php foreach ($pings as $ping): ?>
			<div style="<?= $css["notification"] ?>">
				<div style="<?= $css["notification_meta"] ?>">
					Unread ping from @<?= htmlentities($ping->author) ?> – <?= date("d.m.Y H:i", $ping->time) ?> UTC –
					<a style="<?= $css["link"] ?>" href="<?= DEPLOY_URL ?>/message/<?= $ping->id ?>#<?= $ping->id ?>">
						view message
					</a>
				</div>

				<div>
					<?= htmlentities($ping->message) ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<div style="<?= $css["footer"] ?>">
	<a style="<?= $css["footer_link"] ?>" href="<?= DEPLOY_URL ?>/settings#notifications">
		change notification settings
	</a>
</div>
</body>
</html>
