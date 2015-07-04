<?= $this->inline("header.php") ?>
<div style="width: 300px">
    <style scoped>
        button {
            width: 100%;
            display: block;
            border: 0;
            border-bottom: 2px solid rgba(0, 0, 0, .25);
            font-weight: bold;
            padding: 15px;
            color: rgba(255, 255, 255, .95);
            margin: 8px 0;
            border-radius: 3px;
        }

        button .fa {
            /* best rendering */
            font-size: 14px;
            margin-right: 6px;
        }
    </style>

    <form action="/login" method="get">
        <button type="submit" style="background-color: #4CAF50">
            <i class="fa fa-sign-in"></i> Sign in
        </button>
    </form>
</div>
<?= $this->inline("footer.php") ?>
