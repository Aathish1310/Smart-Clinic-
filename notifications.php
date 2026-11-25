<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();

if (is_post()) {
    csrf_verify();
    if (($_POST['act'] ?? '') === 'read_all') {
        qexec('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$u['id']]);
    } elseif (($_POST['act'] ?? '') === 'read' || ($_POST['act'] ?? '') === 'open') {
        $n = qone('SELECT id, link FROM notifications WHERE id = ? AND user_id = ?', [(int)($_POST['id'] ?? 0), $u['id']]);
        if ($n) {
            qexec('UPDATE notifications SET is_read = 1 WHERE id = ?', [$n['id']]);
            if ($_POST['act'] === 'open' && $n['link']) {
                redirect(safe_path($n['link'], 'notifications.php'));
            }
        }
    }
    redirect('notifications.php');
}

$list = qall('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 60', [$u['id']]);
$pageTitle = 'Notifications';
include ROOT_PATH . '/includes/header.php';
?>
<div class="container py-3" style="max-width:820px">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Notifications</h1>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="read_all"><button class="btn btn-outline-primary btn-sm"><i class="bi bi-check2-all me-1"></i>Mark all as read</button></form>
  </div>
<?php if (!$list): ?><div class="alert alert-info">You have no notifications yet.</div><?php endif; ?>
  <div class="list-group shadow-sm">
<?php foreach ($list as $n): ?>
    <div class="list-group-item d-flex justify-content-between align-items-start gap-3 <?= $n['is_read'] ? '' : 'notif-unread' ?>">
      <div>
        <div class="fw-semibold"><?= h($n['title']) ?></div>
        <div class="small"><?= h($n['message']) ?></div>
        <div class="small text-muted"><?= h(fmt_dt($n['created_at'])) ?></div>
      </div>
      <form method="post" class="text-nowrap"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
<?php if ($n['link']): ?><button class="btn btn-sm btn-outline-primary" name="act" value="open">Open</button><?php elseif (!$n['is_read']): ?><button class="btn btn-sm btn-outline-secondary" name="act" value="read">Mark read</button><?php endif; ?>
      </form>
    </div>
<?php endforeach; ?>
  </div>
</div>
<?php include ROOT_PATH . '/includes/footer.php'; ?>
