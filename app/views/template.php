<!DOCTYPE html>
<html lang="en">
    <head>
        <title>STORE</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Bootstrap -->
        <link href="/../bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
        <link href="/../css/main.css" rel="stylesheet">
        <script src="/js/jquery-3.0.0.min.js"></script>
        <script src="/bootstrap/js/bootstrap.min.js"></script>
        <script src="/js/auth-reg.js"></script>
    </head>
    <body>
        <div class="container">
            <ul class="nav nav-pills">

                <li <?php if ($this->currentPage === 'allgoods') {
   ?>class="disabled" <?php } ?>><a href="/allgoods">All goods</a>
                </li>

                <li <?php if ($this->currentPage === 'viewusersgoods') {
   ?>class="disabled" <?php } ?>><a href="/viewusersgoods">View user's goods</a>
                </li>

                <?php if (!$this->isAuth()) { ?>
                    <li <?php if ($this->currentPage === 'enter') {
                        ?>class="disabled" <?php } ?>><a href="/enter">Authorization/Registration</a>
                    </li>
                <?php } ?>

                <?php if ($this->isAuth()) { ?>
                    <li <?php if ($this->currentPage === 'profile') {
                        ?>class="disabled" <?php } ?>><a href="/profile">Profile</a>
                    </li>

                    <li <?php if ($this->currentPage === 'logout') {
                        ?>class="disabled" <?php } ?>>
                        <a href="/logout">Logout</a>
                    </li>

                    <li>
                        <a href="/profile">
                            <?php echo "You are entered as " . $this->getUserLogin(); ?>
                        </a>
                    </li>
                <?php } ?>

            </ul>
            <?php include 'app/views/' . $content_view; ?>
            <div id="common-errors"><?php
                if (!empty($this->errors)) {
                    foreach ($this->errors as $error) {
                        echo "<p class=\"alert alert-error\">" . $error . "</p>" . "\n";
                    }
                }
                ?>
            </div>
        </div>
    </body>
</html>