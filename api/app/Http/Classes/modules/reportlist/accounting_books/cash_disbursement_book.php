<?php // 0,

namespace App\Http\Classes\modules\reportlist\accounting_books;

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
use App\Http\Classes\SBCPDF;


class cash_disbursement_book
{
  public $modulename = 'Cash Disbursement Book';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);


    switch ($config['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $fields = ['dateid', 'due', 'dclientname', 'dcentername'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dateid.label', 'StartDate');
        data_set($col2, 'dateid.readonly', false);
        data_set($col2, 'due.label', 'EndDate');
        data_set($col2, 'due.readonly', false);
        data_set($col2, 'due.required', true);

        $fields = ['radioposttype', 'radioreporttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'radioreporttype.options', array(
          ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Account Summary', 'value' => '2', 'color' => 'orange'],
        ));

        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
        break;

      default:
        $fields = ['dateid', 'due', 'dclientname', 'dcentername'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dateid.label', 'StartDate');
        data_set($col2, 'dateid.readonly', false);
        data_set($col2, 'due.label', 'EndDate');
        data_set($col2, 'due.readonly', false);
        data_set($col2, 'due.required', true);

        $fields = ['radioposttype', 'radioreporttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set(
          $col3,
          'radioposttype.options',
          [
            ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
            ['label' => 'All', 'value' => '2', 'color' => 'teal']
          ]
        );

        $fields = ['refresh'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.action', 'history');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
        break;
    }
  }

  public function paramsdata($config)
  {

    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable("
        select 'default' as print,
        adddate(left(now(),10),-360) as dateid,
        left(now(),10) as due,
        0 as clientid,
        '' as client,
        '' as clientname,
        '' as dclientname,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '0' as reporttype,
        '0' as posttype,
        '' as contra,
        '' as acnoname,
        '' as dacnoname");
        break;

      default:
        return $this->coreFunctions->opentable("
        select 'default' as print,
        adddate(left(now(),10),-360) as dateid,
        left(now(),10) as due,
        0 as clientid,
        '' as client,
        '' as clientname,
        '' as dclientname,
        '' as center,
        '' as centername,
        '' as dcentername,
        '0' as reporttype,
        '0' as posttype,
        '' as contra,
        '' as acnoname,
        '' as dacnoname");
        break;
    }
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    switch ($config['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila 
      case 52: //technolab
        $result = $this->Vitaline_query($config);
        break;

      default:
        $result = $this->default_query($config);
        break;
    }
    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function default_query($filters)
  {
    $client = $filters['params']['dataparams']['client'];
    $center = $filters['params']['dataparams']['center'];
    $companyid = $filters['params']['companyid'];
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));

    $posttype = $filters['params']['dataparams']['posttype'];
    $reporttype = $filters['params']['dataparams']['reporttype'];
    $filter = "";
    $sort = "  order by docno, credit";

    if ($client != "") {
      $clientid = $filters['params']['dataparams']['clientid'];
      $filter = " and client.clientid=" . $clientid;
    }
    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $sort = "order by dateid2, docno";
    }

    switch ($reporttype) {
      case 0:
        switch ($posttype) {
          case 0: //posted
            $query = "select acno, description, sum(debit) as debit, sum(credit) as credit
            from (select 'p' as tr, 'cd' as bk, head.docno, head.rem, client.client, client.clientname,
            client.tin, date(head.dateid) as dateid, coa.acno, coa.acnoname as description, 
            detail.checkno,detail.ref,detail.db as debit, detail.cr as credit, 
            cntnum.bref,cntnum.center 
            from (((glhead as head
            left join gldetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.clientid=detail.clientid 
            where cntnum.doc = 'CV' and date(head.dateid) between '" . $startdate . "' 
            and '" . $enddate . "' $filter) as x 
            where ifnull(acno,'') <> '' 
            group by acno, description
            order by credit";
            break;
          case 1: //unposted
            $query = "select acno, description, sum(debit) as debit, sum(credit) as credit
            from (select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.client, head.clientname,
            client.tin, date(head.dateid) as dateid, coa.acno, coa.acnoname as description, 
            detail.checkno,detail.ref,detail.db as debit, detail.cr as credit, 
            cntnum.bref,cntnum.center 
            from (((lahead as head
            left join ladetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.client=detail.client
            where cntnum.doc = 'CV' and date(head.dateid) between '" . $startdate . "' 
            and '" . $enddate . "' $filter  ) as x
            where ifnull(acno,'') <> ''
            group by acno, description
            order by credit";
            break;
          default: //all
            $query = "select acno, description, sum(debit) as debit, sum(credit) as credit
            from (select 'p' as tr, 'cd' as bk, head.docno, head.rem, client.client, 
            client.clientname,client.tin, date(head.dateid) as dateid, coa.acno, 
            coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.bref,cntnum.center
            from (((glhead as head
            left join gldetail as detail on detail.trno=head.trno)
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.clientid=detail.clientid
            where  cntnum.doc = 'CV'
            and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter 
            
            UNION ALL 

            select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.client, head.clientname,
            client.tin, date(head.dateid) as dateid, coa.acno, coa.acnoname as description, 
            detail.checkno,detail.ref,detail.db as debit, detail.cr as credit, 
            cntnum.bref,cntnum.center 
            from (((lahead as head
            left join ladetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.client=detail.client
            where cntnum.doc = 'CV' and date(head.dateid) between '" . $startdate . "' 
            and '" . $enddate . "' $filter ) as x
            where ifnull(acno,'') <> '' group by acno, description
            order by credit";
            break;
        }
        break;

      case 1:
        switch ($posttype) {
          case 0: //posted
            $query = "select 'p' as tr, 'cd' as bk, cntnum.bref, head.trno, head.docno, head.rem, client.client, head.clientname, date(detail.postdate) as postdate,
            client.tin, date(head.dateid) as dateid,head.dateid as dateid2, coa.acno, coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.bref,cntnum.center 
            from (((glhead as head
            left join gldetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.clientid=detail.clientid 
            where cntnum.doc = 'CV'
            and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . $sort;
            break;

          case 1: //unposted
            $query = "select 'u' as tr, 'cd' as bk, cntnum.bref, head.trno, head.docno, head.rem, head.client, head.clientname, date(detail.postdate) as postdate,
            client.tin, date(head.dateid) as dateid,head.dateid as dateid2, coa.acno, coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.bref,cntnum.center from (((lahead as head
            left join ladetail as detail on detail.trno=head.trno) left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) left join client on client.client=detail.client
            where cntnum.doc = 'CV'
            and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' " . $filter . $sort;
            break;
          default: //all
            $query = "select  tr,  bk, bref, trno, docno,rem, client, clientname,
            postdate,
            tin,  dateid,dateid2, acno,description,
            checkno,ref, debit,  credit,bref,center
            from (select 'p' as tr, 'cd' as bk, cntnum.bref, head.trno, head.docno, head.rem, client.client, head.clientname,
            date(detail.postdate) as postdate,
            client.tin, date(head.dateid) as dateid,head.dateid as dateid2, coa.acno, coa.acnoname as description,
            detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.center
            from (((glhead as head
            left join gldetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid)
            left join client on client.clientid=detail.clientid 
            where  cntnum.doc = 'CV'
            and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter  
            
            UNION ALL

            select 'u' as tr, 'cd' as bk, cntnum.bref, head.trno, head.docno, head.rem, head.client, head.clientname, date(detail.postdate) as postdate,
            client.tin, date(head.dateid) as dateid,head.dateid as dateid2, coa.acno, coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.center from (((lahead as head
            left join ladetail as detail on detail.trno=head.trno) left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) left join client on client.client=detail.client
            where  cntnum.doc = 'CV'
            and date(head.dateid)  between '" . $startdate . "' and '" . $enddate . "' $filter  ) as x
            $sort";
            break;
        }
        break;
    }

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function Vitaline_query($filters)
  {
    $client = $filters['params']['dataparams']['client'];
    $center = $filters['params']['dataparams']['center'];
    $startdate = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $enddate = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $posttype = $filters['params']['dataparams']['posttype'];
    $reporttype = $filters['params']['dataparams']['reporttype'];

    $filter = "";
    if ($client != "") {
      $clientid = $filters['params']['dataparams']['clientid'];
      $filter = " and client.clientid=" . $clientid;
    }
    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }

    if ($posttype == 0) { // posted
      switch ($reporttype) {
        case 1: // detailed
          $query = "select 'p' as tr, 'cd' as bk, cntnum.bref, head.trno, head.docno, head.rem, client.client, client.clientname, 
            date(detail.postdate) as postdate,
            client.tin, date(head.dateid) as dateid, coa.acno, coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.bref,cntnum.center from (((glhead as head
            left join gldetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.clientid=detail.clientid 
            where cntnum.doc='cv'
            and head.dateid between '" . $startdate . "' and '" . $enddate . "' $filter ";
          break;

        case 0: // summarized
          $query = "select detail.trno, date(cntnum.postdate) as postdate, head.rem, date(head.dateid) as dateid, head.docno,
                    detail.ref, 
                    case when left(coa.alias, 2) = 'CB' then 'Good Check' else ''
                    end as paymenttype, date(detail.postdate) as checkdate, detail.checkno, head.clientname, sum(detail.cr) as cr,
                    case when left(coa.alias, 2) = 'WT' then sum(detail.db) else 0
                    end as wt
                    from glhead as head
                    left join gldetail as detail ON head.trno = detail.trno
                    left join cntnum ON cntnum.trno = head.trno
                    left join coa ON coa.acnoid = detail.acnoid
                    where head.doc = 'CV' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter
                    group by detail.trno, date(cntnum.postdate), head.rem, date(head.dateid), head.docno,
                    detail.ref, coa.alias, date(detail.postdate), detail.checkno, head.clientname
                    order by docno
                    ";
          break;

        case 2: // account 
          $query = "select acno, description, sum(debit) as debit, sum(credit) as credit
            from (select 'p' as tr, 'cd' as bk, head.docno, head.rem, client.client, client.clientname,
            client.tin, date(head.dateid) as dateid, coa.acno, coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.bref,cntnum.center from (((glhead as head
            left join gldetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.clientid=detail.clientid 
            where cntnum.doc='cv'
            and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter
            ) as x where ifnull(acno,'')<>'' group by acno, description";
          break;
      }
    } else { // unposted
      switch ($reporttype) {
        case 1: // detailed
          $query = "select 'p' as tr, 'cd' as bk, cntnum.bref, head.trno, head.docno, head.rem, client.client, client.clientname, 
            date(detail.postdate) as postdate,
            client.tin, date(head.dateid) as dateid, coa.acno, coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.bref,cntnum.center from (((lahead as head
            left join ladetail as detail on detail.trno=head.trno) 
            left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) 
            left join client on client.client=detail.client 
            where cntnum.doc='cv'
            and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter ";
          break;

        case 0: // summarized
          $query = "select detail.trno, date(cntnum.postdate) as postdate, head.rem, date(head.dateid) as dateid, head.docno,
                    detail.ref, 
                    case when left(coa.alias, 2) = 'CB' then 'Good Check' else ''
                    end as paymenttype, date(detail.postdate) as checkdate, detail.checkno, head.clientname, sum(detail.cr) as cr,
                    case when left(coa.alias, 2) = 'WT' then sum(detail.db) else 0
                    end as wt
                    from lahead as head
                    left join ladetail as detail ON head.trno = detail.trno
                    left join cntnum ON cntnum.trno = head.trno
                    left join coa ON coa.acnoid = detail.acnoid
                    where head.doc = 'CV' and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter
                    group by detail.trno, date(cntnum.postdate), head.rem, date(head.dateid), head.docno,
                    detail.ref, coa.alias, date(detail.postdate), detail.checkno, head.clientname
                    order by docno
                    ";
          break;

        case 2: // account 
          $query = "select 'u' as tr, 'cd' as bk, head.docno, head.rem, head.client, head.clientname,
            client.tin, date(head.dateid) as dateid, coa.acno, coa.acnoname as description, detail.checkno,detail.ref,
            detail.db as debit, detail.cr as credit, cntnum.bref,cntnum.center from (((lahead as head
            left join ladetail as detail on detail.trno=head.trno) left join cntnum on cntnum.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid) left join client on client.client=detail.client
            where cntnum.doc='cv'
            and date(head.dateid) between '" . $startdate . "' and '" . $enddate . "' $filter";
          break;
      }
    } //end if

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config, $result)
  {

    switch ($config['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
          case 0:
            $reportdata =  $this->vitaline_cvbook_summarized($result, $config);
            break;

          case 1:
            $reportdata =  $this->vitaline_cvbook_detailed($result, $config);
            break;

          case 2:
            $reportdata =  $this->vitaline_accountsummary($result, $config);
            break;
        } // end switch report type
        break;

      default: // default sbc
        if ($config['params']['dataparams']['reporttype'] == 1) {
          switch ($config['params']['companyid']) {
            case 10: //afti
            case 12: //afti usd
              $reportdata = $this->AFTI_CRBOOK_DETAILED($result, $config);
              break;
            case 15: //nathina
            case 17: //unihome
            case 28: //xcomp
            case 39: //CBBSI
              $reportdata =  $this->MSJOY_CDBOOK_detailed($result, $config);
              break;
            default:
              $reportdata =  $this->default_CDBOOK_detailed($result, $config);
              break;
          }
        } else {
          switch ($config['params']['companyid']) {
            case 15: //nathina
            case 17: //unihome
            case 28: //xcomp
            case 39: //CBBSI
              $reportdata =  $this->MSJOY_CDBOOK_summarized($result, $config);
              break;
            default:
              $reportdata =  $this->default_CDBOOK_summarized($result, $config);
              break;
          }
        }
        break;
    }

    return $reportdata;
  }

  private function MSJOY_Header($params)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';

    $str = '';
    $client = $params['params']['dataparams']['client'];

    if ($client == '') {
      $client = 'ALL';
    }

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    if ($params['params']['dataparams']['reporttype'] == 1) {

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br>';

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CASH DISBURSEMENT BOOK - DETAILED', null, null, '', $border, '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow('');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize10, '', '', '4px');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DOCUMENT #', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '5px,10px,20px,30px');
      $str .= $this->reporter->col('PAYEE NAME PARTICULARS', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DATE', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('REFFERENCE #', null, null, '', $border, 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', $border, 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', $border, 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CASH DISBURSEMENT BOOK - SUMMARIZED', null, null, '', $border, '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow('');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', $border, '', 'l', '', '10', '', '', '');
      $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize10, '', '', '4px');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', '', '', '', '', '');

      $str .= $this->reporter->startrow('', null, '', $border, '', '', '', $font, 'B', '11', '', '');
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', $border, 'B', 'l', $font, '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', $border, 'B', 'c', $font, '', $font, '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', $border, 'B', 'r', '', $font, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', $border, 'B', 'r', '', $font, 'B', '', '');
      $str .= $this->reporter->endrow();
    } //end if

    return $str;
  } //end fn

  private function MSJOY_DetailPart($params, $field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8, $field9)
  {
    $border = '1px solid ';
    $fontsize10 = '10';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($field1, '100', null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field2 . "<br>" . $field3, '150', null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field4, '75', null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field5, '75', null, '', $border, '', 'c', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field6, '175', null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field7, '75', null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field8, '75', null, '', $border, '', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field9, '75', null, '', $border, '', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function MSJOY_CDBOOK_summarized($data, $params)
  {
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = '';
    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return "
        <div style='position:relative;'>
          <div class='text-center' style='position:absolute; top:150px; left:400px;'>
            <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
            <br>
            <div style='font-size:32px; color:#1E1E1E'>Sorry, we couldn't find any matches.</div>
            <div style='font-size:32px; color:#1E1E1E'>Please try again.</div>
          </div>
        </div>
      ";
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_Header($params);

    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, $decimal_currency);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, $decimal_currency);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $str .= $this->reporter->col($value->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();
    } //end foreach


    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');

    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function MSJOY_CDBOOK_detailed($data, $params)
  {
    $border = '1px solid ';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 36;
    $page = 35;

    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return "
        <div style='position:relative;'>
          <div class='text-center' style='position:absolute; top:150px; left:400px;'>
            <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
            <br>
            <div style='font-size:32px; color:#1E1E1E'>Sorry, we couldn't find any matches.</div>
            <div style='font-size:32px; color:#1E1E1E'>Please try again.</div>
          </div>
        </div>
      ";
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->MSJOY_Header($params);

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";

    foreach ($data as $key => $value) {

      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $cname = "";
        $rem = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', null, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $value->docno;
        $date = $value->dateid;
        $cname = $value->clientname;
        $rem = $value->rem;
      }
      $debit = number_format($value->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($value->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }


      $str .= $this->MSJOY_DetailPart($params, $docno, $cname, $rem, $date, $value->acno, $value->description, $value->ref, $debit, $credit);

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $docno = $value->docno;
      $date = $value->dateid;
      $cname = $value->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->MSJOY_Header($params);
        $page = $page + $count;
      } //end if
    } //end foreach


    $str .= $this->reporter->startrow('', null, '', $border, '', '', $font, '', 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function AFTI_CRBOOK_DETAILED($data, $params)
  {
    $border = '1px solid ';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $this->reporter->linecounter = 0;
    if (empty($data)) return $this->othersClass->emptydata($params);

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->generateDefaultHeader($params);

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";

    foreach ($data as $key => $value) {
      $debit = $value->debit == 0 ? '-' : number_format($value->debit, $decimal_currency);
      $credit = $value->credit == 0 ? '-' : number_format($value->credit, $decimal_currency);

      $docno = $value->docno;
      $date = $value->dateid;
      $cname = $value->clientname;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($date, '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($docno, '100', null, '', $border, 'LTRB', 'C', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($cname, '200', null, '', $border, 'LTRB', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($value->rem, '500', null, '', $border, 'LTRB', 'L', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $totaldb += $value->debit;
      $totalcr += $value->credit;
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, '', $border, 'LTRB', '', '', '', '', '', '');
    $str .= $this->reporter->col('', '100', null, '', $border, 'LTRB', '', '', '', '', '', '');
    $str .= $this->reporter->col('Totals', '200', null, '', $border, 'LTRB', 'L', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), '100', null, '', $border, 'LTRB', 'R', $font, $fontsize10, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col('', '500', null, '', $border, 'LTRB', '', '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function generateDefaultDetailPart($params, $field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8, $field9)
  {
    $border = '1px solid ';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';

    $str = '';
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($field1, 120, null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field2 . "<br>" . $field3, 250, null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field4, 110, null, '', $border, '', 'CT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field5, 100, null, '', $border, '', 'CT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field6, 200, null, '', $border, '', 'LT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field7, 120, null, '', $border, '', 'CT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field8, 150, null, '', $border, '', 'RT', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col($field9, 150, null, '', $border, '', 'RT', $font, $fontsize10, '', '', '');

    $str .= $this->reporter->endrow();
    return $str;
  } //end fn

  private function generateDefaultHeader($params)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';
    $companyid = $params['params']['companyid'];

    $str = '';
    $client = $params['params']['dataparams']['client'];

    if ($client == '') {
      $client = 'ALL';
    }

    $posttype = $params['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:
        $post = 'Posted';
        break;
      case 1:
        $post = 'Unposted';
        break;
      default:
        $post = 'All';
        break;
    }

    if ($params['params']['dataparams']['reporttype'] == 1) {

      $str .= $this->reporter->begintable(1200);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

    

      $str .= '<br><br>';

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CASH DISBURSEMENT BOOK - DETAILED', null, null, '', $border, '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow('');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize10, '', '', '4px');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      switch ($params['params']['companyid']) {
        case 10: //afti
        case 12: //afti usd
          $str .= $this->reporter->begintable('1200');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Reference No.', '100', null, '', $border, 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Account Name', '200', null, '', $border, 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Debit', '100', null, '', $border, 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Credit', '100', null, '', $border, 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->col('Details', '500', null, '', $border, 'LTRB', 'C', $font, $fontsize10, 'B', '', '');
          $str .= $this->reporter->endrow();
          break;
      }
    } else {

      $str .= $this->reporter->begintable(800);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CASH DISBURSEMENT BOOK - SUMMARIZED', null, null, '', $border, '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow('');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', $border, '', 'l', '', '10', '', '', '');
      $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize10, '', '', '4px');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
    } //end if

    return $str;
  } //end fn

  private function default_summary_table_cols($layoutsize, $border, $font, $fontsize)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow('', null, '', $border, '', '', '', $font, 'B', '11', '', '');
    $str .= $this->reporter->col('ACCOUNT CODE', 100, null, '', $border, 'B', 'l', $font, '', '');
    $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 400, null, '', $border, 'B', 'c', $font, '', $font, '', '');
    $str .= $this->reporter->col('DEBIT', 150, null, '', $border, 'B', 'r', '', $font, 'B', '', '');
    $str .= $this->reporter->col('CREDIT', 150, null, '', $border, 'B', 'r', '', $font, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function default_detail_table_cols($layoutsize, $border, $font, $fontsize)
  {
    $fontsize10 = '10';

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', 120, null, '', $border, 'TB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('PAYEE NAME PARTICULARS', 250, null, '', $border, 'TB', 'l', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('DATE', 110, null, '', $border, 'TB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('ACCOUNT CODE', 100, null, '', $border, 'TB', 'R', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('ACCOUNT DESCRIPTION', 200, null, '', $border, 'TB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('REFFERENCE #', 120, null, '', $border, 'TB', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('DEBIT', 150, null, '', $border, 'TB', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('CREDIT', 150, null, '', $border, 'TB', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function default_CDBOOK_summarized($data, $params)
  {
    $border = '1px solid ';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = '';
    $count = 60;
    $page = 59;
    $fontsize10 = '10';
    $fontsize11 = 11;

    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return "
        <div style='position:relative;'>
          <div class='text-center' style='position:absolute; top:150px; left:400px;'>
            <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
            <br>
            <div style='font-size:32px; color:#1E1E1E'>Sorry, we couldn't find any matches.</div>
            <div style='font-size:32px; color:#1E1E1E'>Please try again.</div>
          </div>
        </div>
      ";
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->generateDefaultHeader($params);
    $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);

    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, $decimal_currency);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, $decimal_currency);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $str .= $this->reporter->col($value->acno, 100, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, 400, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, 150, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->col($credit, 150, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '', '', 0, '', 1);
      $str .= $this->reporter->endrow();


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->generateDefaultHeader($params);
        }
        $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');

    $str .= $this->reporter->col('', 100, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', 400, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', '', 0, '', 0, 1);
    $str .= $this->reporter->col(number_format($totalcr, 2), 150, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '', '', 0, '', 0, 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function default_CDBOOK_detailed($data, $params)
  {
    $border = '1px solid ';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';
    $fontsize11 = 11;

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 36;
    $page = 35;
    $layoutsize = 1200;

    $this->reporter->linecounter = 0;

    if (empty($data)) {
      return "
        <div style='position:relative;'>
          <div class='text-center' style='position:absolute; top:150px; left:400px;'>
            <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
            <br>
            <div style='font-size:32px; color:#1E1E1E'>Sorry, we couldn't find any matches.</div>
            <div style='font-size:32px; color:#1E1E1E'>Please try again.</div>
          </div>
        </div>
      ";
    }

    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->generateDefaultHeader($params);
    $str .= $this->default_detail_table_cols($layoutsize, $border, $font, $fontsize11);

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $date = "";
    $cname = "";

    foreach ($data as $key => $value) {

      if ($docno == $value->docno) {
        $docno = "";
        $date = "";
        $cname = "";
        $rem = "";
      } else {
        if ($docno != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(' ', 120, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 250, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 110, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 100, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 200, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 120, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 150, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->col(' ', 150, null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '5px');
          $str .= $this->reporter->endrow();
        }
        $docno = $value->docno;
        $date = $value->dateid;
        $cname = $value->clientname;
        $rem = $value->rem;
      }
      $debit = number_format($value->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($value->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $str .= $this->generateDefaultDetailPart($params, $docno, $cname, $rem, $date, $value->acno, $value->description, $value->ref, $debit, $credit);
      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;
      $docno = $value->docno;
      $date = $value->dateid;
      $cname = $value->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->generateDefaultHeader($params);
        }
        $str .= $this->default_detail_table_cols($layoutsize, $border, $font, $fontsize11);

        $page = $page + $count;
      } //end if
    } //end foreach


    $str .= $this->reporter->startrow('', null, '', $border, '', '', $font, '', 'b', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('', null, null, '', $border, 'T', 'c', $font, '', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', $border, 'T', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function vitaline_cvbook_header($params)
  {
    $font = $this->companysetup->getrptfont($params['params']);  //FONT UPDATED
    $fontsize10 = '10';
    $str = '';
    $client = $params['params']['dataparams']['client'];

    if ($client == '') {
      $client = 'ALL';
    }

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    if ($params['params']['dataparams']['reporttype'] == 1) {
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
      $str .= $this->reporter->endtable();
      $str .= '<br><br>';

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DETAILED CASH DISBURSEMENT BOOK', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow('');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '4px');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Posted Date', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '5px,10px,20px,30px');
      $str .= $this->reporter->col('DOCUMENT #', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '5px,10px,20px,30px');
      $str .= $this->reporter->col('PAYEE NAME<br>PARTICULARS', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DATE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('REFFERENCE&nbsp#', null, null, '', '1px solid ', 'B', 'l', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
      $str .= $this->reporter->endtable();
      $str .= '<br><br>';

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DETAILED CASH DISBURSEMENT BOOK - SUMMARIZED', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow('');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
      $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '4px');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->printline();

      $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', '', '', '', '', '');

      $str .= $this->reporter->startrow('', null, '', '1px solid ', '', '', '', $font, 'B', '11', '', '');
      $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, '', '');
      $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'c', $font, '', $font, '', '');
      $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', '', $font, 'B', '', '');
      $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', '', $font, 'B', '', '');
      $str .= $this->reporter->endrow();
    } //end if

    return $str;
  } //end fn

  private function vitaline_cvbook_detailed($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';

    $count = 46;
    $page = 44;

    if (empty($data)) {
      return "
        <div style='position:relative;'>
          <div class='text-center' style='position:absolute; top:150px; left:400px;'>
            <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
            <br>
            <div style='font-size:32px; color:#1E1E1E'>Sorry, we couldn't find any matches.</div>
            <div style='font-size:32px; color:#1E1E1E'>Please try again.</div>
          </div>
        </div>
      ";
    }

    $str = '';
    $str .= $this->reporter->beginreport();

    $str .= $this->vitaline_cvbook_header($params);

    $totaldb = 0;
    $totalcr = 0;
    $docno = "";
    $subtotaldb = 0;
    $subtotalcr = 0;

    $trno = $postdate = $docno = $clientname = $rem = $dateid = $bref = "";


    foreach ($data as $key => $value) {
      $debit = number_format($value->debit, $decimal_currency);
      if ($debit == 0) {
        $debit = '-';
      }
      $credit = number_format($value->credit, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      if ($trno != $value->trno) {
        if ($bref != $value->bref) {
          if ($bref != '') {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col($bref . '&nbspSub total:', '175', null, '', '1px dashed ', 'T', 'RT', $font, $fontsize10, 'B', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'r', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col(number_format($subtotaldb, $decimal_currency), '75', null, '', '1px dashed ', 'T', 'R', $font, $fontsize10, 'B', '', '4px');
            $str .= $this->reporter->col(number_format($subtotalcr, $decimal_currency), '75', null, '', '1px dashed ', 'T', 'R', $font, $fontsize10, 'B', '', '4px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, '', '1px solid ', 'B', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '100', null, '', '1px solid ', 'B', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '150', null, '', '1px solid ', 'B', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'B', 'LT', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '175', null, '', '1px solid ', 'B', 'c', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'B', 'l', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->col('', '75', null, '', '1px solid ', 'B', 'r', $font, $fontsize10, '', '', '4px');
            $str .= $this->reporter->endrow();
            $subtotaldb = 0;
            $subtotalcr = 0;
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->page_break();
              $str .= $this->vitaline_cvbook_header($params);
              $page = $page + $count;
            } //end if
          }
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'r', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '175', null, '', '1px dashed ', 'T', 'c', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'r', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'r', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->endrow();
        }
        $trno = $value->trno;
        $postdate = $value->postdate;
        $docno = $value->docno;
        $clientname = $value->clientname;
        $rem = $value->rem;
        $dateid = $value->dateid;
      } else {
        $trno = $postdate = $docno = $clientname = $rem = $dateid = "";
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($postdate, '100', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($docno, '100', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($clientname . '<br><i>' . $rem . '</i>', '150', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($dateid, '75', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->acno, '75', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, '175', null, '', '1px solid ', '', 'CT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->ref, '75', null, '', '1px solid ', '', 'LT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, '75', null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, '75', null, '', '1px solid ', '', 'RT', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();
      $trno = $value->trno;
      $postdate = $value->postdate;
      $docno = $value->docno;
      $clientname = $value->clientname;
      $rem = $value->rem;
      $dateid = $value->dateid;
      $bref = $value->bref;
      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $subtotaldb += $value->debit;
      $subtotalcr += $value->credit;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->vitaline_cvbook_header($params);
        $page = $page + $count;
      } //end if
    } //end foreach


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'LT', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col($bref . '&nbspSub total: ', '175', null, '', '1px dashed ', 'T', 'RT', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'r', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 'T', 'l', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col(number_format($subtotaldb, $decimal_currency), '75', null, '', '1px dashed ', 'T', 'R', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($subtotalcr, $decimal_currency), '75', null, '', '1px dashed ', 'T', 'R', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, '', 'b', '', '4px');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', '', '', '', '4px');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', '', '', '', '4px');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', '', '', '', '4px');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', '', '', '', '4px');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', '', '', '', '4px');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'L', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', '', '', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function vitaline_cvbook_summarized($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $client = $params['params']['dataparams']['client'];
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';

    if ($client == '') {
      $client = 'ALL';
    }

    $count = 60;
    $page = 59;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = '';
    $str .= $this->reporter->beginreport('1000');
    $str .= $this->vitaline_cvbook_summarized_header($data, $params);

    $trno = '';
    $totalreceived = 0;
    $totalwt = 0;


    foreach ($data as $key => $value) {
      $credit = number_format($value->cr, $decimal_currency);
      if ($credit == 0) {
        $credit = '-';
      }

      $wt = number_format($value->wt, $decimal_currency);
      if ($wt == 0) {
        $wt = '-';
      }

      if ($trno != $value->trno) {
        if ($trno != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, '', '1px dashed ', 't', 'C', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '150', null, '', '1px dashed ', 't', 'l', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'l', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'l', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '175', null, '', '1px dashed ', 't', 'c', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'l', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'C', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'l', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'l', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'r', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'r', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->col('', '75', null, '', '1px dashed ', 't', 'r', $font, $fontsize10, '', '', '4px');
          $str .= $this->reporter->endrow();
        }
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      if ($value->ref != "") {
        $str .= $this->reporter->col($value->postdate, '100', null, '', '1px solid ', '', 'C', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->rem, '150', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->dateid, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->docno, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->ref, '175', null, '', '1px solid ', '', 'c', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->paymenttype, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->checkdate, '75', null, '', '1px solid ', '', 'C', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->checkno, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->clientname, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($credit, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($wt, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      }

      if ($value->paymenttype != '') {
        $str .= $this->reporter->col($value->postdate, '100', null, '', '1px solid ', '', 'C', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->rem, '150', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->dateid, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->docno, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->ref, '175', null, '', '1px solid ', '', 'c', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->paymenttype, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->checkdate, '75', null, '', '1px solid ', '', 'C', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->checkno, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($value->clientname, '75', null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($credit, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col($wt, '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('', '75', null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
        $totalreceived += $value->cr;
        $totalwt += $value->wt;
      }
      $str .= $this->reporter->endrow();
      $trno = $value->trno;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->vitaline_cvbook_summarized_header($data, $params);
        $page = $page + $count;
      } //end if
    } //end foreach


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, '', '1px solid ', 't', 'C', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '150', null, '', '1px solid ', 't', 'l', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px solid ', 't', 'l', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px solid ', 't', 'l', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '175', null, '', '1px solid ', 't', 'c', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px solid ', 't', 'l', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px solid ', 't', 'C', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px solid ', 't', 'l', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->col('GRAND TOTAL :', '100', null, '', '1px solid ', 't', 'r', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totalreceived, $decimal_currency), '75', null, '', '1px solid ', 't', 'r', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totalwt, $decimal_currency), '75', null, '', '1px solid ', 't', 'r', $font, $fontsize10, 'B', '', '4px');
    $str .= $this->reporter->col('', '75', null, '', '1px solid ', 't', 'r', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function vitaline_cvbook_summarized_header($data, $params)
  {
    $client = $params['params']['dataparams']['client'];
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';

    if ($client == '') {
      $client = 'ALL';
    }

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CASH DISBURSEMENT SUMMARIZED REPORT', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');

    $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Posted Date', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '5px,10px,20px,30px');
    $str .= $this->reporter->col('PARTICULARS', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Document Date', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Document No.', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Reference No.', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Payment Type', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Check Date', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Check Number', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Payor Name', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Total Amt Release', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Withholding Tax', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('Date Released', null, null, '', '1px solid ', 'B', 'C', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function vitaline_accountsummary($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $fontsize10 = '10';

    $str = '';
    $client = $params['params']['dataparams']['client'];

    if ($client == '') {
      $client = 'ALL';
    }

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT SUMMARY', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($params['params']['dataparams']['dateid'])) . ' TO ' . date('M-d-Y', strtotime($params['params']['dataparams']['due'])), null, null, '', '1px solid ', '', 'l', '', '10', '', '', '');
    $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->col('Supplier: ' . strtoupper($client), null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', '', '', '', '', '');
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', '', '', $font, 'B', '11', '', '');
    $str .= $this->reporter->col('ACCOUNT CODE', null, null, '', '1px solid ', 'B', 'l', $font, '', '');
    $str .= $this->reporter->col('ACCOUNT DESCRIPTION', null, null, '', '1px solid ', 'B', 'c', $font, '', $font, '', '');
    $str .= $this->reporter->col('DEBIT', null, null, '', '1px solid ', 'B', 'r', '', $font, 'B', '', '');
    $str .= $this->reporter->col('CREDIT', null, null, '', '1px solid ', 'B', 'r', '', $font, 'B', '', '');
    $str .= $this->reporter->endrow();

    $totaldb = 0;
    $totalcr = 0;

    foreach ($data as $key => $value) {
      $credit = number_format($value->credit, $decimal_currency);

      if ($credit == 0) {
        $credit = '-';
      } //end if

      $debit = number_format($value->debit, $decimal_currency);

      if ($debit == 0) {
        $debit = '-';
      } //end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $totaldb = $totaldb + $value->debit;
      $totalcr = $totalcr + $value->credit;

      $str .= $this->reporter->col($value->acno, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($value->description, null, null, '', '1px solid ', '', 'l', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($debit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->col($credit, null, null, '', '1px solid ', '', 'r', $font, $fontsize10, '', '', '');
      $str .= $this->reporter->endrow();
    } //end foreach


    $str .= $this->reporter->startrow('', null, '50', '1px solid ', '', 'B', $font, 'B', '11', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('', null, '', '1px solid ', '', 'B', $font, 'B', '12', '', '');

    $str .= $this->reporter->col('', null, null, '', '1px solid ', 'T', 'c', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, $decimal_currency), null, null, '', '1px solid ', 'T', 'r', $font, $fontsize10, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function graphColumn($config)
  {
    $year = date('Y');
    $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.ext) as amt from 
          glhead as head left join glstock as stock 
          on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
          year(dateid)='" . $year . "' group by month(head.dateid)
          UNION ALL
          select month(head.dateid),sum(stock.ext) from lahead as head 
          left join lastock as stock
          on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where head.doc='SJ' and 
          year(dateid)='" . $year . "' group by month(head.dateid)) as T group by m";
    $data = $this->coreFunctions->opentable($qry);
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    foreach ($data as $key => $value) {
      $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
    }
    $series = [['name' => 'Total', 'data' => $graphdata], ['name' => 'monthly', 'data' => $graphdata]];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 400],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Sales ' . $year, 'align' => 'left', 'style' => ['color' => 'white']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['categories' => $months],
      'yaxis' => ['title' => ['text' => '$ (thousands)']],
      'fill' => ['opacity' => 1]
    ];
    return array('series' => $series, 'chartoption' => $chartoption);
  }
}//end class