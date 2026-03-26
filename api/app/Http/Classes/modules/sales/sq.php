<?php

namespace App\Http\Classes\modules\sales;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\modules\reportlist\hris_reports\employment_status_entry_or_change;

class sq
{
	private $btnClass;
	private $fieldClass;
	private $tabClass;
	public $modulename = 'SALES ORDER';
	public $gridname = 'inventory';
	private $companysetup;
	private $coreFunctions;
	private $othersClass;
	private $logger;
	public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
	public $tablenum = 'transnum';
	public $head = 'sqhead';
	public $hhead = 'hsqhead';
	public $stock = 'qsstock';
	public $hstock = 'hqsstock';
	public $tablelogs = 'transnum_log';
	public $tablelogs_del = 'del_transnum_log';
	private $stockselect;
	public $dqty = 'isqty';
	public $hqty = 'iss';
	public $damt = 'isamt';
	public $hamt = 'amt';
	public $fields = ['trno', 'docno', 'dateid', 'delcharge'];
	public $except = ['trno', 'dateid'];
	public $showfilteroption = true;
	public $showfilter = true;
	public $showcreatebtn = true;
	public $showfilterlabel = [
		['val' => 'draft', 'label' => 'Draft', 'color' => 'red'],
		['val' => 'locked', 'label' => 'Locked', 'color' => 'cyan'],
		['val' => 'posted', 'label' => 'Posted', 'color' => 'blue'],
		['val' => 'drsi', 'label' => 'For DR/SI', 'color' => 'purple'],
		['val' => 'overdue', 'label' => 'Overdue', 'color' => 'orange'],
		['val' => 'complete', 'label' => 'Completed', 'color' => 'green'],
		['val' => 'close', 'label' => 'Closed', 'color' => 'grey'],
		['val' => 'all', 'label' => 'All', 'color' => 'pink']
	];
	private $reporter;
	private $helpClass;

	public function __construct()
	{
		$this->btnClass = new buttonClass;
		$this->fieldClass = new txtfieldClass;
		$this->tabClass = new tabClass;
		$this->companysetup = new companysetup;
		$this->coreFunctions = new coreFunctions;
		$this->othersClass = new othersClass;
		$this->logger = new Logger;
		$this->reporter = new SBCPDF;
		$this->helpClass = new helpClass;
	}

	public function getAttrib()
	{
		$attrib = array(
			'view' => 2469,
			'edit' => 2470,
			'new' => 2471,
			'save' => 2472,
			// 'change' => 2473, remove change doc
			'delete' => 2474,
			'print' => 2475,
			'lock' => 2476,
			'unlock' => 2477,
			'post' => 2478,
			'unpost' => 2479,
			'deleteitem' => 3718
		);
		return $attrib;
	}


	public function createdoclisting($config)
	{
		$action = 0;
		$liststatus = 1;
		$date = 2;
		$doc = 3;
		$deldate = 4;
		$client = 5;
		$yourref = 6;
		$ext = 7;
		$companyid = $config['params']['companyid'];

		$getcols = ['action', 'liststatus',  'listdate', 'listdocument', 'listdeldate', 'listclientname', 'yourref', 'ext'];
		$stockbuttons = ['view'];
		$cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
		$cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
		$cols[$doc]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
		$cols[$doc]['label'] = 'SO #';
		$cols[$client]['label'] = 'Customer Name';
		if ($companyid == 10 or $companyid == 12) { //afti, afti usd
			$cols[$yourref]['label'] = 'Customer PO';
			$cols[$liststatus]['name'] = 'statuscolor';
			$cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
		}
		return $cols;
	}

	public function paramsdatalisting($config)
	{
		$fields = [];
		array_push($fields, 'selectprefix', 'docno');
		$col1 = $this->fieldClass->create($fields);
		data_set($col1, 'docno.type', 'input');
		data_set($col1, 'docno.label', 'Search');
		data_set($col1, 'selectprefix.label', 'Search by');
		data_set($col1, 'selectprefix.type', 'lookup');
		data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
		data_set($col1, 'selectprefix.action', 'lookupsearchby');

		$data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");

		return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
	}

