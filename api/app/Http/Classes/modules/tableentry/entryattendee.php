<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\builder\lookupclass;
use App\Http\Classes\modules\inventory\va;

class entryattendee
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ATTENDEE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'attendee';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  private $logger;
  public $style = 'width:100%;';
  private $fields = [
    'exhibitid', 'clientid', 'client', 'companyname', 'contactname', 'contactno', 'department', 'designation', 'email', 'isexhibit', 'isseminar', 'issource',
    'dateid', 'contactid', 'clientstatus', 'mrktremarks', 'saleremarks', 'salesperson', 'salesid', 'status', 'officialwebsite', 'officialemail'
  ];
  public $showclosebtn = true;
  private $lookupclass;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupclass = new lookupclass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $doc = $config['params']['doc'];

    $action = 0;
    $clientstatus = 1;
    $companyname = 2;
    $officialwebsite = 3;
    $officialemail = 4;
    $contactname = 5;
    $contactno = 6;    
    $dateid = 7;
    $department = 8;
    $designation = 9;
    $email = 10;
    $mrktremarks = 11;
    $salesperson = 12;
    $saleremarks = 13;
    $status = 14;
    $inqstatus = 15;
    $docno = 16;

    $columns = ['action', 'client', 'companyname', 'officialwebsite', 'officialemail', 'contactname', 'contactno', 'dateid', 'department', 'designation', 'email', 'mrktremarks', 'status', 'isinactive','salesperson', 'saleremarks', 'inqstatus', 'docno'];
    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];


    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$client]['label'] = "Customer ID";
    $obj[0][$this->gridname]['columns'][$client]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$companyname]['type'] = "editlookup";
    $obj[0][$this->gridname]['columns'][$companyname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$companyname]['lookupclass'] = "clientdetailattendee";

    $obj[0][$this->gridname]['columns'][$companyname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$contactname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$contactname]['type'] = "editlookup";
    $obj[0][$this->gridname]['columns'][$contactname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$contactname]['lookupclass'] = "lookupcontactperson";

    $obj[0][$this->gridname]['columns'][$contactno]['type'] = "cinput";
    $obj[0][$this->gridname]['columns'][$contactno]['maxlength'] = "50";
    $obj[0][$this->gridname]['columns'][$contactno]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";


    $obj[0][$this->gridname]['columns'][$salesperson]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$salesperson]['type'] = "editlookup";
    $obj[0][$this->gridname]['columns'][$salesperson]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$salesperson]['lookupclass'] = "agentdetailattendee";
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$department]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$department]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$designation]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$email]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$status]['label'] = "Activity Status";
    $obj[0][$this->gridname]['columns'][$saleremarks]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$saleremarks]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$mrktremarks]['type'] = 'textarea';

    $mrkt = 0;
    $sale = 0;
    switch ($doc) {
      case 'SEMINAR':
        $mrkt = 3666;
        $sale = 3667;
        # code...
        break;
      case 'SOURCE':
        $mrkt = 3668;
        $sale = 3669;
        break;
      case 'EXHIBIT':
        $mrkt = 3670;
        $sale = 3671;
        break;
    }

    $mrktremark_access = $this->othersClass->checkAccess($config['params']['user'], $mrkt);
    $saleremark_access = $this->othersClass->checkAccess($config['params']['user'], $sale);
    if ($mrktremark_access == 0) {
      $obj[0][$this->gridname]['columns'][$mrktremarks]['readonly'] = true;
    }

    if ($saleremark_access == 0) {
      $obj[0][$this->gridname]['columns'][$saleremarks]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'][$status]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$status]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$status]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$status]['lookupclass'] = "lookupstatus";

    $obj[0][$this->gridname]['columns'][$docno]['label'] = "Sales Activity Ref No.";
    $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$isinactive]['label'] = "Lead Status";
    if ($doc != 'SOURCE') {
      $obj[0][$this->gridname]['columns'][$docno]['label'] = "Doc # Ref.";
    }

    if ($doc != 'SOURCE') {
      $obj[0][$this->gridname]['columns'][$dateid]['type'] = "coldel";
    }
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $agent = 0;
    $agentname = '';
    if ($config['params']['companyid'] == 10) { //afti
      $salesperson_qry = "
      select ag.clientid as agent, ag.clientname as agentname, 
      branch.clientid as branchid, branch.client as branchcode, branch.clientname as branchname,
      ag.tel2 as contactno
      from client as ag
      left join client as branch on branch.clientid = ag.branchid
      where ag.clientid = ?";
      $salesperson_res = $this->coreFunctions->opentable($salesperson_qry, [$config['params']['adminid']]);
      if (!empty($salesperson_res)) {
        $agent = $salesperson_res[0]->agent;
        $agentname = $salesperson_res[0]->agentname;
      }
    }

    $id = $config['params']['sourcerow']['line'];
    $data = [];
    $data['line'] = 0;
    $data['exhibitid'] = $id;
    $data['clientid'] = 0;
    $data['client'] = '';
    $data['companyname'] = '';
    $data['officialwebsite'] = '';
    $data['officialemail'] = '';
    $data['contactid'] = 0;
    $data['contactname'] = '';
    $data['contactno'] = '';
    $data['salesid'] = $agent;
    $data['salesperson'] = $agentname;
    $data['department'] = '';
    $data['designation'] = '';
    $data['email'] = '';
    $data['mrktremarks'] = '';
    $data['saleremarks'] = '';
    $data['clientstatus'] = '';
    $data['status'] = '';
    $data['docno'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    $data['dateid'] = date('Y-m-d');
    $data['isinactive'] = 0;
    switch ($config['params']['doc']) {
      case 'EXHIBIT':
        $data['isexhibit'] = 1;
        $data['isseminar'] = 0;
        $data['issource'] = 0;
        break;
      case 'SEMINAR':
        $data['isexhibit'] = 0;
        $data['isseminar'] = 1;
        $data['issource'] = 0;
        break;
      case 'SOURCE':
        $data['isexhibit'] = 0;
        $data['isseminar'] = 0;
        $data['issource'] = 1;
        break;
    }
    return $data;
  }

  public function save($config)
  {
    $doc = $config['params']['doc'];
    $data = [];
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];


    $row['contactid'] = isset($row['contactid']) ? $row['contactid'] : 0;
    $row['salesid'] = isset($row['salesid']) ? $row['salesid'] : 0;
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value], '', $companyid);
    }

    if ($row['isinactive'] == 'false') {
      $data['isinactive'] = 0;
    }else{
      $data['isinactive'] = 1;
    }

    if ($row['clientid'] != 0) {
      $data['client'] = "";
      $data['companyname'] = "";
    }

    if ($row['contactid'] != 0) {
      $data['contactname'] = "";
    }

    if ($row['salesid'] != 0) {
      $data['salesperson'] = "";
    }

    if ($row['line'] == 0) {

      $qry = "select clientid as value from " . $this->table . " where clientid = '" . $data['clientid'] . "' and contactid = '" . $data['contactid'] . "'  and exhibitid = " . $row['exhibitid'] . "
      and isexhibit = '" . $row['isexhibit'] . "' and isseminar = '" . $row['isseminar'] . "' and issource = '" . $row['issource'] . "' and clientid <>0 and contactid <>0";
      $checking = $this->coreFunctions->datareader($qry);
      if (!empty($checking)) {
        return ['status' => false, 'msg' => 'Already Exist. - ' . $data['client'], 'data' => $data];
      }

      $line = $this->coreFunctions->insertGetId($this->table, $data);
      $returnrow = $this->loaddataperrecord($line, $config['params']['doc']);
      $params = $config;
      $params['params']['doc'] = strtoupper("entryattendee");
      $this->logger->sbcmasterlog(
        $row['exhibitid'],
        $params,
        ' CREATE - CLIENT STATUS: ' . $row['clientstatus']
          . ', COMPANY NAME: ' . $row['companyname']
          . ', OFFICIAL WEBSITE: ' . $row['officialwebsite']
          . ', OFFICIAL EMAIL: ' . $row['officialemail']
          . ', CONTACT NO:' . $row['contactno']
          . ', SALES PERSON: ' . $row['salesperson']
          . ', DEPARTMENT:' . $row['department']
          . ', DESIGNATION:' . $row['designation']
          . ', EMAIL:' . $row['email']
          . ', MARKETING REMARKS:' . $row['mrktremarks']
          . ', SALE REMARKS:' . $row['saleremarks']
          . ', STATUS:' . $row['status']
      );
      $returnrow = $this->loaddataperrecord($line, $config['params']['doc']);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      $qry = "select clientid as value from " . $this->table . " where clientid = '" . $data['clientid'] . "'  and contactid = '" . $data['contactid'] . "' 
        and isexhibit = '" . $row['isexhibit'] . "' and isseminar = '" . $row['isseminar'] . "' and issource = '" . $row['issource'] . "'  and clientid <>0 and contactid <>0 and line <> ".$row['line'];
      $checking1 = $this->coreFunctions->datareader($qry);

      if (!empty($checking1)) {
        return ['status' => false, 'msg' => 'Already Exist. - ' . $data['client'], 'data' => $data];
      }

      if ($row['clientid'] != 0) {
        $qry = "
        select client.clientname as value
        from client
        left join contactperson as cp on cp.line=client.billcontactid
        where client.iscustomer=1 and client.isinactive=0 
        and client.clientid = " . $row['clientid'] . " 
        and client.clientname LIKE '%" . $row['companyname'] . "%'";
        $checking = $this->coreFunctions->datareader($qry);

        if ($checking == "") {
          $data['clientid'] = 0;
          $data['client'] = '';
          $data['companyname'] = $row['companyname'];
        }
      }

      if ($row['salesid'] != 0) {
        $qry = "
        select client.clientname as value
        from client        
        where client.isagent=1 and client.isinactive=0 
        and client.clientid = " . $row['salesid'] . " 
        and client.clientname LIKE '%" . $row['salesperson'] . "%'";
        $checking = $this->coreFunctions->datareader($qry);

        if ($checking == "") {
          $data['salesid'] = 0;
          $data['salesperson'] = $row['salesperson'];
        }
      }

      if ($data['contactid'] != 0) {
        $qry = "select ifnull(concat(lname,', ',fname,' ',mname), '') as value 
        from contactperson 
        where concat(lname,', ',fname,' ',mname) like '%" . $row['contactname'] . "%' and  clientid = '" . $row['clientid'] . "'";
        $checking = $this->coreFunctions->datareader($qry);

        if ($checking == "") {
          $data['contactid'] = 0;
          $data['contactname'] = $row['contactname'];
        }
      }
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line'], $config['params']['doc']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $returnrow = $this->loaddataperrecord($row['exhibitid'], $config['params']['doc']);

    $params = $config;
    $params['params']['doc'] = strtoupper("entryattendee");
    $this->logger->sbcmasterlog(
      $row['exhibitid'],
      $params,
      ' DELETE - COMPANY NAME: ' . $row['companyname']
        . ', OFFICIAL WEBSITE: ' . $row['officialwebsite']
        . ', OFFICIAL EMAIL: ' . $row['officialemail']
        . ', CONTACT NO:' . $row['contactno']
        . ', SALES PERSON:' . $row['salesperson']
        . ', DEPARTMENT:' . $row['department']
        . ', DESIGNATION:' . $row['designation']
        . ', EMAIL:' . $row['email']
        . ', MARKETING REMARKS:' . $row['mrktremarks']
        . ', SALE REMARKS:' . $row['saleremarks']
        . ', STATUS:' . $row['status']
    );

    $returnrow = $this->loaddataperrecord($row['exhibitid'], $config['params']['doc']);

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line, $doc)
  {
    $select = "a.line, a.exhibitid, a.clientid,c.client,case a.isinactive when 1 then 'true' else 'false' end as isinactive,
      a.contactno,a.department,a.designation,a.email,a.isexhibit,a.isseminar, a.issource,
      left(a.dateid, 10) as dateid,
      a.contactid, 
      case
        when a.contactid != 0 then concat(cp.lname,', ',cp.fname,' ',cp.mname)
        else a.contactname
      end as contactname,
      case
        when a.clientid != 0 then c.clientname
        else a.companyname
      end as companyname, a.clientstatus,a.mrktremarks,a.saleremarks,a.salesid,
      case
        when a.salesid != 0 then ag.clientname
        else a.salesperson
      end as salesperson,a.status,a.officialwebsite,a.officialemail, num.docno, ifnull(a.optrno,0) as optrno,'' as inqstatus";
    $select = $select . ",'' as bgcolor ";
    switch ($doc) {
      case 'EXHIBIT':
        $filter = ' and a.isexhibit=1';

        break;
      case 'SEMINAR':
        $filter = ' and a.isseminar=1';
        break;
      case 'SOURCE':
        $filter = ' and a.issource=1';
        break;
    }
    $qry = "select " . $select . "
    from attendee as a 
    left join client as c on c.clientid=a.clientid
    left join client as ag on ag.clientid=a.salesid
    left join contactperson as cp on cp.line = a.contactid
    left join transnum as num on num.trno=a.optrno
    where a.line = ? " . $filter . " order by line";

    $data = $this->coreFunctions->opentable($qry, [$line]);
    $data2 = [];

    switch ($doc) {
      case 'SEMINAR':
      case 'EXHIBIT':
      case 'SOURCE':
        foreach ($data as $key => $value) {
          $data2[$key]['line'] = $value->line;
          $data2[$key]['exhibitid'] = $value->exhibitid;
          $data2[$key]['clientid'] = $value->clientid;
          $data2[$key]['client'] = $value->client;
          $data2[$key]['contactno'] = $value->contactno;
          $data2[$key]['department'] = $value->department;
          $data2[$key]['designation'] = $value->designation;
          $data2[$key]['email'] = $value->email;
          $data2[$key]['isexhibit'] = $value->isexhibit;
          $data2[$key]['isseminar'] = $value->isseminar;
          $data2[$key]['issource'] = $value->issource;
          $data2[$key]['dateid'] = $value->dateid;
          $data2[$key]['contactid'] = $value->contactid;
          $data2[$key]['contactname'] = $value->contactname;
          $data2[$key]['companyname'] = $value->companyname;
          $data2[$key]['clientstatus'] = $value->clientstatus;
          $data2[$key]['mrktremarks'] = $value->mrktremarks;
          $data2[$key]['status'] = $value->status;
          $data2[$key]['salesid'] = $value->salesid;
          $data2[$key]['salesperson'] = $value->salesperson;
          $data2[$key]['isinactive'] = $value->isinactive;
          $data2[$key]['bgcolor'] = '';

          $data2[$key]['officialwebsite'] = $value->officialwebsite;
          $data2[$key]['officialemail'] = $value->officialemail;

          if ($value->optrno != 0) {
            $qry1 = "select docno,doc,trno from transnum where trno = " . $value->optrno;

            $xdata1 = $this->coreFunctions->opentable($qry1);

            $data2[$key]['docno'] = $xdata1[0]->docno;

            switch ($xdata1[0]->doc) {
              case 'QS':
                $data2[$key]['saleremarks'] = $this->coreFunctions->datareader("select ifnull(rem,'') as value from 
                (select rem,line from qscalllogs where trno = ? union all select rem,line from hqscalllogs where trno = ?) as a 
                order by line desc limit 1", [$value->optrno, $value->optrno]);

                $data2[$key]['inqstatus'] = $this->coreFunctions->datareader("select ifnull(status,'') as value from 
                (select status,line from qscalllogs where trno = ? union all select status,line from hqscalllogs where trno = ?) as a 
                order by line desc limit 1", [$value->optrno, $value->optrno]);
                break;
              case 'AO':
              case 'SQ':
                $isposted = $this->othersClass->isposted2($value->optrno, 'transnum');
                if ($isposted) {
                  $data2[$key]['saleremarks'] = 'PROCESSED';
                  $data2[$key]['inqstatus'] = '';
                } else {
                  $data2[$key]['saleremarks'] = '';
                  $data2[$key]['inqstatus'] = '';
                }
                break;
              case 'OP':
                $data2[$key]['saleremarks'] = $this->coreFunctions->datareader("select ifnull(rem,'') as value  from calllogs where trno = ?  order by line desc limit 1", [$value->optrno]);
                $data2[$key]['inqstatus'] = $this->coreFunctions->datareader("select ifnull(status,'') as value  from calllogs where trno = ?  order by line desc limit 1", [$value->optrno]);
                break;
              default:
                $data2[$key]['saleremarks'] = '';
                $data2[$key]['inqstatus'] = '';
                $data2[$key]['docno'] = '';
                break;
            }
          } else {
            $data2[$key]['saleremarks'] = '';
            $data2[$key]['docno'] = '';
            $data2[$key]['inqstatus'] = '';
          }

          $data2[$key]['optrno'] = $value->optrno;
        }

        return $data2;
        break;

      default:
        return $data;
        break;
    }
  }

  public function loaddata($config)
  {
    $data2 = [];

    $center = $config['params']['center'];
    $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
    $filter = '';
    switch ($config['params']['doc']) {
      case 'EXHIBIT':
        $filter = ' and a.isexhibit=1';

        break;
      case 'SEMINAR':
        $filter = ' and a.isseminar=1';
        break;
      case 'SOURCE':
        $filter = ' and a.issource=1';
        break;
    }
    $select = "a.line, a.exhibitid, a.clientid,c.client,case a.isinactive when 1 then 'true' else 'false' end as isinactive,
      a.contactno,a.department,a.designation,a.email,a.isexhibit,a.isseminar, a.issource,
      left(a.dateid, 10) as dateid, 
      a.contactid, 
      case
        when a.contactid != 0 then concat(cp.lname,', ',cp.fname,' ',cp.mname)
        else a.contactname
      end as contactname,
      case
        when a.clientid != 0 then c.clientname
        else a.companyname
      end as companyname, a.clientstatus,a.mrktremarks,a.saleremarks,a.salesid,
      case
        when a.salesid != 0 then ag.clientname
        else a.salesperson
      end as salesperson,a.status,a.officialwebsite,a.officialemail,num.docno, ifnull(a.optrno,0) as optrno,
       '' as inqstatus";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from attendee as a 
    left join client as c on c.clientid=a.clientid
    left join client as ag on ag.clientid=a.salesid
    left join contactperson as cp on cp.line = a.contactid
    left join transnum as num on num.trno=a.optrno
    where a.exhibitid = ? and a.isinactive =0 " . $filter . " order by line";

    $data = $this->coreFunctions->opentable($qry, [$line]);

    switch ($config['params']['doc']) {
      case 'SEMINAR':
      case 'EXHIBIT':
      case 'SOURCE':
        foreach ($data as $key => $value) {
          $data2[$key]['line'] = $value->line;
          $data2[$key]['exhibitid'] = $value->exhibitid;
          $data2[$key]['clientid'] = $value->clientid;
          $data2[$key]['client'] = $value->client;
          $data2[$key]['contactno'] = $value->contactno;
          $data2[$key]['department'] = $value->department;
          $data2[$key]['designation'] = $value->designation;
          $data2[$key]['email'] = $value->email;
          $data2[$key]['isexhibit'] = $value->isexhibit;
          $data2[$key]['isseminar'] = $value->isseminar;
          $data2[$key]['issource'] = $value->issource;
          $data2[$key]['dateid'] = $value->dateid;
          $data2[$key]['contactid'] = $value->contactid;
          $data2[$key]['contactname'] = $value->contactname;
          $data2[$key]['companyname'] = $value->companyname;
          $data2[$key]['clientstatus'] = $value->clientstatus;
          $data2[$key]['mrktremarks'] = $value->mrktremarks;
          $data2[$key]['status'] = $value->status;
          $data2[$key]['salesid'] = $value->salesid;
          $data2[$key]['salesperson'] = $value->salesperson;
          $data2[$key]['isinactive'] = $value->isinactive;
          $data2[$key]['bgcolor'] = '';

          $data2[$key]['officialwebsite'] = $value->officialwebsite;
          $data2[$key]['officialemail'] = $value->officialemail;

          if ($value->optrno != 0) {
            $qry1 = "select docno,doc,trno from transnum where trno = " . $value->optrno;

            $xdata1 = $this->coreFunctions->opentable($qry1);
            if (!empty($xdata1)) {
              $data2[$key]['docno'] = $xdata1[0]->docno;

              switch ($xdata1[0]->doc) {
                case 'QS':
                  $data2[$key]['saleremarks'] = $this->coreFunctions->datareader("select ifnull(rem ,'') as value from 
                  (select concat(left(dateid,10),'/ ',probability,'/ ',rem) as rem,line from qscalllogs where trno = ? union all select concat(left(dateid,10),'/ ',probability,'/ ',rem) as rem,line from hqscalllogs where trno = ?) as a 
                  order by line desc limit 1", [$value->optrno, $value->optrno]);
                  $data2[$key]['inqstatus'] = $this->coreFunctions->datareader("select ifnull(status,'') as value from 
                  (select status,line from qscalllogs where trno = ? union all select status,line from hqscalllogs where trno = ?) as a 
                  order by line desc limit 1", [$value->optrno, $value->optrno]);
                  break;
                case 'AO':
                case 'SQ':
                  $isposted = $this->othersClass->isposted2($value->optrno, 'transnum');
                  if ($isposted) {
                    $data2[$key]['saleremarks'] = 'PROCESSED';
                    $data2[$key]['inqstatus'] = '';
                  } else {
                    $data2[$key]['saleremarks'] = '';
                    $data2[$key]['inqstatus'] = '';
                  }
                  break;
                case 'OP':
                  $data2[$key]['saleremarks'] = $this->coreFunctions->datareader("select ifnull(concat(left(dateid,10),'/ ',rem),'') as value  from calllogs where trno = ?  order by line desc limit 1", [$value->optrno]);
                  $data2[$key]['inqstatus'] = $this->coreFunctions->datareader("select ifnull(status,'') as value  from calllogs where trno = ?  order by line desc limit 1", [$value->optrno]);
                  break;
                default:
                  $data2[$key]['saleremarks'] = '';
                  $data2[$key]['docno'] = '';
                  $data2[$key]['inqstatus'] = '';
                  break;
              }
            }
          } else {
            $data2[$key]['saleremarks'] = '';
            $data2[$key]['docno'] = '';
            $data2[$key]['inqstatus'] = '';
          }

          $data2[$key]['optrno'] = $value->optrno;
        }

        return $data2;
        break;

      default:
        return $data;
        break;
    }
  }

  public function saveallentry($config)
  {
    $doc = $config['params']['doc'];
    $data = $config['params']['data'];
    $status = 0;

    foreach ($data as $key => $value) {
      $data2 = [];     
     

      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        if ($data[$key]['isinactive'] == 'false') {
          $data2['isinactive'] = 0;
        }else{
          $data2['isinactive'] = 1;
        }

        if ($data[$key]['clientid'] != 0) {
          $data2['client'] = "";
          $data2['companyname'] = "";
        }

        if ($data[$key]['contactid'] != 0) {
          $data2['contactname'] = "";
        }

        if ($data[$key]['salesid'] != 0) {
          $data2['salesperson'] = "";
        }

        if ($data[$key]['line'] == 0) {
          $qry = "select clientid as value from " . $this->table . " where clientid = '" . $data[$key]['clientid'] . "' 
          and isexhibit = '" . $data[$key]['isexhibit'] . "' and isseminar = '" . $data[$key]['isseminar'] . "' and issource = '" . $data[$key]['issource'] . "'";
          $checking = $this->coreFunctions->datareader($qry);
          if (!empty($checking)) {
            return ['status' => false, 'msg' => 'Already Exist. - ' . $data[$key]['client'], 'data' => $data];
          }
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $params = $config;
          $params['params']['doc'] = strtoupper("entryattendee");
          $this->logger->sbcmasterlog(
            $data[$key]['exhibitid'],
            $params,
            ' CREATE - CLIENT STATUS: ' . $data[$key]['clientstatus']
              . ', COMPANY NAME: ' . $data[$key]['companyname']
              . ', OFFICIAL WEBSITE: ' . $data[$key]['officialwebsite']
              . ', OFFICIAL EMAIL: ' . $data[$key]['officialemail']
              . ', CONTACT NO:' . $data[$key]['contactno']
              . ', SALES PERSON:' . $data[$key]['salesperson']
              . ', DEPARTMENT:' . $data[$key]['department']
              . ', DESIGNATION:' . $data[$key]['designation']
              . ', EMAIL:' . $data[$key]['email']
              . ', MARKETING REMARKS:' . $data[$key]['mrktremarks']
              . ', STATUS:' . $data[$key]['status']
          );
        } else {
          $qry = "select clientid as value from " . $this->table . " where clientid = '" . $data[$key]['clientid'] . "' and line = '" . $data[$key]['line'] . "'
          and isexhibit = '" . $data[$key]['isexhibit'] . "' and isseminar = '" . $data[$key]['isseminar'] . "' and issource = '" . $data[$key]['issource'] . "'";
          $checking = $this->coreFunctions->datareader($qry);

          if (!empty($checking)) {
          } else {
            $clientid = isset($data[$key]['clientid']) ? $data[$key]['clientid'] : 0;
            $qry = "select clientid as value from " . $this->table . " where clientid = '" . $clientid . "' 
            and isexhibit = '" . $data[$key]['isexhibit'] . "' and isseminar = '" . $data[$key]['isseminar'] . "' and issource = '" . $data[$key]['issource'] . "'";
            $checking1 = $this->coreFunctions->datareader($qry);

            if (!empty($checking1)) {
              return ['status' => false, 'msg' => 'Already Exist. - ' . $data[$key]['client'], 'data' => $data];
            }
          }

          if ($data[$key]['clientid'] != 0) {
            $qry = "
            select client.clientname as value
            from client
            left join contactperson as cp on cp.line=client.billcontactid
            where client.iscustomer=1 and client.isinactive=0 
            and client.clientid = " . $data[$key]['clientid'] . " 
            and client.clientname LIKE '%" . $data[$key]['companyname'] . "%'";
            $checking = $this->coreFunctions->datareader($qry);

            if ($checking == "") {
              $data2['clientid'] = 0;
              $data2['client'] = '';
              $data2['companyname'] = $data[$key]['companyname'];
            }
          }

          if ($data[$key]['salesid'] != 0) {
            $qry = "
            select client.clientname as value
            from client            
            where client.isagent=1 and client.isinactive=0 
            and client.clientid = " . $data[$key]['salesid'] . " 
            and client.clientname LIKE '%" . $data[$key]['salesperson'] . "%'";
            $checking = $this->coreFunctions->datareader($qry);

            if ($checking == "") {
              $data2['salesid'] = 0;
              $data2['salesperson'] = $data[$key]['salesperson'];
            }
          }

          if ($data[$key]['contactid'] != 0) {
            $qry = "select ifnull(concat(lname,', ',fname,' ',mname), '') as value 
            from contactperson 
            where concat(lname,', ',fname,' ',mname) LIKE '%" . $data[$key]['contactname'] . "%' and  clientid = '" . $data2['clientid'] . "'";
            $checking = $this->coreFunctions->datareader($qry);

            if ($checking == "") {
              $data2['contactid'] = 0;
              $data2['contactname'] = $data[$key]['contactname'];
            }
          }

          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'da' => $data];
  } // end function

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'clientdetailattendee': // na deretso po dito
        return $this->lookupclient($config);
        break;
      case 'lookupcontactperson':
        return $this->lookupcontactperson($config);
        break;
      case 'lookupclientstatus':
        return $this->lookupclientstatus($config);
        break;
      case 'lookupstatus':
        return $this->lookupstatus($config);
        break;
      case 'agentdetailattendee':
        return $this->lookupagent($config);
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }


  public function lookuplogs($config)
  {
    $doc = strtoupper('entryattendee');
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['sourcerow']['line'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = " . $trno . "
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno ="  . $trno;

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function lookupclient($config)
  {

    $title = 'List of Customers';


    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'clientid' => 'clientid',
        'client' => 'client',
        'companyname' => 'companyname',
        'contactname' => 'cpname',
        'contactno' => 'contactno',
        'department' => 'department',
        'designation' => 'designation',
        'email' => 'email'
      )
    );


    $cols = [
      ['name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'companyname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'addr', 'label' => 'Address', 'align' => 'left', 'field' => 'addr', 'sortable' => true, 'style' => 'font-size:16px;']

    ];

    $qry = "select client.clientid,client.client,client.clientname as companyname,addr,ifnull(concat(cp.lname,', ',fname,' ',mname),'') as cpname,
    ifnull(cp.contactno,'') as contactno,ifnull(cp.department,'') as department,ifnull(cp.designation,'') as designation,ifnull(cp.email,'') as email
    from client
    left join contactperson as cp on cp.line=client.billcontactid
    where iscustomer=1 and isinactive=0 order by client";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupagent($config)
  {

    $title = 'List of Agent';


    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'salesid' => 'salesid',
        'salesperson' => 'salesperson'
      )
    );


    $cols = [
      ['name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'salesperson', 'sortable' => true, 'style' => 'font-size:16px;']

    ];

    $qry = "select client.clientid as salesid,client.client,client.clientname as salesperson
    from client
    where isagent=1 and isinactive=0 order by client";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupcontactperson($config)
  {

    $title = 'List of Contact Person';


    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'contactid' => 'contactid',
        'contactname' => 'cpname',
      )
    );


    $cols = [
      ['name' => 'cpname', 'label' => 'Contact Person', 'align' => 'left', 'field' => 'cpname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $clientid = $config['params']['row']['clientid'];
    $qry = "
      select cp.line as contactid, concat(cp.lname,', ',fname,' ',mname) as cpname
      from contactperson as cp
      where cp.clientid = '" . $clientid . "'
    ";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupclientstatus($config)
  {
    $title = 'Client Status';
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'clientstatus' => 'clientstatus'
      )
    );


    $cols = [['name' => 'clientstatus', 'label' => 'Client Status', 'align' => 'left', 'field' => 'clientstatus', 'sortable' => true, 'style' => 'font-size:16px;']];

    $qry = "
    select 'Old' as clientstatus
    union all
    select 'New' as clientstatus";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }


  public function lookupstatus($config)
  {
    $title = 'List of Status';
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'status' => 'status'
      )
    );


    $cols = [['name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status', 'sortable' => true, 'style' => 'font-size:16px;']];

    $qry = "
    select 'Sent Email/Text/Brochure' as status
    union all
    select 'Sent Quotation/For Evaluation' as status
    union all
    select 'Outsource' as status
    union all
    select 'No Offer' as status
    union all
    select 'Purchase Order' as status
    union all
    select 'For Product Presentation' as status
    ";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }


  public function createprofile($config)
  {
    $doc = $config['params']['doc'];
    $data = [];
    $data2 = [];
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];

    $exhibitid = $row['exhibitid'];
    $line = $row['line'];
    $filter = '';

    switch ($doc) {
      case 'EXHIBIT':
        $filter = ' and a.isexhibit=1';

        break;
      case 'SEMINAR':
        $filter = ' and a.isseminar=1';
        break;
      case 'SOURCE':
        $filter = ' and a.issource=1';
        break;
    }

    $qry = "select a.line, a.exhibitid, a.clientid,c.client,
    a.contactno,a.department,a.designation,a.email,a.isexhibit,a.isseminar, a.issource,
    left(a.dateid, 10) as dateid, a.contactid, a.contactname ,
    ifnull(c.clientname,a.companyname) as companyname, a.clientstatus,a.mrktremarks,a.saleremarks,a.salesid,a.status,a.officialwebsite,a.officialemail,
    ag.client as agent
from attendee as a
  left join client as c on c.clientid=a.clientid
  left join client as ag on ag.clientid=a.salesid
  left join contactperson as cp on cp.line = a.contactid
  where a.line = ? and a.exhibitid = ? and a.clientstatus = 'New' " . $filter;

    $res = $this->coreFunctions->opentable($qry, [$line, $exhibitid]);

    if (!empty($res)) {
      if ($res[0]->clientid != 0) {
        return ['status' => false, 'msg' => 'Profile already exist'];
      }

      $exist = $this->coreFunctions->getfieldvalue("client", "clientid", "clientname = ? and iscustomer =1", [$res[0]->companyname]);
      if (strlen(($exist)) != 0) {
        $this->coreFunctions->execqry('update attendee set clientid = ? where line = ? and exhibitid = ? ', 'update', [$exist, $line, $exhibitid]);
        return ['status' => false, 'msg' => 'Customer already exist.'];
      }


      $clientcode = $this->getnewclient($config); // create customer

      $clientid = $this->coreFunctions->opentable("select clientname from client where client=?", [$clientcode]);
      if ($clientid) {
        return ['status' => false, 'msg' => $clientcode . ' already used by ' . $clientid[0]->clientname . '. Failed to generate code.'];
      }

      $data['client'] = $clientcode;
      $data['clientname'] = $res[0]->companyname;
      $data['agent'] = $res[0]->agent;
      $data['status'] = 'ACTIVE';
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['iscustomer'] = 1;
      $data['center'] = $center;
      $data['officialemail'] = $res[0]->officialemail;
      $data['officialwebsite'] = $res[0]->officialwebsite;

      foreach ($data as $key => $value) {
        $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
      }
      // create client
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', 'ATTENDEE - ' . $clientid . ' - ' . $clientcode . ' - ' . $res[0]->companyname . 'client_log');
      // update attendee clientid
      $this->coreFunctions->execqry("update attendee set clientid = ? where line = ? and exhibitid=?", 'update', [$clientid, $line, $exhibitid]);

      //contactperson
      $data2['clientid'] = $clientid;
      $data2['fname'] = $res[0]->contactname;
      $data2['email'] = $res[0]->email;
      $data2['contactno'] = $res[0]->contactno;
      $data2['designation'] = $res[0]->designation;
      $data2['department'] = $res[0]->department;
      $data2['billdefault'] = 1;
      $data2['shipdefault'] = 1;


      foreach ($data2 as $key2 => $value2) {
        $data2[$key2] = $this->othersClass->sanitizekeyfield($key2, $data2[$key2]);
      }

      $contactid = $this->coreFunctions->insertGetId('contactperson', $data2);
      // update attendee contact
      $this->coreFunctions->execqry("update attendee set contactid = ? where line = ? and exhibitid=?", 'update', [$contactid, $line, $exhibitid]);
      $this->coreFunctions->execqry("update client set shipcontactid = ?,billcontactid=? where clientid=?", 'update', [$contactid, $contactid, $clientid]);

      return ['status' => true, 'msg' => 'Create Profile Successfully'];
    } else {
      return ['status' => true, 'msg' => 'No available data to generate.'];
    }
  }

  private function getnewclient($config)
  {
    $pref = 'C';
    $docnolength =  $this->companysetup->getclientlength($config['params']);
    $last = $this->othersClass->getlastclient($pref, 'customer');
    $start = $this->othersClass->SearchPosition($last);
    $seq = substr($last, $start) + 1;
    $poseq = $pref . $seq;
    $newclient = $this->othersClass->PadJ($poseq, $docnolength);
    return $newclient;
  }
} //end class
