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

 case 'admin:create-super':
   if (DB::scalar("SELECT COUNT(*) FROM users WHERE role='super_admin'") > 0) {
     throw new RuntimeException('A super administrator already exists. Additional privileged accounts must be created through the reviewed admin process.');
   }

   $ask = static function (string $label): string {
     fwrite(STDOUT, $label);
     $value = fgets(STDIN);
     return trim($value === false ? '' : $value);
   };

   $firstName = $ask('First name: ');
   $lastName  = $ask('Last name: ');
   $email     = mb_strtolower($ask('Email: '));

   if ($firstName === '' || $lastName === '') {
     throw new RuntimeException('First and last name are required.');
   }

   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
     throw new RuntimeException('Enter a valid email address.');
   }

   if (App\Models\User::emailExists($email)) {
     throw new RuntimeException('An account with that email already exists.');
   }

   $secret = static function (string $label): string {
     fwrite(STDOUT, $label);
     system('stty -echo');
     try {
       return trim((string) fgets(STDIN));
     } finally {
       system('stty echo');
       fwrite(STDOUT, PHP_EOL);
     }
   };

   $password = $secret('Password: ');
   $confirmation = $secret('Confirm password: ');

   if ($password !== $confirmation) {
     throw new RuntimeException('Passwords do not match.');
   }

   if (
     strlen($password) < 12 ||
     !preg_match('/[A-Z]/', $password) ||
     !preg_match('/[a-z]/', $password) ||
     !preg_match('/[0-9]/', $password)
   ) {
     throw new RuntimeException('Password must contain at least 12 characters with uppercase, lowercase and a number.');
   }

   $id = App\Models\User::create([
     'first_name'        => $firstName,
     'last_name'         => $lastName,
     'email'             => $email,
     'password_hash'     => App\Services\AuthService::hash($password),
     'role'              => 'super_admin',
     'status'            => 'active',
     'email_verified_at' => gmdate('Y-m-d H:i:s'),
   ]);

   unset($password, $confirmation);

   echo 'Super administrator created successfully. User ID: '.$id.PHP_EOL;
   break;
 default: echo "StayIn commands: migrate [--dry-run], migrate:status, seed, admin:create-super, bookings:expire-holds, notifications:send, jobs:run, finance:reconcile\n";
 }
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1); }
finally { flock($lock,LOCK_UN); fclose($lock); }
