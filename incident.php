<?php require 'config.php';login_required();$u=user();
function load($id){return q("SELECT i.*,t.name type,l.name lab,r.full_name reporter,r.username rid,a.full_name assignee FROM incidents i JOIN incident_types t ON t.id=i.incident_type_id JOIN labs l ON l.id=i.lab_id JOIN users r ON r.id=i.reporter_id LEFT JOIN users a ON a.id=i.assigned_to WHERE i.id=?",[(int)$id])->fetch();}
$i=load($_GET['id']??0);
if(!$i||(is_reporter()&&$i['reporter_id']!=$u['id'])||(is_tech()&&$i['assigned_to']!=$u['id'])){http_response_code(404);exit('Incident not found');}
$link='incident.php?id='.$i['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $a=$_POST['action']??'';$sv=in_array($_POST['severity']??'',SEVERITIES)?$_POST['severity']:$i['severity'];$note=trim($_POST['note']??'');
  if($a==='assign'&&is_admin()&&$i['status']!=='Handled'){
    $t=q("SELECT * FROM users WHERE id=? AND role='technician'",[(int)$_POST['assigned_to']])->fetch();
    if($t){
      if($i['status']==='Reported')log_update($i['id'],$u['id'],'Received by Admin','Case received by admin');
      q("UPDATE incidents SET assigned_to=?,status='Assigned to Technician',severity=?,acknowledged_at=COALESCE(acknowledged_at,NOW()) WHERE id=?",[$t['id'],$sv,$i['id']]);
      log_update($i['id'],$u['id'],'Assigned to Technician','Assigned to '.$t['full_name'].($note?': '.$note:''));
      notify($t['id'],"New case assigned to you: {$i['ref']} ({$i['type']}, $sv)",$link);
      notify($i['reporter_id'],"{$i['ref']}: assigned to a technician",$link);
      notify($u['id'],"{$i['ref']} assigned to {$t['full_name']}. Pending handling.",$link);
      flash("Assigned to {$t['full_name']}. It is now pending handling.");
    }
  }elseif($a==='update'&&is_tech()&&$i['assigned_to']==$u['id']){
    $st=in_array($_POST['status'],['In Progress','Handled'])?$_POST['status']:'In Progress';
    q("UPDATE incidents SET status=?,severity=?,resolved_at=CASE WHEN ?::text='Handled' THEN NOW() END WHERE id=?",[$st,$sv,$st,$i['id']]);
    log_update($i['id'],$u['id'],$st,$note!==''?$note:($st==='Handled'?'Case handled':'Work started'));
    if($st==='Handled'){notify($i['reporter_id'],"{$i['ref']} has been handled by the technician",$link);notify_role(['admin'],"{$i['ref']} was handled by {$u['full_name']}",$link);flash('Case marked as handled. The reporter and admin have been told.');}
    else{notify($i['reporter_id'],"{$i['ref']}: the technician is working on it",$link);flash('Status updated.');}
  }
  header('Location: '.$link);exit;
}
// Admin opening a new report counts as "received"
if(is_admin()&&$i['status']==='Reported'){
  q("UPDATE incidents SET status='Received by Admin',acknowledged_at=NOW() WHERE id=?",[$i['id']]);
  log_update($i['id'],$u['id'],'Received by Admin','Case received by admin');notify($i['reporter_id'],"{$i['ref']} has been received by the admin",$link);$i=load($i['id']);
}
$ups=q("SELECT p.*,us.full_name FROM incident_updates p JOIN users us ON us.id=p.user_id WHERE incident_id=? ORDER BY p.id",[$i['id']])->fetchAll();
$files=q("SELECT * FROM attachments WHERE incident_id=?",[$i['id']])->fetchAll();
$techs=q("SELECT id,full_name FROM users WHERE role='technician' ORDER BY full_name")->fetchAll();
$title=$i['ref'];include 'includes/header.php'; ?>
<h1><?=h($i['ref'])?> <?=chip($i['severity'])?> <?=chip($i['status'])?></h1>
<div class="panel"><?=tracker($i['status'])?></div>
<div class="grid">
<div class="panel"><h3>Details</h3>
<p><b>Type:</b> <?=h($i['type'])?><br><b>Lab:</b> <?=h($i['lab'])?><br><b>Device:</b> <?=h($i['device_id']?:'-')?> (<?=h($i['gadget'])?>)<br><b>When:</b> <?=h($i['occurred_at'])?><br><b>People affected:</b> <?=(int)$i['people_affected']?><br><b>Reported by:</b> <?=h($i['reporter'])?> (<?=h($i['rid'])?>)<br><b>Technician:</b> <?=h($i['assignee']?:'Not yet assigned')?></p>
<p><b>What happened</b><br><?=nl2br(h($i['description']))?></p><p><b>Possible harm</b><br><?=nl2br(h($i['impact']?:'-'))?></p>
<?php foreach($files as $f): ?><p><a href="uploads/<?=h($f['filename'])?>" target="_blank">Evidence: <?=h($f['original'])?></a></p><?php endforeach; ?></div>
<div class="panel"><h3>History</h3><ul class="timeline"><?php foreach($ups as $p): ?><li><b><?=h($p['status'])?></b> <span class="muted"><?=h($p['created_at'])?> by <?=h($p['full_name'])?></span><br><?=h($p['note'])?></li><?php endforeach; ?></ul></div>
</div>
<?php if(is_admin()&&$i['status']!=='Handled'): ?>
<form class="panel" method="post"><?=csrf()?><input type="hidden" name="action" value="assign"><h3><?=$i['assigned_to']?'Reassign this case':'Assign this case to a technician'?></h3>
<div class="row"><div><label class="req">Technician</label><select name="assigned_to" required><option value="">Choose...</option><?php foreach($techs as $t): ?><option value="<?=$t['id']?>" <?=$t['id']==$i['assigned_to']?'selected':''?>><?=h($t['full_name'])?></option><?php endforeach; ?></select></div>
<div><label>Severity (suggested by the system)</label><select name="severity"><?php foreach(SEVERITIES as $s): ?><option <?=$s==$i['severity']?'selected':''?>><?=$s?></option><?php endforeach; ?></select></div></div>
<label>Instructions for the technician (optional)</label><textarea name="note"></textarea><p><button>Assign case</button></p></form>
<?php elseif(is_tech()&&$i['status']!=='Handled'): ?>
<form class="panel" method="post"><?=csrf()?><input type="hidden" name="action" value="update"><h3>Update this case</h3>
<div class="row"><div><label>Status</label><select name="status"><option>In Progress</option><option>Handled</option></select></div>
<div><label>Severity</label><select name="severity"><?php foreach(SEVERITIES as $s): ?><option <?=$s==$i['severity']?'selected':''?>><?=$s?></option><?php endforeach; ?></select></div></div>
<label>What did you do? (the reporter and admin can read this)</label><textarea name="note"></textarea><p><button>Save update</button></p></form>
<?php endif; include 'includes/footer.php';
