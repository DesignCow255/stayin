<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap/app.php';
use App\Core\{Database as DB, Config, Migrator};
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$command = $argv[1] ?? 'help';
$lock = fopen(dirname(__DIR__).'/storage/private/cli.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDERR, "Another job is running.\n"); exit(1); }
try {
 switch ($command) {
 case 'migrate':
  if (!in_array(Config::string('app.env'), ['local','development','testing'], true)) throw new RuntimeException('Production migrations require a reviewed deployment procedure.');
  $result = (new Migrator)->migrate(in_array('--dry-run',$argv,true));
  echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
  if ($result['failed'] || $result['modified']) exit(1);
  break;
 case 'migrate:status': echo json_encode((new Migrator)->status(),JSON_PRETTY_PRINT).PHP_EOL; break;
 case 'bookings:expire-holds': echo App\Services\BookingService::expire().' expired holds'.PHP_EOL; break;
 case 'notifications:send': case 'jobs:run': echo App\Services\MailService::work().' messages processed'.PHP_EOL; break;
 case 'finance:reconcile': echo json_encode(App\Services\FinanceService::reconcile(),JSON_PRETTY_PRINT).PHP_EOL; break;
 case 'seed': (new App\Services\DevelopmentSeeder)->run(); break;
 default: echo "StayIn commands: migrate [--dry-run], migrate:status, seed, bookings:expire-holds, notifications:send, jobs:run, finance:reconcile\n";
 }
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1); }
finally { flock($lock,LOCK_UN); fclose($lock); }
