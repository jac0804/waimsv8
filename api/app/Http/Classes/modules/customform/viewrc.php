<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class viewrc
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'RETURN/BOUNCED CHECKS';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {

    $clientid = $config['params']['clientid'];
    $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
    $this->modulename = $this->modulename . ' - ' . $customername;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'checkdate', 'docno', 'checkno', 'db', 'cr', 'rem']
      ]
    ];

    $stockbuttons = ['referencemodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['totalfield'] = ['db', 'cr'];

    // 3 = db
    $obj[0][$this->gridname]['columns'][3]['align'] = 'right';
    // 4 = cr
    $obj[0][$this->gridname]['columns'][4]['align'] = 'right';


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'pdc');

    $fields = ['db'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['cr'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid, 0.0 as db, 0.0 as cr');
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $date = $config['params']['dataparams']['dateid'];

    $qry = "select docno,dateid,rem,FORMAT(db,2) as db,
        FORMAT(cr,2) as cr,FORMAT(bal,2) as bal,ref,'RCTAB' as tabtype from (select `cntnum`.`doc` as `doc`,`arledger`.`docno` as `docno`,`arledger`.`trno` as `trno`,`arledger`.`line` as `line`,`arledger`.`dateid` as `dateid`,`arledger`.`db` as `db`,
            `arledger`.`cr` as `cr`,(case when (`arledger`.`bal` = 0) then 'applied' else ltrim(`arledger`.`bal`) end) as `bal`,`arledger`.`clientid` as `clientid`,
            arledger.ref as `ref`,agent.client as `agent`,`gldetail`.`rem` as `rem`,
            `arledger`.`bal` as `balance` from (((`arledger` left join `cntnum` on((`cntnum`.`trno` = `arledger`.`trno`)))
            left join `coa` on((`coa`.`acnoid` = `arledger`.`acnoid`))) left join `gldetail` on(((`gldetail`.`trno` = `arledger`.`trno`)
            and (`gldetail`.`line` = `arledger`.`line`)))) left join client on client.clientid= arledger.clientid left join client as agent on agent.clientid = arledger.agentid where (`coa`.`alias` = 'arb')  and client.clientid= $clientid  and arledger.dateid>='$date' and cntnum.center = '$center'
            union all
            select `lahead`.`doc` as `doc`,`lahead`.`docno` as `docno`,`lahead`.`trno` as `trno`,`ladetail`.`line` as `line`,`lahead`.`dateid` as `dateid`,`ladetail`.`db` as `db`,
            `ladetail`.`cr` as `cr`,abs((`ladetail`.`db` - `ladetail`.`cr`)) as `bal`,`client`.`clientid` as `clientid`,`ladetail`.`ref` as `ref`,'' as `agent`,
            `ladetail`.`rem` as `rem`,abs((`ladetail`.`db` - `ladetail`.`cr`)) as `balance`
            from (((`lahead` left join `ladetail` on((`ladetail`.`trno` = `lahead`.`trno`)))
            left join `client` on((`client`.`client` = `ladetail`.`client`)))
            left join `coa` on((`coa`.`acnoid` = `ladetail`.`acnoid`))) left join cntnum on cntnum.trno = lahead.trno where (`coa`.`alias` = 'arb')  and client.clientid= $clientid  and lahead.dateid>='$date' and cntnum.center = '$center' and ladetail.refx=0 and ladetail.linex=0) as T 
            ";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
