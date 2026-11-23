<?php $u=user();$unread=$u?q("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0",[$u['id']])->fetch()['c']:0; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title??'Home')?> | <?=h(SYSTEM_NAME)?></title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="top"><a class="brand" href="index.php"><img src="assets/logo.png" alt="Kenya School of TVET logo"><span>Kenya School of TVET <small>Cybersecurity Awareness and Incident Reporting System</small></span></a>
<?php if($u): ?><nav>
<a href="index.php"><?=is_admin()?'Reported incidents':(is_tech()?'My assigned cases':'Home')?></a>
<?php if(is_admin()): ?><a href="stats.php">Dashboard</a><?php endif; ?>
<a href="awareness.php">Awareness</a>
<?php if(is_reporter()): ?><a href="quiz.php">Quizzes</a><a href="report.php">Report incident</a><a href="track.php">Track my reports</a><?php endif; ?>
<?php if(is_admin()): ?><a href="manage_content.php">Manage content</a><a href="users.php">Users</a><?php endif; ?>
<a href="notifications.php">Alerts<?php if($unread): ?> <b class="badge"><?=$unread?></b><?php endif; ?></a>
<a href="logout.php">Log out (<?=h($u['username'])?>, <?=h(ucfirst($u['role']))?>)</a></nav><?php endif; ?></header>
<main>
<?php if($f=flash()): ?><p class="flash"><?=h($f)?></p><?php endif; ?>
