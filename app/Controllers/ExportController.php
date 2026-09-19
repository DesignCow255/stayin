<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Database as DB,HttpException};
use App\Services\{AuthService,Gate};
final class ExportController extends Controller {
 public function bookings(Request $r):Response {
  $admin=Gate::allows('bookings.view');if(!$admin&&!AuthService::anyRole(['host']))throw HttpException::forbidden();
  $rows=DB::select('SELECT b.booking_reference,p.name,b.check_in,b.check_out,b.total_amount,b.currency,b.status FROM bookings b JOIN properties p ON p.id=b.property_id'.($admin?'':' WHERE p.host_id=?').' ORDER BY b.id DESC LIMIT 10000',$admin?[]:[AuthService::id()]);
  $f=fopen('php://temp','r+');fputcsv($f,['Reference','Property','Check-in','Check-out','Total','Currency','Status'],',','"','');
  foreach($rows as $row)fputcsv($f,array_map(static fn($v)=>preg_match('/^[\s]*[=+@-]/u',(string)$v)?"'".$v:$v,array_values($row)),',','"','');
  rewind($f);$csv=stream_get_contents($f);fclose($f);return new Response($csv,200,['Content-Type'=>'text/csv; charset=utf-8','Content-Disposition'=>'attachment; filename="bookings.csv"','Cache-Control'=>'no-store']);
 }
}
