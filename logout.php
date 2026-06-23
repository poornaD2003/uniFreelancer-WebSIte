<?php
session_start();
session_unset();
session_destroy();
header("Location: student_freelancer_site.php");
exit();
?>
