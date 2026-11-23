<?php require 'config.php';login_required();
$topics=q("SELECT * FROM topics ORDER BY id")->fetchAll();$tid=(int)($_GET['topic']??0);$s=trim($_GET['s']??'');
$sql="SELECT c.*,t.name topic FROM content c JOIN topics t ON t.id=c.topic_id WHERE 1";$a=[];
if($tid){$sql.=" AND c.topic_id=?";$a[]=$tid;}
if($s!==''){$sql.=" AND (c.title LIKE ? OR c.body LIKE ?)";$a[]="%$s%";$a[]="%$s%";}
$rows=q($sql." ORDER BY c.id DESC",$a)->fetchAll();
$title='Awareness';include 'includes/header.php'; ?>
<h1>Cybersecurity awareness</h1>
<form class="panel row" method="get"><div><label>Topic</label><select name="topic"><option value="0">All topics</option><?php foreach($topics as $t): ?><option value="<?=$t['id']?>" <?=$t['id']==$tid?'selected':''?>><?=h($t['name'])?></option><?php endforeach; ?></select></div>
<div><label>Search</label><input name="s" value="<?=h($s)?>"></div><div><label>&nbsp;</label><button>Find</button></div></form>
<?php foreach($rows as $c): $m=$c['media'];$ext=strtolower(pathinfo($m,PATHINFO_EXTENSION)); ?>
<article class="panel"><h3><?=h($c['title'])?></h3><p class="muted"><?=h($c['topic'])?>, <?=h($c['type'])?></p><p><?=nl2br(h($c['body']))?></p>
<?php if($m&&in_array($ext,['jpg','jpeg','png','gif'])): ?><img class="poster" src="<?=h($m)?>" alt="<?=h($c['title'])?>">
<?php elseif($m&&in_array($ext,['mp4','webm'])): ?><video controls src="<?=h($m)?>"></video>
<?php elseif($m): ?><p><a href="<?=h($m)?>" target="_blank" rel="noopener">Open <?=h($c['type'])?></a></p><?php endif; ?></article>
<?php endforeach; if(!$rows): ?><p class="panel">Nothing found. Try another topic or search word.</p><?php endif;
include 'includes/footer.php';
