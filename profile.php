<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$patient = $u['role'] === 'patient' ? qone('SELECT * FROM patients WHERE user_id = ?', [$u['id']]) : null;

if (is_post()) {
    csrf_verify();
    $act = $_POST['act'] ?? '';
    if ($act === 'profile') {
        $name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            flash('danger', 'Please enter your name.');
        } elseif (!preg_match('/^\+?[0-9 \-]{9,15}$/', $phone)) {
            flash('danger', 'Please enter a valid phone number.');
        } else {
            qexec('UPDATE users SET full_name = ?, phone = ? WHERE id = ?', [$name, $phone, $u['id']]);
            if ($patient) {
                $dob = trim($_POST['dob'] ?? '');
                $dobOk = $dob === '' || (($d = DateTime::createFromFormat('Y-m-d', $dob)) && $d->format('Y-m-d') === $dob && $dob <= date('Y-m-d'));
                $gender = $_POST['gender'] ?? '';
                qexec('UPDATE patients SET dob = ?, gender = ?, address = ?, emergency_contact = ? WHERE user_id = ?',
                      [$dobOk && $dob !== '' ? $dob : $patient['dob'], in_array($gender, ['female', 'male', 'other'], true) ? $gender : null,
                       mb_substr(trim($_POST['address'] ?? ''), 0, 255) ?: null, mb_substr(trim($_POST['emergency_contact'] ?? ''), 0, 120) ?: null, $u['id']]);
            }
            log_activity((int)$u['id'], 'profile_updated', 'Profile details changed');
            flash('success', 'Profile updated.');
        }
    } elseif ($act === 'password') {
        $row = qone('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $row['password_hash'])) {
            flash('danger', 'Your current password is not correct.');
        } elseif (!valid_password($new)) {
            flash('danger', 'New password must be at least 8 characters with letters and numbers.');
        } elseif ($new !== ($_POST['new_password2'] ?? '')) {
            flash('danger', 'The new passwords do not match.');
        } else {
            qexec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            log_activity((int)$u['id'], 'password_changed', '');
            flash('success', 'Password changed.');
        }
    }
    redirect('profile.php');
}

$pageTitle = 'My profile';
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-3" style="max-width:900px">
  <h1 class="h3 mb-3">My profile</h1>
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card"><div class="card-header">Personal details</div><div class="card-body">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="profile">
          <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?= h($u['full_name']) ?>" required></div>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">E-mail (login)</label><input class="form-control" value="<?= h($u['email']) ?>" disabled></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= h($u['phone']) ?>" required></div>
          </div>
<?php if ($patient): ?>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Date of birth</label><input type="date" class="form-control" name="dob" max="<?= h(date('Y-m-d')) ?>" value="<?= h($patient['dob']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Gender</label><select class="form-select" name="gender"><option value="">Prefer not to say</option>
              <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $k => $v): ?><option value="<?= $k ?>"<?= $patient['gender'] === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="mb-3"><label class="form-label">Address</label><input class="form-control" name="address" maxlength="255" value="<?= h($patient['address']) ?>"></div>
          <div class="mb-3"><label class="form-label">Emergency contact</label><input class="form-control" name="emergency_contact" maxlength="120" value="<?= h($patient['emergency_contact']) ?>" placeholder="Name and phone number"></div>
<?php endif; ?>
          <button class="btn btn-primary">Save changes</button>
        </form>
      </div></div>
    </div>
    <div class="col-lg-5">
      <div class="card"><div class="card-header">Change password</div><div class="card-body">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="password">
          <div class="mb-3"><label class="form-label">Current password</label><input type="password" class="form-control" name="current_password" required></div>
          <div class="mb-3"><label class="form-label">New password</label><input type="password" class="form-control" name="new_password" required><div class="form-text">8+ characters, letters and numbers.</div></div>
          <div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" class="form-control" name="new_password2" required></div>
          <button class="btn btn-outline-primary">Change password</button>
        </form>
      </div></div>
    </div>
  </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
