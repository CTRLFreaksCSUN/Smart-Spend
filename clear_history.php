<?php
// clear_history.php
session_start();
$_SESSION['spending_history'] = [];
echo json_encode(['success' => true]);
?>