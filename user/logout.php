<?php
session_start();
session_destroy();
header('Location: /app/electrastore/index.php');
exit();
?>