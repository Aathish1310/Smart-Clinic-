<?php
/** Handles every state-changing button on appointment pages (POST only, CSRF checked). */
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
if (!is_post()) {
    redirect(home_for($u['role']));
}
csrf_verify();

$a = appointment_full((int)($_POST['id'] ?? 0));
if (!$a) {
    not_found();
}
if (!can_view_appointment($u, $a)) {
    deny();
}
$action = $_POST['action'] ?? '';
$back = safe_path($_POST['back'] ?? '', 'appointment.php?id=' . $a['id']);

if (!in_array($action, ['confirm', 'cancel', 'complete', 'no_show', 'notes'], true) || !can_act($u, $a, $action)) {
    deny('You are not allowed to perform this action.');
}

if ($action === 'notes') {
    qexec('UPDATE appointments SET doctor_notes = ? WHERE id = ?', [mb_substr(trim($_POST['notes'] ?? ''), 0, 3000) ?: null, $a['id']]);
    log_activity((int)$u['id'], 'notes_saved', $a['ref_no']);
    flash('success', 'Notes saved.');
    redirect($back);
}

$map = ['confirm' => 'confirmed', 'cancel' => 'cancelled', 'complete' => 'completed', 'no_show' => 'no_show'];
$r = set_status((int)$a['id'], $map[$action], $u, $_POST['reason'] ?? null);
flash($r['ok'] ? 'success' : 'danger', $r['msg']);
redirect($back);
