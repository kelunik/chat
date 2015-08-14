<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Code+Pro|Lato">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/main.css">
    <link rel="icon" href="/favicon.ico">
    <title><?= $this->escape(APP_NAME) ?></title>
</head>
<body>

<header id="top">
    <?php if (isset($login)): ?>
        <form action="/logout" method="post">
            <button type="submit"><i class="fa fa-sign-out"></i></button>
        </form>
    <?php endif; ?>
</header>

<main>