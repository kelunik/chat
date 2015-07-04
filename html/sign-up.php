<?= $this->inline("header.php") ?>
<div style="margin: auto">
    <style scoped>
        h1 {
            padding: 20px;
        }
    </style>

    <h1>choose a username</h1>

    <?php if (isset($error)): ?>
        <div class="error">
            <?= $this->escape($error) ?>
        </div>
    <?php endif; ?>

    <form action="/join" method="post">
        <input type="text" name="username" value="<?= $this->escape($hint) ?>" placeholder="Username…">
        <button type="submit">Confirm</button>
    </form>
</div>
<?= $this->inline("footer.php") ?>