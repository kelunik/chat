<?= $this->inline("header.php") ?>
<div style="width: 300px">
    <style scoped>
        .button {
            width: 100%;
            display: block;
            border: 0;
            border-bottom: 2px solid rgba(0, 0, 0, .25);
            font-weight: bold;
            padding: 15px;
            color: rgba(255, 255, 255, .95);
            margin: 8px 0;
            border-radius: 3px;
            text-align: center;
            font-family: 'Lato', sans-serif;
        }

        .button, .button:hover {
            text-decoration: none !important;
            cursor: default;
            align-items: flex-start;
            line-height: 100%;
        }

        .button .fa {
            /* best rendering */
            font-size: 14px;
            margin-right: 6px;
        }
    </style>

    <h1 style="text-align: center; font-weight: normal; padding: 50px 0;">welcome \o/</h1>

    <a href="login" class="button" style="background-color: #4CAF50">
        <i class="fa fa-sign-in"></i> Sign in
    </a>
</div>
<?= $this->inline("footer.php") ?>
