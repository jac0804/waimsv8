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

class viewdailytaskattachment
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'DAILY TASK ATTACHMENT';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    private $table = 'waims_attachments';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = false;
    public $showclosebtn = true;
    public $fields = [];


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
        $companyid = $config['params']['companyid'];

        $viewallow = $this->othersClass->checkAccess($config['params']['user'], 1730);
        $downloadallow = $this->othersClass->checkAccess($config['params']['user'], 1732);
        $colums = ['action', 'ext', 'title'];

        foreach ($colums as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $colums]];

        $stockbuttons = [];

        if ($viewallow == '1') {
            array_push($stockbuttons, 'view');
        }

        if ($downloadallow == '1') {
            array_push($stockbuttons, 'download');
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        if (isset($obj[0]['customformacctg']['columns'][0]['btns']['view'])) {
            $obj[0]['customformacctg']['columns'][0]['btns']['view']['action'] = 'viewfile';
        }

        $obj[0]['customformacctg']['columns'][$ext]['label'] = 'File Type';
        $obj[0]['customformacctg']['columns'][$ext]['align'] = 'left';
        $obj[0]['customformacctg']['columns'][$ext]['style'] = 'width: 40px;whiteSpace: normal;min-width:40px;max-width:40px;';
        $obj[0]['customformacctg']['columns'][$action]['style'] = 'width: 50px;whiteSpace: normal;min-width:20px;max-width:50px;';
        $this->modulename .= '-' . $config['params']['row']['username'];
        return $obj;
    }

    public function createtabbutton($config)
    {
        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = [];
        $col1 = $this->fieldClass->create($fields); 

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data($config)
    {
        $line = $config['params']['row']['trno'];
        $qry = "
        select 'notice' as type,md5(trno) as trno2,md5(line) as line2,trno,line,title,picture,
        substring_index(picture, '.', -1) as ext 
        from $this->table
        where trno = ? order by line desc";

        $data = $this->coreFunctions->opentable($qry, [$line]);
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
