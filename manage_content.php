<?php require 'config.php';role_required('admin');
$topics=q("SELECT * FROM topics ORDER BY id")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $a=$_POST['action']??'';
  if($a==='content'){
    $media=trim($_POST['link']);
    if(!empty($_FILES['file']['name'])&&$_FILES['file']['error']===0&&$_FILES['file']['size']<=50*1024*1024){
      $ext=strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
      if(in_array($ext,['jpg','jpeg','png','gif','mp4','webm','pdf'])){$fn='content_'.bin2hex(random_bytes(6)).'.'.$ext;move_uploaded_file($_FILES['file']['tmp_name'],'uploads/'.$fn);$media='uploads/'.$fn;}
    }
    q("INSERT INTO content(topic_id,title,type,body,media) VALUES(?,?,?,?,?)",[(int)$_POST['topic'],trim($_POST['title']),$_POST['type'],trim($_POST['body']),$media]);flash('Content published.');
  }elseif($a==='question'){
    q("INSERT INTO questions(topic_id,question,a,b,c,d,correct,explanation) VALUES(?,?,?,?,?,?,?,?)",[(int)$_POST['topic'],trim($_POST['question']),$_POST['a'],$_POST['b'],$_POST['c'],$_POST['d'],$_POST['correct'],trim($_POST['explanation'])]);flash('Question added.');
  }elseif($a==='del_content'){q("DELETE FROM content WHERE id=?",[(int)$_POST['id']]);flash('Content deleted.');}
  elseif($a==='del_question'){q("DELETE FROM questions WHERE id=?",[(int)$_POST['id']]);flash('Question deleted.');}
  header('Location: manage_content.php');exit;
}
$cs=q("SELECT c.id,c.title,c.type,t.name topic FROM content c JOIN topics t ON t.id=c.topic_id ORDER BY c.id DESC")->fetchAll();
$qs=q("SELECT q.id,q.question,t.name topic FROM questions q JOIN topics t ON t.id=q.topic_id ORDER BY q.id DESC")->fetchAll();
$opts='';foreach($topics as $t)$opts.='<option value="'.$t['id'].'">'.h($t['name']).'</option>';
$title='Manage content';include 'includes/header.php'; ?>
<h1>Manage awareness content and quizzes</h1>
<div class="grid">
<form class="panel" method="post" enctype="multipart/form-data"><?=csrf()?><input type="hidden" name="action" value="content"><h3>Add content</h3>
<label>Topic</label><select name="topic"><?=$opts?></select><label>Type</label><select name="type"><option>tip</option><option>video</option><option>poster</option><option>faq</option><option>guide</option></select>
<label>Title</label><input name="title" required><label>Text</label><textarea name="body"></textarea>
<label>Upload poster / video / PDF (optional)</label><input type="file" name="file"><label>Or a link (e.g. YouTube)</label><input name="link" placeholder="https://">
<p><button>Publish</button></p></form>
<form class="panel" method="post"><?=csrf()?><input type="hidden" name="action" value="question"><h3>Add quiz question</h3>
<label>Topic</label><select name="topic"><?=$opts?></select><label>Question</label><input name="question" required>
<label>Option A</label><input name="a" required><label>Option B</label><input name="b" required><label>Option C</label><input name="c"><label>Option D</label><input name="d">
<label>Correct answer</label><select name="correct"><option>a</option><option>b</option><option>c</option><option>d</option></select>
<label>Explanation shown after answering</label><input name="explanation"><p><button>Add question</button></p></form></div>
<h2>Published content</h2><div class="scroll"><table><?php foreach($cs as $c): ?><tr><td><?=h($c['title'])?></td><td><?=h($c['topic'])?></td><td><?=h($c['type'])?></td><td><form method="post" onsubmit="return confirm('Delete this?')"><?=csrf()?><input type="hidden" name="action" value="del_content"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="danger">Delete</button></form></td></tr><?php endforeach; ?></table></div>
<h2>Quiz questions</h2><div class="scroll"><table><?php foreach($qs as $c): ?><tr><td><?=h($c['question'])?></td><td><?=h($c['topic'])?></td><td><form method="post" onsubmit="return confirm('Delete this?')"><?=csrf()?><input type="hidden" name="action" value="del_question"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="danger">Delete</button></form></td></tr><?php endforeach; ?></table></div>
<?php include 'includes/footer.php';
