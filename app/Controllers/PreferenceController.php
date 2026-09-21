<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Session,Database as DB,BusinessException};
use App\Services\MailService;
final class PreferenceController extends Controller {
 public function save(Request $r):Response {$v=$r->validate(['locale'=>'in:en,sw','currency'=>'in:TZS,USD','theme'=>'in:dark,light,system'])->validated();foreach($v as $k=>$value)Session::put($k,$value);return $this->redirect(safe_return_path($r->header('Referer')),303);}
 public function newsletter(Request $r):Response {
  $r->validate(['email'=>'required|email|max:190']);if(!$r->bool('consent'))throw new BusinessException('Consent is required for travel emails.');
  $email=strtolower($r->string('email'));$token=bin2hex(random_bytes(32));
  DB::execute("INSERT INTO newsletter_consents(email,token_hash,status) VALUES(?,?,'pending') ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),status='pending',consent_at=UTC_TIMESTAMP()",[$email,hash('sha256',$token)]);
  MailService::queue($email,'Confirm StayIn travel emails','Confirm your subscription: '.url('/newsletter/confirm/'.$token)."\nUnsubscribe at any time: ".url('/newsletter/unsubscribe/'.$token));Session::flash('status',['message'=>'Please check your email to confirm your subscription.']);return $this->redirect('/',303);
 }
 public function newsletterToken(Request $r):Response {
  $token=(string)$r->routeParam('token');$action=(string)$r->routeParam('action');if(!in_array($action,['confirm','unsubscribe'],true))throw new BusinessException('Invalid newsletter action.');
  $row=DB::first('SELECT * FROM newsletter_consents WHERE token_hash=?',[hash('sha256',$token)]);if(!$row)throw new BusinessException('Invalid newsletter link.');
  if($r->method()==='POST'){
   DB::transaction(function()use($row,$action){$active=$action==='confirm';DB::update('newsletter_consents',['status'=>$active?'active':'unsubscribed',$active?'confirmed_at':'unsubscribed_at'=>gmdate('Y-m-d H:i:s')],['email'=>$row['email']]);DB::execute('INSERT INTO newsletter_subscribers(email,status) VALUES(?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)',[$row['email'],$active?'active':'unsubscribed']);});Session::flash('status',['message'=>'Newsletter preferences updated.']);return $this->redirect('/',303);
  }
  return $this->view('newsletter',['metaTitle'=>'Newsletter preferences','action'=>$action,'token'=>$token]);
 }
}
