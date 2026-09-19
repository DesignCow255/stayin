<?php
declare(strict_types=1);
namespace App\Payments;
use App\Core\{Config,BusinessException};
final class MockGateway implements PaymentGatewayInterface {
 public static function available():bool {return in_array(Config::string('app.env'),['local','development','testing'],true)&&Config::bool('payments.gateways.mock.enabled',false)&&Config::string('payments.default')==='mock';}
 private function guard():void {if(!self::available())throw new BusinessException('Payment processing is unavailable until a verified provider is configured.','payments_unavailable',503);}
 public function verifyPayment(string $reference,int $amountMinor,string $currency,string $scenario):array {
  $this->guard(); if(!in_array($scenario,['success','failed','pending','timeout'],true))throw new BusinessException('Invalid payment simulation.');
  return ['id'=>'mock:'.$reference,'amount_minor'=>$amountMinor,'currency'=>$currency,'status'=>$scenario,'provider'=>'mock'];
 }
 public function refund(string $reference,int $amountMinor,string $currency):array {$this->guard();return ['id'=>'refund:'.$reference,'amount_minor'=>$amountMinor,'currency'=>$currency,'status'=>'completed'];}
}
