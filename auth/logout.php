<?php
require_once __DIR__ . '/../includes/auth_session.php';
app_logout();
header('Location: login.php', true, 303);
exit;
