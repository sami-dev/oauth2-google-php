<?php
// page2.php

session_start();

echo 'Welcome to page #2<br />';

echo '<br/>Favorite Color: ' . $_SESSION['favcolor']; // green
echo '<br/>Favorite Animal: ' .$_SESSION['animal'];   // cat
echo '<br/>Current Date: ' .date('Y m d H:i:s', $_SESSION['time']);

// You may want to use SID here, like we did in page1.php
echo '<br /><a href="page1.php">page 1</a>';
?>
