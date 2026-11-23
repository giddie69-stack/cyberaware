<?php require 'config.php';role_required('student','staff');$u=user();
$types=q("SELECT * FROM incident_types ORDER BY id")->fetchAll();$labs=q("SELECT * FROM labs ORDER BY id")->fetchAll();$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $type=(int)$_POST['type'];$lab=(int)$_POST['lab'];$desc=trim($_POST['description']);$people=max(1,(int)$_POST['people']);
  if(!$type||!$lab||$desc==='')$err='Choose the incident type and laboratory, and describe what happened.';
  else{
    $t=q("SELECT weight FROM incident_types WHERE id=?",[$type])->fetch();
    $sev=suggest_severity($t['weight']??1,$people);
    $occ=!empty($_POST['occurred_at'])?date('Y-m-d H:i:s',strtotime($_POST['occurred_at'])):date('Y-m-d H:i:s');
    q("INSERT INTO incidents(reporter_id,incident_type_id,lab_id,device_id,gadget,occurred_at,description,people_affected,impact,severity) VALUES(?,?,?,?,?,?,?,?,?,?)",
      [$u['id'],$type,$lab,trim($_POST['device']),trim($_POST['gadget']),$occ,$desc,$people,trim($_POST['impact']),$sev]);
    $id=db()->lastInsertId();$ref='INC-'.str_pad($id,4,'0',STR_PAD_LEFT);q("UPDATE incidents SET ref=? WHERE id=?",[$ref,$id]);
    if(!empty($_FILES['evidence']['name'][0])){
      foreach($_FILES['evidence']['name'] as $k=>$orig){
        $ext=strtolower(pathinfo($orig,PATHINFO_EXTENSION));
        if($_FILES['evidence']['error'][$k]!==0||$_FILES['evidence']['size'][$k]>3*1024*1024||!in_array($ext,['jpg','jpeg','png','pdf']))continue;
        $fn=bin2hex(random_bytes(8)).'.'.$ext;move_uploaded_file($_FILES['evidence']['tmp_name'][$k],'uploads/'.$fn);
        q("INSERT INTO attachments(incident_id,filename,original) VALUES(?,?,?)",[$id,$fn,$orig]);
      }
    }
    q("INSERT INTO incident_updates(incident_id,user_id,status,note) VALUES(?,?,?,?)",[$id,$u['id'],'Reported','Incident reported']);
    notify_role(['admin'],"New $sev incident $ref reported",'incident.php?id='.$id);
    flash("Report submitted. Your reference is $ref. You will be notified of updates.");header('Location: incident.php?id='.$id);exit;
  }
}
$title='Report an incident';include 'includes/header.php'; ?>
<h1>Report an incident</h1><p class="muted">Takes about two minutes. Reporting as <b><?=h($u['full_name'])?></b> (<?=h($u['username'])?>).</p>
<?php if($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
<form class="panel" method="post" enctype="multipart/form-data"><?=csrf()?>
<div class="row">
<div><label class="req">What kind of incident?</label><select name="type" required><option value="">Choose...</option><?php foreach($types as $t): ?><option value="<?=$t['id']?>"><?=h($t['name'])?></option><?php endforeach; ?></select></div>
<div><label class="req">Laboratory</label><select name="lab" required><option value="">Choose...</option><?php foreach($labs as $l): ?><option value="<?=$l['id']?>"><?=h($l['name'])?></option><?php endforeach; ?></select></div>
<div><label>Computer / device ID</label><input name="device" placeholder="e.g. LAB2-PC14"></div>
<div><label>Gadget used</label><select name="gadget"><option>Lab computer</option><option>Flash disk / USB</option><option>Phone</option><option>Laptop</option><option>Other</option></select></div>
<div><label>When did it happen?</label><input type="datetime-local" name="occurred_at" value="<?=date('Y-m-d\TH:i')?>"></div>
<div><label>How many people are affected?</label><input type="number" min="1" name="people" value="1"></div>
</div>
<label class="req">What happened?</label><textarea name="description" required></textarea>
<label>What harm could this cause?</label><textarea name="impact" placeholder="e.g. lost coursework, spread to other computers, money scam"></textarea>
<label>Evidence (screenshots or PDF, max 3 MB each)</label><input type="file" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.pdf">
<p><button>Submit report</button> <a class="btn ghost" href="index.php">Cancel</a></p></form>
<?php include 'includes/footer.php';
