<?php
declare(strict_types=1);
namespace App\Payments;
interface PaymentGatewayInterface {
 public function verifyPayment(string $reference,int $amountMinor,string $currency,string $scenario):array;
 public function refund(string $reference,int $amountMinor,string $currency):array;
}
