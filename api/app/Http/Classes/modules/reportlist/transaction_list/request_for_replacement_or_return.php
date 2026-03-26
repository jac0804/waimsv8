<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class request_for_replacement_or_return
{
  public $modulename = 'Request for Replacement/Return';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  private $head = 'rfhead';
  private $hhead = 'hrfhead';
  private $stock = 'rfstock';
  private $hstock = 'hrfstock';
  private $tablenum = 'transnum';
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
    $fields = ['radioprint', 'start', 'end', 'dcentername'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.required', false);

    $fields = ['radioposttype', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $center = $config['params']['center'];
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '0' as posttype,
    '0' as reporttype,
    '' as center,'' as dcentername
    ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0:
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;

      case 1:
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    $query = $this->default_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $reporttype = $config['params']['dataparams']['reporttype'];

    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";

    if ($fcenter != "") {
      $filter .= " and num.center = '$fcenter'";
    }


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "select 'POSTED' as status, head.docno, left(head.dateid,10) as dateid, head.client,head.clientname,
                        sum(stock.ext) as ext, head.createby, head.others
                    from " . $this->hhead . " as head
                    left join " . $this->hstock . " as stock on stock.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    left join client on client.client = head.client
                    left join " . $this->tablenum . " as num on num.trno = head.trno
                    where head.doc='RF' and date(head.dateid) between '$start' and '$end' $filter 
                    group by head.docno, head.clientname, head.createby, 
                             head.dateid,head.client, head.others
                    order by docno ";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, left(head.dateid,10) as dateid, head.client,head.clientname,
                        sum(stock.ext) as ext, head.createby, head.others
                    from " . $this->head . " as head
                    left join " . $this->stock . " as stock on stock.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    left join client on client.client = head.client
                    left join " . $this->tablenum . " as num on num.trno = head.trno
                    where head.doc='RF' and date(head.dateid) between '$start' and '$end' $filter
                    group by head.docno, head.clientname, head.createby, 
                             head.dateid,head.client, head.others
                    order by docno";
            break;

          default: // all
            $query = "select 'POSTED' as status, head.docno, left(head.dateid,10) as dateid, head.client,head.clientname,
                        sum(stock.ext) as ext, head.createby, head.others
                    from " . $this->hhead . " as head
                    left join " . $this->hstock . " as stock on stock.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    left join client on client.client = head.client
                    left join " . $this->tablenum . " as num on num.trno = head.trno
                    where head.doc='RF' and date(head.dateid) between '$start' and '$end' $filter $filter1
                    group by head.docno, head.clientname, head.createby, 
                             head.dateid,head.client, head.others
                    union all
                    select 'UNPOSTED' as status, head.docno, left(head.dateid,10) as dateid, head.client,head.clientname,
                        sum(stock.ext) as ext, head.createby, head.others
                    from " . $this->head . " as head
                    left join " . $this->stock . " as stock on stock.trno=head.trno
                    left join item on item.itemid=stock.itemid
                    left join client on client.client = head.client
                    left join " . $this->tablenum . " as num on num.trno = head.trno
                    where head.doc='RF' and date(head.dateid) between '$start' and '$end' $filter $filter1
                    group by head.docno, head.clientname, head.createby, 
                             head.dateid,head.client, head.others
                    order by docno";
            break;
        } // end switch posttype
        break;

      case 1: // detailed

        switch ($posttype) {
          case 0: // posted
            $query = "select
       num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode) as shipaddress,
       left(head.dateid,10) as dateid,
       head.yourref as ponum,
       case head.recommend when 'Others, please Specify' then head.others else head.recommend end as recommend,
       stock.iss,
       stock.amt,
       emp.clientname as filedby,
       head.awb,
       head.`action`,
       head.rfnno,
       left(head.dateclose,10) as dateclose,
       ghead.docno as sjdocno,
       left(num.postdate, 10) as postdate,
       concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
       ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno
    from hrfhead as head
    left join transnum as num on num.trno = head.trno
    left join hrfstock as stock on stock.trno = head.trno
    left join item on stock.itemid = item.itemid
    left join client on head.client = client.client
    left join client as emp on head.empid = emp.clientid
    left join hsqhead as sq on sq.trno = head.sotrno
    left join hqshead as qs on qs.sotrno = sq.trno
    left join glstock as gstock on gstock.refx = qs.trno
    left join glhead as ghead on ghead.trno = gstock.trno
    left join billingaddr as s on s.line=head.shipid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where date(head.dateid) between '$start' and '$end' $filter
    group by num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       s.addrline1,s.addrline2,s.city,s.province,s.country,s.zipcode,
       head.dateid,
       head.yourref,
       head.recommend,head.others,
       stock.iss,
       stock.amt,
       emp.clientname,
       head.awb,
       head.`action`,
       head.rfnno,
       head.dateclose,
       ghead.docno,
       num.postdate,
       item.itemname,brand.brand_desc,mm.model_name,i.itemdescription";
            break;
          case 1: // unposted
            $query = "select
       num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode) as shipaddress,
       left(head.dateid,10) as dateid,
       head.yourref as ponum,
       case head.recommend when 'Others, please Specify' then head.others else head.recommend end as recommend,
       stock.iss,
       stock.amt,
       emp.clientname as filedby,
       head.awb,
       head.`action`,
       head.rfnno,
       left(head.dateclose,10) as dateclose,
       ghead.docno as sjdocno,
       left(num.postdate, 10) as postdate,
       concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
       ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno
    from rfhead as head
    left join transnum as num on num.trno = head.trno
    left join rfstock as stock on stock.trno = head.trno
    left join item on stock.itemid = item.itemid
    left join client on head.client = client.client
    left join client as emp on head.empid = emp.clientid
    left join hsqhead as sq on sq.trno = head.sotrno
    left join hqshead as qs on qs.sotrno = sq.trno
    left join glstock as gstock on gstock.refx = qs.trno
    left join glhead as ghead on ghead.trno = gstock.trno
    left join billingaddr as s on s.line=head.shipid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where date(head.dateid) between '$start' and '$end' $filter
    group by num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       s.addrline1,s.addrline2,s.city,s.province,s.country,s.zipcode,
       head.dateid,
       head.yourref,
       head.recommend,head.others,
       stock.iss,
       stock.amt,
       emp.clientname,
       head.awb,
       head.`action`,
       head.rfnno,
       head.dateclose,
       ghead.docno,
       num.postdate,
       item.itemname,brand.brand_desc,mm.model_name,i.itemdescription";
            break;
          default: // sana all
            $query = "select
       num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode) as shipaddress,
       left(head.dateid,10) as dateid,
       head.yourref as ponum,
       case head.recommend when 'Others, please Specify' then head.others else head.recommend end as recommend,
       stock.iss,
       stock.amt,
       emp.clientname as filedby,
       head.awb,
       head.`action`,
       head.rfnno,
       left(head.dateclose,10) as dateclose,
       ghead.docno as sjdocno,
       left(num.postdate, 10) as postdate,
       concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
       ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno
    from hrfhead as head
    left join transnum as num on num.trno = head.trno
    left join hrfstock as stock on stock.trno = head.trno
    left join item on stock.itemid = item.itemid
    left join client on head.client = client.client
    left join client as emp on head.empid = emp.clientid
    left join hsqhead as sq on sq.trno = head.sotrno
    left join hqshead as qs on qs.sotrno = sq.trno
    left join glstock as gstock on gstock.refx = qs.trno
    left join glhead as ghead on ghead.trno = gstock.trno
    left join billingaddr as s on s.line=head.shipid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where date(head.dateid) between '$start' and '$end' $filter
    group by num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       s.addrline1,s.addrline2,s.city,s.province,s.country,s.zipcode,
       head.dateid,
       head.yourref,
       head.recommend,head.others,
       stock.iss,
       stock.amt,
       emp.clientname,
       head.awb,
       head.`action`,
       head.rfnno,
       head.dateclose,
       ghead.docno,
       num.postdate,
       item.itemname,brand.brand_desc,mm.model_name,i.itemdescription
