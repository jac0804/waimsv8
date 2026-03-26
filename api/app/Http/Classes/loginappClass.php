<?php

namespace App\Http\Classes;

use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

use Exception;
use Throwable;

class loginappClass
{

  private $othersClass;
  private $coreFunctions;
  private $logger;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->logger = new Logger;
  }

  public function downloadUserImages($params)
  {
    $pics = $this->coreFunctions->opentable("select clientid as empcode, picture as img from client where isemployee=1 and picture<>'' order by clientid");
    if (!empty($pics)) {
      foreach ($pics as $p) {
        $ext = explode('.', $p->img);
        $ext = $ext[1];
        $data = file_get_contents(Storage::disk('public')->url(substr($p->img, 1)));
        $p->img = 'data:image/' . $ext . ';base64,' . base64_encode($data);
      }
    }
    return json_encode(['images' => $pics]);
  }

  public function downloadAccounts($params)
  {
    $date = $this->othersClass->getCurrentTimeStamp();
    $users = $this->coreFunctions->opentable("select c.clientid as empcode, c.client, c.email, c.clientname as name, c.password, case c.isinactive when 1 then 0 else 1 end as isactive, emp.idbarcode from client as c left join employee as emp on emp.empid=c.clientid where c.isemployee=1 order by c.clientid");
    return json_encode(['users' => $users, 'date' => $date]);
  }

  public function uploadLog($params)
  {
    $data = $params['data'];
    $msg = '';
    $status = false;
    $datenow = $this->othersClass->getCurrentTimeStamp();
    switch ($data['type']) {
      case 'timein':
        $data['mode'] = 'IN';
        break;
      case 'timeout':
        $data['mode'] = 'OUT';
        break;
    }
    if ($data['dateid'] != '') {
      $data['dateid'] = date('Y-m-d', strtotime($data['dateid']));
    }
    if ($data['time'] != '') {
      $data['time'] = date('H:i:s', strtotime($data['time']));
    }
    $data['idbarcode'] = $this->coreFunctions->getfieldvalue('employee', 'idbarcode', 'empid=?', [$data['id']]);
    $empname = $this->coreFunctions->getfieldvalue('client', 'clientname', 'clientid=?', [$data['id']]);
    $tr = $this->coreFunctions->opentable("select userid from timerec where userid='" . $data['idbarcode'] . "' and date(curdate)='" . $data['dateid'] . "' and timeinout='" . $data['dateid'] . " " . $data['time'] . "' and `mode`='" . $data['mode'] . "'");
    if (empty($tr)) {
      if ($this->coreFunctions->execqry("insert into timerec(userid, timeinout, `mode`, curdate) values(?, ?, ?, ?)", 'insert', [$data['idbarcode'], $data['dateid'] . ' ' . $data['time'], $data['mode'], $data['dateid']]) > 0) {
        if ($data['pic'] != '') {
          $this->saveImage($data);
        }
        $tokens = $this->coreFunctions->opentable("select dtoken from employee where dtoken<>''");
        if (!empty($tokens)) {
          foreach ($tokens as $token) {
            $ndata = [
              'title' => 'Employee ' . $empname . ' Logged-' . ($data['mode'] == 'IN' ? 'in' : 'out'),
              'body' => 'Date: ' . $data['dateid'] . ' Time: ' . $data['time']
            ];
            // =================== DO NOT REMOVE ====================
            // temporary disable notification - 2025-12-23 - FRED
            // $this->othersClass->sendNotif($token->dtoken, $ndata);
          }
        }
        $msg = "Record saved.";
        $status = true;
      } else {
        $msg = "Error saving record.";
      }
    } else {
      $msg = "Duplicate entry, Record not saved.";
    }
    return json_encode(['msg' => $msg, 'status' => $status, 'date' => $datenow]);
  }

  public function saveImage($data)
  {
    $image_64 = 'data:image/jpeg;base64,' . $data['pic']; //your base64 encoded data
    $timestamp = strtotime($data['dateid'] . ' ' . $data['time']);
    $filename = '/loginpics/' . $data['id'] . '-' . $data['mode'] . '-' . $timestamp . '.jpg';
    $data['idbarcode'] = $this->coreFunctions->getfieldvalue('employee', 'idbarcode', 'empid=?', [$data['id']]);
    $img = substr($image_64, strpos($image_64, ',') + 1);
    $img = base64_decode($img);
    if (Storage::disk('public')->exists($filename)) {
      Storage::disk('public')->delete($filename);
    }
    Storage::disk('public')->put($filename, $img);
    $this->coreFunctions->execqry("update timerec set picture='" . $filename . "' where userid='" . $data['idbarcode'] . "' and date(curdate)='" . $data['dateid'] . "' and `mode`='" . $data['mode'] . "'", 'update');
    // $this->coreFunctions->execqry("insert into loginpic(dateid, mode, idbarcode, picture) values(?, ?, ?, ?)", 'insert', [$data['dateid'].' '.$data['time'], $data['mode'], $data['id'], $filename]);
  }

  public function uploadLogs($params)
  {
    $data = $params['data'];
    $res = [];
    $mode = '';
    $time = '';
    $pic = '';
    $datenow = $this->othersClass->getCurrentTimeStamp();
    foreach ($data as $key => $d) {
      $mode = '';
      $a = $this->othersClass->sanitize($d, 'ARRAY');
      if ($a['timein'] != '') {
        $return = $this->saveLog($a, 'IN');
        $res[$key]['success'] = $return['success'];
        $res[$key]['line'] = $return['line'];
        $res[$key]['msg'] = $return['msg'];
      }
      if ($a['timeout'] != '') {
        $return = $this->saveLog($a, 'OUT');
        $res[$key]['success'] = $return['success'];
        $res[$key]['line'] = $return['line'];
        $res[$key]['msg'] = $return['msg'];
      }
    }
    return json_encode(['data' => $res, 'date' => $datenow]);
  }

  public function saveLog($data, $mode)
  {
    $time = '';
    switch ($mode) {
      case 'IN':
        $time = $data['timein'];
        $pic = $data['inPic'];
        break;
      case 'OUT':
        $time = $data['timeout'];
        $pic = $data['outPic'];
        break;
    }
    if ($data['dateid'] != '') {
      $data['dateid'] = date('Y-m-d', strtotime($data['dateid']));
    }
    if ($time != '') {
      $time = date('H:i:s', strtotime($time));
    }
    $data['idbarcode'] = $this->coreFunctions->getfieldvalue('employee', 'idbarcode', 'empid=?', [$data['id']]);
    $tr = $this->coreFunctions->opentable("select userid from timerec where userid='" . $data['idbarcode'] . "' and date(curdate)='" . $data['dateid'] . "' and timeinout='" . $data['dateid'] . " " . $time . "' and `mode`='" . $mode . "'");
    if (empty($tr)) {
      if ($this->coreFunctions->execqry("insert into timerec(userid, timeinout, `mode`, curdate) values(?, ?, ?, ?)", 'insert', [$data['idbarcode'], $data['dateid'] . ' ' . $time, $mode, $data['dateid']]) > 0) {
        if ($pic != '') {
          $this->saveImage(['id' => $data['id'], 'mode' => $mode, 'dateid' => $data['dateid'], 'time' => $time, 'pic' => $pic]);
        }
        return ['success' => true, 'line' => $data['line'], 'msg' => ''];
      } else {
        return ['success' => false, 'line' => $data['line'], 'msg' => 'Error saving record, try to reupload.'];
      }
    } else {
      return ['success' => false, 'msg' => 'Upload error: Duplicate record for ' . $data['dateid'], 'line' => ''];
    }
  }
} // end class
