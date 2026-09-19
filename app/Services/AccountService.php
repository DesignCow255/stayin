<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB,BusinessException};
use App\Models\User;
final class AccountService {
 public static function resetLink(string $email):void {
  $user=User::findByEmail($email);if(!$user||$user['status']!=='active')return;
  $token=bin2hex(random_bytes(32));
  DB::transaction(function()use($user,$token){
   DB::execute('DELETE FROM password_resets WHERE email=?',[$user['email']]);
   DB::insert('password_resets',['email'=>$user['email'],'token'=>hash('sha256',$token),'expires_at'=>gmdate('Y-m-d H:i:s',time()+3600)]);
   MailService::queue($user['email'],'Reset your StayIn password','Use this single-use link within one hour: '.url('/reset-password/'.$token));
  });
 }
 public static function reset(string $token,string $password):void {
  if(strlen($password)<10||strlen($password)>72)throw new BusinessException('Use a password of 10–72 characters.');
  DB::transaction(function()use($token,$password){
   $r=DB::lockFirst('SELECT * FROM password_resets WHERE token=? AND expires_at>UTC_TIMESTAMP()',[hash('sha256',$token)]);
   if(!$r)throw new BusinessException('This password reset link is invalid or expired.');
   DB::update('users',['password_hash'=>AuthService::hash($password),'failed_logins'=>0,'locked_until'=>null],['email'=>$r['email']]);
   DB::execute('DELETE FROM password_resets WHERE email=?',[$r['email']]);
   MailService::queue($r['email'],'Your StayIn password changed','Your password was changed. Contact support if this was not you.');
  });
 }
 public static function verification(int $id):void {
  $u=User::find($id);if(!$u||$u['email_verified_at'])return;
  $token=bin2hex(random_bytes(32));
  DB::insert('email_verifications',['user_id'=>$id,'token_hash'=>hash('sha256',$token),'expires_at'=>gmdate('Y-m-d H:i:s',time()+172800)]);
  MailService::queue($u['email'],'Verify your StayIn email','Verify your email within 48 hours: '.url('/verify-email/'.$token));
 }
 public static function verify(string $token):void {
  DB::transaction(function()use($token){
   $v=DB::lockFirst('SELECT * FROM email_verifications WHERE token_hash=? AND used_at IS NULL AND expires_at>UTC_TIMESTAMP()',[hash('sha256',$token)]);
   if(!$v)throw new BusinessException('This verification link is invalid or expired.');
   DB::update('users',['email_verified_at'=>gmdate('Y-m-d H:i:s')],['id'=>$v['user_id']]);
   DB::update('email_verifications',['used_at'=>gmdate('Y-m-d H:i:s')],['id'=>$v['id']]);
  });
 }
}
