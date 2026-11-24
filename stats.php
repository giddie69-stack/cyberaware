<?php require 'config.php';role_required('admin');
if(isset($_GET['export'])){
  header('Content-Type: text/csv');header('Content-Disposition: attachment; filename=incidents.csv');$o=fopen('php://output','w');
  fputcsv($o,['Ref','Type','Lab','Device','Severity','Status','People','Reported','Resolved']);
  foreach(q("SELECT i.ref,t.name,l.name lab,i.device_id,i.severity,i.status,i.people_affected,i.created_at,i.resolved_at FROM incidents i JOIN incident_types t ON t.id=i.incident_type_id JOIN labs l ON l.id=i.lab_id ORDER BY i.id") as $r)fputcsv($o,$r);exit;
}
$t=q("SELECT COUNT(*) total,COUNT(*) FILTER (WHERE status<>'Handled') open_n,COUNT(*) FILTER (WHERE severity='Critical' AND status<>'Handled') crit,ROUND((AVG(EXTRACT(EPOCH FROM resolved_at-created_at))/3600)::numeric,1) avg_h FROM incidents")->fetch();
function bars($sql,$title){$rows=q($sql)->fetchAll();$max=max(1,...array_map(fn($r)=>$r['c'],$rows?:[['c'=>1]]));
  echo '<div class="panel"><h3>'.h($title).'</h3><table>';foreach($rows as $r)echo '<tr><td style="width:45%">'.h($r['label']).'</td><td><span class="barwrap"><span class="bar" style="width:'.round($r['c']/$max*100).'%"></span></span></td><td>'.$r['c'].'</td></tr>';
  if(!$rows)echo '<tr><td class="muted">No data yet</td></tr>';echo '</table></div>';}
$title='Dashboard';include 'includes/header.php'; ?>
<h1>Dashboard</h1>
<div class="grid">
<div class="panel"><p class="stat"><?=(int)$t['total']?></p>incidents reported</div><div class="panel"><p class="stat"><?=(int)$t['open_n']?></p>still open</div>
<div class="panel"><p class="stat <?=$t['crit']?'bad':''?>"><?=(int)$t['crit']?></p>critical and open</div><div class="panel"><p class="stat"><?=$t['avg_h']??'-'?> h</p>average time to resolve</div></div>
<p><a class="btn ghost" href="stats.php?export=1">Download incidents (CSV, opens in Excel)</a></p>
<div class="grid">
<?php bars("SELECT t.name label,COUNT(*) c FROM incidents i JOIN incident_types t ON t.id=i.incident_type_id GROUP BY t.id ORDER BY c DESC",'By incident type');
bars("SELECT l.name label,COUNT(*) c FROM incidents i JOIN labs l ON l.id=i.lab_id GROUP BY l.id ORDER BY c DESC",'By laboratory');
bars("SELECT severity label,COUNT(*) c FROM incidents GROUP BY severity ORDER BY array_position(ARRAY['Critical','High','Medium','Low'],severity::text)",'By severity');
bars("SELECT status label,COUNT(*) c FROM incidents GROUP BY status ORDER BY array_position(ARRAY['Reported','Received by Admin','Assigned to Technician','In Progress','Handled'],status::text)",'By status');
bars("SELECT device_id label,COUNT(*) c FROM incidents WHERE device_id<>'' GROUP BY device_id HAVING COUNT(*)>1 ORDER BY c DESC LIMIT 8",'Devices with repeat incidents');
bars("SELECT to_char(created_at,'YYYY-MM') label,COUNT(*) c FROM incidents GROUP BY 1 ORDER BY label DESC LIMIT 6",'Incidents per month'); ?>
</div>
<h2>Awareness: quiz results by topic</h2>
<div class="scroll"><table><tr><th>Topic</th><th>Attempts</th><th>Average score</th></tr>
<?php foreach(q("SELECT t.name,COUNT(a.id) n,ROUND(AVG(a.score::numeric/a.total)*100) pct FROM topics t LEFT JOIN quiz_attempts a ON a.topic_id=t.id GROUP BY t.id ORDER BY pct NULLS LAST")->fetchAll() as $r): ?>
<tr><td><?=h($r['name'])?></td><td><?=$r['n']?></td><td><?=$r['pct']!==null?$r['pct'].'%':'-'?></td></tr><?php endforeach; ?></table></div>
<p class="muted">Lowest averages at the top show where users need more awareness training.</p>
<?php include 'includes/footer.php';
