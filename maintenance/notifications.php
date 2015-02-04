<?php

use Mysql\Pool;
use function Amp\run;
use function Amp\stop;

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../general_config.php";

$connect = sprintf("host=%s;user=%s;pass=%s;db=%s", DB_HOST, DB_USER, DB_PASS, DB_DB);
$db = new Pool($connect);

$run = function () use ($db) {
    $q = yield $db->prepare("SELECT u.mail, u.id FROM users AS u, pings AS p, messages AS m WHERE p.userId = u.id && m.id = p.messageId && m.time < ? && p.seen = 0 && p.mailed = 0 GROUP BY u.id LIMIT 10", [time() - 3600 * 24]);
    $users = yield $q->fetchObjects();

    $settingsQuery = yield $db->prepare("SELECT `value` FROM user_settings WHERE `key` = 'MAIL_NOTIFICATIONS' && `userId` = ?");
    $clearPingsQuery = yield $db->prepare("UPDATE pings SET mailed = 1 WHERE userId = ?");

    $tpl = new Tpl(new Parsedown);
    $tpl->load(TEMPLATE_DIR . "mail.php", Tpl::LOAD_PHP);

    $transport = (new Swift_SmtpTransport(MAIL_SERVER, MAIL_PORT, 'ssl'))
        ->setUsername(MAIL_USER)
        ->setPassword(MAIL_PASS);

    $mailer = new Swift_Mailer($transport);

    foreach ($users as $user) {
        $settingsResult = yield $settingsQuery->execute([$user->id]);

        if (yield $settingsResult->rowCount() === 1) {
            $setting = yield $settingsResult->fetchObject();

            if ($setting->value === "never") {
                yield $clearPingsQuery->execute([$user->id]);
                continue;
            }
        }

        $result = yield $db->prepare("SELECT m.id, r.name AS room, u.name AS author, m.text AS message, m.time FROM pings AS p, messages AS m, users AS u, rooms AS r WHERE p.messageId = m.id && r.id = m.roomId && u.id = m.userId && p.seen = 0 && p.mailed = 0 && p.userId = ?", [$user->id]);
        $pings = yield $result->fetchObjects();

        $tpl->set('pings', $pings);
        $html = $tpl->page();

        $message = (new Swift_Message())
            ->setSubject(count($pings) === 1 ? "a new ping on t@lk" : (count($pings) . " new pings on t@lk"))
            ->setFrom([MAIL_USER => "t@lk notifications"])
            ->setTo([$user->mail])
            ->setBody($html, "text/html", "utf-8");

        try {
            $mailer->send($message);
            yield $clearPingsQuery->execute([$user->id]);
            print "sent mail to " . $user->mail . " with " . sizeof($pings) . " pings\n";
        } catch (\Exception $e) {
            print $e->getMessage();
            print "\n\n";
        }
    }

    stop();
};

print "cron start up @ " . date("c") . "\n";

run($run);

print "cron shut down @ " . date("c") . "\n";
