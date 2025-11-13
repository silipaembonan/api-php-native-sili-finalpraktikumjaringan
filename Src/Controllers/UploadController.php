<?php
namespace Src\Controllers; use Src\Helpers\Response;
class UploadController extends BaseController{
  public function store(){
    if(($_SERVER['CONTENT_TYPE']??'') && str_contains($_SERVER['CONTENT_TYPE'],'application/json')){
      return $this->error(415,'Use multipart/form-data for upload');
    }
    
    if(empty($_FILES['file'])) return $this->error(422,'file is required');
    $f=$_FILES['file']; if($f['error']!==UPLOAD_ERR_OK){
      $errors = [UPLOAD_ERR_INI_SIZE=>'File too large (ini)', UPLOAD_ERR_FORM_SIZE=>'File too large (form)', 
      UPLOAD_ERR_PARTIAL=>'Partial upload', UPLOAD_ERR_NO_FILE=>'No file', UPLOAD_ERR_NO_TMP_DIR=>'No temp dir', 
      UPLOAD_ERR_CANT_WRITE=>'Cant write', UPLOAD_ERR_EXTENSION=>'Extension blocked'];
      return $this->error(400,'Upload error: '.($errors[$f['error']]??'Unknown'));
    }
    if($f['size']>2*1024*1024) return $this->error(422,'Max 2MB');
    $finfo=new \finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($f['tmp_name']);
    $allowed=['image/png'=>'png','image/jpeg'=>'jpg','application/pdf'=>'pdf','text/plain'=>'txt'];
    if(!isset($allowed[$mime])) return $this->error(422,'Invalid mime');
    $name=bin2hex(random_bytes(8)).'.'.$allowed[$mime];
    $dir=__DIR__.'/../../uploads';
    if(!is_dir($dir) && !mkdir($dir,0775,true)) return $this->error(500,'Cannot create upload directory');
    $dest=$dir.'/'.$name;
    if(!move_uploaded_file($f['tmp_name'],$dest)) return $this->error(500,'Save failed');
    $this->ok(['path'=>"/uploads/$name"],201);
  }

  public function index(){
    $dir=__DIR__.'/../../uploads';
    if(!is_dir($dir)) return $this->ok(['files'=>[]]);
    $items=scandir($dir);
    $files=[];
    foreach($items as $item){
      if($item==='.'||$item==='..') continue;
      $full=$dir.'/'.$item;
      if(is_file($full)) $files[]=$item;
    }
    $this->ok(['files'=>$files]);
  }

  public function show($name){
    $name=basename($name);
    if($name==='') return $this->error(404,'File not found');
    $path=__DIR__.'/../../uploads/'.$name;
    if(!is_file($path)) return $this->error(404,'File not found');
    $finfo=new \finfo(FILEINFO_MIME_TYPE);
    $mime=$finfo->file($path) ?: 'application/octet-stream';
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($path));
    header('Content-Disposition: inline; filename="'.$name.'"');
    readfile($path);
    exit;
  }
}
