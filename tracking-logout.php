<?php

unset($_SESSION['tracked_report']);
unset($_SESSION['tracked_report_id']);
unset($_SESSION['tracking_code']);
unset($_SESSION['message_success']);
unset($_SESSION['message_error']);
unset($_SESSION['evidence_error']);
header('Location: index.php');
exit;