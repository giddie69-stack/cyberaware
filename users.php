<?php require 'config.php';role_required('admin');
$roles=['student','staff','technician','admin'];$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(($_POST['action']??'')==='role'&&(int)$_POST['id']!==user()['id']&&in_array($_POST['role'],$roles)){q("UPDATE users SET role=? WHERE id=?",[$_POST['role'],(int)$_POST['id']]);flash('Role updated.');}
  if(($_POST['action']??'')==='add'){
    $un=trim($_POST['username']);$em=trim($_POST['email']);
    if($un===''||trim($_POST['full_name'])===''||!filter_var($em,FILTER_VALIDATE_EMAIL)||strlen($_POST['password'])<8||!in_array($_POST['role'],$roles))flash('Fill every field with * (valid email, password of 8+ characters).');
    elseif(q("SELECT id FROM users WHERE username=? OR email=?",[$un,$em])->fetch())flash('That ID or email is already used.');
    else{q("INSERT INTO users(username,full_name,email,password_hash,role) VALUES(?,?,?,?,?)",[$un,trim($_POST['full_name']),$em,password_hash($_POST['password'],PASSWORD_DEFAULT),$_POST['role']]);flash('User added.');}
  }
  header('Location: users.php');exit;
}
$us=q("SELECT * FROM users ORDER BY array_position(ARRAY['admin','technician','staff','student'],role::text),username")->fetchAll();
$title='Users';include 'includes/header.php'; ?>
<h1>Users and roles</h1>
<form class="panel row" method="post"><?=csrf()?><input type="hidden" name="action" value="add">
<div><label class="req">ID / username</label><input name="username" required></div><div><label class="req">Full name</label><input name="full_name" required></div>
<div><label class="req">Email</label><input type="email" name="email" required></div><div><label class="req">Password (8+)</label><input name="password" type="password" required></div>
<div><label class="req">Role</label><select name="role"><?php foreach($roles as $r): ?><option value="<?=$r?>" <?=$r==='technician'?'selected':''?>><?=ucfirst($r)?></option><?php endforeach; ?></select></div><div><label>&nbsp;</label><button>Add user</button></div></form>
<p class="muted">* required. Technicians and admins are created here. Students and staff can also register themselves.</p>
<div class="scroll"><table><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>
<?php foreach($us as $x): ?><tr><td><?=h($x['username'])?></td><td><?=h($x['full_name'])?></td><td><?=h($x['email'])?></td><td>
<form method="post"><?=csrf()?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?=$x['id']?>"><select name="role" onchange="this.form.submit()" <?=$x['id']==user()['id']?'disabled':''?>><?php foreach($roles as $r): ?><option value="<?=$r?>" <?=$r==$x['role']?'selected':''?>><?=ucfirst($r)?></option><?php endforeach; ?></select></form></td></tr><?php endforeach; ?></table></div>
<?php include 'includes/footer.php';
