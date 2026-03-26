<?php
session_start();
session_destroy();
header('Location: /electrastore/index.php');
exit();
?>