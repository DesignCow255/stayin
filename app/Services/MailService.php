<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Config,Database as DB};
final class MailService {
 public static function queue(string $to,string $subject,string $body):void {if(filter_var($to,FILTER_VALIDATE_EMAIL))DB::insert('email_queue',['recipient'=>$to,'subject'=>$subject,'body'=>$body]);}
 public static function work():int {
  $count=0;
  foreach(DB::select("SELECT * FROM email_queue WHERE status='pending' AND attempts<3 ORDER BY id LIMIT 50") as $m){
   if(in_array(Config::string('app.env'),['local','development','testing'],true)){
    $path=Config::string('app.base_path').'/storage/private/mail'; if(!is_dir($path))mkdir($path,0700,true);
    file_put_contents($path.'/'.$m['id'].'.eml','To: '.$m['recipient']."\nSubject: ".$m['subject']."\nContent-Type: text/plain; charset=UTF-8\n\n".$m['body']);chmod($path.'/'.$m['id'].'.eml',0600);
    DB::update('email_queue',['status'=>'captured','sent_at'=>gmdate('Y-m-d H:i:s')],['id'=>$m['id']]);$count++;
   } else {
    // Never claim delivery without a configured, tested transport.
    throw new \RuntimeException('SMTP transport must be configured and verified before processing production email.');
   }
  }
  return $count;
 }
}
