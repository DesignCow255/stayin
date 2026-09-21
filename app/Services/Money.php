<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\BusinessException;
final class Money {
 public static function minor(string|int $value): int {
  $value=(string)$value;
  if (!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D',$value)) throw new BusinessException('Enter a valid non-negative monetary amount.');
  [$whole,$fraction]=array_pad(explode('.',$value),2,'');
  return (int)$whole*100+(int)str_pad($fraction,2,'0');
 }
 public static function decimal(int $minor): string { return ($minor<0?'-':'').intdiv(abs($minor),100).'.'.str_pad((string)(abs($minor)%100),2,'0',STR_PAD_LEFT); }
 public static function percent(int $minor,int $basisPoints): int { return intdiv($minor*$basisPoints+5000,10000); }
}
