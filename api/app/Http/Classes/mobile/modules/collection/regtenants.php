<?php
namespace App\Http\Classes\mobile\modules\collection;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class regtenants {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['area', 'category', 'tenant'];
    $cfHeadFields = $this->fieldClass->create($fields);
    data_set($cfHeadFields, 'area.type', 'lookup');
    data_set($cfHeadFields, 'area.action', 'arealookup');
    data_set($cfHeadFields, 'area.readonly', 'true');
    data_set($cfHeadFields, 'area.fields', 'area');

    $inputLookupFields = [];
    $inputLookupButtons = [];

    $fields = ['area', 'transtype', 'tenant', 'loc', 'dateid', 'beginning', 'ending', 'consumption', 'remarks'];
    $readingLookupFields = $this->fieldClass->create($fields);
    data_set($readingLookupFields, 'tenant.type', 'input');
    data_set($readingLookupFields, 'dateid.type', 'input');
    data_set($readingLookupFields, 'area.type', 'input');
    array_push($inputLookupFields, ['form'=>'readingLookupFields', 'fields'=>$readingLookupFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $readingLookupButtons = $this->buttonClass->create($btns);
    data_set($readingLookupButtons, 'saverecord.form', 'readingLookupButtons');
    data_set($readingLookupButtons, 'saverecord.func', 'saveReading');
    data_set($readingLookupButtons, 'saverecord.functype', 'global');
    data_set($readingLookupButtons, 'cancelrecord.form', 'readingLookupButtons');
    data_set($readingLookupButtons, 'cancelrecord.func', 'cancelReading');
    data_set($readingLookupButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'readingLookupButtons', 'btns'=>$readingLookupButtons]);

    $fields = ['area', 'transtype', 'tenant', 'loc', 'rent', 'cusa', 'outstandingbal', 'remarks', 'payment', 'amt', 'balance'];
    $collectLookupFields = $this->fieldClass->create($fields);
    data_set($collectLookupFields, 'tenant.type', 'input');
    data_set($collectLookupFields, 'dateid.type', 'input');
    data_set($collectLookupFields, 'area.type', 'input');
    array_push($inputLookupFields, ['form'=>'collectLookupFields', 'fields'=>$collectLookupFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $collectLookupButtons = $this->buttonClass->create($btns);
    data_set($collectLookupButtons, 'saverecord.form', 'collectLookupButtons');
    data_set($collectLookupButtons, 'saverecord.func', 'saveCollection');
    data_set($collectLookupButtons, 'saverecord.functype', 'global');
    data_set($collectLookupButtons, 'cancelrecord.form', 'collectLookupButtons');
    data_set($collectLookupButtons, 'cancelrecord.func', 'cancelCollection');
    data_set($collectLookupButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'collectLookupButtons', 'btns'=>$collectLookupButtons]);

    return ['cfHeadFields'=>$cfHeadFields, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons];
  }

  public function getFunc() {
    return '({
      docForm: { area: "", selArea: [], category: "", tenant: "", selTenant: [], transtypeOpts: [], transtype: "", loc: "", rent: "", cusa: "", outstandingbal: "", remarks: "", payment: "", amt: "", balance: "", consumption: "", beginning: "", dateid: "", ending: "" },
      loadTableData: function () {
        sbc.isFormEdit = true;
        if (sbc.cfheadfields.length > 0) {
          for (var cfhf in sbc.cfheadfields) {
            if (sbc.cfheadfields[cfhf].name === "tenant") sbc.cfheadfields[cfhf].show = "false";
          }
        }
        sbc.globalFunc.loadAreasLookup();
      }
    })';
  }
}