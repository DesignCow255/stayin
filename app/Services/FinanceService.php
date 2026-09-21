<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB,BusinessException};
final class FinanceService {
 public static function entry(int $booking,int $host,string $type,int $amount,string $currency,string $key):void {DB::insert('financial_ledger',['booking_id'=>$booking,'host_id'=>$host,'entry_type'=>$type,'amount_minor'=>$amount,'currency'=>$currency,'event_key'=>$key]);}
 public static function reconcile():array {
  return DB::select("SELECT b.id,b.booking_reference,b.currency,b.total_amount,COALESCE(p.paid,0) AS received,COALESCE(l.booked,0) AS ledger_payment_minor FROM bookings b LEFT JOIN (SELECT booking_id,SUM(amount) AS paid FROM payments WHERE status='completed' GROUP BY booking_id) p ON p.booking_id=b.id LEFT JOIN (SELECT booking_id,SUM(amount_minor) AS booked FROM financial_ledger WHERE entry_type='payment' GROUP BY booking_id) l ON l.booking_id=b.id WHERE b.payment_status IN ('paid','refunded') AND (p.paid IS NULL OR p.paid<>b.total_amount OR l.booked IS NULL OR l.booked<>b.total_amount*100) ORDER BY b.id DESC LIMIT 500");
 }
 public static function balances(?int $host=null):array {
  return DB::select("SELECT host_id,currency,SUM(CASE WHEN entry_type IN ('host_payable','host_adjustment','settlement') THEN amount_minor ELSE 0 END) AS balance_minor FROM financial_ledger".($host?' WHERE host_id=?':'').' GROUP BY host_id,currency',$host?[$host]:[]);
 }
 public static function draft(int $host,string $currency,int $actor):int {
  return DB::transaction(function()use($host,$currency,$actor){
   DB::lockFirst('SELECT id FROM users WHERE id=?',[$host]);
   $rows=DB::select("SELECT b.id,SUM(l.amount_minor) AS amount FROM bookings b JOIN financial_ledger l ON l.booking_id=b.id AND l.entry_type IN ('host_payable','host_adjustment') LEFT JOIN settlement_items si ON si.booking_id=b.id WHERE l.host_id=? AND l.currency=? AND b.check_out<CURRENT_DATE AND b.status IN ('completed','confirmed','cancelled') AND si.booking_id IS NULL GROUP BY b.id HAVING SUM(l.amount_minor)>0",[$host,$currency]);
   if(!$rows)throw new BusinessException('No completed, unsettled stays are eligible.');
   $id=DB::insert('host_settlements',['host_id'=>$host,'currency'=>$currency,'amount_minor'=>array_sum(array_column($rows,'amount'))]);
   foreach($rows as $row)DB::insert('settlement_items',['booking_id'=>$row['id'],'settlement_id'=>$id,'amount_minor'=>$row['amount']]);
   self::audit($actor,'settlement.draft','host_settlements',$id);return $id;
  });
 }
 public static function transition(int $id,string $status,string $reference,int $actor):void {
  DB::transaction(function()use($id,$status,$reference,$actor){
   $s=DB::lockFirst('SELECT * FROM host_settlements WHERE id=?',[$id]);
   if(!$s)throw new BusinessException('Settlement not found.');
   $next=['draft'=>'approved','approved'=>'processing','processing'=>'paid'];
   if(($next[$s['status']]??'')!==$status)throw new BusinessException('Invalid settlement transition.');
   if($status==='paid' && strlen(trim($reference))<4)throw new BusinessException('A verified payout reference is required.');
   DB::update('host_settlements',['status'=>$status,'reference'=>$reference?:null,'paid_at'=>$status==='paid'?gmdate('Y-m-d H:i:s'):null],['id'=>$id]);
   if($status==='paid')foreach(DB::select('SELECT * FROM settlement_items WHERE settlement_id=?',[$id]) as $item)self::entry((int)$item['booking_id'],(int)$s['host_id'],'settlement',-(int)$item['amount_minor'],$s['currency'],'settlement:'.$id.':'.$item['booking_id']);
   self::audit($actor,'settlement.'.$status,'host_settlements',$id);
  });
 }
 public static function audit(int $actor,string $action,string $entity,int $id,array $data=[]):void {DB::insert('audit_logs',['user_id'=>$actor,'action'=>$action,'entity_type'=>$entity,'entity_id'=>$id,'new_values'=>$data]);}
}