	public function loaddoclisting($config)
	{

		$date1 = date('Y-m-d', strtotime($config['params']['date1']));
		$date2 = date('Y-m-d', strtotime($config['params']['date2']));
		$itemfilter = $config['params']['itemfilter'];
		$doc = $config['params']['doc'];
		$center = $config['params']['center'];
		$condition = '';
		$leftjoin = '';
		$join = '';
		$limit = '';
		$status = '';
		$addparams = '';
		$searchfilter = $config['params']['search'];
		$statcolor = 'red';

		$group = " group by head.docno,head.trno,qt.clientname,head.dateid,head.lockdate,head.createby,head.editby,head.viewby,num.postedby,qt.yourref,qt.deldate,qt.yourref ";
		switch ($itemfilter) {
			case 'draft':
				$condition = ' and num.postdate is null ' . $group;
				$status = 'DRAFT';
				break;
			case 'posted':
				$condition = '  and qs.void <>0 and num.postdate is not null group by head.trno,head.docno,qt.clientname,head.dateid,
                head.lockdate,head.createby,head.editby,head.viewby,num.postedby,qt.yourref,qt.deldate having sum(qs.iss-qs.sjqa-qs.voidqty)<>0';
				$status = 'POSTED';
				$statcolor = 'blue';
				break;
			case 'locked':
				$condition = ' and head.lockdate is not null and num.postdate is null ' . $group;
				$status = 'LOCKED';
				$statcolor = 'red';
				break;
			case 'drsi': // with stocks but not yet served
				$leftjoin = ' left join hqsstock as stock on stock.trno=qt.trno left join rrstatus as rr on rr.itemid = stock.itemid ';
				$condition = ' and rr.bal<>0 and stock.iss>stock.sjqa and num.postdate is not null ' . $group;
				$status = 'FOR DR/SI';
				$statcolor = 'grey';
				break;
			case 'overdue': // overdue delivery date and not yet fully served
				$leftjoin = ' left join hqsstock as stock on stock.trno=qt.trno ';
				$condition = ' and stock.iss>(stock.sjqa+stock.voidqty) and stock.void =0 and num.postdate is not null and qt.deldate < now() ' . $group;
				$status = 'OVERDUE';
				$statcolor = 'red';
				break;
			case 'complete': // all item served or void
				$condition = '  and qs.void =0 and num.postdate is not null group by head.trno,head.docno,qt.clientname,head.dateid,
                head.lockdate,head.createby,head.editby,head.viewby,num.postedby,qt.yourref,qt.deldate having sum(qs.iss-qs.sjqa-qs.voidqty)=0';
				$status = 'COMPLETED';
				$statcolor = 'green';
				break;
			case 'close': // all items served or void and SJ already posted
				$condition = '  and qs.void =0 and num.postdate is not null and (select ifnull(sum(trno),0) from lastock where refx = qs.trno)=0 group by head.trno,head.docno,qt.clientname,head.dateid,
                head.lockdate,head.createby,head.editby,head.viewby,num.postedby,qt.yourref,qt.deldate having sum(qs.iss-qs.sjqa-qs.voidqty)=0';
				$status = 'CLOSE';
				$statcolor = 'grey';
				break;
		}


		$companyid = $config['params']['companyid'];
		switch ($companyid) {
			case 10: //afti
			case 12: //afti usd
				$dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
				if ($searchfilter == "") $limit = 'limit 50';
				break;
			default:
				$dateid = "left(head.dateid,10) as dateid";
				if ($searchfilter == "") $limit = 'limit 150';
				break;
		}

		if (isset($config['params']['doclistingparam'])) {
			$test = $config['params']['doclistingparam'];
			if ($test['selectprefix'] != "") {
				switch ($test['selectprefix']) {
					case 'Item Code':
						$addparams = " and (item.partno like '%" . $test['docno'] . "%')";
						break;
					case 'Item Name':
						$addparams = " and (item.itemname like '%" . $test['docno'] . "%')";
						break;
					case 'Model':
						$addparams = " and (model.model_name like '%" . $test['docno'] . "%')";
						break;
					case 'Brand':
						$addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%')";
						break;
					case 'Item Group':
						$addparams = " and (p.name like '%" . $test['docno'] . "%')";
						break;
				}

				if (isset($test)) {
					$join = " left join item on item.itemid = qs.itemid 
					left join model_masterfile as model on model.model_id = item.model 
					left join frontend_ebrands as brand on brand.brandid = item.brand
					left join projectmasterfile as p on p.line = item.projectid  ";

					$limit = '';
				}
			}
		}

		$filtersearch = "";
		if (isset($config['params']['search'])) {
			$searchfield = ['head.docno', 'qt.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'qt.yourref'];
			if ($searchfilter != "") {
				$filtersearch = $this->othersClass->multisearch($searchfield, $searchfilter);
			}
		} else {
			$limit = 'limit 25';
		}

		if ($itemfilter == 'all') {
			$condition .= $group;
			$status = 'POSTED';
			$statcolor = 'blue';
		}
		$qry = "select distinct head.dateid as date2,head.trno,head.docno,qt.clientname,$dateid, case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end as status,'" . $statcolor . "' as statuscolor,head.createby,head.editby,head.viewby,num.postedby,qt.yourref ,date_format(qt.deldate,'%m-%d-%Y') as deldate,format(sum(qs.ext),2) as ext
        from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join hqshead as qt on qt.sotrno=head.trno 
        left join hqsstock as qs on qs.trno=qt.trno 
        " . $leftjoin . $join . "
        where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $addparams  .  $filtersearch . $condition . "
        
        union all
        select distinct head.dateid as date2,head.trno,head.docno,qt.clientname,$dateid,'$status' as status,case sum(qs.iss-qs.sjqa-qs.voidqty) when 0 then 'green' else '" . $statcolor . "' end as statuscolor,head.createby,head.editby,head.viewby, num.postedby,qt.yourref,date_format(qt.deldate,'%m-%d-%Y') as deldate,format(sum(qs.ext),2) as ext
        from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join hqshead as qt on qt.sotrno=head.trno 
        left join hqsstock as qs on qs.trno=qt.trno 
        " . $leftjoin . $join . "
        where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $addparams . $filtersearch . $condition . "
        
        order by date2 desc,docno desc $limit";
		$data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
		return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
	}

	public function createHeadbutton($config)
	{
		$btns = array(
			'load',
			'new',
			'save',
			'delete',
			'cancel',
			'print',
			'post',
			'unpost',
			'lock',
			'unlock',
			'logs',
			'edit',
			'backlisting',
			'toggleup',
			'toggledown',
			'help',
			'others'
		);

		$buttons = $this->btnClass->create($btns);
		$step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
		$step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
		$step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
		$step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
		$step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
		$step6 = $this->helpClass->getFields(['btndelete']);


		$buttons['help']['items'] = [
			'create' => ['label' => 'How to create New Document', 'action' => $step1],
			'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
			'additem' => ['label' => 'How to add item/s', 'action' => $step3],
			'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
			'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
			'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
		];

		$buttons['others']['items'] = [
			'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
			'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
			'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
			'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
		];

		if ($this->companysetup->getisshowmanual($config['params'])) {
			$buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
		}

		return $buttons;
	} // createHeadbutton

	public function createtab2($access, $config)
	{
		$companyid = $config['params']['companyid'];

		if ($companyid == 10 || $companyid == 12) { //afti, afti usd
			$billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
			$termstaxandcharges = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewtermstaxcharges']];
			$instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];
			$viewleadtimesetting = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewleadtimesetting']];

			$tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysqcomment', 'label' => 'Comments']];
			$comments = $this->tabClass->createtab($tab, []);

			$return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
			$return['INSTRUCTION'] = ['icon' => 'fa fa-info', 'customform' => $instructiontab];
			$return['LEAD TIME DURATION'] = ['icon' => 'fa fa-clock', 'customform' => $viewleadtimesetting];
			$return['TERMS, TAXES AND CHARGES'] = ['icon' => 'fa fa-file-invoice', 'customform' => $termstaxandcharges];
			$return['COMMENTS'] = ['icon' => 'fa fa-comment', 'tab' => $comments];
		}
		$tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
		$obj = $this->tabClass->createtab($tab, []);

		$return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

		return $return;
	}


