<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(home_for(current_user()['role']));
}

$f = ['full_name' => '', 'email' => '', 'phone' => '', 'dob' => '', 'gender' => ''];
$errors = [];
if (is_post()) {
    csrf_verify();
    foreach ($f as $k => $_) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $f['email'] = strtolower($f['email']);
    $pass = $_POST['password'] ?? '';
    if ($f['full_name'] === '' || mb_strlen($f['full_name']) > 100) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 120) {
        $errors[] = 'Please enter a valid e-mail address.';
    } elseif (qval('SELECT COUNT(*) FROM users WHERE email = ?', [$f['email']])) {
        $errors[] = 'An account with this e-mail already exists. Try logging in.';
    }
    if (!preg_match('/^\+?[0-9 \-]{9,15}$/', $f['phone'])) {
        $errors[] = 'Please enter a valid phone number (9-15 digits).';
    }
    if ($f['dob'] !== '' && (!($d = DateTime::createFromFormat('Y-m-d', $f['dob'])) || $d->format('Y-m-d') !== $f['dob'] || $f['dob'] > date('Y-m-d'))) {
        $errors[] = 'Please enter a valid date of birth.';
    }
    if (!in_array($f['gender'], ['', 'female', 'male', 'other'], true)) {
        $errors[] = 'Please choose a valid gender option.';
    }
    if (!valid_password($pass)) {
        $errors[] = 'Password must be at least 8 characters and contain letters and numbers.';
    }
    if ($pass !== ($_POST['password2'] ?? '')) {
        $errors[] = 'The two passwords do not match.';
    }
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        qexec('INSERT INTO users (role, full_name, email, phone, password_hash) VALUES (?,?,?,?,?)',
              ['patient', $f['full_name'], $f['email'], $f['phone'], password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = (int)$pdo->lastInsertId();
        qexec('INSERT INTO patients (user_id, dob, gender) VALUES (?,?,?)', [$uid, $f['dob'] ?: null, $f['gender'] ?: null]);
        $pdo->commit();
        log_activity($uid, 'register', 'New patient account');
        notify($uid, 'Welcome to ' . APP_NAME, 'Your account is ready. You can now find a doctor and book an appointment.', 'doctors.php');
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        flash('success', 'Account created. Welcome, ' . $f['full_name'] . '!');
        redirect('patient/dashboard.php');
    }
}
$pageTitle = 'Register';
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-4">
  <div class="card auth-card mx-auto" style="max-width:640px">
    <div class="card-body p-4">
      <h1 class="h4 mb-1">Create a patient account</h1>
      <p class="text-muted">It takes less than a minute.</p>
<?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-12"><label class="form-label" for="full_name">Full name *</label><input class="form-control" id="full_name" name="full_name" value="<?= h($f['full_name']) ?>" maxlength="100" required></div>
          <div class="col-md-6"><label class="form-label" for="email">E-mail *</label><input class="form-control" type="email" id="email" name="email" value="<?= h($f['email']) ?>" required></div>
          <div class="col-md-6"><label class="form-label" for="phone">Phone *</label><input class="form-control" id="phone" name="phone" value="<?= h($f['phone']) ?>" placeholder="0771234567" required></div>
          <div class="col-md-6"><label class="form-label" for="dob">Date of birth</label><input class="form-control" type="date" id="dob" name="dob" max="<?= h(date('Y-m-d')) ?>" value="<?= h($f['dob']) ?>"></div>
          <div class="col-md-6"><label class="form-label" for="gender">Gender</label>
            <select class="form-select" id="gender" name="gender"><option value="">Prefer not to say</option>
              <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $k => $v): ?><option value="<?= $k ?>"<?= $f['gender'] === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label" for="password">Password *</label><input class="form-control" type="password" id="password" name="password" required><div class="form-text">At least 8 characters, letters and numbers.</div></div>
          <div class="col-md-6"><label class="form-label" for="password2">Confirm password *</label><input class="form-control" type="password" id="password2" name="password2" required></div>
        </div>
        <button class="btn btn-primary w-100 mt-4" type="submit">Create account</button>
      </form>
      <p class="text-center small mt-3 mb-0">Already registered? <a href="<?= h(url('login.php')) ?>">Log in</a></p>
    </div>
  </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
