<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Database as DB,Session,BusinessException};
use App\Services\{AuthService,HostService,UploadService,FinanceService,BookingService};
final class HostController extends Controller {
 public function index(Request $r):Response {
  $id=AuthService::id();return $this->view('host/index',['metaTitle'=>'Host workspace · StayIn','properties'=>DB::select('SELECT * FROM properties WHERE host_id=? ORDER BY id DESC',[$id]),'bookings'=>DB::select('SELECT b.*,p.name FROM bookings b JOIN properties p ON p.id=b.property_id WHERE p.host_id=? ORDER BY b.id DESC LIMIT 100',[$id]),'kyc'=>DB::first('SELECT * FROM host_kyc WHERE user_id=?',[$id]),'balances'=>FinanceService::balances($id),'settlements'=>DB::select('SELECT * FROM host_settlements WHERE host_id=? ORDER BY id DESC LIMIT 100',[$id]),'revenue'=>DB::select("SELECT b.currency,COUNT(*) AS bookings,SUM(b.total_amount) AS total FROM bookings b JOIN properties p ON p.id=b.property_id WHERE p.host_id=? AND b.payment_status='paid' GROUP BY b.currency",[$id])]);
 }
 public function create(Request $r):Response {return $this->view('host/property',['metaTitle'=>'Create a property','property'=>null,'rooms'=>[],'images'=>[]]);}
 public function edit(Request $r):Response {$p=HostService::property((int)$r->routeParam('id'),AuthService::id());return $this->view('host/property',['metaTitle'=>'Manage '.$p['name'],'property'=>$p,'rooms'=>DB::select('SELECT * FROM room_types WHERE property_id=?',[$p['id']]),'images'=>DB::select('SELECT * FROM property_images WHERE property_id=? ORDER BY sort_order',[$p['id']])]);}
 public function store(Request $r):Response {$id=HostService::save($r,AuthService::id());return $this->redirect('/host/properties/'.$id.'/edit',303);}
 public function update(Request $r):Response {$id=HostService::save($r,AuthService::id(),(int)$r->routeParam('id'));return $this->redirect('/host/properties/'.$id.'/edit',303);}
 public function action(Request $r):Response {
  $id=(int)$r->routeParam('id');HostService::property($id,AuthService::id());
  switch($r->string('action')){
   case 'room':HostService::room($r,$id,AuthService::id());break;
   case 'inventory':HostService::inventory($r,$id,AuthService::id());break;
   case 'submit':HostService::submit($id,AuthService::id());break;
   case 'image':$path=UploadService::image($r->file('image'));DB::insert('property_images',['property_id'=>$id,'file_url'=>$path,'sort_order'=>0,'is_primary'=>0]);break;
   default:throw new BusinessException('Unknown property action.');
  }
  Session::flash('status',['message'=>'Property updated.']);return $this->redirect('/host/properties/'.$id.'/edit',303);
 }
 public function kyc(Request $r):Response {
  $u=AuthService::user();if(!$u['email_verified_at'])throw new BusinessException('Verify your email before submitting identity documents.');
  $path=UploadService::image($r->file('document'),true);
  DB::execute("INSERT INTO host_kyc(user_id,document_path,status) VALUES(?,?,'pending') ON DUPLICATE KEY UPDATE document_path=VALUES(document_path),status='pending',submitted_at=UTC_TIMESTAMP(),reviewer_id=NULL,reviewed_at=NULL",[$u['id'],$path]);
  Session::flash('status',['message'=>'Identity document submitted privately for review.']);return $this->redirect('/host',303);
 }
 public function complete(Request $r):Response {
  DB::transaction(function()use($r){$b=DB::lockFirst("SELECT b.* FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.id=? AND p.host_id=?",[$r->int('booking_id'),AuthService::id()]);if(!$b||$b['status']!=='confirmed'||$b['check_out']>gmdate('Y-m-d'))throw new BusinessException('Only a confirmed stay after checkout can be completed.');DB::update('bookings',['status'=>'completed'],['id'=>$b['id']]);BookingService::history((int)$b['id'],'confirmed','completed',AuthService::id());});return $this->redirect('/host/bookings',303);
 }
}
