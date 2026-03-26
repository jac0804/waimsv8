<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\ledgerClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class entryunposted_transaction
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'UNPOSTED TRANSACTION';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
  public $showclosebtn = false;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->ledgerClass = new ledgerClass;
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
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'doc', 'counts']
      ]
    ];

    $stockbuttons = ['view_unposted'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['label'] = "Option";
    $obj[0][$this->gridname]['columns'][0]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    return 0;
  }


  private function selectqry($config)
  {

    $center = $config['params']['center'];
    $company = $config['params']['companyid'];
    $limit = '';
    if ($company == 10 || $company == 12) { //afti, afti usd
      $limit = 'limit 25';
    }
    $sj = "SALES JOURNAL";
    if ($company == 48) { //seastar
      $sj = "WAYBILL";
    }
    $qry = " select distinct '' as actions, case(doc) 
    when 'PR' then 'PURCHASE REQUISITION'
    when 'PO' then 'PURCHASE ORDER'
    when 'RR' then 'RECEIVING REPORT'
    when 'RP' then 'PACKING LIST RECEIVING'
    when 'SV' then 'SUPPLIERS INVOICE'
    when 'DM' then 'PURCHASE RETURN'
    when 'SO' then 'SALES ORDER'
    when 'SJ' then '" . $sj . "'
    when 'SD' then 'SALES JOURNAL DEALER'
    when 'SE' then 'SALES JOURNAL BRANCH'
    when 'SF' then 'SALES JOURNAL ONLINE'
    when 'SH' then 'SPECIAL PARTS ISSUANCE'
    when 'DR' then 'DELIVERY RECEIPT'
    when 'CM' then 'SALES RETURN'
    when 'IS' then 'INVENTORY SETUP'
    when 'PC' then 'PHYSICAL COUNT'
    when 'TS' then 'TRANSFER SLIP'
    when 'AJ' then 'INVENTORY ADJUSTMENT'
    when 'GD' then 'DEBIT MEMO'
    when 'GC' then 'CREDIT MEMO'
    when 'GJ' then 'GENERAL JOURNAL'
    when 'DS' then 'DEPOSIT SLIP'
    when 'AR' then 'AR SETUP'
    when 'CR' then 'RECEIVED PAYMENT'
    when 'KR' then 'COUNTER RECEIPT'
    when 'AP' then 'AP SETUP'
    when 'PV' then 'AP VOUCHER'
    when 'CV' then 'CASH/CHECK VOUCHER'
    when 'MI' then 'MATERIAL ISSUANCE'
    when 'SI' then 'SALES INVOICE'
    when 'ST' then 'STOCK TRANSFER'
    when 'AC' then 'JOB COMPLETION'
    when 'AI' then 'BILLING INVOICE'
    when 'JP' then 'JOB ORDER'
    when 'PG' then 'PRODUCTION INPUT'
     when 'LL' then 'LOADING LIST'

    else '' end as doc,
    count(trno) as counts, doc as doc2
    from(
    select head.trno,head.clientname as customername,head.client,head.doc as doc ,
    head.dateid as datex , head.docno as docno 
    from prhead as head
    left join transnum as trans on head.trno = trans.trno
    where trans.center = '$center'
    UNION ALL
    select head.trno,head.clientname as customername,head.client,head.doc as doc ,
    head.dateid as datex , head.docno as docno 
    from pohead as head
    left join transnum as trans on head.trno = trans.trno
    where trans.center = '$center'
    UNION ALL
    select head.trno,head.clientname as customername,head.client,head.doc as doc ,
    head.dateid as datex , head.docno as docno 
    from sohead as head
    left join transnum as trans on head.trno = trans.trno
    where trans.center = '$center'
    UNION ALL
    select head.trno,head.clientname as customername,head.client,head.doc as doc ,
    head.dateid as datex , head.docno as docno 
    from pchead as head
    left join transnum as trans on head.trno = trans.trno
    where trans.center = '$center'
    UNION ALL
    select head.trno,head.clientname as customername,head.client,head.doc as doc ,
    head.dateid as datex , head.docno as docno 
    from lahead as head
    left join cntnum as cnt on head.trno = cnt.trno
    where cnt.center = '$center'
    ) as countx group by countx.doc order by countx.doc $limit";

    return $qry;
  }

  public function loaddata($config)
  {

    $qry = $this->selectqry($config);
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    return $this->lookunposted($config);
  }

  public function lookunposted($config)
  {
    $center = $config['params']['center'];
    $doc = $config['params']['row']['doc2'];

    $title = 'List of Unposted';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $cols = [
      ['name' => 'center', 'label' => 'Center', 'align' => 'left', 'field' => 'center', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'centername', 'label' => 'Name', 'align' => 'left', 'field' => 'centername', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'doc', 'label' => 'Document', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;']
    ];


    switch ($doc) {
      case 'PR':
        $qry = "select center.name as centername,transnum.center,head.trno,head.clientname as clientname,head.client,head.doc as doc ,
            left(head.dateid,10) as dateid , head.docno as docno  , 'UNPOSTED' as status from prhead as head
            left join transnum on transnum.trno = head.trno
            left join center on center.code = transnum.center where transnum.center='$center'
            order by dateid asc";
        break;

      case 'PO':
        $qry = "select center.name as centername,transnum.center,head.trno,head.clientname as clientname,head.client,head.doc as doc ,
            left(head.dateid,10) as dateid , head.docno as docno  , 'UNPOSTED' as status from pohead as head
            left join transnum on transnum.trno = head.trno
            left join center on center.code = transnum.center
            where head.doc='$doc' and transnum.center='$center'
            order by dateid asc";
        break;

      case 'SO':
        $qry = "select center.name as centername,transnum.center,head.trno,head.clientname as clientname,head.client,head.doc as doc ,
            left(head.dateid,10) as dateid , head.docno as docno  , 'UNPOSTED' as status from sohead as head
            left join transnum on transnum.trno = head.trno
            left join center on center.code = transnum.center 
            where head.doc='$doc' and transnum.center='$center'
            order by dateid asc";
        break;

      case 'PC':
        $qry = "select center.name as centername,transnum.center,head.trno,head.clientname as clientname,head.client,head.doc as doc ,
            left(head.dateid,10) as dateid , head.docno as docno  , 'UNPOSTED' as status from pchead as head
            left join transnum on transnum.trno = head.trno
            left join center on center.code = transnum.center 
            where head.doc='$doc' and transnum.center='$center'
            order by dateid asc";
        break;

      default:
        $qry = "select center.name as centername,cntnum.center,head.trno,head.clientname as clientname,head.client,head.doc as doc ,
            left(head.dateid,10) as dateid , head.docno as docno  , 'UNPOSTED' as status from lahead as head
            left join cntnum on cntnum.trno = head.trno
            left join center on center.code = cntnum.center 
            where head.doc='$doc' and cntnum.center='$center'
            order by dateid asc";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  } //end function


} //end class
