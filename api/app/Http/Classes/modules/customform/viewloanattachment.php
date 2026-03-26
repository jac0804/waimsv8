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

class viewloanattachment
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LOAN ATTACHMENT';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = false;
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
        $companyid = $config['params']['companyid'];
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'ext', 'title', 'encodedby', 'encodeddate']]];
        $viewallow = $this->othersClass->checkAccess($config['params']['user'], 1730);
        $downloadallow = $this->othersClass->checkAccess($config['params']['user'], 1732);
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

        $obj[0]['customformacctg']['columns'][1]['label'] = 'File Type';
        $obj[0]['customformacctg']['columns'][1]['align'] = 'left';
        $obj[0]['customformacctg']['columns'][1]['style'] = 'width: 40px;whiteSpace: normal;min-width:40px;max-width:40px;';
        $obj[0]['customformacctg']['columns'][0]['style'] = 'width: 50px;whiteSpace: normal;min-width:20px;max-width:50px;';
        $obj[0]['customformacctg']['columns'][3]['label'] = 'Encoded By';
        $this->modulename .= '-' . $config['params']['row']['clientname'];
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

        // array_push($fields, 'picture');

        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'picture.style', 'height:300px;width:90%; max-width: 100%;');
        $fields = [];
        $col2 = $this->fieldClass->create($fields);
        $fields = [];
        $col3 = $this->fieldClass->create($fields);
        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        // $result = $this->coreFunctions->opentable("select * from transnum_picture trno =?", [$config['params']['row']['line']]);

        // foreach ($result as $key => $value) {
        //     if ($value->picture != '') {
        //         Storage::disk('public')->url($value->picture);
        //     }
        // }
        return [];
    }

    public function data($config)
    {
        $line = $config['params']['row']['trno'];
        $qry = "
        select  'loanapplicationportal' as type,md5(trno) as trno2,md5(line) as line2,trno,line,title,picture,
        left(encodeddate, 10) as encodeddate,encodedby,substring_index(picture, '.', -1) as ext 
        from loan_picture
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
