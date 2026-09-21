<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Config,BusinessException};
final class UploadService {
 public static function image(?array $file,bool $private=false):string {
  if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>5*1024*1024||!is_uploaded_file($file['tmp_name']))throw new BusinessException('Upload a JPEG, PNG or WebP image up to 5 MB.');
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$size=getimagesize($file['tmp_name']);
  if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)||!$size||$size[0]*$size[1]>20000000)throw new BusinessException('Unsupported image or dimensions.');
  if(!function_exists('imagewebp'))throw new BusinessException('Image processing is unavailable. Contact support.');
  $im=imagecreatefromstring(file_get_contents($file['tmp_name']));if(!$im)throw new BusinessException('Cannot decode this image.');
  $width=min(1800,imagesx($im));$height=(int)round(imagesy($im)*$width/imagesx($im));$scaled=imagescale($im,$width,$height);
  $relative=($private?'storage/private/kyc/':'public/assets/uploads/').bin2hex(random_bytes(20)).'.webp';$path=Config::string('app.base_path').'/'.$relative;
  if(!is_dir(dirname($path)))mkdir(dirname($path),$private?0700:0755,true);
  if(!imagewebp($scaled,$path,85))throw new BusinessException('Unable to store image.');chmod($path,$private?0600:0644);imagedestroy($im);imagedestroy($scaled);
  return $private?$relative:substr($relative,7);
 }
}
