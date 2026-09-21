<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Config,Database as DB};
final class RateLimiter {
 public static function attempt(string $limitName,string $fingerprint):array {
  $c=Config::array('security.rate_limits.'.$limitName); $max=(int)($c['attempts']??60);$decay=max(1,(int)($c['decay_seconds']??60));$window=intdiv(time(),$decay);$bucket=hash('sha256',$limitName.'|'.$fingerprint);
  return DB::transaction(function()use($window,$bucket,$max,$decay){
   DB::execute('INSERT INTO rate_limits(bucket,window_start,hits) VALUES(?,?,1) ON DUPLICATE KEY UPDATE hits=IF(window_start=VALUES(window_start),hits+1,1),window_start=VALUES(window_start)',[$bucket,$window]);
   $hits=(int)DB::scalar('SELECT hits FROM rate_limits WHERE bucket=?',[$bucket]);
   return ['allowed'=>$hits<=$max,'remaining'=>max(0,$max-$hits),'retry_after'=>$decay];
  });
 }
 public static function tooManyAttempts(string $name,string $fingerprint):bool {return !self::attempt($name,$fingerprint)['allowed'];}
}
