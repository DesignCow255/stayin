<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\{Config,Database as DB,Logger};

final class MailService
{
    public static function queue(string $to,string $subject,string $body):void
    {
        if(filter_var($to,FILTER_VALIDATE_EMAIL)) {
            DB::insert('email_queue',['recipient'=>$to,'subject'=>$subject,'body'=>$body]);
        }
    }

    public static function work():int
    {
        $count=0;
        $max=max(1,Config::int('mail.max_attempts',3));
        foreach(DB::select("SELECT * FROM email_queue WHERE status='pending' AND attempts<? ORDER BY id LIMIT 50",[$max]) as $m){
            try {
                if(in_array(Config::string('app.env'),['local','development','testing'],true) || Config::string('mail.mailer','log')==='log'){
                    self::capture($m);
                } elseif(Config::string('mail.mailer')==='smtp') {
                    self::smtp((string)$m['recipient'],(string)$m['subject'],(string)$m['body']);
                    DB::update('email_queue',['status'=>'sent','sent_at'=>gmdate('Y-m-d H:i:s')],['id'=>$m['id']]);
                } else {
                    throw new \RuntimeException('Unsupported mail transport. Set MAIL_MAILER=log or smtp.');
                }
                $count++;
            } catch(\Throwable $e) {
                $attempts=(int)$m['attempts']+1;
                DB::update('email_queue',['attempts'=>$attempts,'status'=>$attempts >= $max ? 'failed' : 'pending'],['id'=>$m['id']]);
                Logger::error('mail.delivery_failed',['mail_id'=>(int)$m['id'],'error'=>$e->getMessage()]);
            }
        }
        return $count;
    }

    private static function capture(array $m):void
    {
        $path=Config::string('app.base_path').'/storage/private/mail';
        if(!is_dir($path) && !mkdir($path,0700,true) && !is_dir($path)) throw new \RuntimeException('Cannot create local mail capture directory.');
        $raw='To: '.$m['recipient']."\nSubject: ".self::header((string)$m['subject'])."\nContent-Type: text/plain; charset=UTF-8\n\n".$m['body'];
        file_put_contents($path.'/'.$m['id'].'.eml',$raw,LOCK_EX);
        chmod($path.'/'.$m['id'].'.eml',0600);
        DB::update('email_queue',['status'=>'captured','sent_at'=>gmdate('Y-m-d H:i:s')],['id'=>$m['id']]);
    }

    private static function smtp(string $to,string $subject,string $body):void
    {
        $host=Config::string('mail.host');
        $port=Config::int('mail.port',587);
        $user=Config::string('mail.username');
        $pass=Config::string('mail.password');
        $encryption=strtolower(Config::string('mail.encryption','tls'));
        $from=Config::string('mail.from.address');
        $fromName=Config::string('mail.from.name','StayIn');
        if($host==='' || $from==='' || !filter_var($from,FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('SMTP host/from address is not configured.');

        $remote=($encryption==='ssl'?'ssl://':'').$host.':'.$port;
        $socket=@stream_socket_client($remote,$errno,$errstr,15,STREAM_CLIENT_CONNECT);
        if(!is_resource($socket)) throw new \RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        stream_set_timeout($socket,15);
        try {
            self::expect($socket,[220]);
            $hostname=gethostname() ?: 'localhost';
            self::command($socket,"EHLO {$hostname}",[250]);
            if($encryption==='tls'){
                self::command($socket,'STARTTLS',[220]);
                if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('SMTP TLS negotiation failed.');
                self::command($socket,"EHLO {$hostname}",[250]);
            }
            if($user!==''){
                self::command($socket,'AUTH LOGIN',[334]);
                self::command($socket,base64_encode($user),[334]);
                self::command($socket,base64_encode($pass),[235]);
            }
            self::command($socket,'MAIL FROM:<'.$from.'>',[250]);
            self::command($socket,'RCPT TO:<'.$to.'>',[250,251]);
            self::command($socket,'DATA',[354]);
            $headers=[
                'Date: '.date(DATE_RFC2822),
                'From: '.self::header($fromName).' <'.$from.'>',
                'To: <'.$to.'>',
                'Subject: '.self::header($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            $payload=implode("\r\n",$headers)."\r\n\r\n".str_replace(["\r\n","\r","\n"],"\r\n",$body);
            $payload=preg_replace('/(?m)^\./','..',$payload) ?? $payload;
            fwrite($socket,$payload."\r\n.\r\n");
            self::expect($socket,[250]);
            self::command($socket,'QUIT',[221]);
        } finally {
            fclose($socket);
        }
    }

    private static function command($socket,string $command,array $codes):void
    {
        fwrite($socket,$command."\r\n");
        self::expect($socket,$codes);
    }

    private static function expect($socket,array $codes):void
    {
        $response='';
        while(($line=fgets($socket,515))!==false){
            $response.=$line;
            if(strlen($line)>=4 && $line[3]===' ') break;
        }
        $code=(int)substr($response,0,3);
        if(!in_array($code,$codes,true)) throw new \RuntimeException('SMTP server rejected the request (code '.$code.').');
    }

    private static function header(string $value):string
    {
        $value=str_replace(["\r","\n"],' ',trim($value));
        return preg_match('/[^\x20-\x7E]/',$value) ? '=?UTF-8?B?'.base64_encode($value).'?=' : $value;
    }
}