	public function createTab($access, $config)
	{
		$sq_makepo = $this->othersClass->checkAccess($config['params']['user'], 2872);
		$deliverydate = $this->othersClass->checkAccess($config['params']['user'], 2874);
		$companyid = $config['params']['companyid'];

		$action = 0;
		$itemdesc = 1;
		$isqty = 2;
		$uom = 3;
		$isamt = 4;
		$disc = 5;
		$ext = 6;
		$wh = 7;
		$whname = 8;
		$qa = 9;
		$void = 10;
		$voidqty = 11;
		$ref = 12;
		$itemname = 13;
		$barcode = 14;
		$stock_projectname = 15;
		$noprint = 16;

		$gridcolumn = ['action', 'itemdescription', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'insurance', 'wh', 'whname', 'qa', 'void', 'voidqty', 'ref', 'itemname', 'barcode', 'stock_projectname', 'noprint'];

		$headgridbtns = ['viewref', 'viewitemstockinfo', 'viewdiagram'];
		if ($deliverydate != 0) {
			array_push($headgridbtns, 'viewdeliverydate');
		}
		if ($sq_makepo != 0) {
			array_push($headgridbtns, 'sq_makepo');
		}


		$tab = [
			$this->gridname => [
				'gridcolumns' => $gridcolumn,
				'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
				'headgridbtns' => $headgridbtns
			],
		];

		$stockbuttons = ['showbalance', 'iteminfo'];

		$obj = $this->tabClass->createtab($tab, $stockbuttons);

		if ($companyid == 10 || $companyid == 12) { //afti, afti usd
			$obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
			$obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
			$obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
		}

		$obj[0]['inventory']['columns'][$wh]['type'] = 'label';
		$obj[0]['inventory']['columns'][$uom]['type'] = 'label';
		$obj[0]['inventory']['columns'][$disc]['type'] = 'label';
		$obj[0]['inventory']['columns'][$isamt]['type'] = 'label';
		$obj[0]['inventory']['columns'][$isqty]['type'] = 'label';
		$obj[0]['inventory']['columns'][$ref]['type'] = 'label';

		$obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
		$obj[0]['inventory']['columns'][$barcode]['label'] = '';

		$obj[0]['inventory']['columns'][$void]['type'] = 'coldel';
		$obj[0]['inventory']['columns'][$qa]['type'] = 'coldel';

		$obj[0]['inventory']['columns'][$isqty]['style'] = 'text-align:right;width:80px';

		$obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'label';
		if ($companyid != 10 && $companyid != 12) { //not afti, not afti usd
			$obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
			$obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
		}

		if ($companyid == 10 || $companyid == 12) { //afti, afti usd
			$obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
			$obj[0]['inventory']['columns'][$whname]['type'] = 'label';
		} else {
			$obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
		}

		$obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

		return $obj;
	}

	public function createtabbutton($config)
	{
		$tbuttons = ['unlinksq'];
		$obj = $this->tabClass->createtabbutton($tbuttons);
		return $obj;
	}

	public function createHeadField($config)
	{
		$companyid = $config['params']['companyid'];
		$fields = ['docno', 'client', 'clientname', 'tin', 'businesstype'];
		$col1 = $this->fieldClass->create($fields);
		data_set($col1, 'client.lookupclass', 'sqcustomer');
		data_set($col1, 'client.condition', ['checkstock']);
		data_set($col1, 'docno.label', 'Transaction#');
		data_set($col1, 'clientname.class', 'sbccsreadonly');
		data_set($col1, 'tin.class', 'sbccsreadonly');
		data_set($col1, 'businesstype.class', 'sbccsreadonly');

		if ($companyid == 10 || $companyid == 12) { //afti, afti usd
			data_set($col1, 'clientname.type', 'textarea');
			data_set($col1, 'businesstype.type', 'textarea');
			data_set($col1, 'businesstype.label', 'Business Style');
		}

		$fields = [['dateid', 'terms'], ['deldate', 'due'], 'dwhname', 'dagentname'];
		$col2 = $this->fieldClass->create($fields);
		data_set($col2, 'due.class', 'sbccsreadonly');
		data_set($col2, 'terms.class', 'sbccsreadonly');
		data_set($col2, 'deldate.class', 'sbccsreadonly');
		data_set($col2, 'due.class', 'sbccsreadonly');
		data_set($col2, 'due.label', 'PO Date');
		data_set($col2, 'dwhname.class', 'sbccsreadonly');
		data_set($col2, 'dagentname.class', 'sbccsreadonly');

		data_set($col2, 'terms.type', 'input');
		data_set($col2, 'dwhname.type', 'input');
		data_set($col2, 'dagentname.type', 'input');

		$fields = ['yourref', 'dbranchname', 'ddeptname'];
		$col3 = $this->fieldClass->create($fields);
		data_set($col3, 'yourref.class', 'sbccsreadonly');
		data_set($col3, 'yourref.label', 'PO #');
		if ($companyid == 10 || $companyid == 12) { //afti, afti usd
			data_set($col3, 'yourref.label', 'Customer PO');
		} else {
			data_set($col3, 'yourref.label', 'PO#');
		}

		data_set($col3, 'dbranchname.required', true);
		data_set($col3, 'ddeptname.required', true);
		data_set($col3, 'ddeptname.label', 'Department');

		data_set($col3, 'cur.type', 'input');

		$fields = [['cur', 'forex'], 'rem', ['lbltotal', 'ext'], ['lbltaxes', 'taxesandcharge'], ['lblgrandtotal', 'totalcash']];
		$col4 = $this->fieldClass->create($fields);
		data_set($col4, 'rem.required', false);
		data_set($col4, 'rem.class', 'sbccsreadonly');
		data_set($col4, 'rem.maxlength', 500);
		data_set($col4, 'cur.class', 'sbccsreadonly');
		data_set($col4, 'forex.class', 'sbccsreadonly');
		data_set($col4, 'ext.class', 'sbccsreadonly');
		data_set($col4, 'ext.label', '');
		data_set($col4, 'taxesandcharge.label', '');
		data_set($col4, 'taxesandcharge.class', 'sbccsreadonly');
		data_set($col4, 'totalcash.label', '');

		return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
	}

