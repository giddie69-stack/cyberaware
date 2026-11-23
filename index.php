<?php require 'config.php';login_required();$u=user();
if(is_admin())run_escalation();
$title='Home';include 'includes/header.php';
$first=h(explode(' ',$u['full_name'])[0]);
$sel="SELECT i.*,t.name type,l.name lab,r.full_name reporter,a.full_name tech,TIMESTAMPDIFF(MINUTE,i.created_at,NOW()) mins FROM incidents i JOIN incident_types t ON t.id=i.incident_type_id JOIN labs l ON l.id=i.lab_id JOIN users r ON r.id=i.reporter_id LEFT JOIN users a ON a.id=i.assigned_to";
if(is_admin()):
  $rows=q("$sel ORDER BY (i.status='Handled'),FIELD(i.severity,'Critical','High','Medium','Low'),i.created_at LIMIT 150")->fetchAll();
  $techs=q("SELECT id,full_name FROM users WHERE role='technician' ORDER BY full_name")->fetchAll();
  $new=count(array_filter($rows,fn($r)=>$r['assigned_to']===null&&$r['status']!=='Handled'));$pend=count(array_filter($rows,fn($r)=>$r['assigned_to']!==null&&$r['status']!=='Handled'));$done=count(array_filter($rows,fn($r)=>$r['status']==='Handled')); ?>
<section class="hero"><h1>Hello, <?=$first?></h1><p>These are the incidents reported in the computer laboratories. Assign each case to a technician and follow it until it is handled.</p></section>
<div class="grid"><div class="panel"><p class="stat bad"><?=$new?></p>waiting to be assigned</div><div class="panel"><p class="stat bad"><?=$pend?></p>assigned, pending handling</div><div class="panel"><p class="stat ok"><?=$done?></p>handled</div></div>
<h2>Reported incidents</h2>
<div class="scroll"><table><tr><th>Ref</th><th>Type</th><th>Lab</th><th>Reported by</th><th>Severity</th><th>Status</th><th>Technician</th><th>Age</th><th></th></tr>
<?php foreach($rows as $r): ?><tr class="<?=row_class($r['status'])?>"><td><?=h($r['ref'])?></td><td><?=h($r['type'])?></td><td><?=h($r['lab'])?></td><td><?=h($r['reporter'])?></td><td><?=chip($r['severity'])?></td><td><?=chip($r['status'])?><?=$r['escalated']&&$r['status']==='Reported'?' <b class="bad">escalated</b>':''?></td>
<td><?php if($r['status']==='Handled'): ?><?=h($r['tech']?:'-')?><?php else: ?>
<form class="inline" method="post" action="incident.php?id=<?=$r['id']?>"><?=csrf()?><input type="hidden" name="action" value="assign"><input type="hidden" name="severity" value="<?=h($r['severity'])?>">
<select name="assigned_to" required aria-label="Technician"><option value="">Choose...</option><?php foreach($techs as $t): ?><option value="<?=$t['id']?>" <?=$t['id']==$r['assigned_to']?'selected':''?>><?=h($t['full_name'])?></option><?php endforeach; ?></select><button><?=$r['assigned_to']?'Reassign':'Assign'?></button></form><?php endif; ?></td>
<td><?=ago($r['mins'])?></td><td><a href="incident.php?id=<?=$r['id']?>">Open</a></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="9">No incidents reported yet.</td></tr><?php endif; ?></table></div>
<p class="muted"><span class="dot red"></span> pending &nbsp; <span class="dot green"></span> handled by the technician</p>
<?php elseif(is_tech()):
  $rows=q("$sel WHERE i.assigned_to=? ORDER BY (i.status='Handled'),FIELD(i.severity,'Critical','High','Medium','Low'),i.created_at",[$u['id']])->fetchAll();
  $pend=count(array_filter($rows,fn($r)=>$r['status']!=='Handled')); ?>
<section class="hero"><h1>Hello, <?=$first?></h1><p>Cases the admin has assigned to you appear here. Open a case, work on it and mark it as handled.</p></section>
<div class="grid"><div class="panel"><p class="stat bad"><?=$pend?></p>pending your action</div><div class="panel"><p class="stat ok"><?=count($rows)-$pend?></p>handled by you</div></div>
<h2>My assigned cases</h2>
<div class="scroll"><table><tr><th>Ref</th><th>Type</th><th>Lab</th><th>Device</th><th>Severity</th><th>Status</th><th>Assigned</th><th></th></tr>
<?php foreach($rows as $r): ?><tr class="<?=row_class($r['status'])?>"><td><?=h($r['ref'])?></td><td><?=h($r['type'])?></td><td><?=h($r['lab'])?></td><td><?=h($r['device_id']?:'-')?></td><td><?=chip($r['severity'])?></td><td><?=chip($r['status'])?></td><td><?=ago($r['mins'])?> ago</td><td><a href="incident.php?id=<?=$r['id']?>">Open</a></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="8">Nothing assigned to you yet.</td></tr><?php endif; ?></table></div>
<?php else:
  $mine=q("SELECT i.id,i.ref,i.status,t.name type FROM incidents i JOIN incident_types t ON t.id=i.incident_type_id WHERE reporter_id=? ORDER BY i.id DESC LIMIT 5",[$u['id']])->fetchAll();
  $tips=q("SELECT c.title,c.body FROM content c WHERE c.type='tip' ORDER BY RAND() LIMIT 1")->fetch();
  $att=q("SELECT t.name,a.score,a.total FROM quiz_attempts a JOIN topics t ON t.id=a.topic_id WHERE a.user_id=? ORDER BY a.id DESC LIMIT 3",[$u['id']])->fetchAll(); ?>
<section class="hero"><h1>Hello, <?=$first?></h1><p>Stay safe online. Learn something new, or report a problem so it gets fixed.</p><a class="btn big" href="report.php">Report an incident</a></section>
<div class="grid">
<div class="panel"><h3>My latest reports</h3>
<?php foreach($mine as $m): ?><p><a href="incident.php?id=<?=$m['id']?>"><?=h($m['ref'])?></a> <?=h($m['type'])?> <?=chip($m['status'])?></p><?php endforeach; if(!$mine): ?><p class="muted">You have not reported anything yet.</p><?php endif; ?><a href="track.php">Track my reports</a></div>
<div class="panel"><h3>Tip of the day</h3><?php if($tips): ?><p><b><?=h($tips['title'])?></b><br><?=h($tips['body'])?></p><?php endif; ?><a href="awareness.php">More awareness resources</a></div>
<div class="panel"><h3>My quizzes</h3>
<?php foreach($att as $a): ?><p><?=h($a['name'])?>: <b><?=$a['score']?>/<?=$a['total']?></b></p><?php endforeach; if(!$att): ?><p class="muted">No quiz taken yet.</p><?php endif; ?><a href="quiz.php">Take a quiz</a></div>
</div>
<?php endif; include 'includes/footer.php';