union all
select
       num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode) as shipaddress,
       left(head.dateid,10) as dateid,
       head.yourref as ponum,
       case head.recommend when 'Others, please Specify' then head.others else head.recommend end as recommend,
       stock.iss,
       stock.amt,
       emp.clientname as filedby,
       head.awb,
       head.`action`,
       head.rfnno,
       left(head.dateclose,10) as dateclose,
       ghead.docno as sjdocno,
       left(num.postdate, 10) as postdate,
       concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
       ifnull(group_concat(rr.serial separator '\\n\\r'),'') as serialno
    from rfhead as head
    left join transnum as num on num.trno = head.trno
    left join rfstock as stock on stock.trno = head.trno
    left join item on stock.itemid = item.itemid
    left join client on head.client = client.client
    left join client as emp on head.empid = emp.clientid
    left join hsqhead as sq on sq.trno = head.sotrno
    left join hqshead as qs on qs.sotrno = sq.trno
    left join glstock as gstock on gstock.refx = qs.trno
    left join glhead as ghead on ghead.trno = gstock.trno
    left join billingaddr as s on s.line=head.shipid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where date(head.dateid) between '$start' and '$end' $filter
    group by num.center,
       head.trno,
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.cperson,
       s.addrline1,s.addrline2,s.city,s.province,s.country,s.zipcode,
       head.dateid,
       head.yourref,
       head.recommend,head.others,
       stock.iss,
       stock.amt,
       emp.clientname,
       head.awb,
       head.`action`,
       head.rfnno,
       head.dateclose,
       ghead.docno,
       num.postdate,
       item.itemname,brand.brand_desc,mm.model_name,i.itemdescription";
            break;
        }
        break;
    }
    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
    $end        = date("m/d/Y", strtotime($config['params']['dataparams']['end']));

    $filterusername  = $config['params']['dataparams'];

    $reporttype = $config['params']['dataparams']['reporttype'];

    $posttype   = $config['params']['dataparams']['posttype'];




    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    $str = '';
    $count = 38;
    $page = 40;

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Request for Replacement/Return (' . $reporttype . ')', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From ' . $start . ' to ' . $end, null, null, false, $border, '', 'C', $font, '12', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams'];
    $prefix     = $config['params']['dataparams'];

    $count = 38;
    $page = 40;
    $str = '';
    $layoutsize = '1090';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $docno = "";
    $i = 0;
    $total = 0;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Filedby', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Clientname', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Shipping Address', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('RFR #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Reason', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Recommendation', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('PO #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Delivery Receipt', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Serial No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Quantity', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Unit Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AWB #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Action Take', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Date Closed', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();


    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->filedby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->shipaddress, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rfnno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->reason, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->recommend, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->ponum, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->sjdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemdescription, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->serialno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->iss, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->amt, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->awb, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->action, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dateclose, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();



        $i++;
      }

      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
    }
    $str .= $this->reporter->endreport();

    return $str;
  }



  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $reporttype = $config['params']['dataparams']['reporttype'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);


    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->others, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OTHERS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class