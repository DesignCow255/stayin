<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB, BusinessException, HttpException, Config};
final class BookingService {
 public static function dates(string $in,string $out): array {
  $start=\DateTimeImmutable::createFromFormat('!Y-m-d',$in); $end=\DateTimeImmutable::createFromFormat('!Y-m-d',$out);
  if (!$start || !$end || $start->format('Y-m-d')!==$in || $end->format('Y-m-d')!==$out || $in<gmdate('Y-m-d') || $out<=$in) throw new BusinessException('Choose valid future check-in and check-out dates.');
  $n=(int)$start->diff($end)->days;
  if ($n>Config::int('booking.max_nights',60) || $start>new \DateTimeImmutable('+730 days')) throw new BusinessException('Choose a stay of up to 60 nights within the next two years.');
  $days=[]; for($d=$start;$d<$end;$d=$d->modify('+1 day')) $days[]=$d->format('Y-m-d'); return $days;
 }
 public static function quote(int $roomId,string $in,string $out,int $guests=1,int $rooms=1): array {
  $days=self::dates($in,$out);
  $room=DB::first("SELECT rt.*,p.host_id,p.status AS property_status,p.cancellation_policy FROM room_types rt JOIN properties p ON p.id=rt.property_id WHERE rt.id=?",[$roomId]);
  if (!$room || $room['status']!=='active' || $room['property_status']!=='published') throw new BusinessException('This room is not available.');
  if($rooms<1 || $rooms>20 || $guests<1 || $guests>(int)$room['max_guests']*$rooms) throw new BusinessException('The selected rooms do not fit your party.');
  $calendar=DB::select('SELECT * FROM availability_blocks WHERE room_type_id=? AND date>=? AND date<? ORDER BY date',[$roomId,$in,$out]);
  if(count($calendar)!==count($days)) throw new BusinessException('The host has not opened every date in this stay.');
  $reservations=DB::select("SELECT check_in,check_out,rooms FROM bookings WHERE room_type_id=? AND check_in<? AND check_out>? AND (status IN ('confirmed','completed','disputed') OR (status IN ('pending','awaiting_payment') AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP())))",[$roomId,$out,$in]);
  $subtotal=0; $nightly=[]; $available=PHP_INT_MAX;
  foreach($calendar as $day) {
   $taken=0;foreach($reservations as $b) if($b['check_in']<=$day['date'] && $b['check_out']>$day['date']) $taken+=(int)$b['rooms'];
   $free=min((int)$room['quantity'],(int)$day['available_quantity'])-$taken;
   if($day['status']!=='available' || $free<$rooms || count($days)<(int)$day['minimum_stay']) throw new BusinessException('These dates no longer have enough rooms. Please choose another stay.','unavailable',409);
   $rate=Money::minor((string)($day['price_override']??$room['base_price'])); $subtotal+=$rate*$rooms;
   $nightly[]=['date'=>$day['date'],'rate_minor'=>$rate,'rooms'=>$rooms]; $available=min($available,$free);
  }
  $percent=DB::scalar("SELECT value FROM settings WHERE `key` IN (?, ?, 'commission.default') ORDER BY FIELD(`key`, ?, ?, 'commission.default') LIMIT 1",['commission.property.'.$room['property_id'],'commission.host.'.$room['host_id'],'commission.property.'.$room['property_id'],'commission.host.'.$room['host_id']]) ?? Config::string('payments.commission.default_percent','10.00');
  $basis=Money::minor((string)$percent); if($basis>10000) throw new BusinessException('Commission configuration requires review.');
  $commission=Money::percent($subtotal,$basis);
  $policy=(string)($room['cancellation_policy']??'');
  // Legacy free-text policies are never guessed into a refundable contract.
  $policyKey=array_key_exists($policy,Config::array('booking.cancellation_policies'))?$policy:'manual_review';
  return ['room_id'=>$roomId,'property_id'=>(int)$room['property_id'],'host_id'=>(int)$room['host_id'],'check_in'=>$in,'check_out'=>$out,'guests'=>$guests,'rooms'=>$rooms,'nights'=>count($days),'nightly'=>$nightly,'subtotal_minor'=>$subtotal,'fees_minor'=>0,'tax_minor'=>0,'discount_minor'=>0,'total_minor'=>$subtotal,'currency'=>$room['currency'],'exchange_rate'=>'1.000000','commission_basis_points'=>$basis,'commission_minor'=>$commission,'host_payable_minor'=>$subtotal-$commission,'policy'=>$policyKey,'policy_text'=>$policy,'available'=>$available];
 }
 public static function hold(int $userId,int $roomId,string $in,string $out,int $guests,int $rooms): array {
  return DB::transaction(function()use($userId,$roomId,$in,$out,$guests,$rooms){
   DB::lockFirst('SELECT id FROM room_types WHERE id=?',[$roomId]);
   $q=self::quote($roomId,$in,$out,$guests,$rooms);
   $reference='SI-'.strtoupper(bin2hex(random_bytes(7)));
   $id=DB::insert('bookings',['booking_reference'=>$reference,'guest_id'=>$userId,'property_id'=>$q['property_id'],'room_type_id'=>$roomId,'check_in'=>$in,'check_out'=>$out,'guests'=>$guests,'rooms'=>$rooms,'subtotal'=>Money::decimal($q['subtotal_minor']),'total_amount'=>Money::decimal($q['total_minor']),'currency'=>$q['currency'],'status'=>'awaiting_payment','expires_at'=>gmdate('Y-m-d H:i:s',time()+Config::int('booking.hold_minutes',15)*60)]);
   DB::insert('booking_price_snapshots',['booking_id'=>$id,'snapshot'=>$q]); self::history($id,null,'awaiting_payment',$userId);
   return self::owned($reference,$userId);
  });
 }
 public static function owned(string $ref,int $userId): array {
  $b=DB::first('SELECT b.*,p.name AS property_name,p.host_id,rt.name AS room_name,s.snapshot FROM bookings b JOIN properties p ON p.id=b.property_id JOIN room_types rt ON rt.id=b.room_type_id LEFT JOIN booking_price_snapshots s ON s.booking_id=b.id WHERE b.booking_reference=? AND b.guest_id=?',[$ref,$userId]);
  if(!$b) throw HttpException::notFound(); return $b;
 }
 public static function history(int $id,?string $from,string $to,?int $actor):void {DB::insert('booking_status_history',['booking_id'=>$id,'from_status'=>$from,'to_status'=>$to,'actor_id'=>$actor]);}
 public static function expire():int {
  return DB::transaction(function(){
   $rows=DB::select("SELECT id,status FROM bookings WHERE status IN ('pending','awaiting_payment') AND expires_at<=UTC_TIMESTAMP() FOR UPDATE");
   foreach($rows as $row) {DB::update('bookings',['status'=>'expired'],['id'=>$row['id']]);self::history((int)$row['id'],$row['status'],'expired',null);} return count($rows);
  });
 }
}
