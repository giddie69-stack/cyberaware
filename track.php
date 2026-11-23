<?php require 'config.php';role_required('student','staff');$u=user();
$rows=q("SELECT i.id,i.ref,i.status,i.created_at,i.severity,t.name type,a.full_name tech FROM incidents i JOIN incident_types t ON t.id=i.incident_type_id LEFT JOIN users a ON a.id=i.assigned_to WHERE i.reporter_id=? ORDER BY i.id DESC",[$u['id']])->fetchAll();
$title='Track my reports';include 'includes/header.php'; ?>
<h1>Track my reports</h1><p class="muted">See which stage each report has reached. You also get an alert whenever it moves.</p>
<?php foreach($rows as $r): ?><div class="panel"><h3><a href="incident.php?id=<?=$r['id']?>"><?=h($r['ref'])?></a> &nbsp;<?=h($r['type'])?> <?=chip($r['status'])?></h3>
<p class="muted">Reported <?=h($r['created_at'])?><?=$r['tech']&&$r['status']!=='Reported'?' &middot; Technician: '.h($r['tech']):''?></p><?=tracker($r['status'])?></div>
<?php endforeach; if(!$rows): ?><div class="panel"><p>You have not reported anything yet.</p><a class="btn" href="report.php">Report an incident</a></div><?php endif;
include 'includes/footer.php';
