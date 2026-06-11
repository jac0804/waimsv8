<?php
namespace App\Http\Classes\mobile\modules\sapint;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class rl {
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

    
    $cfTableButtons = [];

    $btns = ['download'];
    $cfTableHeadButtons = $this->buttonClass->create($btns);
    data_set($cfTableHeadButtons, 'download.func', 'downloadSAPDoc');

    $cLookupHeadFields = [];
    $cLookupTableCols = [];
    $cLookupTableButtons = [];
    $cLookupButtons = [];

    $fields = ['rline', 'rrlinex', 'barcode', 'itemname', 'qty', 'uom', 'qtyreleased'];
    $docLookupTableCols = $this->fieldClass->create($fields);
    data_set($docLookupTableCols, 'barcode.field', 'barcode');
    data_set($docLookupTableCols, 'barcode.label', 'Item Code');
    data_set($docLookupTableCols, 'itemname.field', 'itemname');
    data_set($docLookupTableCols, 'itemname.label', 'Product');
    data_set($docLookupTableCols, 'qty.field', 'qty');
    data_set($docLookupTableCols, 'uom.field', 'uom');
    data_set($docLookupTableCols, 'qty.label', 'Qty Required');
    array_push($cLookupTableCols, ['form'=>'docLookupTableCols', 'fields'=>$docLookupTableCols]);

    

    $btns = ['scan', 'addqty'];
    $docLookupTableButtons = $this->buttonClass->create($btns);
    data_set($docLookupTableButtons, 'scan.color', 'primary');
    data_set($docLookupTableButtons, 'scan.func', 'scanSAPCode');
    data_set($docLookupTableButtons, 'scan.functype', 'global');
    array_push($cLookupTableButtons, ['form'=>'docLookupTableButtons', 'btns'=>$docLookupTableButtons]);

    $inputLookupFields = [];
    $inputLookupButtons = [];

    $fields = ['barcode'];
    $scanQRCodeFields = $this->fieldClass->create($fields);
    data_set($scanQRCodeFields, 'barcode.label', 'Scan QR Code');
    data_set($scanQRCodeFields, 'barcode.autofocus', true);
    data_set($scanQRCodeFields, 'barcode.enterfunc', 'scanQRCode');
    data_set($scanQRCodeFields, 'barcode.functype', 'global');
    array_push($inputLookupFields, ['form'=>'scanQRCodeFields', 'fields'=>$scanQRCodeFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $scanQRCodeButtons = $this->buttonClass->create($btns);
    data_set($scanQRCodeButtons, 'saverecord.func', 'scanQRCode');
    data_set($scanQRCodeButtons, 'saverecord.functype', 'global');
    data_set($scanQRCodeButtons, 'saverecord.label', 'Scan');
    data_set($scanQRCodeButtons, 'cancelrecord.func', 'cancelScanQRCode');
    data_set($scanQRCodeButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'scanQRCodeButtons', 'btns'=>$scanQRCodeButtons]);

    $fields = ['barcode1', 'batchcode', 'rtrno', 'uom', 'codeqty', 'qtyreleased', 'qtyneeded', 'qty'];
    $scanQtyReleaseFields = $this->fieldClass->create($fields);
    data_set($scanQtyReleaseFields, 'uom.type', 'label');
    data_set($scanQtyReleaseFields, 'uom.label', 'UOM: ');
    data_set($scanQtyReleaseFields, 'qty.type', 'input');
    data_set($scanQtyReleaseFields, 'qty.enterfunc', 'saveSAPQtyRelease');
    data_set($scanQtyReleaseFields, 'qty.functype', 'global');
    data_set($scanQtyReleaseFields, 'qty.autofocus', true);
    data_set($scanQtyReleaseFields, 'qtyreleased.label', 'Qty Released: ');
    data_set($scanQtyReleaseFields, 'codeqty.label', 'Qty Required: ');
    array_push($inputLookupFields, ['form'=>'scanQtyReleaseFields', 'fields'=>$scanQtyReleaseFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $scanQtyReleaseButtons = $this->buttonClass->create($btns);
    data_set($scanQtyReleaseButtons, 'saverecord.func', 'saveSAPQtyRelease');
    data_set($scanQtyReleaseButtons, 'saverecord.functype', 'global');
    data_set($scanQtyReleaseButtons, 'cancelrecord.func', 'cancelSAPQtyRelease');
    data_set($scanQtyReleaseButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'scanQtyReleaseButtons', 'btns'=>$scanQtyReleaseButtons]);

    $btns = ['print', 'saverecord', 'cancelrecord'];
    $docLookupButtons = $this->buttonClass->create($btns);
    data_set($docLookupButtons, 'saverecord.label', 'Upload');
    data_set($docLookupButtons, 'saverecord.func', 'uploadSAPDoc');
    data_set($docLookupButtons, 'saverecord.functype', 'global');
    data_set($docLookupButtons, 'cancelrecord.func', 'cancelUploadSAPDoc');
    data_set($docLookupButtons, 'cancelrecord.functype', 'global');
    data_set($docLookupButtons, 'saverecord.icon', 'cloud_upload');
    data_set($docLookupButtons, 'cancelrecord.icon', 'cancel');
    array_push($cLookupButtons, ['form'=>'docLookupButtons', 'btns'=>$docLookupButtons]);

    return ['cfTableCols'=>$cfTableCols, 'cfTableButtons'=>$cfTableButtons, 'cfTableHeadButtons'=>$cfTableHeadButtons, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupButtons'=>$cLookupButtons, 'cLookupTableButtons'=>$cLookupTableButtons, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons, 'cfHeadFields'=>$cfHeadFields];
  }

  public function getFunc() {
    return '({
      paginationData: { label: "", pageNum: 1, totalPage: 0, maxPages: 3, color: "primary", page: 1, rowsPerPage: 20, lastItem: 0 },
      tableGrid: true,
      docForm: { wh: [] },
      tableData: [],
      loadTableData: function () {
        console.log("rl loadtabledata called");
        sbc.globalFunc.cfTableClick = true;
        sbc.globalFunc.cfTableClickFunctype = "global";
        sbc.globalFunc.cfTableClickFunc = "viewSAPDoc";
        sbc.modulefunc.tableFilter = { type: "filter", field: "", label: "", func: "" };
        cfunc.showLoading();
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
          });
          let whfilter = "";
          let data = ["-", "-", "-", "-", sbc.doc];
          if (sbc.modulefunc.docForm.wh.value !== undefined && sbc.modulefunc.docForm.wh.value !== "") {
            whfilter = " and wh=?";
            data = ["-", "-", "-", "-", sbc.doc, sbc.modulefunc.docForm.wh.value];
          }
          tx.executeSql("select trno, ifnull(docno, ?) as docno, doc, client, ifnull(clientname, ?) as clientname, ifnull(yourref, ?) as yourref, ifnull(ourref, ?) as ourref, date(dateid) as dateid from head where doc=? " + whfilter, data, function (tx, res) {
            sbc.modulefunc.tableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.tableData.push(res.rows.item(x));
              }
            }
            $q.loading.hide();
          });
        });
      },
      loadSAPStocks: function () {
        cfunc.showLoading();
        sbc.globalFunc.loadSAPStocks(sbc.modulefunc.cLookupForm.row);
      },
    })';
  }
}