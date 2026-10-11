<?php
// Shim to preserve old link name: redirect to reciep.php with same query string
$qs = $_SERVER['QUERY_STRING'] ?? '';
$target = '/DORCFSRF/pages/admin_dashboard/reciep.php' . ($qs ? '?' . $qs : '');
header('Location: ' . $target);
exit;
?>