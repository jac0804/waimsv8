<?php
namespace App\Http\Classes\mobile\modules\sapint;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class rm {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }

  public function getLayout() {
    $fields = ['ourref', 'dateid', 'yourref', 'docno'];
    $cfTableCols = $this->fieldClass->create($fields);
    data_set($cfTableCols, 'dateid.field', 'dateid');
    data_set($cfTableCols, 'docno.field', 'docno');
    data_set($cfTableCols, 'ourref.label', 'Docnum');
    data_set($cfTableCols, 'yourref.label', 'Prod Order No.');

    $fields = ['wh'];
    $cfHeadFields = $this->fieldClass->create($fields);
    data_set($cfHeadFields, 'wh.label', 'WH');
    data_set($cfHeadFields, 'wh.type', 'select');
    data_set($cfHeadFields, 'wh.options', []);
    data_set($cfHeadFields, 'wh.enterfunc', 'loadTableData');
    data_set($cfHeadFields, 'wh.readonly', false);

    

    $btns = ['download'];
    $cfTableHeadButtons = $this->buttonClass->create($btns);
    data_set($cfTableHeadButtons, 'download.func', 'downloadSAPDoc');

    $cLookupHeadFields = [];
    $cLookupTableCols = [];
    $cLookupTableButtons = [];
    $cLookupButtons = [];

    $fields = ['rline', 'rrlinex', 'barcode', 'itemname', 'qty', 'uom'];
    $docLookupTableCols = $this->fieldClass->create($fields);
    data_set($docLookupTableCols, 'barcode.field', 'barcode');
    data_set($docLookupTableCols, 'barcode.label', 'Item Code');
    data_set($docLookupTableCols, 'itemname.field', 'itemname');
    data_set($docLookupTableCols, 'itemname.label', 'Product');
    data_set($docLookupTableCols, 'qty.field', 'qty');
    data_set($docLookupTableCols, 'uom.field', 'uom');
    array_push($cLookupTableCols, ['form'=>'docLookupTableCols', 'fields'=>$docLookupTableCols]);

    $inputLookupFields = [];
    $inputLookupButtons = [];

    $fields = ['barcode'];
    $scanListFields = $this->fieldClass->create($fields);
    data_set($scanListFields, 'barcode.label', 'Scan QR Code');
    data_set($scanListFields, 'barcode.enterfunc', 'scanListBarcode');
    data_set($scanListFields, 'barcode.functype', 'global');
    array_push($inputLookupFields, ['form'=>'scanListFields', 'fields'=>$scanListFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $scanListButtons = $this->buttonClass->create($btns);
    data_set($scanListButtons, 'saverecord.func', 'scanListBarcode');
    data_set($scanListButtons, 'saverecord.functype', 'global');
    data_set($scanListButtons, 'cancelrecord.func', 'cancelScanListBarcode');
    data_set($scanListButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'scanListButtons', 'btns'=>$scanListButtons]);

    $fields = ['barcode'];
    $scanItemFields = $this->fieldClass->create($fields);
    data_set($scanItemFields, 'barcode.label', 'Scan QR Code');
    data_set($scanItemFields, 'barcode.enterfunc', 'scanItemBarcode');
    data_set($scanItemFields, 'barcode.functype', 'global');
    array_push($inputLookupFields, ['form'=>'scanItemFields', 'fields'=>$scanItemFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $scanItemButtons = $this->buttonClass->create($btns);
    data_set($scanItemButtons, 'saverecord.func', 'scanItemBarcode');
    data_set($scanItemButtons, 'saverecord.functype', 'global');
    data_set($scanItemButtons, 'cancelrecord.func', 'cancelScanItemBarcode');
    data_set($scanItemButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'scanItemButtons', 'btns'=>$scanItemButtons]);    

    $btns = ['viewscanlist', 'scanlist', 'scan', 'saverecord', 'cancelrecord'];
    $docLookupButtons = $this->buttonClass->create($btns);
    data_set($docLookupButtons, 'saverecord.label', 'Upload');
    data_set($docLookupButtons, 'saverecord.func', 'uploadSAPDoc');
    data_set($docLookupButtons, 'saverecord.functype', 'global');
    data_set($docLookupButtons, 'saverecord.icon', 'cloud_upload');
    data_set($docLookupButtons, 'cancelrecord.func', 'cancelUploadSAPDoc');
    data_set($docLookupButtons, 'cancelrecord.functype', 'global');
    data_set($docLookupButtons, 'cancelrecord.icon', 'cancel');
    data_set($docLookupButtons, 'scan.label', 'Scan Actual Item');
    data_set($docLookupButtons, 'scan.func', 'scanSAPActualCode');
    data_set($docLookupButtons, 'scan.functype', 'global');
    data_set($docLookupButtons, 'scan.color', 'primary');
    array_push($cLookupButtons, ['form'=>'docLookupButtons', 'btns'=>$docLookupButtons]);

    return ['cfTableCols'=>$cfTableCols, 'cfTableHeadButtons'=>$cfTableHeadButtons, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupButtons'=>$cLookupButtons, 'cLookupTableButtons'=>$cLookupTableButtons, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons, 'cfHeadFields'=>$cfHeadFields];
  }

  public function getFunc() {
    return '({
      paginationData: { label: "", pageNum: 1, totalPage: 0, maxPages: 3, color: "primary", page: 1, rowsPerPage: 20, lastItem: 0 },
      tableGrid: true,
      docForm: { wh: [] },
      tableData: [],
      loadTableData: function () {
        console.log("rm loadTableData called");
        cfunc.showLoading();
        sbc.globalFunc.cfTableClick = true;
        sbc.globalFunc.cfTableClickFunctype = "global";
        sbc.globalFunc.cfTableClickFunc = "viewSAPDoc";
        sbc.modulefunc.tableFilter = { type: "filter", field: "", label: "", func: "" };
        console.log("rm 2");
        sbc.db.transaction(function (tx) {
          tx.executeSql("select ? as wht union all select distinct wh from head where wh is not null and wh<>? and doc=?", ["", "", sbc.doc], function (tx, res2) {
            let wh = [];
            if (res2.rows.length > 0) {
              for (var x = 0; x < res2.rows.length; x++) {
                wh.push({ label: res2.rows.item(x).wht, value: res2.rows.item(x).wht });
                if (parseInt(x) + 1 == res2.rows.length) {
                  sbc.cfheadfields.find(waw => waw.name === "wh").options = wh;
                }
              }
            }
          }, function (tx, err) {
            console.log("rm err3: ", err.message);
          });
          let whfilter = "";
          let data = ["-", sbc.doc];
          if (sbc.modulefunc.docForm.wh.value !== undefined && sbc.modulefunc.docForm.wh.value !== "") {
            whfilter = " and wh=?";
            data = ["-", sbc.doc, sbc.modulefunc.docForm.wh.value];
          }
          tx.executeSql("select trno, docno, dateid, yourref, ifnull(ourref,?) as ourref from head where doc=? " + whfilter, data, function (tx, res) {
            sbc.modulefunc.tableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.tableData.push(res.rows.item(x));
                if (parseInt(x) + 1 === res.rows.length) {
                  console.log("rmss: ", sbc.modulefunc.tableData);
                }
              }
            }
            $q.loading.hide();
          }, function (tx, err) {
            console.log("rm err: ", err.message);
          });
        }, function (err) {
          console.log("rm err2: ", err.message);
        });
      },
      loadSAPStocks: function () {
        cfunc.showLoading();
        sbc.globalFunc.loadSAPStocks(sbc.modulefunc.cLookupForm.row);
      }
    })';
  }
}