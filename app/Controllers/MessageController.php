<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Database as DB,HttpException};
use App\Services\AuthService;
final class MessageController extends Controller {
 private function booking(Request $r):array {
  $b=DB::first('SELECT b.*,p.host_id,p.name FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.id=? AND (b.guest_id=? OR p.host_id=?)',[(int)$r->routeParam('id'),AuthService::id(),AuthService::id()]);if(!$b)throw HttpException::notFound();return $b;
 }
 public function index(Request $r):Response {$b=$this->booking($r);return $this->view('messages',['metaTitle'=>'Booking messages','booking'=>$b,'messages'=>DB::select('SELECT m.*,u.first_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE booking_id=? ORDER BY m.id LIMIT 200',[$b['id']])]);}
 public function send(Request $r):Response {$b=$this->booking($r);$r->validate(['body'=>'required|max:3000']);DB::insert('messages',['booking_id'=>$b['id'],'sender_id'=>AuthService::id(),'body'=>$r->string('body')]);return $this->redirect('/messages/'.$b['id'],303);}
}
