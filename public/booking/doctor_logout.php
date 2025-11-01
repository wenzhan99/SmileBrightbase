<?php
/**
 * Doctor Logout - Base Version Compliant
 */

session_start();
session_destroy();

header('Location: /SmileBrightbase/public/booking/doctor_login.php?message=logged_out');
exit();

