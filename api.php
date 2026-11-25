<?php
/** Small JSON endpoint used by the slot picker. Requires a logged-in user. */
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

$action = $_GET['action'] ?? '';
if ($action === 'slots') {
    $doctorId = (int)($_GET['doctor_id'] ?? 0);
    $date = (string)($_GET['date'] ?? '');
    $exclude = (int)($_GET['exclude'] ?? 0);
    $slots = doctor_slots($doctorId, $date, $exclude ?: null);
    $msg = null;
    if (!$slots) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            $msg = 'Please choose a valid date.';
        } elseif ($date < date('Y-m-d')) {
            $msg = 'That date has passed.';
        } elseif ($date > date('Y-m-d', strtotime('+' . BOOKING_WINDOW_DAYS . ' days'))) {
            $msg = 'Bookings open up to ' . BOOKING_WINDOW_DAYS . ' days ahead.';
        } elseif (qval('SELECT COUNT(*) FROM doctor_leaves WHERE doctor_id = ? AND leave_date = ?', [$doctorId, $date])) {
            $msg = 'The doctor is on leave on this date.';
        } else {
            $msg = 'The doctor does not have any free times on this date.';
        }
    }
    echo json_encode(['slots' => $slots, 'message' => $msg]);
    exit;
}
http_response_code(400);
echo json_encode(['error' => 'Unknown action.']);
