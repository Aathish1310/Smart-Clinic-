<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(home_for(current_user()['role']));
}

$next = safe_path($_GET['next'] ?? $_POST['next'] ?? '', '');
$email = '';
if (is_post()) {
    csrf_verify();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $user = $email !== '' ? qone('SELECT * FROM users WHERE email = ?', [$email]) : null;
    $generic = 'Incorrect e-mail or password.';

    if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $mins = (int)ceil((strtotime($user['locked_until']) - time()) / 60);
        flash('danger', "Too many failed attempts. This account is locked for about $mins more minute(s).");
    } elseif ($user && $user['is_active'] && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        qexec('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login = ? WHERE id = ?', [date('Y-m-d H:i:s'), $user['id']]);
        log_activity((int)$user['id'], 'login', 'Signed in');
        redirect($next !== '' ? $next : home_for($user['role']));
    } else {
        if ($user) {
            $fails = (int)$user['failed_logins'] + 1;
            if ($fails >= MAX_FAILED_LOGINS) {
                qexec('UPDATE users SET failed_logins = 0, locked_until = ? WHERE id = ?', [date('Y-m-d H:i:s', time() + LOCK_MINUTES * 60), $user['id']]);
                log_activity((int)$user['id'], 'account_locked', 'Too many failed logins');
            } else {
                qexec('UPDATE users SET failed_logins = ? WHERE id = ?', [$fails, $user['id']]);
            }
        }
        flash('danger', $user && !$user['is_active'] ? 'This account has been deactivated. Please contact the clinic.' : $generic);
    }
}
$pageTitle = 'Log in';
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-4">
  <div class="card auth-card mx-auto">
    <div class="card-body p-4">
      <h1 class="h4 mb-1">Welcome back</h1>
      <p class="text-muted">Log in to manage your appointments.</p>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= h($next) ?>">
        <div class="mb-3"><label class="form-label" for="email">E-mail</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= h($email) ?>" required autofocus></div>
        <div class="mb-3"><label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" required></div>
        <button class="btn btn-primary w-100" type="submit">Log in</button>
      </form>
      <p class="text-center small mt-3 mb-0">New patient? <a href="<?= h(url('register.php')) ?>">Create an account</a></p>
    </div>
  </div>
  <div class="card auth-card mx-auto mt-3">
    <div class="card-body small text-muted">
      <strong>Demo accounts</strong> (password <code>demo1234</code>):<br>
      patient <code>nimal.fernando@clinic.test</code> &middot; doctor <code>kavitha.ramanathan@clinic.test</code><br>
      staff <code>priya.jayasuriya@clinic.test</code> &middot; admin <code>admin@clinic.test</code>
    </div>
  </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
