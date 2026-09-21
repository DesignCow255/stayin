<?php
require dirname(__DIR__) . '/bootstrap/app.php';
use App\Core\Database as DB;
$pdo = DB::connection();
$tables = array_map(fn($r) => array_values($r)[0], DB::select('SHOW TABLES'));
$stamp = gmdate('Ymd_His');
$path = dirname(__DIR__).'/database/backups/stayin_db_pre_rebuild_'.$stamp.'.sql';
$f = fopen($path, 'x'); chmod($path, 0600);
fwrite($f, "-- Local development snapshot. Private. Manual restore only.\nSET FOREIGN_KEY_CHECKS=0;\n");
$counts = []; $schema = "-- Structure-only reference. Do not execute against an existing database.\n";
$pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'); $pdo->beginTransaction();
foreach ($tables as $table) {
 $name = '`'.str_replace('`','``',$table).'`';
 $ddl = DB::first('SHOW CREATE TABLE '.$name)['Create Table'];
 $schema .= $ddl.";\n\n"; fwrite($f, $ddl.";\n");
 $counts[$table] = 0;
 foreach (DB::query('SELECT * FROM '.$name) as $row) {
  $values = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($row));
  fwrite($f, 'INSERT INTO '.$name.' VALUES ('.implode(',', $values).");\n"); $counts[$table]++;
 }
}
$pdo->commit(); fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n"); fclose($f);
file_put_contents(dirname(__DIR__).'/database/schema/current_baseline.sql', $schema);
file_put_contents(dirname(__DIR__).'/database/backups/baseline_counts.json', json_encode($counts, JSON_PRETTY_PRINT));
$doc = "# Database baseline\n\nCaptured ".gmdate('c')." from configured local MySQL ".DB::scalar('SELECT VERSION()').". Existing IDs, money, dates and relationships are preserved. All existing tables are retained. Structure reference: `database/schema/current_baseline.sql`. Private consistent data snapshot: `".basename($path)."` (gitignored, mode 0600). No application writers should run during schema evolution.\n\n| Table | Rows |\n|---|---:|\n";
foreach($counts as $table=>$count) $doc .= "| $table | $count |\n";
file_put_contents(dirname(__DIR__).'/docs/DATABASE_BASELINE.md',$doc);
echo 'Backed up '.count($tables).' tables; SHA256 '.hash_file('sha256',$path).PHP_EOL;
