<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$a = appointment_full((int)($_GET['id'] ?? $_POST['id'] ?? 0));
if (!$a) {
    not_found();
}
if (!can_view_appointment($u, $a) || !can_act($u, $a, 'reschedule')) {
    deny();
}
$back = safe_path($_GET['back'] ?? $_POST['back'] ?? '', 'appointment.php?id=' . $a['id']);

if (is_post()) {
    csrf_verify();
    $r = reschedule_appt((int)$a['id'], trim($_POST['appt_date'] ?? ''), trim($_POST['start'] ?? ''), $u);
    flash($r['ok'] ? 'success' : 'danger', $r['msg']);
    redirect($r['ok'] ? 'appointment.php?id=' . $a['id'] . '&back=' . urlencode($back) : 'reschedule.php?id=' . $a['id'] . '&back=' . urlencode($back));
}

$pageTitle = 'Reschedule appointment';
$navActive = 'appts';
$extraScripts = '';
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-3" style="max-width:860px">
  <a class="small text-decoration-none" href="<?= h(url('appointment.php?id=' . $a['id'] . '&back=' . urlencode($back))) ?>"><i class="bi bi-arrow-left"></i> Back to appointment</a>
  <h1 class="h3 my-2">Reschedule <?= h($a['ref_no']) ?></h1>
  <div class="alert alert-secondary">Currently: <strong><?= h(fmt_date($a['appt_date'])) ?>, <?= h(fmt_time($a['start_time'])) ?></strong> with <?= h($a['doctor_name']) ?> (<?= h($a['patient_name']) ?>).
<?php if ($u['role'] === 'patient'): ?> After you move it, the clinic will need to confirm the new time.<?php endif; ?></div>
  <form method="post" class="card card-body">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="back" value="<?= h($back) ?>">
    <div class="mb-3" style="max-width:260px"><label class="form-label" for="apptDate">New date</label>
      <input type="date" class="form-control" id="apptDate" name="appt_date" min="<?= h(date('Y-m-d')) ?>" max="<?= h(date('Y-m-d', strtotime('+' . BOOKING_WINDOW_DAYS . ' days'))) ?>" required></div>
    <label class="form-label">Available times</label>
    <div id="slotPicker" data-api="<?= h(url('api.php')) ?>" data-doctor="<?= (int)$a['doctor_id'] ?>" data-exclude="<?= (int)$a['id'] ?>"></div>
    <input type="hidden" name="start" id="startInput">
    <div class="mt-4"><button class="btn btn-primary" id="bookBtn" disabled><i class="bi bi-calendar-check me-1"></i>Move appointment</button></div>
  </form>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
