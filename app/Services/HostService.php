<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB,Request,HttpException,BusinessException};
use App\Models\User;
final class HostService {
 public static function property(int $id,int $host):array {$p=DB::first('SELECT * FROM properties WHERE id=? AND host_id=?',[$id,$host]);if(!$p)throw HttpException::notFound();return $p;}
 public static function save(Request $r,int $host,?int $id=null):int {
  if($id)self::property($id,$host);
  $v=$r->validate(['name'=>'required|min:3|max:255','property_type'=>'required|in:hotel,lodge,guest_house,homestay,chumba_kimoja,villa,apartment,serviced_apartment','region'=>'required|max:100','address'=>'required|max:255','description_en'=>'required|min:20|max:10000','house_rules'=>'max:3000','cancellation_policy'=>'required|in:flexible,moderate,strict,non_refundable','latitude'=>'numeric|min_value:-90|max_value:90','longitude'=>'numeric|min_value:-180|max_value:180'])->validated();
  // Editing a published listing returns it to review; hosts cannot self-verify.
  $v['status']='draft';$v['verification_status']='pending';
  if($id){DB::update('properties',$v,['id'=>$id]);return $id;}
  return DB::insert('properties',[...$v,'host_id'=>$host,'uuid'=>User::uuid(),'slug'=>slugify($v['name']).'-'.bin2hex(random_bytes(4))]);
 }
 public static function room(Request $r,int $property,int $host):void {
  self::property($property,$host);
  $v=$r->validate(['name'=>'required|max:255','description'=>'max:3000','max_guests'=>'required|integer|min_value:1|max_value:20','quantity'=>'required|integer|min_value:1|max_value:500','base_price'=>'required|numeric|min_value:0.01','currency'=>'required|in:TZS,USD','bed_configuration'=>'max:255'])->validated();
  Money::minor((string)$v['base_price']);DB::insert('room_types',[...$v,'property_id'=>$property]);
 }
 public static function inventory(Request $r,int $property,int $host):void {
  self::property($property,$host);$r->validate(['room_id'=>'required|integer','available_quantity'=>'required|integer|min_value:0|max_value:500','status'=>'required|in:available,blocked,maintenance,closed','minimum_stay'=>'required|integer|min_value:1|max_value:60','price_override'=>'numeric|min_value:0.01']);
  $days=BookingService::dates($r->string('check_in'),$r->string('check_out'));
  DB::transaction(function()use($r,$property,$days){
   $room=DB::lockFirst('SELECT * FROM room_types WHERE id=? AND property_id=?',[$r->int('room_id'),$property]);if(!$room)throw HttpException::notFound();
   foreach($days as $date){
    $taken=(int)DB::scalar("SELECT COALESCE(SUM(rooms),0) FROM bookings WHERE room_type_id=? AND check_in<=? AND check_out>? AND (status IN ('confirmed','completed','disputed') OR (status IN ('pending','awaiting_payment') AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP())))",[$room['id'],$date,$date]);
    $quantity=$r->int('available_quantity');
    if($quantity>(int)$room['quantity']||$quantity<$taken||($taken>0&&$r->string('status')!=='available'))throw new BusinessException('The calendar cannot remove inventory already reserved or exceed room quantity.');
    $price=$r->string('price_override');if($price!=='')Money::minor($price);
    DB::execute('INSERT INTO availability_blocks(room_type_id,date,available_quantity,price_override,minimum_stay,status) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE available_quantity=VALUES(available_quantity),price_override=VALUES(price_override),minimum_stay=VALUES(minimum_stay),status=VALUES(status)',[$room['id'],$date,$quantity,$price?:null,$r->int('minimum_stay'),$r->string('status')]);
   }
  });
 }
 public static function submit(int $id,int $host):void {
  self::property($id,$host);
  if(!DB::scalar("SELECT user_id FROM host_kyc WHERE user_id=? AND status='verified'",[$host]))throw new BusinessException('Complete host identity verification before submitting a property.');
  if(!DB::scalar('SELECT id FROM property_images WHERE property_id=?',[$id])||!DB::scalar("SELECT id FROM room_types WHERE property_id=? AND status='active'",[$id]))throw new BusinessException('Add a property image and active room type first.');
  DB::update('properties',['status'=>'pending'],['id'=>$id]);
 }
}
