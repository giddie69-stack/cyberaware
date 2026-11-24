<?php require 'config.php';
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $un=trim($_POST['username']);$fn=trim($_POST['full_name']);$em=trim($_POST['email']);$pw=$_POST['password'];
  if($un===''||$fn===''||$em==='')$err='Fill in your ID, full name and email.';
  elseif(!filter_var($em,FILTER_VALIDATE_EMAIL))$err='Enter a valid email address.';
  elseif(strlen($pw)<8)$err='Password must be at least 8 characters.';
  elseif(!isset(ROLES[$_POST['role']??'']))$err='Choose your role.';
  elseif(in_array($_POST['role'],['admin','technician'])&&!hash_equals(STAFF_ACCESS_CODE,trim($_POST['access_code']??'')))$err='Admin and Technician accounts need the access code from the ICT Department.';
  elseif(q("SELECT id FROM users WHERE username=? OR email=?",[$un,$em])->fetch())$err='That ID or email already has an account. Log in instead.';
  else{
    q("INSERT INTO users(username,full_name,email,password_hash,role) VALUES(?,?,?,?,?)",[$un,$fn,$em,password_hash($pw,PASSWORD_DEFAULT),$_POST['role']]);
    flash('Account created. You can log in now.');header('Location: login.php');exit;
  }
}
$title='Create account';$bodyclass='auth';$authbg='bg-aerial';include 'includes/auth_top.php'; ?>
<section class="auth-card"><h2>Create account</h2>
<?php if($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
<form method="post"><?=csrf()?>
<label class="req">Student / staff ID (username)</label><input name="username" required value="<?=h($_POST['username']??'')?>">
<label class="req">Full name</label><input name="full_name" required value="<?=h($_POST['full_name']??'')?>">
<label class="req">Email</label><input type="email" name="email" required value="<?=h($_POST['email']??'')?>">
<label class="req" for="role">I am a</label><select id="role" name="role" required><?php foreach(ROLES as $k=>$v): ?><option value="<?=$k?>" <?=($_POST['role']??'student')===$k?'selected':''?>><?=$v?></option><?php endforeach; ?></select>
<div id="accessbox" <?=in_array($_POST['role']??'',['admin','technician'])?'':'hidden'?>><label class="req" for="access_code">Access code (from the ICT Department)</label><input id="access_code" name="access_code" autocomplete="off"></div>
<label class="req">Password (8+ characters)</label><input type="password" name="password" required>
<button>Create account</button></form>
<script>var r=document.getElementById('role'),b=document.getElementById('accessbox');r.onchange=function(){b.hidden=!(r.value==='admin'||r.value==='technician')};</script>
<p class="alt muted">Already registered? <a href="login.php">Log in</a></p><p class="muted req-note">* required</p></section>
<?php include 'includes/auth_bottom.php';
