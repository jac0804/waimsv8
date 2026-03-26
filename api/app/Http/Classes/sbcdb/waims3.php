<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use DateTime;

use Illuminate\Support\Str;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

use function PHPSTORM_META\type;

class waims3
{
    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
    } //end fn

    public function checkdbversion()
    {
        $version =  $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='DB' and psection='DBVERSION' and puser='WAIMS'");
        if ($version == '') {
            return $version;
        } else {
            $current = DateTime::createFromFormat('Y-m', $this->othersClass->getCurrentDate());
            $date = DateTime::createFromFormat('Y-m', $version);

            if ($current >= $version) {
                $formatted = strtolower($current->format('FY'));
            } else {
                $formatted = strtolower($date->format('FY'));
            }
            return $formatted;
        }
    }


    public function august2025($config)
    {

        $this->coreFunctions->execqry("delete from profile where doc=? and psection=? and puser=?", 'delete', ['DB', 'DBVERSION', 'WAIMS']);
        $data = ['doc' => 'DB', 'psection' => 'WAIMS', 'pvalue' => '2025-08', 'puser' => 'WAIMS'];
        $this->coreFunctions->sbcinsert("profile", $data);
    }
}
