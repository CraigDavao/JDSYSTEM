<?php
session_start();
session_unset();
session_destroy();
setcookie("remember_token", "", time() - 3600, "/"); // if you added remember me
header("Location: ../index.php");
exit();
?>
