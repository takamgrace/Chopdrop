<?php
// logout.php
session_start(); session_destroy();
header('Location: http://localhost/chopdrop/index.php'); exit;
