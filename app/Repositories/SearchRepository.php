<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\Database as DB;
use App\Services\BookingService;
final class SearchRepository {
 public static function search(array $f,int $page=1):array {
  $where=["p.status='published'"]; $args=[];
  if(!empty($f['q'])){$where[]='(p.name LIKE ? OR p.region LIKE ? OR p.district LIKE ?)';for($i=0;$i<3;$i++)$args[]='%'.mb_substr((string)$f['q'],0,100).'%';}
  foreach(['region','property_type'] as $key)if(!empty($f[$key])){$where[]='p.'.$key.'=?';$args[]=$f[$key];}
  if(!empty($f['rating'])){$where[]='p.rating>=?';$args[]=(int)$f['rating'];}
  if(!empty($f['verified']))$where[]="p.verification_status='verified'";
  $currency=in_array($f['currency']??'', ['TZS','USD'],true)?$f['currency']:null;
  $rw=["rt.property_id=p.id","rt.status='active'"]; $ra=[];
  if($currency){$rw[]='rt.currency=?';$ra[]=$currency;}
  foreach(['min_price'=>'>=','max_price'=>'<='] as $key=>$op)if(isset($f[$key])&&$f[$key]!==''){$rw[]='rt.base_price'.$op.'?';$ra[]=$f[$key];}
  $rooms=max(1,min(20,(int)($f['rooms']??1)));$guests=max(1,(int)($f['guests']??1));$rw[]='rt.max_guests*?>=?';array_push($ra,$rooms,$guests);
  if(!empty($f['check_in']) || !empty($f['check_out'])){
   $in=(string)($f['check_in']??'');$out=(string)($f['check_out']??'');$n=count(BookingService::dates($in,$out));
   $rw[]="(SELECT COUNT(*) FROM availability_blocks a WHERE a.room_type_id=rt.id AND a.date>=? AND a.date<? AND a.status='available' AND a.minimum_stay<=? AND LEAST(a.available_quantity,rt.quantity)-(SELECT COALESCE(SUM(b.rooms),0) FROM bookings b WHERE b.room_type_id=rt.id AND b.check_in<=a.date AND b.check_out>a.date AND (b.status IN ('confirmed','completed','disputed') OR (b.status IN ('pending','awaiting_payment') AND (b.expires_at IS NULL OR b.expires_at>UTC_TIMESTAMP()))))>=?)=?";
   array_push($ra,$in,$out,$n,$rooms,$n);
  }
  $roomWhere=implode(' AND ',$rw);$where[]='EXISTS(SELECT 1 FROM room_types rt WHERE '.$roomWhere.')';$args=[...$args,...$ra];
  $sql=' FROM properties p WHERE '.implode(' AND ',$where);
  $count=(int)DB::scalar('SELECT COUNT(*)'.$sql,$args);
  $sort=match($f['sort']??''){ 'newest'=>'p.id DESC','rating'=>'p.rating DESC',default=>'p.featured DESC,p.rating DESC,p.id DESC'};
  $offset=(max(1,$page)-1)*24;
  $rows=DB::select("SELECT p.id,p.name,p.slug,p.region,p.property_type,p.rating,p.review_count,p.verification_status,(SELECT file_url FROM property_images i WHERE i.property_id=p.id ORDER BY is_primary DESC,sort_order LIMIT 1) AS cover_image,(SELECT MIN(rt.base_price) FROM room_types rt WHERE $roomWhere) AS min_price,(SELECT rt.currency FROM room_types rt WHERE $roomWhere ORDER BY rt.base_price LIMIT 1) AS currency".$sql." ORDER BY $sort LIMIT 24 OFFSET ".$offset,[...$ra,...$ra,...$args]);
  return ['properties'=>$rows,'count'=>$count,'page'=>$page,'filters'=>$f];
 }
}
