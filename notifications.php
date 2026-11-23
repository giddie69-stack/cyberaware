<?php require 'config.php';login_required();$u=user();
$rows=q("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 50",[$u['id']])->fetchAll();
q("UPDATE notifications SET is_read=1 WHERE user_id=?",[$u['id']]);
$title='Alerts';include 'includes/header.php'; ?>
<h1>Alerts</h1><div class="panel">
<?php foreach($rows as $n): ?><p><?=$n['is_read']?'':'<b class="badge">new</b> '?><?=$n['link']?'<a href="'.h($n['link']).'">'.h($n['message']).'</a>':h($n['message'])?> <span class="muted"><?=h($n['created_at'])?></span></p>
<?php endforeach; if(!$rows): ?><p class="muted">No alerts yet. You will see status changes on your reports here.</p><?php endif; ?></div>
<?php include 'includes/footer.php';