	public function createnewtransaction($docno, $params)
	{
		$data = [];
		$data[0]['trno'] = 0;
		$data[0]['qtrno'] = 0;
		$data[0]['docno'] = $docno;
		$data[0]['dateid'] = $this->othersClass->getCurrentDate();
		$data[0]['due'] = $this->othersClass->getCurrentDate();
		$data[0]['qtdateid'] = $this->othersClass->getCurrentDate();
		$data[0]['deldate'] = $this->othersClass->getCurrentDate();
		$data[0]['client'] = '';
		$data[0]['clientname'] = '';
		$data[0]['yourref'] = '';
		$data[0]['shipto'] = '';
		$data[0]['ourref'] = '';
		$data[0]['rem'] = '';
		$data[0]['agent'] = '';
		$data[0]['agentname'] = '';
		$data[0]['dagentname'] = '';
		$data[0]['branchcode'] = '';
		$data[0]['branchname'] = '';
		$data[0]['dbranchname'] = '';
		$data[0]['ddeptname'] = '';
		$data[0]['deptid'] = '0';
		$data[0]['dept'] = '';
		$data[0]['branch'] = 0;
		$data[0]['terms'] = '';
		$data[0]['forex'] = 1;
		$data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
		$data[0]['address'] = '';
		$data[0]['wh'] = $this->companysetup->getwh($params);
		$name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
		$data[0]['whname'] = $name;
		$data[0]['tin'] = '';
		$data[0]['businesstype'] = '';
		$data[0]['delcharge'] = '0';
		return $data;
	}

	public function loadheaddata($config)
	{
		$doc = $config['params']['doc'];
		$center = $config['params']['center'];
		$trno = $config['params']['trno'];
		$companyid = $config['params']['companyid'];
		if ($trno == 0) {
			$trno = $this->othersClass->readprofile('TRNO', $config);
			if ($trno == '') {
				$trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
			}
			$config['params']['trno'] = $trno;
		} else {
			$this->othersClass->checkprofile('TRNO', $trno, $config);
		}
		$head = [];
		$islocked = $this->othersClass->islocked($config);
		$isposted = $this->othersClass->isposted($config);
		$table = $this->head;
		$htable = $this->hhead;
		$tablenum = $this->tablenum;
		$qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         qt.terms,
         qt.cur,
         qt.tax,
         qt.forex,
         qt.yourref,
         qt.ourref,
         left(head.dateid,10) as dateid, 
         left(qt.dateid,10) as qtdateid, 
         left(qt.deldate,10) as deldate, 
         qt.clientname,
         qt.address, 
         qt.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         qt.rem,
         ifnull(qt.agent, '') as agent, 
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         qt.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(qt.due,10) as due, 
         client.groupid, ifnull(qt.trno,0) as qtrno,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, 
         head.branch,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,qt.deptid,'' as ddeptname,ifnull(client.tin,'') as tin,ifnull(category.cat_name,'') as businesstype,head.delcharge";

		$qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join hqshead as qt on qt.sotrno=head.trno
        left join client on qt.client = client.client
        left join client as warehouse on warehouse.client = qt.wh
        left join client as agent on agent.client = qt.agent
        left join client as b on b.clientid = qt.branch
        left join client as d on d.clientid = qt.deptid
        left join category_masterfile as category on category.cat_id=client.category
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join hqshead as qt on qt.sotrno=head.trno
        left join client on qt.client = client.client
        left join client as warehouse on warehouse.client = qt.wh
        left join client as agent on agent.client = qt.agent
        left join client as b on b.clientid = qt.branch
        left join client as d on d.clientid = qt.deptid
        left join category_masterfile as category on category.cat_id=client.category where head.trno = ? and num.center=? ";

		$head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

		if (!empty($head)) {
			$stock = $this->openstock($head[0]->qtrno, $config);
			$viewdate = $this->othersClass->getCurrentTimeStamp();
			$viewby = $config['params']['user'];
			$msg = 'Data Fetched Success';
			if (isset($config['msg'])) {
				$msg = $config['msg'];
			}
			$this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

			if ($companyid == 10 || $companyid == 12) { //afti, afti usd
				if ($head[0]->tax == '12') {
					$sqry = "select sum(ext) as value from $this->hstock as stock 
                    left join item on item.itemid=stock.itemid 
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
                    left join client as warehouse on warehouse.clientid=stock.whid
                    left join hqshead as head on head.trno=stock.trno 
                    left join projectmasterfile as prj on prj.line = stock.projectid
                    where stock.trno =? ";
					$ext = round($this->coreFunctions->datareader($sqry, [$head[0]->qtrno]), 2);

					$tax = $charges = 0;
					$charges = $ext * .12;
					$tax = round($ext - $charges, 2);
					$amount = $ext + $charges;
					$taxdef = round($this->coreFunctions->datareader("select taxdef as value from hheadinfotrans where trno = ?", [$head[0]->qtrno]), 2);

					if ($taxdef != 0) {
						$charges = $taxdef;
						$amount = $ext + $charges;
					}

					$head[0]->ext = number_format($ext, $this->companysetup->getdecimal('default', $config['params']));
					$head[0]->taxesandcharge = number_format($charges, $this->companysetup->getdecimal('default', $config['params']));
					$head[0]->totalcash = number_format($amount, 2);
				} else {
					$sqry = "select sum(ext) as value from $this->hstock as stock 
                    left join item on item.itemid=stock.itemid 
                    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
                    left join client as warehouse on warehouse.clientid=stock.whid
                    left join hqshead as head on head.trno=stock.trno 
                    left join projectmasterfile as prj on prj.line = stock.projectid
                    where stock.trno =? ";
					$ext = $this->coreFunctions->datareader($sqry, [$head[0]->qtrno]);

					$tax = $charges = 0;
					$charges = 0;
					$tax = 0;
					$amount = $ext + $charges;
					$taxdef = $this->coreFunctions->datareader("select taxdef as value from hheadinfotrans where trno = ?", [$head[0]->qtrno]);

					if ($taxdef != 0) {
						$charges = $taxdef;
						$amount = $ext + $charges;
					}

					$head[0]->ext = number_format($ext, $this->companysetup->getdecimal('price', $config['params']));
					$head[0]->taxesandcharge = number_format($charges, $this->companysetup->getdecimal('price', $config['params']));
					$head[0]->totalcash = number_format($amount, 2);
				}
			}
			return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
		} else {
			$head[0]['trno'] = 0;
			$head[0]['docno'] = '';
			return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
		}
	}

