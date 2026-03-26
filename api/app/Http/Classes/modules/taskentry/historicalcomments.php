<?php

namespace App\Http\Classes\modules\taskentry;

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

class historicalcomments
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Comments';
    public $tablenum = 'headprrem';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'designation';
    private $htable = '';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = [];
    public $showclosebtn = true;
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    public $sqlquery;
    public $logger;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = ['load' => 0];
        return $attrib;
    }

    public function createTab($config)
    {
        $getcols = ['createdate','createby','rem','seendate'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $getcols]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
        $obj[0][$this->gridname]['columns'][$rem]['style'] =  'text-align: left; width: 300px;whiteSpace: normal;min-width:300px;max-width:450px;';
        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$createby]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$createdate]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$seendate]['readonly'] = true;
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

     public function loaddata($config)
  {
      // if ($config['params']['moduletype'] == 'dashboard') {
      //   $doc = 'TM';
      // } else {
      //   $doc = $config['params']['doc'];
      // }

    $otherTrnoField = '';
    $otherTrnoVal = 0;

    // switch ($doc) {
    //   case 'RR':
    //     $trno = isset($config['params']['ledgerdata']['rrtrno']) ? $config['params']['ledgerdata']['rrtrno'] : 0;
    //     $line = isset($config['params']['ledgerdata']['rrline']) ? $config['params']['ledgerdata']['rrline'] : 0;

    //     if (isset($config['params']['dataparams'])) {
    //       $trno = isset($config['params']['dataparams']['rrtrno']) ? $config['params']['dataparams']['rrtrno'] : 0;
    //       $line = isset($config['params']['dataparams']['rrline']) ? $config['params']['dataparams']['rrline'] : 0;
    //     }
    //     break;
    //   case 'TM':
    //   case 'TK':
        if (isset($config['params']['dataparams'])) {
          $trno = isset($config['params']['dataparams']['tmtrno']) ? $config['params']['dataparams']['tmtrno'] : 0;
          $line = isset($config['params']['dataparams']['tmline']) ? $config['params']['dataparams']['tmline'] : 0;

          if ($trno == 0) {
            $otherTrnoField = isset($config['params']['dataparams']['othertrnofield']) ? $config['params']['dataparams']['othertrnofield'] : '';
            $otherTrnoVal = isset($config['params']['dataparams']['othertrnoval']) ? $config['params']['dataparams']['othertrnoval'] : 0;
          }
        }

        if (isset($config['params']['row'])) {
          $trno = isset($config['params']['row']['tmtrno']) ? $config['params']['row']['tmtrno'] : 0;
          $line = isset($config['params']['row']['tmline']) ? $config['params']['row']['tmline'] : 0;

          if ($trno == 0) {
            $otherTrnoField = isset($config['params']['row']['othertrnofield']) ? $config['params']['row']['othertrnofield'] : '';
            $otherTrnoVal = isset($config['params']['row']['othertrnoval']) ? $config['params']['row']['othertrnoval'] : 0;
          }
        }
        
        // var_dump($otherTrnoField);
        // var_dump($otherTrnoVal);
    

    $this->coreFunctions->LogConsole("Trno:" . $trno . ' - Line:' . $line);

    if ($trno != 0 && $line != 0) {
      $qry = "select ifnull(pr.rem,'') as rem,pr.createby,pr.createdate,pr.seendate from headprrem as pr  where pr.tmtrno=$trno and pr.tmline=$line order by pr.line desc";
      $data = $this->coreFunctions->opentable($qry);
      return $data;
    } else {
      switch ($otherTrnoField) {
        case 'dytrno':
          $origTrno = $this->coreFunctions->datareader("select refx as value from dailytask where trno=" . $otherTrnoVal . " union all select refx as value from hdailytask where trno=" . $otherTrnoVal);
          $qry = "select ifnull(pr.rem,'') as rem,pr.createby,pr.createdate,pr.seendate from headprrem as pr where pr." . $otherTrnoField . "<>0 and pr." . $otherTrnoField . "=$origTrno order by pr.line desc";
          $data = $this->coreFunctions->opentable($qry);
          return $data;
          break;
        default:
          return [];
          break;
      }
    }
  }

}
