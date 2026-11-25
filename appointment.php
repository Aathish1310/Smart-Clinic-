<?php
/** Appointment detail page (patient, doctor and staff each see the actions they are allowed). */
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$a = appointment_full((int)($_GET['id'] ?? 0));
if (!$a) {
    not_found();
}
if (!can_view_appointment($u, $a)) {
    deny('This appointment belongs to another patient or doctor.');
}
$back = safe_path($_GET['back'] ?? '', home_for($u['role']));
$active = in_array($a['status'], ['pending', 'confirmed'], true);
$started = hours_until($a) <= 0;
$patient = qone('SELECT p.*, u.phone, u.email FROM patients p JOIN users u ON u.id = p.user_id WHERE p.user_id = ?', [$a['patient_id']]);

$pageTitle = 'Appointment ' . $a['ref_no'];
$navActive = 'appts';
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-3" style="max-width:980px">
  <a class="small text-decoration-none" href="<?= h(url($back)) ?>"><i class="bi bi-arrow-left"></i> Back</a>
  <div class="d-flex flex-wrap justify-content-between align-items-center my-2 gap-2">
    <h1 class="h3 mb-0">Appointment <?= h($a['ref_no']) ?> <?= status_badge($a['status']) ?></h1>
    <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
  </div>
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card mb-3"><div class="card-header">Visit details</div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Date</dt><dd class="col-sm-8"><?= h(fmt_date($a['appt_date'])) ?></dd>
            <dt class="col-sm-4">Time</dt><dd class="col-sm-8"><?= h(fmt_time($a['start_time'])) ?> - <?= h(fmt_time($a['end_time'])) ?></dd>
            <dt class="col-sm-4">Doctor</dt><dd class="col-sm-8"><?= h($a['doctor_name']) ?> <span class="text-muted">(<?= h($a['specialization']) ?>)</span></dd>
            <dt class="col-sm-4">Room</dt><dd class="col-sm-8"><?= h($a['room'] ?: '-') ?></dd>
            <dt class="col-sm-4">Fee</dt><dd class="col-sm-8"><?= h(money($a['consultation_fee'])) ?></dd>
            <dt class="col-sm-4">Patient</dt><dd class="col-sm-8"><?= h($a['patient_name']) ?><?php if ($u['role'] !== 'patient'): ?> <span class="text-muted small">&middot; <?= h($a['patient_phone']) ?></span><?php endif; ?></dd>
            <dt class="col-sm-4">Reason for visit</dt><dd class="col-sm-8"><?= h($a['reason'] ?: '-') ?></dd>
<?php if ($a['status'] === 'cancelled'): ?>
            <dt class="col-sm-4">Cancellation</dt><dd class="col-sm-8 text-danger"><?= h($a['cancel_reason'] ?: 'No reason given') ?></dd>
<?php endif; ?>
          </dl>
        </div>
      </div>
<?php if ($u['role'] !== 'patient' && $patient): ?>
      <div class="card mb-3"><div class="card-header">Patient information</div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Date of birth</dt><dd class="col-sm-8"><?= $patient['dob'] ? h(fmt_date($patient['dob'])) . ' (' . (int)((time() - strtotime($patient['dob'])) / 31557600) . ' yrs)' : '-' ?></dd>
            <dt class="col-sm-4">Gender</dt><dd class="col-sm-8"><?= h(ucfirst((string)$patient['gender']) ?: '-') ?></dd>
            <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?= h($patient['address'] ?: '-') ?></dd>
            <dt class="col-sm-4">Emergency contact</dt><dd class="col-sm-8"><?= h($patient['emergency_contact'] ?: '-') ?></dd>
          </dl>
          <?php if ($u['role'] === 'doctor'): ?><a class="btn btn-sm btn-outline-primary mt-3" href="<?= h(url('doctor/patient.php?id=' . (int)$a['patient_id'])) ?>">View visit history</a>
          <?php else: ?><a class="btn btn-sm btn-outline-primary mt-3" href="<?= h(url('admin/patient.php?id=' . (int)$a['patient_id'])) ?>">Open patient record</a><?php endif; ?>
        </div>
      </div>
<?php endif; ?>
      <div class="card"><div class="card-header">Doctor's notes</div>
        <div class="card-body">
