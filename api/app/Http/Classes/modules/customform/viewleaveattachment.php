<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Illuminate\Support\Facades\Storage;

class viewleaveattachment
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LEAVE ATTACHMENT - ';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $table = 'leave_picture';
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $fields = ['status', 'status2', 'approverem', 'disapproved_remarks2', 'approvedby', 'approvedate', 'disapprovedby', 'disapprovedate', 'approvedby2', 'approvedate2', 'disapprovedby2', 'disapprovedate2'];


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->linkemail = new linkemail;
    }

    public function createTab($config)
    {
        $viewallow = $this->othersClass->checkAccess($config['params']['user'], 1730);
        $downloadallow = $this->othersClass->checkAccess($config['params']['user'], 1732);
        $columns = ['action', 'ext', 'title'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns]];


        $stockbuttons = [];

        if ($viewallow == '1') {
            array_push($stockbuttons, 'view');
        }

        if ($downloadallow == '1') {
            array_push($stockbuttons, 'download');
        }

        foreach ($stockbuttons as $key2 => $value2) {
            $$value2 = $key2;
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
        $obj[0][$this->gridname]['columns'][$action]['btns']['view']['action'] = 'viewfile';
        $obj[0][$this->gridname]['columns'][$title]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$ext]['label'] = 'FileType';
        $this->modulename .= '' . $config['params']['row']['clientname'];
        return $obj;
    }

    public function createtabbutton($config)
    {
        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
      return [];
    }

    public function paramsdata($config)
    {
      return [];
    }

    public function data($config)
    {
        $line = $config['params']['row']['line'];
        $doc = strtolower('LEAVEAPPLICATIONPORTAL');
        $trno = $config['params']['row']['trno'];

        $qry = "select '$doc' as type , md5(ltline) as trno2, md5(line) as line2, ltline as trno, line, title, picture as picture, substring_index(picture,'.',-1) as ext,'' as bgcolor from " . $this->table . " where trno= $trno and ltline = $line order by line";

        $data = $this->coreFunctions->opentable($qry);
        $data = $this->getFileTypes($data);
        return $data;
    }

    public function loaddata($config)
    {
      return [];
    }
    public function getFileTypes($data)
    {
        foreach ($data as $d) {
            switch ($d->ext) {
                case 'JPG':
                case 'JPEG':
                case 'PNG':
                case 'GIF':
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    $d->filetype = 'image';
                    break;
                case 'pdf':
                case 'PDF':
                    $d->filetype = 'pdf';
                    break;
                default:
                    $d->filetype = 'others';
                    break;
            }
        }
        return $data;
    }
} //end class
