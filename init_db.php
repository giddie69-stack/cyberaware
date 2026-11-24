<?php
// Runs when the Docker container starts (not reachable from the web).
// Creates the tables and starter content from schema.sql the first time, when the users table does not exist yet.
require __DIR__.'/config.php';
for($i=1;;$i++){
  try{db();break;}
  catch(PDOException $e){if($i>=10){fwrite(STDERR,"init_db: cannot connect to the database: ".$e->getMessage()."\n");exit(1);}sleep(3);}
}
if(q("SELECT to_regclass('public.users') t")->fetch()['t']===null){
  db()->exec(file_get_contents(__DIR__.'/schema.sql'));
  echo "init_db: database created from schema.sql\n";
}else echo "init_db: database already set up\n";
