<?php
require_once('vendor/autoload.php');
require_once('lib.php');

?>
<html>
<head>
    <title>U2F</title>
    <!-- 
    <script src="yubikey/vendor/yubico/u2flib-server/examples/assets/u2f-api.js"></script>
    -->
    <script src="yubikey/latest.js"></script>
   <script>
        var settings = <?= json_encode($jsVars); ?>;
   </script>
</head>
<body>
<h1>Register</h1>
<form method="post" id="registerForm">
    <h2>STEP 1</h2>
    <input type="text" name="username" />
    <hr />
    <h2>Step 2</h2>
    <input type="text" name="authcode" id="authcode" />
    <hr />
    <input type="submit" value="senden" />
</form>

<h1>AUTH</h1>
<form method="post" id="authForm">
    <h2>STEP 1</h2>
    <input type="text" name="userId" />
    <hr />
    <h2>Step 2</h2>
    <input type="text" name="loginAuth" id="loginAuth" />
    <hr />
    <input type="submit" value="senden" />
</form>
<script>
<?= $javascript; ?>
</script>
</body>
</html>