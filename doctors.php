<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = current_user();
$q = trim($_GET['q'] ?? '');
$spec = (int)($_GET['spec'] ?? 0);

$sql = "SELECT u.id, u.full_name, s.name AS specialization, d.qualification, d.experience_years, d.consultation_fee, d.room, d.bio
        FROM doctors d JOIN users u ON u.id = d.user_id JOIN specializations s ON s.id = d.specialization_id
        WHERE u.is_active = 1";
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR s.name LIKE ? OR d.qualification LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($spec) {
    $sql .= ' AND d.specialization_id = ?';
    $params[] = $spec;
}
$docs = qall($sql . ' ORDER BY u.full_name', $params);
$specs = qall('SELECT id, name FROM specializations ORDER BY name');
$dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

$pageTitle = 'Our doctors';
$navActive = 'doctors';
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-3">
  <h1 class="h3 mb-3">Find a doctor</h1>
  <form class="card card-body mb-3" method="get">
    <div class="row g-2">
      <div class="col-md-6"><input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search by doctor name, speciality or qualification"></div>
      <div class="col-md-4"><select class="form-select" name="spec"><option value="0">All specialities</option>
        <?php foreach ($specs as $s): ?><option value="<?= (int)$s['id'] ?>"<?= $spec === (int)$s['id'] ? ' selected' : '' ?>><?= h($s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button></div>
    </div>
  </form>
<?php if (!$docs): ?>
  <div class="alert alert-info">No doctors match your search.</div>
<?php endif; ?>
  <div class="row g-3">
<?php foreach ($docs as $d):
    $days = qall('SELECT DISTINCT weekday FROM doctor_schedules WHERE doctor_id = ? ORDER BY weekday', [$d['id']]);
    $next = next_available((int)$d['id']);
    if (!$u) {
        $bookUrl = 'login.php?next=' . urlencode('patient/book.php?doctor=' . $d['id']);
    } elseif ($u['role'] === 'patient') {
        $bookUrl = 'patient/book.php?doctor=' . $d['id'];
    } elseif (is_staff($u)) {
        $bookUrl = 'admin/book.php?doctor=' . $d['id'];
    } else {
        $bookUrl = '';
    }
?>
    <div class="col-lg-6">
      <div class="card h-100"><div class="card-body">
        <div class="d-flex gap-3">
          <span class="doctor-avatar"><?= h(strtoupper(substr(preg_replace('/^Dr\.?\s*/i', '', $d['full_name']), 0, 1))) ?></span>
          <div class="flex-grow-1">
            <h2 class="h5 mb-0"><?= h($d['full_name']) ?></h2>
            <div class="text-primary"><?= h($d['specialization']) ?></div>
            <div class="small text-muted"><?= h($d['qualification']) ?> &middot; <?= (int)$d['experience_years'] ?> yrs experience</div>
          </div>
          <div class="text-end"><div class="fw-semibold"><?= h(money($d['consultation_fee'])) ?></div><div class="small text-muted">per visit</div></div>
        </div>
        <?php if ($d['bio']): ?><p class="small text-muted mt-3 mb-2"><?= h($d['bio']) ?></p><?php endif; ?>
        <div class="small mt-2"><i class="bi bi-calendar-week me-1"></i>Works: <?= $days ? h(implode(', ', array_map(fn($r) => $dayNames[$r['weekday']], $days))) : 'No schedule yet' ?>
          <?php if ($d['room']): ?> &middot; <i class="bi bi-door-open me-1"></i><?= h($d['room']) ?><?php endif; ?></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="small"><?= $next ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Next free: ' . h(fmt_date($next['date'])) . ', ' . h(fmt_time($next['start'])) . '</span>' : '<span class="text-danger">No free times in the next ' . BOOKING_WINDOW_DAYS . ' days</span>' ?></span>
          <?php if ($bookUrl): ?><a class="btn btn-primary btn-sm" href="<?= h(url($bookUrl)) ?>"><i class="bi bi-calendar-plus me-1"></i>Book appointment</a><?php endif; ?>
        </div>
      </div></div>
    </div>
<?php endforeach; ?>
  </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
