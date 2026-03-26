<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use Throwable;
use Session;

/* DO NOT REMOVE
Existing setup with image attachment in ledger template
 - warehouse ledger (conti setup, image directory: C:\xampp\htdocs\conti\laravels\database\images)
*/

class imageuploader
{
  private $othersClass;
  private $coreFunctions;
  private $headClass;
  private $logger;
  private $lookupClass;
  private $companysetup;
  private $config = [];
  private $sqlquery;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->headClass = new headClass;
    $this->logger = new Logger;
    $this->lookupClass = new lookupClass;
    $this->companysetup = new companysetup;
    $this->sqlquery = new sqlquery;
  }

  public function sbc($params)
  {

    $this->config['params'] = $params;
    $this->config['return'] = ['status' => false, 'msg' => 'Failed: Not yet setup in imagestatus func'];
    return $this;
  }

  public function imagestatus(Request $request)
  {
    switch ($this->config['params']['table']) {
      case 'client':
      case 'item':
      case 'obapplication':
      case 'la_picture':
        $this->imageupload($request);
        break;
      case 'client_picture':
      case 'cntnum_picture':
      case 'transnum_picture':
      case 'docunum_picture':
      case 'hrisnum_picture':
      case 'app_picture':
      case 'loan_picture':
      case 'leave_picture':
        $this->imageuploadmodule($request);
        break;
      case 'reqcategory':
        $this->imageuploadtblentry($request);
        break;
      case 'changepassword':
        if ($this->config['params']['logintype'] == '9bc65c2abec141778ffaa729489f3e87') {
          $this->changepassword();
        } else {
          $this->changeclientpassword();
        }
        break;
      case 'unlockscreen':
        $this->unlockscreen();
        break;
      case 'signature':
        $this->saveSignature($request);
        break;
      case 'qnstock':
        $this->qnimageupload($request);
        break;
      case 'waims_notice_attachments':
        $this->noticeimageupload($request);
        break;
      case 'waims_attachments':
        $this->waimsattachupload($request);
        break;
    }
    return $this;
  }

  private function unlockscreen()
  {
    $qry = "select password as value from useraccess where md5(userid)='" . $this->config['params']['userid'] . "'";
    $oldpass = $this->coreFunctions->datareader($qry);
    if ($oldpass == $this->config['params']['pass']) {
      $this->config['return'] = ['status' => true, 'msg' => 'Unlock success'];
    } else {
      if ($this->config['params']['logintype'] == '9bc65c2abec141778ffaa729489f3e87') {
        $this->config['return'] = ['status' => false, 'msg' => 'Unlock Failed'];
      } else {
        $client = $this->othersClass->checkclientaccess($this->config['params']['user'], $this->config['params']['pass']);
        if ($client['status']) {
          $this->config['return'] = ['status' => true, 'msg' => 'Unlock success'];
        } else {
          $this->config['return'] = ['status' => false, 'msg' => 'Unlock Failed'];
        }
      }
    }
    return $this;
  }

  private function changeclientpassword()
  {
    $qry = "select md5(password) as value from client where md5(clientid)='" . $this->config['params']['userid'] . "'";
    $oldpass = $this->coreFunctions->datareader($qry);
    if ($oldpass != '') {
      if ($oldpass == $this->config['params']['oldpass']) {
        if ($oldpass != md5($this->config['params']['pwd'])) {
          $qry = "update client set password='" . $this->config['params']['pwd'] . "' where md5(clientid)='" . $this->config['params']['userid'] . "'";
          $this->coreFunctions->execqry($qry);
          $this->config['return'] = ['status' => true, 'msg' => 'Update Password success'];
        } else {
          $this->config['return'] = ['status' => false, 'msg' => 'New Password is the same from Old Password...'];
        }
      } else {
        $this->config['return'] = ['status' => false, 'msg' => 'Invalid Old Password...'];
      }
    } else {
      $this->config['return'] = ['status' => false, 'msg' => 'Invalid Old Password...'];
    }
    return $this;
  } // end function


  private function changepassword()
  {
    $qry = "select password as value from useraccess where md5(userid)='" . $this->config['params']['userid'] . "'";
    $oldpass = $this->coreFunctions->datareader($qry);
    if ($oldpass != '') {
      if ($oldpass == $this->config['params']['oldpass']) {
        if ($oldpass != md5($this->config['params']['pwd'])) {
          $qry = "update useraccess set pwd='" . $this->config['params']['pwd'] . "',password=md5('" . $this->config['params']['pwd'] . "') where md5(userid)='" . $this->config['params']['userid'] . "'";
          $this->coreFunctions->execqry($qry);
          $this->config['return'] = ['status' => true, 'msg' => 'Update Password success'];
        } else {
          $this->config['return'] = ['status' => false, 'msg' => 'New Password is the same from Old Password...'];
        }
      } else {
        $this->config['return'] = ['status' => false, 'msg' => 'Invalid Old Password...'];
      }
    } else {
      $this->config['return'] = ['status' => false, 'msg' => 'Invalid Old Password...'];
    }
    return $this;
  } // end function

  public function qnimageupload(Request $request)
  {
    $required = ['file', 'field', 'action', 'lookupclass', 'tableid', 'doc', 'center', 'user', 'companyid', 'folder', 'table', 'fieldid', 'line'];
    $creds = $request->only($required);
    $myfile = $request->file('file');

    $hasfile = $request->hasFile('file');
    $file_ext = '';
    $filename = '';
    $data = [];
    $mainfolder = '/images/';
    $creds['line'] = $this->othersClass->val($creds['line']);
    if ($creds['line'] == 0) {
      $this->config['return'] = ['status' => false, 'msg' => 'Please save first.', 'filename' => ''];
      return;
    }
    if ($hasfile) {
      $file_ext = $myfile->extension();
      $available_ext = ["jpg", "jpeg", "png"];
      if (!in_array($file_ext, $available_ext)) {
        $msg = "Only Required Extensions are (" . $available_ext . ") [ERR_S3_001]";
        $this->config['return'] = ['status' => false, 'msg' => $msg];
      }

      $img = Image::read($myfile);
      // $img = Image::make($myfile);
      // $img->stream(); // <-- Key point

      $suff = 0;
      $pic = $this->coreFunctions->opentable("select " . $creds['field'] . " as picture from " . $creds['table'] . " where " . $creds['fieldid'] . "=? and line=?", [$creds['tableid'], $creds['line']]);
      if ($pic[0]->picture != '') {
        $filename = $pic[0]->picture;
        $pic = explode('/', $pic[0]->picture);
        $pic = end($pic);
        $pic = explode('.', $pic);
        $pic = $pic[0];
        $pic = explode('-', $pic);
        $suff = end($pic);
      }
      $filename = str_replace($mainfolder, '', $filename);
      if (Storage::disk('public')->exists($filename)) {
        Storage::disk('public')->delete($filename);
      }

      $filename = $creds['folder'] . '/' . $creds['tableid'] . '-' . ($suff + 1) . '.' . $file_ext;
      $data['picture'] = $mainfolder . $filename;

      if ($this->coreFunctions->sbcupdate($creds['table'], $data, [$creds['fieldid'] => $creds['tableid'], 'line' => $creds['line']]) == 0) {
        $msg = 'Failed to update ' . $creds['table'] . '...';
        $this->config['return'] = ['status' => false, 'msg' => $msg];
      } else {
        $status = true;
        if ($status) {
          $directory = Storage::disk('public')->put($filename, $img->encodeByExtension($myfile->getClientOriginalExtension()));
          $msg = "'Successfully uploaded.";
        }
        $this->config['return'] = ['status' => $status, 'msg' => $msg, 'filename' => $mainfolder . $filename];
      }
    }
    return $this;
  }

  private function imageupload(Request $request)
  {
    $required = ['file', 'field', 'action', 'lookupclass', 'tableid', 'doc', 'center', 'user', 'companyid', 'folder', 'table', 'fieldid'];
    $creds = $request->only($required);
    $myfile = $request->file('file');


    if ($creds['table'] == 'la_picture') {
      $creds['table'] = 'cntnum_picture';
    }

    $hasfile = $request->hasFile('file');
    $file_ext = '';
    $filename = '';
    $data = [];
    $mainfolder = '/images/';
    if ($hasfile) {
      $file_ext = $myfile->extension();
      $available_ext = ["jpg", "jpeg", "png"];
      if (!in_array($file_ext, $available_ext)) {
        $msg = "Only Required Extensions are (" . $available_ext . ") [ERR_S3_001]";
        $this->config['return'] = ['status' => false, 'msg' => $msg];
      }

      $img = Image::read($myfile);
      // $img = Image::make($myfile);
      // image becomes blurry
      // $img->resize(120, 120, function ($constraint) {
      //   $constraint->aspectRatio();
      // });
      // $img->stream(); // <-- Key point

      $suff = 0;
      $pic = $this->coreFunctions->opentable("select " . $creds['field'] . " as picture from " . $creds['table'] . " where " . $creds['fieldid'] . "=?", [$creds['tableid']]);

      if (!empty($pic)) {
        if ($pic[0]->picture != '') {
          $filename = $pic[0]->picture;
          $pic = explode('/', $pic[0]->picture);
          $pic = end($pic);
          $pic = explode('.', $pic);
          $pic = $pic[0];
          $pic = explode('-', $pic);
          $suff = end($pic);
        }
      }

      $filename = str_replace($mainfolder, '', $filename);
      if (Storage::disk('public')->exists($filename)) {
        Storage::disk('public')->delete($filename);
      }


      $filename = $creds['folder'] . '/' . $creds['tableid'] . '-' . ($suff + 1) . '.' . $file_ext;
      $data['picture'] = $mainfolder . $filename;

      $tablelog = '';
      switch ($creds['table']) {
        case 'client':
          $tablelog = 'client_log';
          break;
        case 'item':
          $tablelog = 'item_log';
          break;
        case 'cntnum_picture':
          $tablelog = 'table_log';
          $this->coreFunctions->execqry("delete from " . $creds['table'] . "  where trno=?", 'delete', [$creds['tableid']]);
          $encodeddate = $this->othersClass->getCurrentTimeStamp();
          $this->coreFunctions->sbcinsert($creds['table'], ['trno' => $creds['tableid'], 'line' => 1, 'encodeddate' => $encodeddate, 'encodedby' => $creds['user']]);
          break;
      }



      if ($this->coreFunctions->sbcupdate($creds['table'], $data, [$creds['fieldid'] => $creds['tableid']]) == 0) {
        $msg = 'Failed to update ' . $creds['table'] . '...';
        $this->config['return'] = ['status' => false, 'msg' => $msg];
      } else {
        $status = true;
        if ($creds['doc'] == "OBAPPLICATION") {
          $status = $this->coreFunctions->opentable("select initialstatus as status from " . $creds['table'] . " where " . $creds['fieldid'] . "=?", [$creds['tableid']]);
          if ($status[0]->status != 'A' || $status[0]->status == "") {
            $status = false;
            $msg = "Can't upload image while status (ENTRY or FOR APPROVAL)";
          }
        }
        if ($status) {
          // $directory = Storage::disk('public')->put($filename, $img);
          $directory = Storage::disk('public')->put($filename, $img->encodeByExtension($myfile->getClientOriginalExtension()));
          $msg = "'Successfully uploaded.";
        }
        if ($tablelog != '') {
          $this->logger->sbcwritelogimage($creds['tableid'], $tablelog, $creds['user'], 'UPLOAD', 'UPLOAD IMAGE - ' . $filename, $creds['doc']);
        }
        $this->config['return'] = ['status' => $status, 'msg' => $msg, 'filename' => $mainfolder . $filename];
      }
    }
    return $this;
  } //end function

  private function saveSignature($request)
  {
    $params = $request->all();
    $img = $params['img'];
    $filename = 'sample';
    $img = explode(',', $img);
    $resource = imagecreatefromstring(base64_decode($img[1]));
    $old_width = imagesx($resource);
    $old_height = imagesy($resource);
    $width = 200;
    $height = ($old_height / $old_width) * 200;
    $resource_copy  = imagecreatetruecolor($width, $height);
    imagealphablending($resource_copy, false);
    imagesavealpha($resource_copy, true);
    imagecopyresampled($resource_copy, $resource, 0, 0, 0, 0, $width, $height, $old_width, $old_height);
    if (Storage::disk('public')->exists('/images/signatures/' . $filename . '.png')) {
      Storage::disk('public')->delete('/images/signatures/' . $filename . '.png');
    }
    imagepng($resource_copy, public_path() . '/images/signatures/' . $filename . '.png');
    imagedestroy($resource);
    imagedestroy($resource_copy);
    $this->config['return'] = ['status' => true, 'msg' => 'saved', 'filename' => $filename];
    return $this;
  }

  private function waimsattachupload(Request $request)
  {
    $required = ['ext', 'filename', 'file', 'field', 'action', 'lookupclass', 'trno', 'doc', 'center', 'user', 'companyid', 'folder', 'table', 'fieldid', 'title', 'tmline'];
    $creds = $request->only($required);
    $myfile = $request->file('file');
    $hasfile = $request->hasFile('file');
    $file_ext = $filename = $hashfilename2 = $hashfilename = $hashfilenamef = '';
    $data = [];
    $tablelog = 'masterfile_log';
    $mainfolder = '/images/';
    if ($hasfile) {
      $file_ext = $myfile->extension();
      $hashfilename2 = $myfile->createFromBase($myfile);
      $hashfilename = $hashfilename2->getClientOriginalName();
      $hashfilenamef = str_replace('.' . $file_ext, '', $hashfilename);

      $suff = 0;
      $line = $this->coreFunctions->datareader("select line as value from " . $creds['table'] . " where trno=? order by line desc limit 1", [$creds['trno']], '', true);
      $line += 1;

      if ($creds['filename'] == '') {
        $creds['filename'] = $hashfilename;
      }
      $filename = $creds['folder'] . '/' . strtolower($creds['doc']) . '/' . $creds['trno'] . '_' . $line . '_' . $creds['filename'];
      $data['picture'] = $mainfolder . $filename;
      $data['line'] = $line;
      $data['trno'] = $creds['trno'];
      $data['doc'] = $creds['doc'];
      $data['tmline'] = $creds['tmline']; //edit for TM only
      $data['title'] = $this->othersClass->sanitizekeyfield('title', $creds['title']);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
          $img = Image::read($myfile);
          //$img->stream();
          $fileupload = Storage::disk('sbcpath')->put($filename, $img->encodeByExtension($myfile->getClientOriginalExtension()));
          //$fileupload = Storage::disk('sbcpath')->put($filename, $img);
          break;
        default:
          // $img = $myfile;
          // if (Storage::disk('sbcpath')->exists($filename)) {
          //   Storage::disk('sbcpath')->delete($filename);
          // }
          // $fileupload = Storage::disk('sbcpath')->put($filename, file_get_contents($myfile));
          if (Storage::disk('sbcpath')->exists($filename)) {
            Storage::disk('sbcpath')->delete($filename);
          }
          $fileupload = Storage::disk('sbcpath')->put($filename, file_get_contents($myfile));
          break;
      }
      if ($fileupload) {
        if ($this->coreFunctions->sbcinsert($creds['table'], $data) == 0) {
          $this->config['return'] = ['status' => false, 'msg' => 'Failed to update ' . $creds['table'] . '...'];
        } else {
          $this->logger->sbcwritelogimage($creds['trno'], $tablelog, $creds['user'], 'ATTACHMENT', 'INSERT TITLE - ' . $creds['title'], $creds['doc']);
          $this->config['return'] = ['status' => true, 'msg' => 'Successfully uploaded.', 'closemodal' => true];
        }
      } else {
        $this->config['return'] = ['status' => false, 'msg' => 'File upload error, Please try again.'];
      }
    }
    return $this;
  }

  private function noticeimageupload(Request $request)
  {
    $required = ['ext', 'filename', 'file', 'field', 'action', 'lookupclass', 'trno', 'doc', 'center', 'user', 'companyid', 'folder', 'table', 'fieldid', 'title'];
    $creds = $request->only($required);
    $myfile = $request->file('file');
    $hasfile = $request->hasFile('file');
    $file_ext = $filename = $hashfilename2 = $hashfilename = $hashfilenamef = '';
    $data = [];
    $mainfolder = '/images/';
    if ($hasfile) {
      $file_ext = $myfile->extension();
      $hashfilename2 = $myfile->createFromBase($myfile);
      $hashfilename = $hashfilename2->getClientOriginalName();
      $hashfilenamef = str_replace('.' . $file_ext, '', $hashfilename);

      $suff = 0;
      $line = $this->coreFunctions->datareader("select line as value from " . $creds['table'] . " where trno=? order by line desc limit 1", [$creds['trno']], '', true);
      $line += 1;

      if ($creds['filename'] == '') $creds['filename'] = $hashfilename;
      $filename = $creds['folder'] . '/' . $creds['trno'] . '_' . $line . '_' . $creds['filename'];
      $data['picture'] = $mainfolder . $filename;
      $data['line'] = $line;
      $data['trno'] = $creds['trno'];
      $data['title'] = $this->othersClass->sanitizekeyfield('title', $creds['title']);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
          // $img = Image::make($myfile);
          // $img->stream();
          $img = Image::read($myfile);
          // $fileupload = Storage::disk('sbcpath')->put($filename, $img);
          $fileupload = Storage::disk('sbcpath')->put($filename, $img->encodeByExtension($myfile->getClientOriginalExtension()));
          break;
        default:
          $img = $myfile;
          if (Storage::disk('sbcpath')->exists($filename)) {
            Storage::disk('sbcpath')->delete($filename);
          }
          $fileupload = Storage::disk('sbcpath')->put($filename, file_get_contents($myfile));
          break;
      }
      if ($fileupload) {
        if ($this->coreFunctions->sbcinsert($creds['table'], $data) == 0) {
          $this->config['return'] = ['status' => false, 'msg' => 'Failed to update ' . $creds['table'] . '...'];
        } else {
          $this->logger->sbcwritelogimage($creds['trno'], $creds['table'], $creds['user'], 'ATTACHMENT', 'INSERT TITLE - ' . $creds['title'], $creds['doc']);
          $this->config['return'] = ['status' => true, 'msg' => 'Successfully uploaded.', 'closemodal' => true];
        }
      } else {
        $this->config['return'] = ['status' => false, 'msg' => 'File upload error, Please try again.'];
      }
    }
    return $this;
  }

  private function imageuploadmodule(Request $request)
  {
    //need to change in php.ini to increase size
    //upload_max_filesize
    //post_max_size
    $required = ['ext', 'filename', 'file', 'field', 'action', 'lookupclass', 'tableid', 'doc', 'center', 'user', 'companyid', 'folder', 'table', 'fieldid', 'title'];
    $creds = $request->only($required);
    // var_dump($creds);
    $myfile = $request->file('file');

    $hasfile = $request->hasFile('file');
    $file_ext = '';
    $filename = '';
    $hashfilename2 = '';
    $hashfilename = '';
    $data = [];
    $mainfolder = '/images/modules/';
    if ($hasfile) {
      $file_ext = $myfile->extension();
      $hashfilename2 = $myfile->createFromBase($myfile);
      $hashfilename = $hashfilename2->getClientOriginalName();
      $hashfilenamef = str_replace('.' . $file_ext, '', $hashfilename);

      $suff = 0;
      if ($creds['table'] == 'leave_picture') {
        $qry = "select max(line) as value from " . $creds['table'] . "";
        $line = $this->coreFunctions->datareader($qry);
      } else {
        $qry = "select line as value from " . $creds['table'] . " where trno=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$creds['tableid']]);
      }
      $this->coreFunctions->LogConsole($qry);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;

      // $filename = $creds['folder'] . '/' . $line . '_' . $creds['filename'];
      if ($creds['table'] == 'reqcategory') {
        if ($creds['filename'] == '') {
          $creds['filename'] =  $hashfilename;
        }
        $filename = $creds['folder'] . '/' . $creds['tableid'] . '_' . $line . '_' . $creds['filename'];
        $data['picpath'] = $mainfolder . $filename;
        $data['filename'] = $creds['filename'];
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $creds['user'];
      } else {
        if ($creds['filename'] == '') {
          $creds['filename'] =  $hashfilename;
        }
        $filename = $creds['folder'] . '/' . $creds['tableid'] . '_' . $line . '_' . $creds['filename'];
        $data['picture'] = $mainfolder . $filename;
        $data['line'] = $line;
        $data['trno'] = $creds['tableid'];
        if ($creds['table'] == 'leave_picture') {
          if ($creds['tmline'] != null || $creds['tmline'] != 0) {
            $data['ltline'] = $creds['tmline']; //leaveapp
          }
        }
        $data['title'] = $this->othersClass->sanitizekeyfield('title', $creds['title']);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['encodeddate'] = $current_timestamp;
        $data['encodedby'] = $creds['user'];
      }


      switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
          $img = Image::read($myfile);
          // $img = Image::make($myfile);
          // $img->stream(); // <-- Key point
          break;
        default:
          $img = $myfile;
          break;
      }

      $table = 'table_log';
      switch ($creds['table']) {
        case 'transnum_picture':
          $table = 'transnum_log';
          break;
        case 'hrisnum_picture':
          $table = 'hrisnum_log';
          break;
        case 'docunum_picture':
          $table = 'docunum_log';
          break;
        case 'client_picture':
          $table = 'client_log';
          break;
        case 'app_picture':
          $table = 'masterfile_log';
          break;
        case 'loan_picture':
        case 'leave_picture':
          $table = 'payroll_log';
          break;
      }
      $this->coreFunctions->LogConsole("table:" . $table);
      switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
          // $fileupload = Storage::disk('sbcpath')->put('/modules/' . $filename, $img);
          $fileupload = Storage::disk('sbcpath')->put('/modules/' . $filename, $img->encodeByExtension($myfile->getClientOriginalExtension()));
          break;
        default:
          // $path = database_path() . $mainfolder . $creds['folder'];
          //$img->move($path,$hashfilename.'_'.$creds['tableid'].'_'.$line.'.'.$file_ext );  
          if (Storage::disk('sbcpath')->exists('/modules/' . $filename)) {
            Storage::disk('sbcpath')->delete('/modules/' . $filename);
          }
          $fileupload = Storage::disk('sbcpath')->put('/modules/' . $filename, file_get_contents($myfile));
          // $img->move($path, $line . '_' . $creds['filename']);
          break;
      }
      if ($fileupload) {
        $return = $this->coreFunctions->sbcinsert($creds['table'], $data);
        if (!$return) {
          $this->config['return'] = ['status' => false, 'msg' => 'Failed to update ' . $creds['table'] . '...'];
        } else {
          switch ($table) {
            case 'payroll_log':
              $this->logger->sbcmasterlog2($creds['tableid'], $this->config, 'ADD ATTACHMENTS -' . $creds['title'], $table);
              break;
            default:
              $this->logger->sbcwritelogimage($creds['tableid'], $table, $creds['user'], 'ATTACHMENT', 'INSERT TITLE - ' . $creds['title'], $creds['doc']);
              break;
          }

          $this->config['return'] = ['status' => true, 'msg' => 'Successfully uploaded.', 'closemodal' => true];
        }
      } else {
        $this->config['return'] = ['status' => false, 'msg' => 'File upload error, Please try again.'];
      }

      // if ($this->coreFunctions->sbcinsert($creds['table'], $data) == 0) {
      //   $msg = 'Failed to update ' . $creds['table'] . '...';
      //   $this->config['return'] = ['status' => false, 'msg' => $msg];
      // } else {
      //   $fileupload = false;
      //   $this->logger->sbcwritelogimage($creds['tableid'], $table, $creds['user'], 'ATTACHMENT', 'INSERT TITLE - ' . $creds['title']);
      //   switch ($file_ext) {
      //     case 'jpg':
      //     case 'jpeg':
      //     case 'png':
      //       $fileupload = Storage::disk('sbcpath')->put('/modules/' . $filename, $img);
      //       break;
      //     default:
      //       $path = database_path() . $mainfolder . $creds['folder'];
      //       //$img->move($path,$hashfilename.'_'.$creds['tableid'].'_'.$line.'.'.$file_ext );  
      //       if(Storage::disk('sbcpath')->exists('/modules/'.$filename)) {
      //         Storage::disk('sbcpath')->delete('/modules/'.$filename);
      //       }
      //       $fileupload = Storage::disk('sbcpath')->put('/modules/'.$filename, file_get_contents($myfile));
      //       // $img->move($path, $line . '_' . $creds['filename']);
      //       break;
      //   }
      //   if($fileupload) {
      //     $this->config['return'] = ['status' => true, 'msg' => 'Successfully uploaded.'];
      //   } else {
      //     $this->config['return'] = ['status'=>false, 'msg'=>'File upload error, Please try again.'];
      //   }
      // }
    }
    return $this;
  } //end function

  private function imageuploadtblentry(Request $request)
  {
    //need to change in php.ini to increase size
    //upload_max_filesize
    //post_max_size
    $required = ['ext', 'filename', 'file', 'field', 'action', 'lookupclass', 'tableid', 'doc', 'center', 'user', 'companyid', 'folder', 'table', 'fieldid', 'title'];
    $creds = $request->only($required);
    $myfile = $request->file('file');

    $hasfile = $request->hasFile('file');
    $file_ext = '';
    $filename = '';
    $hashfilename2 = '';
    $hashfilename = '';
    $data = [];
    $mod = strtolower($this->companysetup->getsystemtype($this->config['params']));
    //$mainfolder = '/' . $mod . '/';
    $mainfolder = '/' . $mod . '/';
    if ($hasfile) {
      $file_ext = $myfile->extension();
      $hashfilename2 = $myfile->createFromBase($myfile);
      $hashfilename = $hashfilename2->getClientOriginalName();
      $hashfilenamef = str_replace('.' . $file_ext, '', $hashfilename);

      $suff = 0;
      $line =  1;

      // $filename = $creds['folder'] . '/' . $line . '_' . $creds['filename'];
      if ($creds['filename'] == '') {
        $creds['filename'] =  $hashfilename;
      }
      $filename = $creds['folder'] . '/' . $creds['tableid'] . '_' . $line . '_' . $creds['filename'];
      $data['picpath'] = '/images'. $mainfolder . $filename;
      $data['filename'] = $creds['filename'];
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      $data['editdate'] = $current_timestamp;
      $data['editby'] = $creds['user'];


      switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
          $img = Image::read($myfile);
          // $img = Image::make($myfile);
          // $img->stream(); // <-- Key point
          break;
        default:
          $img = $myfile;
          break;
      }

      $table = 'masterfile_log';
      $this->coreFunctions->LogConsole("table:" . $table);
      switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
          //$fileupload = Storage::disk('sbcpath')->put('/modules/' . $filename, $img);
          $fileupload = Storage::disk('public')->put($mainfolder . $filename, $img->encodeByExtension($myfile->getClientOriginalExtension()));
          break;
        default:
          //$img->move($path,$hashfilename.'_'.$creds['tableid'].'_'.$line.'.'.$file_ext );  
          if (Storage::disk('public')->exists($mainfolder . $filename)) {
            Storage::disk('public')->delete($mainfolder .  $filename);
          }
          $fileupload = Storage::disk('public')->put($mainfolder . $filename, file_get_contents($myfile));
          // $img->move($path, $line . '_' . $creds['filename']);
          break;
      }

      if ($fileupload) {
        $return = $this->coreFunctions->sbcupdate($creds['table'], $data, ["line" => $creds['tableid']]);
        if (!$return) {
          $this->config['return'] = ['status' => false, 'msg' => 'Failed to update ' . $creds['table'] . '...'];
        } else {
          $this->logger->sbcwritelogimage($creds['tableid'], $table, $creds['user'], 'ATTACHMENT', 'INSERT TITLE - ' . $creds['title'], $creds['doc']);

          $this->config['return'] = ['status' => true, 'msg' => 'Successfully uploaded.', 'closemodal' => true];
        }
      } else {
        $this->config['return'] = ['status' => false, 'msg' => 'File upload error, Please try again.'];
      }
    }
    return $this;
  } //end function

  public function imagefilename()
  {
    switch ($this->config['params']['type']) {
      case md5('module'):
        switch ($this->config['params']['id2']) {
          case md5('viewmanual'):
            return $this->getmanualmodule();
            break;
          default:
            return $this->getfilenamemodule();
            break;
        }
        break;
      case md5('message'):
        return $this->getfilenamemessage();
        break;
    }
  }

  public function attachmentdownload(Request $request)
  {
    switch ($this->config['params']['type']) {
      case md5('module'):
        switch ($this->config['params']['id2']) {
          case md5('viewmanual'):
            return $this->manualdownload($request);
            break;
          default:
            return $this->imagedownload($request);
            break;
        }
        break;
      case md5('message'):
        return $this->messagedownload($request);
        break;
    }
  }

  public function manualdownload(Request $request)
  {
    $path = $this->companysetup->getmanualpath($this->config['params']);
    return $this->getimagebypath($path . $this->config['params']['lookupclass'] . '.pdf');
  }

  public function imagedownload(Request $request)
  {

    $table = '';
    $id = $this->config['params']['id2'];
    $trno = $this->config['params']['trno'];
    $line = $this->config['params']['line'];
    $condition = "";
    $filter = " where md5(trno)=? ";
    switch ($id) {
      case md5('cp'):
        $table = 'cntnum_picture';
        $log = 'table_log';
        break;
      case md5('tp'):
        $table = 'transnum_picture';
        $log = 'transnum_log';
        break;
      case md5('hp'):
        $table = 'hrisnum_picture';
        $log = 'hrisnum_log';
        break;
      case md5('dp'):
        $table = 'docunum_picture';
        $log = 'docunum_log';
        break;
      case md5('ledger'):
        $table = 'client_picture';
        $log = 'client_log';
        break;
      case md5('app'):
        $table = 'app_picture';
        $log = 'masterfile_log';
        break;
      case md5('notice'):
        $table = 'waims_attachments';
        $log = 'masterfile_log';
        break;
      case md5('loanapplicationportal'):
        $table = 'loan_picture';
        $log = 'payroll_log';
        $condition = " and md5(line) = '$line' ";
        break;
      case md5('leaveapplicationportal'):
        $table = 'leave_picture';
        $log = 'payroll_log';
        $condition = " and md5(line) = '$line' ";
        $filter = "where md5(ltline) =?";
        break;
    }
    $qry = "select trno,title from " . $table . " $filter $condition limit 1";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      switch ($log) {
        case 'payroll_log':
          switch ($table) {
            case 'leave_picture':
              $this->config['params']['doc'] = 'LEAVEAPPLICATIONPORTAL';
              break;
            case 'loan_picture':
              $this->config['params']['doc'] = 'LOANAPPLICATIONPORTAL';
              break;
          }
          $this->logger->sbcmasterlog2($data[0]->trno, $this->config, 'ATTACHMENT - DOWNLOAD TITLE ' . $data[0]->title, $log);
          break;
        default:
          // if ($table == 'waims_attachments') {
          //   $this->config['params']['doc'] = '';
          // }
          $this->logger->sbcwritelogimage($data[0]->trno, $log, $this->config['params']['user'], 'ATTACHMENT', 'DOWNLOAD TITLE - ' . $data[0]->title,'');
          break;
      }
    }


    $qry = "select picture as value from " . $table . " $filter and md5(line) =? limit 1";
    $path = $this->coreFunctions->datareader($qry, [$trno, $line]);
    return $this->getimagebypath($path);
  } //end function


  public function messagedownload(Request $request)
  {
    $msgid = $this->config['params']['pathid'];
    $qry = "select attach as value from conversation_msg where md5(msg_id)=? limit 1";
    $path = $this->coreFunctions->datareader($qry, [$msgid]);
    return $this->getimagebypath($path);
  }

  public function getfilenamemessage()
  {
    $msgid = $this->config['params']['pathid'];
    $qry = "select attach as value from conversation_msg where md5(msg_id)=? limit 1";
    $path = $this->coreFunctions->datareader($qry, [$msgid]);
    return $this->getfilename($path);
  }

  public function getmanualmodule()
  {
    $path = $this->companysetup->getmanualpath($this->config['params']);

    return $this->getfilename($path . '/' . $this->config['params']['lookupclass'] . '.pdf');
  }

  public function getfilenamemodule()
  {
    $table = '';
    $id = $this->config['params']['id2'];
    $trno = $this->config['params']['trno'];
    $line = $this->config['params']['line'];
    $filter = " md5(trno)=? and md5(line) =? ";
    switch ($id) {
      case md5('cp'):
        $table = 'cntnum_picture';
        break;
      case md5('tp'):
        $table = 'transnum_picture';
        break;
      case md5('hp'):
        $table = 'hrisnum_picture';
        break;
      case md5('dp'):
        $table = 'docunum_picture';
        break;
      case md5('ledger'):
        $table = 'client_picture';
        break;
      case md5('app'):
        $table = 'app_picture';
        break;
      case md5('notice'):
        $table = 'waims_attachments';
        break;
      case md5('loanapplicationportal'):
        $table = 'loan_picture';
        break;
      case md5('leaveapplicationportal'):
        $filter = "md5(ltline)=? and md5(line) =?";
        $table = 'leave_picture';
        break;
    }
    $qry = "select picture as value from " . $table . " where $filter limit 1";
    $this->coreFunctions->LogConsole($qry . ' ' . $trno . ' ' . $line);
    $path = $this->coreFunctions->datareader($qry, [$trno, $line]);
    return $this->getfilename($path);
  }

  private function getfilename($path)
  {
    if ($path == '') {
      return ['status' => false, 'msg' => 'Failed to download'];
    } else {
      $pic = explode('/', $path);
      $pic = end($pic);
      $pic = explode('.', $pic);
      $ext = $pic[1];
      $id = Str::random(9);
      $filename = $pic[0] . '.' . $ext;
      return ['status' => true, 'msg' => 'Success', 'filename' => $filename];
    }
  }



  public function getimagebypath($path)
  {
    if ($path == '') {
      return ['status' => false, 'msg' => 'Failed to download'];
    } else {
      $headers = array(
        'Content-Type: ' . mime_content_type(database_path() . $path),
      );
      $pic = explode('/', $path);
      $pic = end($pic);
      $pic = explode('.', $pic);
      $ext = $pic[1];
      $filename = $pic[0] . '.' . $ext;
      return response()->download(database_path() . $path, $filename, $headers, 'attachment');
    }
  }

  public function execute()
  {
    return response()->json($this->config['return'], 200);
  } // end function






































} // end class