	public function updatehead($config, $isupdate)
	{
		$companyid = $config['params']['companyid'];
		$head = $config['params']['head'];
		$data = [];
		$data['editdate'] = $this->othersClass->getCurrentTimeStamp();
		$data['editby'] = $config['params']['user'];

		if ($isupdate) {
			unset($this->fields[1]);
			unset($head['docno']);
		}

		foreach ($this->fields as $key) {
			if (array_key_exists($key, $head)) {
				$data[$key] = $head[$key];
				if (!in_array($key, $this->except)) {
					$data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
				} //end if    
			}
		}

		$data['editdate'] = $this->othersClass->getCurrentTimeStamp();
		$data['editby'] = $config['params']['user'];

		if ($isupdate) {
			$this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
			$this->coreFunctions->sbcupdate('hqshead', ['sotrno' => $head['trno']], ['trno' => $head['qtrno']]);
		} else {
			$data['doc'] = $config['params']['doc'];
			$data['createdate'] = $this->othersClass->getCurrentTimeStamp();
			$data['createby'] = $config['params']['user'];

			if ($this->coreFunctions->sbcinsert($this->head, $data)) {
				$this->coreFunctions->sbcupdate('hqshead', ['sotrno' => $head['trno']], ['trno' => $head['qtrno']]);
				$qtrno = $this->coreFunctions->getfieldvalue("hqshead", "trno", "sotrno=?", [$head['trno']]);
				$this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$head['trno'], $qtrno]);
				$this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
			}
		}
	} // end function


	public function deletetrans($config)
	{
		$trno = $config['params']['trno'];
		$doc = $config['params']['doc'];
		$table = $config['docmodule']->tablenum;
		$docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
		$qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
		$trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
		$qtrno = $this->coreFunctions->datareader("select trno as value from hqshead where sotrno = ? ", [$trno]);

		$this->coreFunctions->execqry('update hqshead set sotrno=0 where sotrno=?', 'delete', [$trno]);
		$this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
		$this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
		$this->othersClass->deleteattachments($config);
		$this->coreFunctions->execqry('update attendee set optrno=? where optrno=?', 'update', [$qtrno, $trno]);
		$this->logger->sbcdel_log($trno, $config, $docno);
		return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
	} //end function


	public function posttrans($config)
	{
		$trno = $config['params']['trno'];
		$user = $config['params']['user'];
		$qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
		$isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

		if (!empty($isitemzeroqty)) {
			return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
		}
		$docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

		$deldate = $this->coreFunctions->datareader("select  qt.deldate as value from " . $this->head . " as head left join hqshead as qt on qt.sotrno=head.trno where head.trno = ?", [$trno]);

		$dateid = $this->coreFunctions->datareader("select dateid as value from " . $this->head . "  where trno = ?", [$trno]);

		if ($deldate < $dateid) {
			return ['status' => false, 'msg' => 'Posting failed. The delivery date must greater than today`s date.'];
		}

		if ($this->othersClass->isposted($config)) {
			return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
		}
		//for glhead
		$qry = "insert into " . $this->hhead . " (trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, 
        lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, pdate,delcharge)
        SELECT trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, 
        lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, '" . date('Y-m-d') . "' ,delcharge
        FROM " . $this->head . " where trno=? limit 1";
		$posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
		if ($posthead) {

			$date = $this->othersClass->getCurrentTimeStamp();
			$data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 8];
			$this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
			$this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
			$this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
			return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
		} else {
			return ['status' => false, 'msg' => 'Error on Posting Head'];
		}
	} //end function

	public function unposttrans($config)
	{
		$trno = $config['params']['trno'];
		$user = $config['params']['user'];

		$qt_trno = $this->coreFunctions->datareader("select trno as value from hqshead where sotrno = ? LIMIT 1", [$trno]);

		if($qt_trno!=0){
			$checking = $this->coreFunctions->opentable("select trno from lastock where refx = ?
			union all
			select trno from glstock where refx = ?", [$qt_trno, $qt_trno]);

			if (!empty($checking)) {
				return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
			}

			$qry = "select trno from " . $this->hstock . " where trno=? and ((qa+sjqa+voidqty)>0 or void<>0)";
			$data = $this->coreFunctions->opentable($qry, [$qt_trno]);
			if (!empty($data)) {
				return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
			}

			$qry = "select trno from " . $this->hstock . " where trno=? and poqa>0 ";
			$data = $this->coreFunctions->opentable($qry, [$qt_trno]);
			if (!empty($data)) {
				return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already have PO...'];
			}
		}
	

		$docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

		$qry = "insert into " . $this->head . "(trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate,delcharge)
                select trno, doc, docno, dateid, voiddate, approvedby, approveddate, printtime, lockuser, lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate,delcharge from " . $this->hhead . " where trno=? limit 1";
		//head
		if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

			$this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null,statid=0 where trno=?", 'update', [$trno]);
			$this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
			$this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
			return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
		} else {
			return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, head problems...'];
		}
	} //end function

	private function getstockselect($config)
	{
		$companyid = $config['params']['companyid'];
		$qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
		if ($companyid == 10 || $companyid == 12) { //afti, afti usd
			$qty_dec = 0;
		}

		$sqlselect = "select 
		item.itemid,
		stock.trno, 
		stock.line,
		item.barcode, 
		item.itemname,
		concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
		stock.uom, 
		stock.iss,
		FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
		FORMAT(stock.isqty," . $qty_dec . ")  as isqty,
		FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
		left(stock.encodeddate,10) as encodeddate,
		stock.disc, 
		case when stock.void=0 then 'false' else 'true' end as void,
		round((stock.iss-stock.sjqa-stock.voidqty)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
		stock.whid,
		warehouse.client as wh,
		warehouse.clientname as whname,
		stock.loc,stock.expiry,
		item.brand,
		stock.rem, 
		head.docno as ref,
		ifnull(uom.factor,1) as uomfactor,
		'' as bgcolor,
		case when stock.void=0 then '' else 'bg-red-2' end as errcolor,case when (stock.sjqa+stock.voidqty)<>stock.iss and stock.void<>1 then 'bg-orange-2' else '' end as qacolor,
		prj.name as stock_projectname,
		stock.projectid as projectid,stock.sgdrate,
		case when stock.noprint=0 then 'false' else 'true' end as noprint,stock.voidqty/uom.factor as voidqty,stock.sortline ";
		return $sqlselect;
	}

	public function openstock($trno, $config)
	{
		$sqlselect = $this->getstockselect($config);

		$qry = $sqlselect . "  
        FROM $this->hstock as stock 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as warehouse on warehouse.clientid=stock.whid
        left join hqshead as head on head.trno=stock.trno 
        left join projectmasterfile as prj on prj.line = stock.projectid
		left join model_masterfile as mm on mm.model_id = item.model
		left join frontend_ebrands as brand on brand.brandid = item.brand
    	left join iteminfo as i on i.itemid  = item.itemid
        where stock.trno =? order by stock.sortline,stock.line ";

		$stock = $this->coreFunctions->opentable($qry, [$trno]);
		return $stock;
	} //end function

	public function openstockline($config)
	{
		$sqlselect = $this->getstockselect($config);
		$trno = $config['params']['trno'];
		$line = $config['params']['line'];
		$qry = $sqlselect . "  
        FROM $this->stock as stock
        left join item on item.itemid=stock.itemid 
        left join model_masterfile as mm on mm.model_id = item.model
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as warehouse on warehouse.clientid=stock.whid 
        left join projectmasterfile as prj on prj.line = stock.projectid
		left join frontend_ebrands as brand on brand.brandid = item.brand
    	left join iteminfo as i on i.itemid  = item.itemid
        where stock.trno = ? and stock.line = ? ";
		$stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
		return $stock;
	} // end function

	public function stockstatus($config)
	{
		switch ($config['params']['action']) {
			case 'createversion':
				$return = $this->posttrans($config);
				if ($return['status']) {
					return $this->othersClass->createversion($config);
				} else {
					return $return;
				}
				break;
			case 'additem':
				$return =  $this->additem('insert', $config);

				return $return;
				break;
			case 'addallitem': // save all item selected from lookup
				return $this->addallitem($config);
				break;
			case 'quickadd':
				return $this->quickadd($config);
				break;
			case 'deleteitem':
				return $this->deleteitem($config);
				break;
			case 'saveitem': //save all item edited
				return $this->updateitem($config);
				break;
			case 'saveperitem':
				return $this->updateperitem($config);
				break;
			case 'deleteallitem':
				return $this->deleteallitem($config);
				break;
			default:
				return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
				break;
		}
	}

	public function stockstatusposted($config)
	{
		$action = $config['params']['action'];
		if ($action == 'stockstatusposted') {
			$action = $config['params']['lookupclass'];
		}

		switch ($action) {
			case 'diagram':
				return $this->diagram($config);
				break;

			default:
				return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
				break;
		}
	}

	public function getposummaryqry($config)
	{
		$qry = "select stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, so.docno, date(head.dateid) as dateid,
				FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
				FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
				FORMAT(stock.isamt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as rrcost, stock.disc,
				FORMAT(stock.amt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cost,
				FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,
				FORMAT(((stock.qa+stock.sjqa+stock.poqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
				FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
				head.yourref,m.model_name as model,item.famt,stock.sgdrate,stock.projectid,
					head.branch, head.deptid,item.amt4 as tpphp,head.deldate, head.terms, head.cur, head.forex,head.vattype,head.tax,stock.sortline
				from hsqhead as so 
					left join hqshead as head on head.sotrno=so.trno 
					left join hqsstock as stock on stock.trno=head.trno
				left join item on item.itemid=stock.itemid
				left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
				left join model_masterfile as m on m.model_id = item.model
				left join transnum on transnum.trno = head.trno
				left join client as wh on wh.clientid=stock.whid
				where so.doc='SQ' and stock.iss > (stock.poqa + stock.voidqty) and stock.iscanvass=0 and stock.void = 0 and so.trno = ?  order by so.docno,stock.sortline, stock.line";
		return $qry;
	}
	public function diagram($config)
	{

		$data = [];
		$nodes = [];
		$links = [];
		$data['width'] = 1500;
		$startx = 100;

		$qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
				CAST(concat('Total OP Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
				from hophead as head
				left join hopstock as s on s.trno = head.trno
				left join hqsstock as qtstock on qtstock.refx = s.trno and s.line = qtstock.linex
				left join hqshead as qthead on qthead.trno = qtstock.trno
				left join hsqhead as sohead on sohead.trno = qthead.sotrno
				where sohead.trno = ?
				group by head.trno,head.docno,head.dateid,s.refx";
		$t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
		if (!empty($t)) {
			$startx = 550;
			$a = 0;
			foreach ($t as $key => $value) {
				//qs quotation 
				data_set(
					$nodes,
					$t[$key]->docno,
					[
						'align' => 'right',
						'x' => 100,
						'y' => 50 + $a,
						'w' => 250,
						'h' => 80,
						'type' => $t[$key]->docno,
						'label' => $t[$key]->rem,
						'color' => '#88DDFF',
						'details' => [$t[$key]->dateid]
					]
				);
				array_push($links, ['from' => $t[$key]->docno, 'to' => 'qt']);
				$a = $a + 100;

				// quotation
				$qry = "
						select head.docno,left(head.dateid,10) as dateid,
						CAST(concat('Total QS Amt: ',round(sum(s.ext),2)) as CHAR) as rem
						from hqshead as head 
						left join hqsstock as s on s.trno = head.trno
						where head.sotrno = ?
						group by head.docno,head.dateid";
				$x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
				$poref = $t[$key]->docno;
				if (!empty($x)) {
					foreach ($x as $key2 => $value) {
						data_set(
							$nodes,
							'qt',
							[
								'align' => 'left',
								'x' => 500,
								'y' => 50 + $a,
								'w' => 250,
								'h' => 80,
								'type' => $x[$key2]->docno,
								'label' => $x[$key2]->rem,
								'color' => '#ff88dd',
								'details' => [$x[$key2]->dateid]
							]
						);
						array_push($links, ['from' => 'qt', 'to' => 'so']);
						$a = $a + 100;
					}
				}

				// SO
				$qry = "
					select head.docno,left(head.dateid,10) as dateid,
					CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
					from sqhead as head
					left join hqshead as qthead on qthead.sotrno = head.trno
					left join hqsstock as s on s.trno = qthead.trno
					where head.trno = ?
					group by head.docno,head.dateid
					union all
					select head.docno,left(head.dateid,10) as dateid,
					CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
					from hsqhead as head
					left join hqshead as qthead on qthead.sotrno = head.trno
					left join hqsstock as s on s.trno = qthead.trno
					where head.trno = ?
					group by head.docno,head.dateid";
				$sodata = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
				if (!empty($sodata)) {
					foreach ($sodata as $sodatakey => $value) {
						data_set(
							$nodes,
							'so',
							[
								'align' => 'left',
								'x' => 600,
								'y' => 100 + $a,
								'w' => 250,
								'h' => 80,
								'type' => $sodata[$sodatakey]->docno,
								'label' => $sodata[$sodatakey]->rem,
								'color' => 'blue',
								'details' => [$sodata[$sodatakey]->dateid]
							]
						);
						array_push($links, ['from' => 'so', 'to' => 'sj']);
						$a = $a + 100;
					}
				}
			}
		}

		//SJ
		$qry = "
			select sjhead.docno,
			date(sjhead.dateid) as dateid,
			CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
			sjhead.trno
			from hqshead as head
			left join hqsstock as stock on stock.trno = head.trno
			left join hsqhead as sohead on sohead.trno = head.sotrno
			left join glstock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
			left join glhead as sjhead on sjhead.trno = sjstock.trno
			left join arledger as ar on ar.trno = sjhead.trno
			where sohead.trno = ? and sjhead.docno is not null
			group by sjhead.docno, sjhead.dateid, ar.bal, sjhead.trno
			union all 
			select sjhead.docno,
			date(sjhead.dateid) as dateid,
			CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(sum(sjstock.ext),2)) as CHAR) as rem, 
			sjhead.trno
			from hqshead as head
			left join hqsstock as stock on stock.trno = head.trno
			left join hsqhead as sohead on sohead.trno = head.sotrno
			left join lastock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
			left join lahead as sjhead on sjhead.trno = sjstock.trno
			where sohead.trno = ? and sjhead.docno is not null
			group by sjhead.docno, sjhead.dateid, sjhead.trno";
		$t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
		if (!empty($t)) {
			data_set(
				$nodes,
				'sj',
				[
					'align' => 'left',
					'x' => 450 + $startx,
					'y' => 300,
					'w' => 250,
					'h' => 80,
					'type' => $t[0]->docno,
					'label' => $t[0]->rem,
					'color' => 'green',
					'details' => [$t[0]->dateid]
				]
			);

			foreach ($t as $key => $value) {
				//CR
				$rrtrno = $t[$key]->trno;
				$apvqry = "
					select  head.docno, date(head.dateid) as dateid, head.trno,
					CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
					from glhead as head
					left join gldetail as detail on head.trno = detail.trno
					where detail.refx = ?
					union all
					select  head.docno, date(head.dateid) as dateid, head.trno,
					CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
					from lahead as head
					left join ladetail as detail on head.trno = detail.trno
					where detail.refx = ?";
				$apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
				if (!empty($apvdata)) {
					foreach ($apvdata as $key2 => $value2) {
						data_set(
							$nodes,
							'cr',
							[
								'align' => 'left',
								'x' => $startx + 800,
								'y' => 100,
								'w' => 250,
								'h' => 80,
								'type' => $apvdata[$key2]->docno,
								'label' => $apvdata[$key2]->rem,
								'color' => '#6D50E8',
								'details' => [$apvdata[$key2]->dateid]
							]
						);
						array_push($links, ['from' => 'sj', 'to' => 'cr']);
						$a = $a + 100;
					}
				}

				//CM
				$dmqry = "
						select head.docno as docno,left(head.dateid,10) as dateid,
						CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
						from glhead as head
						left join glstock as stock on stock.trno=head.trno 
						left join item on item.itemid = stock.itemid
						where stock.refx=?
						group by head.docno, head.dateid
						union all
						select head.docno as docno,left(head.dateid,10) as dateid,
						CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
						from lahead as head
						left join lastock as stock on stock.trno=head.trno 
						left join item on item.itemid=stock.itemid
						where stock.refx=?
						group by head.docno, head.dateid";
				$dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
				if (!empty($dmdata)) {
					foreach ($dmdata as $key2 => $value2) {
						data_set(
							$nodes,
							$dmdata[$key2]->docno,
							[
								'align' => 'left',
								'x' => $startx + 800,
								'y' => 300,
								'w' => 250,
								'h' => 80,
								'type' => $dmdata[$key2]->docno,
								'label' => $dmdata[$key2]->rem,
								'color' => 'red',
								'details' => [$dmdata[$key2]->dateid]
							]
						);
						array_push($links, ['from' => 'sj', 'to' => $dmdata[$key2]->docno]);
						$a = $a + 100;
					}
				}
			}
		}

		$data['nodes'] = $nodes;
		$data['links'] = $links;

		return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
	}

	public function updateperitem($config)
	{
		$config['params']['data'] = $config['params']['row'];
		$this->additem('update', $config);
		$data = $this->openstockline($config);
		return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
	}


	public function updateitem($config)
	{
		foreach ($config['params']['row'] as $key => $value) {
			$config['params']['data'] = $value;
			$this->additem('update', $config);
		}
		$data = $this->openstock($config['params']['trno'], $config);
		return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
	} //end function

	public function addallitem($config)
	{
		foreach ($config['params']['row'] as $key => $value) {
			$config['params']['data'] = $value;
			$this->additem('insert', $config);
		}

		$data = $this->openstock($config['params']['trno'], $config);
		return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
	} //end function

	public function quickadd($config)
	{
		$barcodelength = $this->companysetup->getbarcodelength($config['params']);
		$config['params']['barcode'] = trim($config['params']['barcode']);
		if ($barcodelength == 0) {
			$barcode = $config['params']['barcode'];
		} else {
			$barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
		}
		$wh = $config['params']['wh'];

		$item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
		$item = json_decode(json_encode($item), true);

		if (!empty($item)) {
			$config['params']['barcode'] = $barcode;
			$lprice = $this->getlatestprice($config);
			$lprice = json_decode(json_encode($lprice), true);
			if (!empty($lprice['data'])) {
				$item[0]['amt'] = $lprice['data'][0]['amt'];
				$item[0]['disc'] = $lprice['data'][0]['disc'];
			}

			$config['params']['data'] = $item[0];
			return $this->additem('insert', $config);
		} else {
			return ['status' => false, 'msg' => 'Barcode not found.', ''];
		}
	}

	// insert and update item
	public function additem($action, $config)
	{
		$uom = $config['params']['data']['uom'];
		$itemid = $config['params']['data']['itemid'];
		$trno = $config['params']['trno'];
		$disc = $config['params']['data']['disc'];
		$wh = $config['params']['data']['wh'];
		$loc = $config['params']['data']['loc'];
		$void = 'false';
		$rem = '';
		$expiry = '';
		$noprint = 'false';

		if (isset($config['params']['data']['void'])) {
			$void = $config['params']['data']['void'];
		}

		if (isset($config['params']['data']['rem'])) {
			$rem = $config['params']['data']['rem'];
		}

		if (isset($config['params']['data']['expiry'])) {
			$expiry = $config['params']['data']['expiry'];
		}

		if (isset($config['params']['data']['noprint'])) {
			$noprint = $config['params']['data']['noprint'];
		}

		$line = 0;
		if ($action == 'insert') {
			$qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
			$line = $this->coreFunctions->datareader($qry, [$trno]);
			if ($line == '') {
				$line = 0;
			}
			$line = $line + 1;
			$config['params']['line'] = $line;
			$amt = $config['params']['data']['amt'];
			$qty = $config['params']['data']['qty'];
		} elseif ($action == 'update') {
			$config['params']['line'] = $config['params']['data']['line'];
			$line = $config['params']['data']['line'];
			$amt = $config['params']['data'][$this->damt];
			$qty = $config['params']['data'][$this->dqty];
			$config['params']['line'] = $line;
		}
		$amt = $this->othersClass->sanitizekeyfield('amt', $amt);
		$qty = $this->othersClass->sanitizekeyfield('qty', $qty);
		$qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
		$item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
		$factor = 1;
		if (!empty($item)) {
			$item[0]->factor = $this->othersClass->val($item[0]->factor);
			if ($item[0]->factor !== 0) $factor = $item[0]->factor;
		}
		$forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
		$computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
		$whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

		if (floatval($forex) == 0) {
			$forex = 1;
		}

		$data = [
			'trno' => $trno,
			'line' => $line,
			'itemid' => $itemid,
			'isamt' => $amt,
			'amt' => $computedata['amt'] * $forex,
			'isqty' => $qty,
			'iss' => $computedata['qty'],
			'ext' => $computedata['ext'],
			'disc' => $disc,
			'whid' => $whid,
			'loc' => $loc,
			'void' => $void,
			'uom' => $uom,
			'rem' => $rem,
			'expiry' => $expiry,
			'noprint' => $noprint
		];
		foreach ($data as $key => $value) {
			$data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
		}
		$current_timestamp = $this->othersClass->getCurrentTimeStamp();
		$data['editdate'] = $current_timestamp;
		$data['editby'] = $config['params']['user'];
		if ($action == 'insert') {
			$data['encodeddate'] = $current_timestamp;
			$data['encodedby'] = $config['params']['user'];
			if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
				$this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext']);
				$row = $this->openstockline($config);
				$this->loadheaddata($config);
				return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
			} else {
				return ['status' => false, 'msg' => 'Add item Failed'];
			}
		} elseif ($action == 'update') {
			return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
		}
	} // end function



	public function deleteallitem($config)
	{
		$isallow = true;
		$trno = $config['params']['trno'];
		$qtrno = $this->coreFunctions->datareader("select trno as value from hqshead where sotrno = ? ", [$trno]);
		$this->coreFunctions->execqry('update hqshead set sotrno = 0 where sotrno = ?', 'update', [$trno]);
		$this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED QOUTATION REFERENCE');
		$this->coreFunctions->execqry('update attendee set optrno=? where optrno=?', 'update', [$qtrno, $trno]);
		$this->loadheaddata($config);
		return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => [], 'reloadhead' => true];
	}


	public function deleteitem($config)
	{
		$config['params']['trno'] = $config['params']['row']['trno'];
		$config['params']['line'] = $config['params']['row']['line'];
		$data = $this->openstockline($config);
		$trno = $config['params']['trno'];
		$line = $config['params']['line'];
		$qry = "delete from " . $this->stock . " where trno=? and line=?";
		$this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
		$this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
		return ['status' => true, 'msg' => 'Item was successfully deleted.'];
	} // end function

	public function getlatestprice($config)
	{
		$barcode = $config['params']['barcode'];
		$client = $config['params']['client'];
		$center = $config['params']['center'];
		$trno = $config['params']['trno'];

		$qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,disc,uom from(select head.docno,head.dateid,
          stock.isamt as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.isamt <> 0
          UNION ALL
          select head.docno,head.dateid,stock.isamt as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock.isamt <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
		$data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);

		$usdprice = 0;
		$forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
		$cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
		$dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

		if (!empty($data)) {
			return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
		} else {
			$qry = "select amt,disc,uom from item where barcode=?";
			$data = $this->coreFunctions->opentable($qry, [$barcode]);
			if (floatval($forex) <> 1) {
				$usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
				if ($cur == '$') {
					$data[0]->amt = $usdprice;
				} else {
					$data[0]->amt = round($usdprice * $dollarrate, $this->companysetup->getdecimal('price', $config['params']));
				}
			}


			if (floatval($data[0]->amt) == 0) {
				return ['status' => false, 'msg' => 'No Latest price found...'];
			} else {
				return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
			}
		}
	} // end function

	// report 
	public function reportsetup($config)
	{
		$txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
		$txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

		$modulename = $this->modulename;
		$data = [];
		$style = 'width:500px;max-width:500px;';
		return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
	}


	public function reportdata($config)
	{
		$companyid = $config['params']['companyid'];
		if ($companyid == 10 || $companyid != 12) { //afti, not afti usd
		} else {
			$this->logger->sbcviewreportlog($config);
		}

		$data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
		$str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
		return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
	}
} //end class
