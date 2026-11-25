<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Book clinic appointments online';
$navActive = 'home';
$u = current_user();
$docs = qall("SELECT u.id, u.full_name, s.name AS specialization, d.qualification, d.consultation_fee
              FROM doctors d JOIN users u ON u.id = d.user_id JOIN specializations s ON s.id = d.specialization_id
              WHERE u.is_active = 1 ORDER BY u.full_name LIMIT 6");
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-3">
  <section class="hero p-4 p-md-5 mb-4">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h1 class="display-6 fw-bold">Book your clinic visit in a minute - no phone calls, no waiting in line.</h1>
        <p class="lead mt-3">Choose a doctor, pick a free time and get a confirmation and a reminder. Doctors and clinic staff manage schedules and bookings from one simple system.</p>
        <div class="mt-4 d-flex flex-wrap gap-2">
<?php if ($u): ?>
          <a class="btn btn-light btn-lg" href="<?= h(url(home_for($u['role']))) ?>"><i class="bi bi-speedometer2 me-1"></i>Go to my dashboard</a>
<?php else: ?>
          <a class="btn btn-light btn-lg" href="<?= h(url('register.php')) ?>"><i class="bi bi-person-plus me-1"></i>Create a patient account</a>
          <a class="btn btn-outline-light btn-lg" href="<?= h(url('login.php')) ?>">Log in</a>
<?php endif; ?>
        </div>
      </div>
      <div class="col-lg-4 d-none d-lg-block text-center"><i class="bi bi-calendar2-heart" style="font-size:8rem;opacity:.85"></i></div>
    </div>
  </section>

  <h2 class="h4 mb-3">How it works</h2>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><span class="step-badge mb-3">1</span><h3 class="h6">Register &amp; log in</h3><p class="text-muted mb-0">Create a free patient account with your contact details. Your information is stored securely.</p></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><span class="step-badge mb-3">2</span><h3 class="h6">Choose a doctor and time</h3><p class="text-muted mb-0">Search by name or speciality and see only the times that are really free - double booking is not possible.</p></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><span class="step-badge mb-3">3</span><h3 class="h6">Get confirmation &amp; reminders</h3><p class="text-muted mb-0">The clinic confirms your booking and you receive a reminder before the visit. Cancel or reschedule online.</p></div></div></div>
  </div>

  <div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="h4 mb-0">Our doctors</h2>
    <a href="<?= h(url('doctors.php')) ?>">See all doctors &rarr;</a>
  </div>
  <div class="row g-3">
<?php foreach ($docs as $d): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100"><div class="card-body d-flex gap-3">
        <span class="doctor-avatar"><?= h(strtoupper(substr(preg_replace('/^Dr\.?\s*/i', '', $d['full_name']), 0, 1))) ?></span>
        <div>
          <h3 class="h6 mb-0"><?= h($d['full_name']) ?></h3>
          <div class="text-primary small"><?= h($d['specialization']) ?></div>
          <div class="text-muted small"><?= h($d['qualification']) ?></div>
          <div class="small mt-1">Consultation: <strong><?= h(money($d['consultation_fee'])) ?></strong></div>
        </div>
      </div></div>
    </div>
<?php endforeach; ?>
  </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
