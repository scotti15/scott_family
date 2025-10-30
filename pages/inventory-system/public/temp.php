<?php
// Run once in a temp file, then delete
$hash = password_hash('nottherealpassword', PASSWORD_DEFAULT);
echo $hash;
?>
