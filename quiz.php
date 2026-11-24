<?php require 'config.php';login_required();$u=user();$tid=(int)($_GET['topic']??$_POST['topic']??0);$result=null;
if($tid&&$_SERVER['REQUEST_METHOD']==='POST'){
  $ids=array_map('intval',array_keys($_POST['ans']??[]));$qs=[];$score=0;
  if($ids)$qs=q("SELECT * FROM questions WHERE topic_id=? AND id IN (".implode(',',$ids).")",[$tid])->fetchAll();
  foreach($qs as &$x){$x['given']=$_POST['ans'][$x['id']]??'';if($x['given']===$x['correct'])$score++;}unset($x);
  if($qs)q("INSERT INTO quiz_attempts(user_id,topic_id,score,total) VALUES(?,?,?,?)",[$u['id'],$tid,$score,count($qs)]);
  $result=['qs'=>$qs,'score'=>$score];
}
$title='Quizzes';include 'includes/header.php';
if(!$tid): $rows=q("SELECT t.id,t.name,(SELECT COUNT(*) FROM questions WHERE topic_id=t.id) n,(SELECT MAX(score::numeric/total) FROM quiz_attempts WHERE topic_id=t.id AND user_id=?) best FROM topics t ORDER BY t.id",[$u['id']])->fetchAll(); ?>
<h1>Quizzes</h1><div class="grid"><?php foreach($rows as $r): if(!$r['n'])continue; ?>
<div class="panel"><h3><?=h($r['name'])?></h3><p class="muted"><?=$r['n']?> questions<?=$r['best']!==null?', best score '.round($r['best']*100).'%':''?></p><a class="btn" href="quiz.php?topic=<?=$r['id']?>">Start</a></div><?php endforeach; ?></div>
<?php elseif($result): ?>
<h1>You scored <?=$result['score']?> out of <?=count($result['qs'])?></h1>
<?php foreach($result['qs'] as $x): $ok=$x['given']===$x['correct']; ?><div class="panel"><p><b><?=h($x['question'])?></b></p>
<p class="<?=$ok?'ok':'bad'?>"><?=$ok?'Correct.':'Not quite. Right answer: '.h($x[$x['correct']])?></p><p class="muted"><?=h($x['explanation'])?></p></div><?php endforeach; ?>
<a class="btn" href="quiz.php?topic=<?=$tid?>">Try again</a> <a class="btn ghost" href="quiz.php">All quizzes</a>
<?php else: $qs=q("SELECT * FROM questions WHERE topic_id=? ORDER BY RANDOM() LIMIT 10",[$tid])->fetchAll(); ?>
<h1>Quiz</h1><form method="post"><?=csrf()?><input type="hidden" name="topic" value="<?=$tid?>">
<?php foreach($qs as $x): ?><fieldset class="panel"><legend><b><?=h($x['question'])?></b></legend>
<?php foreach(['a','b','c','d'] as $o): if($x[$o]==='')continue; ?><label class="opt"><input type="radio" name="ans[<?=$x['id']?>]" value="<?=$o?>" required><?=h($x[$o])?></label><?php endforeach; ?></fieldset><?php endforeach; ?>
<button>Check my answers</button></form>
<?php endif; include 'includes/footer.php';
