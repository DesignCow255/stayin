<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Database as DB,Session,BusinessException};
use App\Services\{AuthService,HostService,UploadService,FinanceService,BookingService};
final class HostController extends Controller {
 public function index(Request $r):Response {
  $id=AuthService::id();
  $properties=DB::select('SELECT * FROM properties WHERE host_id=? ORDER BY id DESC',[$id]);
  $bookings=DB::select("SELECT b.*,p.name,rt.name AS room_name,u.first_name,u.last_name FROM bookings b JOIN properties p ON p.id=b.property_id JOIN room_types rt ON rt.id=b.room_type_id JOIN users u ON u.id=b.guest_id WHERE p.host_id=? ORDER BY b.id DESC LIMIT 100",[$id]);
  $revenue=DB::select("SELECT b.currency,COUNT(*) AS bookings,SUM(b.total_amount) AS total,AVG(b.total_amount) AS average_booking_value FROM bookings b JOIN properties p ON p.id=b.property_id WHERE p.host_id=? AND b.payment_status IN ('paid','refunded') GROUP BY b.currency",[$id]);
  $revenueTrend=DB::select("SELECT DATE(b.created_at) AS day,b.currency,SUM(b.total_amount) AS total,COUNT(*) AS bookings FROM bookings b JOIN properties p ON p.id=b.property_id WHERE p.host_id=? AND b.created_at>=DATE_SUB(CURRENT_DATE,INTERVAL 13 DAY) AND b.payment_status IN ('paid','refunded') GROUP BY DATE(b.created_at),b.currency ORDER BY day",[$id]);
  $statusMix=DB::select("SELECT b.status,COUNT(*) AS total FROM bookings b JOIN properties p ON p.id=b.property_id WHERE p.host_id=? GROUP BY b.status ORDER BY total DESC",[$id]);
  $propertyPerformance=DB::select("SELECT p.id,p.name,p.region,p.status,p.rating,p.review_count,COUNT(b.id) AS bookings,COALESCE(SUM(CASE WHEN b.payment_status IN ('paid','refunded') THEN b.total_amount ELSE 0 END),0) AS revenue,COALESCE(MAX(b.currency),'TZS') AS currency,AVG(DATEDIFF(b.check_out,b.check_in)) AS average_stay FROM properties p LEFT JOIN bookings b ON b.property_id=p.id WHERE p.host_id=? GROUP BY p.id,p.name,p.region,p.status,p.rating,p.review_count ORDER BY revenue DESC,p.id DESC",[$id]);
  $upcoming=DB::select("SELECT b.*,p.name,rt.name AS room_name,u.first_name,u.last_name FROM bookings b JOIN properties p ON p.id=b.property_id JOIN room_types rt ON rt.id=b.room_type_id JOIN users u ON u.id=b.guest_id WHERE p.host_id=? AND b.check_in>=CURRENT_DATE AND b.status IN ('confirmed','awaiting_payment','pending') ORDER BY b.check_in ASC LIMIT 8",[$id]);
  $availability=DB::select("SELECT ab.status,COUNT(*) AS nights FROM availability_blocks ab JOIN room_types rt ON rt.id=ab.room_type_id JOIN properties p ON p.id=rt.property_id WHERE p.host_id=? AND ab.date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL 30 DAY) GROUP BY ab.status",[$id]);
  return $this->view('host/index',['metaTitle'=>'Host workspace · StayIn','properties'=>$properties,'bookings'=>$bookings,'upcoming'=>$upcoming,'propertyPerformance'=>$propertyPerformance,'availability'=>$availability,'statusMix'=>$statusMix,'revenueTrend'=>$revenueTrend,'balances'=>FinanceService::balances($id),'settlements'=>DB::select('SELECT * FROM host_settlements WHERE host_id=? ORDER BY id DESC LIMIT 100',[$id]),'revenue'=>$revenue]);
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
 public function complete(Request $r):Response {
  DB::transaction(function()use($r){$b=DB::lockFirst("SELECT b.* FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.id=? AND p.host_id=?",[$r->int('booking_id'),AuthService::id()]);if(!$b||$b['status']!=='confirmed'||$b['check_out']>gmdate('Y-m-d'))throw new BusinessException('Only a confirmed stay after checkout can be completed.');DB::update('bookings',['status'=>'completed'],['id'=>$b['id']]);BookingService::history((int)$b['id'],'confirmed','completed',AuthService::id());});return $this->redirect('/host/bookings',303);
 }
}