<?php if (can_act($u, $a, 'notes')): ?>
          <form method="post" action="<?= h(url('appointment_action.php')) ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="back" value="<?= h('appointment.php?id=' . $a['id'] . '&back=' . urlencode($back)) ?>">
            <textarea class="form-control mb-2" name="notes" rows="4" maxlength="3000" placeholder="Diagnosis, advice, prescription summary..."><?= h($a['doctor_notes']) ?></textarea>
            <button class="btn btn-sm btn-primary" name="action" value="notes"><i class="bi bi-save me-1"></i>Save notes</button>
          </form>
<?php else: ?>
          <?= $a['doctor_notes'] ? '<p class="mb-0" style="white-space:pre-wrap">' . h($a['doctor_notes']) . '</p>' : '<p class="text-muted mb-0">No notes have been added.</p>' ?>
<?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card mb-3 no-print"><div class="card-header">Actions</div>
        <div class="card-body d-grid gap-2">
<?php
$selfBack = 'appointment.php?id=' . $a['id'] . '&back=' . urlencode($back);
$post = function (string $action, string $label, string $cls, string $icon, string $confirm = '') use ($a, $selfBack) {
    echo '<form method="post" action="' . h(url('appointment_action.php')) . '"' . ($confirm ? ' data-confirm="' . h($confirm) . '"' : '') . '>'
       . csrf_field() . '<input type="hidden" name="id" value="' . (int)$a['id'] . '"><input type="hidden" name="back" value="' . h($selfBack) . '">'
       . '<button class="btn btn-' . $cls . ' w-100" name="action" value="' . $action . '"><i class="bi bi-' . $icon . ' me-1"></i>' . h($label) . '</button></form>';
};
$any = false;
if ($a['status'] === 'pending' && can_act($u, $a, 'confirm')) { $post('confirm', 'Confirm appointment', 'success', 'check-circle'); $any = true; }
if ($active && can_act($u, $a, 'reschedule')) { echo '<a class="btn btn-outline-primary" href="' . h(url('reschedule.php?id=' . $a['id'] . '&back=' . urlencode($back))) . '"><i class="bi bi-calendar2-week me-1"></i>Reschedule</a>'; $any = true; }
if ($a['status'] === 'confirmed' && $started && can_act($u, $a, 'complete')) { $post('complete', 'Mark as completed', 'primary', 'clipboard2-check'); $post('no_show', 'Mark as no-show', 'outline-secondary', 'person-x', 'Mark this patient as a no-show?'); $any = true; }
if ($active && can_act($u, $a, 'cancel')) {
    echo '<form method="post" action="' . h(url('appointment_action.php')) . '" data-confirm="Cancel this appointment?">' . csrf_field()
       . '<input type="hidden" name="id" value="' . (int)$a['id'] . '"><input type="hidden" name="back" value="' . h($selfBack) . '">'
       . '<input class="form-control form-control-sm mb-2" name="reason" maxlength="255" placeholder="Reason for cancelling (optional)">'
       . '<button class="btn btn-outline-danger w-100" name="action" value="cancel"><i class="bi bi-x-circle me-1"></i>Cancel appointment</button></form>';
    if ($u['role'] === 'patient' && hours_until($a) < CANCEL_CUTOFF_HOURS) { echo '<div class="small text-muted">Online changes close ' . CANCEL_CUTOFF_HOURS . ' hours before the visit. Please call the clinic.</div>'; }
    $any = true;
}
if (!$any) { echo '<div class="text-muted small">No actions are available for this appointment.</div>'; }
?>
        </div>
      </div>
      <div class="card"><div class="card-header">Timeline</div>
        <div class="card-body">
          <div class="timeline">
            <div class="timeline-item"><strong>Booked</strong><div class="small text-muted"><?= h(fmt_dt($a['created_at'])) ?></div></div>
<?php if ($a['updated_at'] !== $a['created_at']): ?>
            <div class="timeline-item"><strong>Last updated - <?= h(status_label($a['status'])) ?></strong><div class="small text-muted"><?= h(fmt_dt($a['updated_at'])) ?></div></div>
<?php endif; ?>
<?php if ($a['reminder_sent']): ?><div class="timeline-item"><strong>Reminder sent</strong></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
