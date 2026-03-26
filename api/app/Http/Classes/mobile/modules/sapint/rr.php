<?php
namespace App\Http\Classes\mobile\modules\sapint;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class rr {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }

  public function getLayout() {
    $fields = ['ourref', 'dateid', 'client', 'clientname', 'yourref', 'docno'];
    $cfTableCols = $this->fieldClass->create($fields);
    data_set($cfTableCols, 'yourref.label', 'Num at Card');
    data_set($cfTableCols, 'client.label', 'Supplier Code');
    data_set($cfTableCols, 'client.field', 'client');
    data_set($cfTableCols, 'clientname.label', 'Supplier Name');
    data_set($cfTableCols, 'clientname.field', 'clientname');
    data_set($cfTableCols, 'docno.label', 'Trans No.');
    data_set($cfTableCols, 'docno.field', 'docno');
    data_set($cfTableCols, 'dateid.field', 'dateid');
    data_set($cfTableCols, 'ourref.label', 'Docnum');

    $btns = ['download'];
    $cfTableHeadButtons = $this->buttonClass->create($btns);
    data_set($cfTableHeadButtons, 'download.func', 'downloadSAPDoc');

    $cLookupHeadFields = [];
    $cLookupTableCols = [];
    $cLookupTableButtons = [];
    $cLookupButtons = [];

    $fields = ['rline', 'barcode', 'itemname', 'batchcode', 'qty', 'uom', 'printed'];
    $docLookupTableCols = $this->fieldClass->create($fields);
    data_set($docLookupTableCols, 'barcode.field', 'barcode');
    data_set($docLookupTableCols, 'barcode.label', 'Item Code');
    data_set($docLookupTableCols, 'itemname.field', 'itemname');
    data_set($docLookupTableCols, 'itemname.label', 'Product');
    data_set($docLookupTableCols, 'qty.field', 'qty');
    data_set($docLookupTableCols, 'uom.field', 'uom');
    data_set($docLookupTableCols, 'batchcode.field', 'batchcode');
    array_push($cLookupTableCols, ['form'=>'docLookupTableCols', 'fields'=>$docLookupTableCols]);

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

    return ['cfTableCols'=>$cfTableCols, 'cfTableHeadButtons'=>$cfTableHeadButtons, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupButtons'=>$cLookupButtons];
  }

  public function getFunc() {
    return '({
      paginationData: { label: "", pageNum: 1, totalPage: 0, maxPages: 3, color: "primary", page: 1, rowsPerPage: 20, lastItem: 0 },
      tableGrid: true,
      docForm: [],
      tableData: [],
      loadTableData: function () {
        console.log("loadTableData rr called");
        sbc.globalFunc.cfTableClick = true;
        sbc.globalFunc.cfTableClickFunctype = "global";
        sbc.globalFunc.cfTableClickFunc = "viewSAPDoc";
        sbc.modulefunc.tableFilter = { type: "filter", field: "", label: "", func: "" };
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, ifnull(docno, ?) as docno, doc, client, ifnull(clientname, ?) as clientname, ifnull(yourref, ?) as yourref, ifnull(ourref, ?) as ourref, date(dateid) as dateid from head where doc=?", ["-", "-", "-", "-", sbc.doc], function (tx, res) {
            sbc.modulefunc.tableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.tableData.push(res.rows.item(x));
              }
            }
            $q.loading.hide();
          });
        });
      }
    })';
  }
}