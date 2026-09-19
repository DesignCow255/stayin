<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Database as DB,Session,HttpException,BusinessException};
use App\Services\{AuthService,Gate,FinanceService,Money};
final class AdminController extends Controller {
 private function allow(string $permission):void {if(!Gate::allows($permission))throw HttpException::forbidden();}
 public function index(Request $r):Response {
  $this->allow('admin.access');
  return $this->view('admin/index',['metaTitle'=>'Control centre · StayIn','counts'=>['Properties'=>DB::scalar('SELECT COUNT(*) FROM properties'),'Bookings'=>DB::scalar('SELECT COUNT(*) FROM bookings'),'Pending properties'=>DB::scalar("SELECT COUNT(*) FROM properties WHERE status='pending'"),'Pending identity reviews'=>DB::scalar("SELECT COUNT(*) FROM host_kyc WHERE status='pending'")],'properties'=>Gate::allows('properties.view')?DB::select('SELECT id,name,status,verification_status,host_id FROM properties ORDER BY id DESC LIMIT 100'):[],'kyc'=>Gate::allows('kyc.review')?DB::select('SELECT k.*,u.first_name,u.last_name FROM host_kyc k JOIN users u ON u.id=k.user_id ORDER BY submitted_at DESC'):[],'payments'=>Gate::allows('payments.view')?DB::select('SELECT id,booking_id,provider,amount,currency,status,created_at FROM payments ORDER BY id DESC LIMIT 100'):[],'balances'=>Gate::allows('finance.view')?FinanceService::balances():[],'settlements'=>Gate::allows('finance.view')?DB::select('SELECT * FROM host_settlements ORDER BY id DESC LIMIT 100'):[],'mismatches'=>Gate::allows('finance.view')?FinanceService::reconcile():[],'users'=>Gate::allows('users.view')?DB::select('SELECT id,first_name,last_name,role,status FROM users ORDER BY id DESC LIMIT 100'):[],'audit'=>Gate::allows('audit.view')?DB::select('SELECT action,entity_type,entity_id,created_at FROM audit_logs ORDER BY id DESC LIMIT 30'):[]]);
 }
 public function action(Request $r):Response {
  $actor=AuthService::id();$id=$r->int('id');$action=$r->string('action');
  switch($action){
   case 'kyc':
    $this->allow('kyc.review');$r->validate(['status'=>'required|in:verified,rejected,suspended','note'=>'required|max:500']);
    if($id===$actor)throw new BusinessException('Another reviewer must review your identity.');
    DB::transaction(function()use($r,$id,$actor){DB::update('host_kyc',['status'=>$r->string('status'),'reviewer_id'=>$actor,'review_note'=>$r->string('note'),'reviewed_at'=>gmdate('Y-m-d H:i:s')],['user_id'=>$id]);FinanceService::audit($actor,'kyc.'.$r->string('status'),'users',$id);});break;
   case 'property':
    $this->allow('properties.moderate');$r->validate(['status'=>'required|in:published,suspended']);
    DB::transaction(function()use($r,$id,$actor){
     $p=DB::lockFirst('SELECT * FROM properties WHERE id=?',[$id]);if(!$p)throw HttpException::notFound();
     if($r->string('status')==='published'){
      if($p['status']!=='pending'||!DB::scalar("SELECT user_id FROM host_kyc WHERE user_id=? AND status='verified'",[$p['host_id']]))throw new BusinessException('A submitted property and verified host are required.');
      if(!DB::scalar('SELECT id FROM property_images WHERE property_id=?',[$id])||!DB::scalar("SELECT id FROM room_types WHERE property_id=? AND status='active'",[$id]))throw new BusinessException('Property needs images and rooms.');
     }
     DB::update('properties',['status'=>$r->string('status'),'verification_status'=>$r->string('status')==='published'?'verified':'pending'],['id'=>$id]);FinanceService::audit($actor,'property.'.$r->string('status'),'properties',$id);
    });break;
   case 'user':
    $this->allow('users.manage');$r->validate(['status'=>'required|in:active,suspended']);
    $u=DB::first('SELECT role FROM users WHERE id=?',[$id]);if(!$u||$id===$actor||in_array($u['role'],['admin','super_admin'],true))throw new BusinessException('Privileged accounts require a separate reviewed process.');
    DB::transaction(function()use($r,$id,$actor){DB::update('users',['status'=>$r->string('status')],['id'=>$id]);FinanceService::audit($actor,'user.'.$r->string('status'),'users',$id);});break;
   case 'commission':
    $this->allow('finance.manage');$r->validate(['percent'=>'required|numeric|min_value:0|max_value:100','scope'=>'required|in:default,host,property']);Money::minor($r->string('percent'));
    $key='commission.'.$r->string('scope').($r->string('scope')==='default'?'':'.'.$id);
    DB::transaction(function()use($r,$key,$actor,$id){DB::execute("INSERT INTO settings(`key`,value,type) VALUES(?,?,'decimal') ON DUPLICATE KEY UPDATE value=VALUES(value)",[$key,$r->string('percent')]);FinanceService::audit($actor,'commission.updated','settings',$id,['key'=>$key,'percent'=>$r->string('percent')]);});break;
   case 'settlement_draft':$this->allow('finance.manage');$r->validate(['currency'=>'required|in:TZS,USD']);FinanceService::draft($id,$r->string('currency'),$actor);break;
   case 'settlement':$this->allow('settlements.approve');FinanceService::transition($id,$r->string('status'),$r->string('reference'),$actor);break;
   default:throw new BusinessException('Unknown administrative action.');
  }
  Session::flash('status',['message'=>'Action recorded in the audit log.']);return $this->redirect('/admin',303);
 }
 public function document(Request $r):Response {
  $this->allow('kyc.review');$id=(int)$r->routeParam('id');$relative=DB::scalar('SELECT document_path FROM host_kyc WHERE user_id=?',[$id]);
  $base=realpath(config('app.base_path').'/storage/private/kyc');$path=$relative?realpath(config('app.base_path').'/'.$relative):false;
  if(!$base||!$path||!str_starts_with($path,$base.'/'))throw HttpException::notFound();
  FinanceService::audit(AuthService::id(),'kyc.document_view','users',$id);
  return new Response(file_get_contents($path),200,['Content-Type'=>'image/webp','Cache-Control'=>'no-store','Content-Disposition'=>'inline']);
 }
 public function content(Request $r):Response {$this->allow('cms.manage');return $this->view('admin/content',['metaTitle'=>'Content studio','slides'=>DB::select('SELECT * FROM hero_slides ORDER BY display_order'),'pages'=>DB::select('SELECT * FROM pages ORDER BY id')]);}
 public function saveContent(Request $r):Response {
  $this->allow('cms.manage');$kind=$r->string('kind');$id=$r->int('id');
  if($kind==='page'){$v=$r->validate(['title_en'=>'required|max:255','title_sw'=>'required|max:255','body_en'=>'required|max:30000','body_sw'=>'max:30000','status'=>'required|in:published,draft'])->validated();$table='pages';}
  elseif($kind==='hero'){$v=$r->validate(['title_en'=>'required|max:255','title_sw'=>'required|max:255','description_en'=>'max:500','description_sw'=>'max:500','image_url'=>'required|url|max:500','cta_link'=>'required|max:500','cta_text_en'=>'required|max:100','cta_text_sw'=>'required|max:100','display_order'=>'required|integer|min_value:0|max_value:100','is_active'=>'required|in:0,1'])->validated();if(!str_starts_with($v['image_url'],'https://')||!str_starts_with($v['cta_link'],'/')||str_starts_with($v['cta_link'],'//'))throw new BusinessException('Use an HTTPS image and an internal CTA path.');$table='hero_slides';}
  else throw new BusinessException('Unknown content type.');
  DB::transaction(function()use($table,$v,$id){DB::update($table,$v,['id'=>$id]);FinanceService::audit(AuthService::id(),'cms.update',$table,$id);});return $this->redirect('/admin/content',303);
 }
}
