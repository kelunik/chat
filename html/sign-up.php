<!doctype html>
<html>
<head>
    <title>Sign Up</title>
</head>
<body>
<?php if(isset($error)): ?>
    Error: <?= $this->escape($error); ?><br>
<?php endif; ?>
<form action="/sign-up" method="post">
    <label>
        Username<br />
        <input type="text" name="username" value="<?= $this->escape($hint) ?>">
    </label>
    <button type="submit">Confirm Account Creation</button>
</form>
</body>
</html>