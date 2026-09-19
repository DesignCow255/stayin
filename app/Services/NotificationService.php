<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database as DB;
final class NotificationService {
 public static function send(int $user,string $type,string $title,string $message):void {
  DB::insert('notifications',['user_id'=>$user,'type'=>$type,'title_en'=>$title,'title_sw'=>$title,'message_en'=>$message,'message_sw'=>$message]);
  $email=DB::scalar('SELECT email FROM users WHERE id=?',[$user]);if($email)MailService::queue($email,$title,$message);
 }
}
