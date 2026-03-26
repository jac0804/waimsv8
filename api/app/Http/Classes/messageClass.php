<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Exception;
use Throwable;
use Session;



class messageClass
{
  private $othersClass;
  private $coreFunctions;
  private $headClass;
  private $logger;
  private $lookupClass;
  private $companysetup;
  private $config = [];
  private $sqlquery;
  private $fieldClass;
  private $tabClass;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->headClass = new headClass;
    $this->logger = new Logger;
    $this->lookupClass = new lookupClass;
    $this->companysetup = new companysetup;
    $this->sqlquery = new sqlquery;
    $this->tabClass = new tabClass;
    $this->fieldClass = new txtfieldClass;
  }

  public function sbc($params)
  {
    $this->config['params'] = $params;
    return $this;
  }


  private function loadcompose()
  {
    $fields = ['msggroup', 'clientlist', 'subjectname', 'wysiwygrem'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'subjectname.label', 'Subject');
    data_set($col1, 'wysiwygrem.height', '10rem');
    data_set($col1, 'wysiwygrem.label', 'Message');
    data_set($col1, 'wysiwygrem.style', 'width:100%;');

    $obj['compose']['cols'] = ['col' => $col1];
    $obj['compose']['data'] = $this->coreFunctions->opentable("select '' as msggroup,'' as clientlist, 0 as clientid,'' as rem,'' as subjectname ");

    $this->config['return'] = ['status' => true, 'msg' => 'Successfully loaded.', 'sbcobj' => $obj];
    return $this;
  }


  public function messagestatus(Request $request)
  {
    switch ($this->config['params']['action']) {
      case 'getclient':
        $this->getclient();
        break;
      case 'loadforms':
        $this->loadcompose();
        break;
      case 'getmsglist':
        $this->getmsglist();
        break;
      case 'sendmsg':
        $this->sendmsg($request);
        break;
      case 'getmsgdata':
        $data = $this->getmsgdata();
        $msgcount = $this->getmsgcount();
        $this->config['return'] = ['status' => true, 'msg' => 'Load data success', 'data' => $data, 'msgcount' => $msgcount];
        break;
      case 'getconvodata':
        $this->getconversation();
        break;
      case 'reply':
        $this->reply($request);
        break;
      case 'delete':
        $this->delete();
        break;
    }

    return $this;
  } //end function

  private function getmsgcount()
  {
    $qry = "
      select count(msguser.is_view) as value
      from conversation_user as msguser
      left join conversation_msg as msg on msg.conversation_id=msguser.conversation_id
      where msguser.user_id = " . $this->config['params']['admin_id'] . " and msg.start=1 and msguser.trash=0 and msguser.is_view=0 
      ";
    return $this->coreFunctions->datareader($qry);
  }

  private function delete()
  {
    $data['trash'] = 1;
    $this->coreFunctions->sbcupdate('conversation_user', $data, ['conversation_id' => $this->config['params']['con_id'], 'user_id' => $this->config['params']['admin_id']]);
    $this->config['return'] = ['status' => true, 'msg' => 'Transfer to Trash Success'];
    return $this;
  } //end function

  private function getmsglist()
  {
    $getcols = ['action', 'listclientname', 'subjectname', 'attachment', 'postdate', 'qa'];
    $stockbuttons = ['view', 'delete'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $cols[2]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $cols[5]['label'] = 'Reply';
    $cols[5]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';


    $data = $this->getmsgdata();
    $msgcount = $this->getmsgcount();
    $this->config['return'] = ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'cols' => $cols, 'msgcount' => $msgcount];
    return $this;
  } //end function


  private function getmsgdata()
  {
    switch ($this->config['params']['folders']) {
      case 'inbox':
        $sql = "select msguser.is_view,msguser.conversation_id,msg.subject as subjectname,info.modifydate as postdate,client.clientname,
        case when msg.attach='' then 'A' else 'B' end as attachment,
        (select count(conuser.conversation_id) from conversation_msg as conuser where conuser.conversation_id=msg.conversation_id and conuser.start=0) as qa
        from conversation_user as msguser
        left join conversation_msg as msg on msg.conversation_id=msguser.conversation_id 
        left join conversation_msg_info as info on info.id=msg.conversation_id
        left join client on client.clientid=msg.user_id 
        where msguser.user_id = ? and msg.start=1 and msguser.trash=0 group by msg.attach,msguser.is_view,msguser.conversation_id,msg.subject,info.modifydate,client.clientname,msg.conversation_id order by info.modifydate desc";
        break;
      case 'send':
        $sql = "select msguser.is_view,msguser.conversation_id,msg.subject as subjectname,info.modifydate as postdate,client.clientname,
        case when msg.attach='' then 'A' else 'B' end as attachment,
        (select count(conuser.conversation_id) from conversation_msg as conuser where conuser.conversation_id=msg.conversation_id and conuser.start=0) as qa
        from conversation_msg as msg 
        left join conversation_user as msguser on msguser.conversation_id = msg.conversation_id and msguser.user_id = msg.user_id
        left join conversation_msg_info as info on info.id=msg.conversation_id
        left join client on client.clientid=msg.user_id 
        where msg.user_id = ? and msg.start=1 and msguser.trash=0 group by msg.attach,msguser.is_view,msguser.conversation_id,msg.subject,info.modifydate,client.clientname,msg.conversation_id order by info.modifydate desc";
        break;
      case 'trash':
        $sql = "select msguser.is_view,msguser.conversation_id,msg.subject as subjectname,info.modifydate as postdate,client.clientname,
        case when msg.attach='' then 'A' else 'B' end as attachment,
        (select count(conuser.conversation_id) from conversation_msg as conuser where conuser.conversation_id=msg.conversation_id and conuser.start=0) as qa
        from conversation_user as msguser
        left join conversation_msg as msg on msg.conversation_id=msguser.conversation_id 
        left join conversation_msg_info as info on info.id=msg.conversation_id
        left join client on client.clientid=msg.user_id 
        where msguser.user_id = ? and msg.start=1 and msguser.trash=1 group by msg.attach,msguser.is_view,msguser.conversation_id,msg.subject,info.modifydate,client.clientname,msg.conversation_id order by info.modifydate desc";
        break;
    }
    return $this->coreFunctions->opentable($sql, [$this->config['params']['admin_id']]);
  } // end function


  private function getconversation()
  {
    $sql = '
        select md5(msg.msg_id) as msgid,msg.user_id,msg.msg,msg.createdate,client.clientname,right(msg.attach,5) as attach
        from conversation_msg as msg
        left join client on client.clientid=msg.user_id
        where msg.conversation_id = ?        
     ';
    $data = $this->coreFunctions->opentable($sql, [$this->config['params']['con_id']]);

    $this->coreFunctions->sbcupdate('conversation_user', ['is_view' => 1], ['conversation_id' => $this->config['params']['con_id'], 'user_id' => $this->config['params']['admin_id']]);

    $this->config['return'] = ['status' => true, 'msg' => 'Load Conversation Success', 'data' => $data];
    return $this;
  } //end function


  private function sendmsg(Request $request)
  {
    if ($this->config['params']['msggroup'] == '') {
      $this->config['return'] = ['status' => false, 'msg' => 'Sorry pls select Group first...'];
      return $this;
    }
    if ($this->config['params']['adminid'] == 0) {
      $this->config['return'] = ['status' => false, 'msg' => 'Sorry not allowed to send messages...'];
      return $this;
    }
    $userid = $this->config['params']['adminid'];
    $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'md5(clientid)=?', [$this->config['params']['clientid']]);
    $msg = [];
    $info = [];
    $detail = [];
    $user_type = 0;
    $filter = '';
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    switch ($this->config['params']['msggroup']) {
      case 'ADMIN':
        $user_type = 1;
        $filter = " isadmin = 1 and ";
        break;
      case 'EMPLOYEE':
        $user_type = 2;
        $filter = " isemployee = 1 and ";
        break;
      case 'STUDENT':
        $user_type = 3;
        $filter = " isstudent = 1 and ";
        break;
    }
    $msg['user_id'] = $userid;
    $msg['user_type'] = $user_type;
    $msg['subject'] = $this->othersClass->sanitizekeyfield('subjectname', $this->config['params']['subjectname']);
    $msg['msg'] = $this->othersClass->sanitizekeyfield('rem', $this->config['params']['rem']);
    $msg['start'] = 1;
    $msg['createdate'] = $current_timestamp;

    $info['createdate'] = $current_timestamp;
    $info['modifydate'] = $current_timestamp;
    $info['status'] = 0;
    $info['draft'] = 0;
    $info['fav_status'] = 0;
    $infoid = $this->coreFunctions->insertGetId('conversation_msg_info', $info);


    $msg['conversation_id'] = $infoid;
    $msg_id = $this->coreFunctions->insertGetId('conversation_msg', $msg);

    $detail['conversation_id'] = $infoid;
    $detail['user_id'] = $userid;
    $detail['user_type'] = $user_type;
    $detail['is_sender'] = 1;
    $detail['trash'] = 0;
    $detail['is_view'] = 0;
    $this->coreFunctions->sbcinsert('conversation_user', $detail);
    // send to specific person
    if ($clientid !== '') {
      $detail['user_id'] = $clientid;
      $detail['is_sender'] = 0;
      $this->coreFunctions->sbcinsert('conversation_user', $detail);
    } else {
      // send to all member of the group
      $list = $this->coreFunctions->opentable('select clientid from client where ' . $filter . ' clientid<>' . $userid);
      $list = json_decode(json_encode($list), true);
      foreach ($list as $key) {
        $detail['user_id'] = $key['clientid'];
        $detail['is_sender'] = 0;
        $this->coreFunctions->sbcinsert('conversation_user', $detail);
      }
    }
    if ($this->config['params']['file'] === '') {
      $returnmsg = 'Email send...';
    } else {
      $result = $this->imageuploadmodule($request, $msg_id);
      if ($result['status']) {
        $returnmsg = 'Email send...';
      } else {
        $returnmsg = $result['msg'];
      }
    }
    $this->config['return'] = ['status' => true, 'msg' => $returnmsg];
    return $this;
  } //end function


  private function reply(Request $request)
  {
    if ($this->config['params']['adminid'] === 0) {
      return ['status' => false, 'msg' => 'Sorry not allowed to send messages...'];
    }
    $userid = $this->config['params']['adminid'];
    $msg = [];
    $user_type = 0;
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $user_type = $this->coreFunctions->getfieldvalue('conversation_msg', 'user_type', 'conversation_id=? and start=1 ', [$this->config['params']['con_id']]);
    $msg['user_id'] = $userid;
    $msg['user_type'] = $user_type;
    $msg['subject'] = '';
    $msg['msg'] = $this->othersClass->sanitizekeyfield('rem', $this->config['params']['rem']);
    $msg['start'] = 0;
    $msg['createdate'] = $current_timestamp;
    $msg['conversation_id'] = $this->config['params']['con_id'];
    $msg_id = $this->coreFunctions->insertGetId('conversation_msg', $msg);

    $this->coreFunctions->sbcupdate('conversation_msg_info', ['modifydate' => $current_timestamp], ['id' => $this->config['params']['con_id']]);
    $this->coreFunctions->sbcupdate('conversation_user', ['is_view' => 0], ['conversation_id' => $this->config['params']['con_id']]);

    if ($this->config['params']['file'] === '') {
      $returnmsg = 'Email send...';
    } else {
      $result = $this->imageuploadmodule($request, $msg_id);
      if ($result['status']) {
        $returnmsg = 'Email send...';
      } else {
        $returnmsg = $result['msg'];
      }
    }
    $this->config['return'] = ['status' => true, 'msg' => $returnmsg];
    return $this;
  } //end function


  private function imageuploadmodule(Request $request, $msg_id)
  {
    $myfile = $request->file('file');
    $hasfile = $request->hasFile('file');
    $file_ext = '';
    $filename = '';
    $hashfilename = '';
    $hashfilename2 = '';
    $data = [];
    $mainfolder = '/images/message/';
    $subfolder = 'attachment/';
    if ($hasfile) {
      $file_ext = $myfile->extension();
      $hashfilename2 = $myfile->createFromBase($myfile);
      $hashfilename = $hashfilename2->getClientOriginalName();
      $hashfilename = str_replace('.' . $file_ext, '', $hashfilename);


      $filename = $hashfilename . '-' . $msg_id . '.' . $file_ext;
      $data['attach'] = $mainfolder . $subfolder . $filename;

      switch ($file_ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
          if ($myfile->getSize() > 2097152) {
            $msg = "Maximum file size is 2MB [ERR_S3_002]";
            $this->config['return'] = ['status' => false, 'msg' => $msg];
          } //end if
          $img = Image::make($myfile);
          $img->resize(120, 120, function ($constraint) {
            $constraint->aspectRatio();
          });
          $img->stream(); // <-- Key point
          break;
        default:
          $img = $myfile;
          break;
      }

      if ($this->coreFunctions->sbcupdate('conversation_msg', $data, ['msg_id' => $msg_id]) == 0) {
        $msg = 'Failed to save the attachment';
        return ['status' => false, 'msg' => $msg];
      } else {
        switch ($file_ext) {
          case 'jpg':
          case 'jpeg':
          case 'png':
            Storage::disk('sbcpath')->put('/message/' . $subfolder . $filename, $img);
            break;
          default:
            $path = database_path() . $mainfolder . $subfolder;
            $img->move($path, $filename);
            break;
        }
        return ['status' => true, 'msg' => 'Successfully uploaded.'];
      }
    }
    return $this;
  } //end function




  private function getclient()
  {
    $filter = '';
    switch ($this->config['params']['group']) {
      case 'EMPLOYEE':
        $filter = " where isemployee=1 ";
        break;
      case 'STUDENT':
        $filter = " where isstudent=1 ";
        break;
      case 'ADMIN':
        $filter = " where isadmin=1 ";
        break;
    }
    $sql = "select clientid as value,clientname as label from client " . $filter . " order by clientname";
    $data = $this->coreFunctions->opentable($sql);

    $data = json_decode(json_encode($data), true);
    $this->config['return'] = ['status' => true, 'msg' => 'Data collected...', 'data' => $data];
    return $this;
  }













































  public function execute()
  {
    return response()->json($this->config['return'], 200);
  } // end function




} // end class
