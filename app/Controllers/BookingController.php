<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Request,Response,Session,BusinessException,Config};
use App\Services\{BookingService,AuthService,PaymentService};
final class BookingController extends Controller {
 public function hold(Request $r):Response {
  if(Config::bool('booking.require_email_verification')&&!AuthService::user()['email_verified_at'])throw new BusinessException('Verify your email before booking.');
  $b=BookingService::hold(AuthService::id(),$r->int('room_id'),$r->string('check_in'),$r->string('check_out'),$r->int('guests',1),$r->int('rooms',1));
  return $r->expectsJson()?$this->json(['booking'=>$b,'checkout_url'=>url('/checkout/'.$b['booking_reference'])],201):$this->redirect('/checkout/'.$b['booking_reference'],303);
 }
 public function showCheckout(Request $r):Response {
  $b=BookingService::owned((string)$r->routeParam('reference'),AuthService::id());
  return $this->view('checkout',['metaTitle'=>'Your booking · StayIn','booking'=>$b,'quote'=>$b['snapshot']?json_decode($b['snapshot'],true):null]);
 }
 public function pay(Request $r):Response {
  if(!$r->bool('terms'))throw new BusinessException('Please accept the booking terms before continuing.');
  $b=PaymentService::pay((string)$r->routeParam('reference'),AuthService::id(),$r->string('scenario','success'));
  Session::flash('status',['message'=>$b['payment_status']==='paid'?'Your booking is confirmed.':'Payment '.$b['simulation'].'. Your hold will expire at its original deadline.']);
  return $this->redirect('/checkout/'.$b['booking_reference'],303);
 }
 public function cancel(Request $r):Response {PaymentService::cancel((string)$r->routeParam('reference'),AuthService::id());Session::flash('status',['message'=>'Booking cancelled. Any eligible mock refund has been recorded.']);return $this->redirect('/guest/bookings',303);}
 public function receipt(Request $r):Response {
   $b=BookingService::receipt((string)$r->routeParam('reference'),AuthService::id());
  if(!in_array($b['payment_status'],['paid','refunded'],true))throw new BusinessException('A receipt is available after payment confirmation.');
  return $this->view('receipt',['metaTitle'=>'Receipt '.$b['booking_reference'],'booking'=>$b]);
 }
}
