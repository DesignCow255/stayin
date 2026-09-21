<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB,BusinessException};
use App\Payments\MockGateway;
final class PaymentService {
 public static function pay(string $ref,int $userId,string $scenario='success'):array {
  return DB::transaction(function()use($ref,$userId,$scenario){
   $b=BookingService::owned($ref,$userId);
   DB::lockFirst('SELECT id FROM room_types WHERE id=?',[$b['room_type_id']]);
   $b=DB::lockFirst('SELECT * FROM bookings WHERE id=?',[$b['id']]);
   if($b['payment_status']==='paid')return $b;
   if($b['status']!=='awaiting_payment'||!$b['expires_at']||strtotime($b['expires_at'])<=time())throw new BusinessException('This booking hold expired or is no longer payable.');
   $raw=DB::scalar('SELECT snapshot FROM booking_price_snapshots WHERE booking_id=?',[$b['id']]);
   if(!$raw)throw new BusinessException('Legacy bookings require manual payment review.');
   $q=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
   $event=(new MockGateway)->verifyPayment($ref,Money::minor((string)$b['total_amount']),$b['currency'],$scenario);
   if($event['amount_minor']!==$q['total_minor']||$event['currency']!==$q['currency'])throw new BusinessException('Payment amount or currency mismatch.');
   $payment=DB::first("SELECT * FROM payments WHERE booking_id=? AND provider='mock' ORDER BY id DESC LIMIT 1",[$b['id']]);
   $status=match($event['status']){'success'=>'completed','failed'=>'failed',default=>'pending'};
   if(!$payment){$id=DB::insert('payments',['booking_id'=>$b['id'],'user_id'=>$userId,'provider'=>'mock','provider_transaction_id'=>$event['id'],'payment_method'=>'development_mock','amount'=>$b['total_amount'],'currency'=>$b['currency'],'status'=>$status]);}
   else {$id=(int)$payment['id'];DB::update('payments',['status'=>$status],['id'=>$id]);}
   if($status!=='completed')return [...$b,'simulation'=>$event['status']];
   DB::update('payments',['completed_at'=>gmdate('Y-m-d H:i:s')],['id'=>$id]);
   DB::insert('payment_events',['payment_id'=>$id,'event_type'=>'verified_success','provider_event_id'=>$event['id'],'payload'=>$event,'processed'=>1,'processed_at'=>gmdate('Y-m-d H:i:s')]);
   DB::update('bookings',['status'=>'confirmed','payment_status'=>'paid','confirmed_at'=>gmdate('Y-m-d H:i:s')],['id'=>$b['id']]);
   BookingService::history((int)$b['id'],$b['status'],'confirmed',$userId);
   foreach(['payment'=>$q['total_minor'],'commission'=>$q['commission_minor'],'host_payable'=>$q['host_payable_minor']] as $type=>$amount)FinanceService::entry((int)$b['id'],$q['host_id'],$type,$amount,$q['currency'],'payment:'.$id.':'.$type);
   NotificationService::send($userId,'booking_confirmed','Booking confirmed',$ref.' is confirmed.');
   NotificationService::send($q['host_id'],'new_booking','New booking',$ref.' has been paid.');
   return [...$b,'status'=>'confirmed','payment_status'=>'paid'];
  });
 }
 public static function cancel(string $ref,int $userId):void {
  DB::transaction(function()use($ref,$userId){
   $original=BookingService::owned($ref,$userId);
   DB::lockFirst('SELECT id FROM room_types WHERE id=?',[$original['room_type_id']]);
   $b=DB::lockFirst('SELECT * FROM bookings WHERE id=?',[$original['id']]);
   if($b['status']==='cancelled')return;
   if(!in_array($b['status'],['awaiting_payment','pending','confirmed'],true))throw new BusinessException('This booking cannot be cancelled automatically.');
   if($b['payment_status']==='paid') {
    if(!$original['snapshot'])throw new BusinessException('This legacy booking needs a support review before cancellation.');
    if(DB::scalar('SELECT booking_id FROM settlement_items WHERE booking_id=?',[$b['id']]))throw new BusinessException('A settlement exists. Contact support for a reviewed adjustment.');
    $q=json_decode($original['snapshot'],true); if($q['policy']==='manual_review')throw new BusinessException('Contact support to review this property’s cancellation terms.');
    if($b['check_in']<=gmdate('Y-m-d'))throw new BusinessException('Contact support for cancellations on or after check-in.');
    $days=(int)(new \DateTimeImmutable('today'))->diff(new \DateTimeImmutable($b['check_in']))->days;$percent=0;
    foreach(\App\Core\Config::array('booking.cancellation_policies.'.$q['policy'].'.rules') as $rule)if($days>=$rule['days_before']){$percent=$rule['refund_percent'];break;}
    $refund=Money::percent($q['total_minor'],$percent*100);
    $p=DB::first("SELECT * FROM payments WHERE booking_id=? AND status='completed' ORDER BY id DESC LIMIT 1",[$b['id']]);
    if(!$p||$p['provider']!=='mock')throw new BusinessException('This payment requires a provider refund review.');
    if($refund>0)(new MockGateway)->refund($ref,$refund,$q['currency']);
    DB::insert('refunds',['booking_id'=>$b['id'],'payment_id'=>$p['id'],'amount_minor'=>$refund,'currency'=>$q['currency'],'calculation'=>['policy'=>$q['policy'],'days_before'=>$days,'percent'=>$percent],'status'=>'completed']);
    FinanceService::entry((int)$b['id'],$q['host_id'],'refund',-$refund,$q['currency'],'refund:'.$b['id']);
    FinanceService::entry((int)$b['id'],$q['host_id'],'host_adjustment',-Money::percent($q['host_payable_minor'],$percent*100),$q['currency'],'refund:host:'.$b['id']);
    FinanceService::entry((int)$b['id'],$q['host_id'],'commission_adjustment',-Money::percent($q['commission_minor'],$percent*100),$q['currency'],'refund:commission:'.$b['id']);
    if($percent===100)DB::update('bookings',['payment_status'=>'refunded'],['id'=>$b['id']]);
   }
   DB::update('bookings',['status'=>'cancelled','cancelled_at'=>gmdate('Y-m-d H:i:s')],['id'=>$b['id']]);BookingService::history((int)$b['id'],$b['status'],'cancelled',$userId);
  });
 }
}
