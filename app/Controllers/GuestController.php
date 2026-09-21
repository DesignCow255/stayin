<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Database as DB,Session,BusinessException};
use App\Services\AuthService;
use App\Models\User;
final class GuestController extends Controller {
 public function index(Request $r):Response {
  $id=AuthService::id();
  $bookings=DB::select("SELECT b.*,p.name AS property_name,p.slug,p.region,p.district,p.address,rt.name AS room_name,(SELECT file_url FROM property_images WHERE property_id=p.id ORDER BY is_primary DESC,sort_order ASC LIMIT 1) AS cover_image FROM bookings b JOIN properties p ON p.id=b.property_id JOIN room_types rt ON rt.id=b.room_type_id WHERE b.guest_id=? ORDER BY b.created_at DESC LIMIT 100",[$id]);
  $nextTrip=DB::first("SELECT b.*,p.name AS property_name,p.slug,p.region,p.district,p.address,p.latitude,p.longitude,rt.name AS room_name,(SELECT file_url FROM property_images WHERE property_id=p.id ORDER BY is_primary DESC,sort_order ASC LIMIT 1) AS cover_image FROM bookings b JOIN properties p ON p.id=b.property_id JOIN room_types rt ON rt.id=b.room_type_id WHERE b.guest_id=? AND b.check_out>=CURRENT_DATE AND b.status IN ('confirmed','awaiting_payment','pending') ORDER BY b.check_in ASC LIMIT 1",[$id]);
  $travelStats=DB::first("SELECT COUNT(*) AS trips,COALESCE(SUM(GREATEST(DATEDIFF(check_out,check_in),0)),0) AS nights,COALESCE(SUM(CASE WHEN payment_status IN ('paid','refunded') THEN total_amount ELSE 0 END),0) AS spend,COALESCE(MAX(currency),'TZS') AS currency FROM bookings WHERE guest_id=?",[$id])?:[];
  $saved=DB::select("SELECT p.id,p.slug,p.name,p.region,p.rating,p.review_count,(SELECT MIN(base_price) FROM room_types WHERE property_id=p.id AND status='active') AS min_price,(SELECT currency FROM room_types WHERE property_id=p.id AND status='active' LIMIT 1) AS currency FROM favourites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=? AND p.status='published' ORDER BY f.created_at DESC",[$id]);
  $payments=DB::select("SELECT pay.*,b.booking_reference,p.name AS property_name FROM payments pay LEFT JOIN bookings b ON b.id=pay.booking_id LEFT JOIN properties p ON p.id=b.property_id WHERE pay.user_id=? ORDER BY pay.id DESC LIMIT 30",[$id]);
  return $this->view('guest',['metaTitle'=>'Your StayIn','bookings'=>$bookings,'nextTrip'=>$nextTrip,'travelStats'=>$travelStats,'payments'=>$payments,'saved'=>$saved,'notifications'=>DB::select('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 30',[$id]),'searches'=>DB::select('SELECT * FROM saved_searches WHERE user_id=? ORDER BY id DESC LIMIT 20',[$id]),'user'=>AuthService::user()]);
 }
 public function profile(Request $r):Response {
  $v=$r->validate(['first_name'=>'required|min:2|max:100','last_name'=>'required|min:2|max:100','phone'=>'phone','preferred_language'=>'required|in:en,sw','preferred_currency'=>'required|in:TZS,USD'])->validated();
  $v['phone']=$v['phone']??null; if($v['phone']&&DB::scalar('SELECT id FROM users WHERE phone=? AND id<>?',[$v['phone'],AuthService::id()]))throw new BusinessException('This phone is already in use.');
  User::update(AuthService::id(),$v); Session::flash('status',['message'=>'Profile updated.']);return $this->redirect('/guest/profile',303);
 }
 public function favourite(Request $r):Response {
  $id=$r->int('property_id');if(!DB::scalar("SELECT id FROM properties WHERE id=? AND status='published'",[$id]))throw new BusinessException('Property not available.');
  if($r->string('action')==='remove')DB::execute('DELETE FROM favourites WHERE user_id=? AND property_id=?',[AuthService::id(),$id]);
  else DB::execute('INSERT IGNORE INTO favourites(user_id,property_id) VALUES(?,?)',[AuthService::id(),$id]);
  Session::flash('status',['message'=>'Saved stays updated.']);return $this->redirect('/guest/favourites',303);
 }
 public function read(Request $r):Response {DB::update('notifications',['is_read'=>1,'read_at'=>gmdate('Y-m-d H:i:s')],['id'=>$r->int('id'),'user_id'=>AuthService::id()]);return $this->redirect('/guest',303);}
 public function review(Request $r):Response {
  $r->validate(['booking_id'=>'required|integer','rating'=>'required|integer|min_value:1|max_value:5','comment'=>'required|max:3000']);
  DB::transaction(function()use($r){
   $b=DB::lockFirst("SELECT * FROM bookings WHERE id=? AND guest_id=? AND status='completed' AND check_out<=CURRENT_DATE",[$r->int('booking_id'),AuthService::id()]);
   if(!$b||DB::scalar('SELECT id FROM reviews WHERE booking_id=?',[$b['id']]))throw new BusinessException('Only a completed, unreviewed stay can be reviewed.');
   DB::insert('reviews',['booking_id'=>$b['id'],'guest_id'=>AuthService::id(),'property_id'=>$b['property_id'],'rating'=>$r->int('rating'),'comment'=>$r->string('comment')]);
   DB::execute("UPDATE properties SET rating=(SELECT AVG(rating) FROM reviews WHERE property_id=? AND status='published'),review_count=(SELECT COUNT(*) FROM reviews WHERE property_id=? AND status='published') WHERE id=?",[$b['property_id'],$b['property_id'],$b['property_id']]);
  });return $this->redirect('/guest',303);
 }
 public function saveSearch(Request $r):Response {DB::insert('saved_searches',['user_id'=>AuthService::id(),'query'=>$r->only(['q','region','check_in','check_out','guests','rooms','currency','property_type'])]);return $this->redirect('/guest',303);}
 public function export(Request $r):Response {
  $u=AuthService::user();$profile=array_intersect_key($u,array_flip(['id','first_name','last_name','email','phone','preferred_language','preferred_currency','created_at']));
  return Response::json(['profile'=>$profile,'bookings'=>DB::select('SELECT booking_reference,check_in,check_out,total_amount,currency,status FROM bookings WHERE guest_id=?',[$u['id']]),'reviews'=>DB::select('SELECT rating,comment,created_at FROM reviews WHERE guest_id=?',[$u['id']])])->withHeader('Content-Disposition','attachment; filename="stayin-data.json"');
 }
 public function privacy(Request $r):Response {DB::insert('privacy_requests',['user_id'=>AuthService::id(),'request_type'=>'deletion_review']);Session::flash('status',['message'=>'Your privacy request is recorded for review. Financial history is retained pending review.']);return $this->redirect('/guest',303);}
}
