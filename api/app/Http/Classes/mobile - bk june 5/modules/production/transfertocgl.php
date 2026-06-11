<?php
namespace App\Http\Classes\mobile\modules\production;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class transfertocgl {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['docno', 'dateid', 'yourref', 'ourref', 'prdno'];
    $cfTableCols = $this->fieldClass->create($fields);
    data_set($cfTableCols, 'docno.field', 'docno');
    data_set($cfTableCols, 'dateid.field', 'dateid');
    $btns = ['viewdoc'];
    $cfTableButtons = $this->buttonClass->create($btns);
    $btns = ['download'];
    $cfTableHeadButtons = $this->buttonClass->create($btns);

    $cLookupHeadFields = [];
    $cLookupHeadButtons = [];
    $cLookupTableCols = [];
    $cLookupTableButtons = [];
    $fields = ['docno', 'dateid', 'whname', 'clientname', 'scanstat', 'searchcoil', 'refresh'];
    $docLookupFields = $this->fieldClass->create($fields);
    data_set($docLookupFields, 'docno.type', 'label');
    data_set($docLookupFields, 'docno.label', 'Doc #: ');
    data_set($docLookupFields, 'dateid.type', 'label');
    data_set($docLookupFields, 'dateid.label', 'Date: ');
    data_set($docLookupFields, 'lcno.type', 'label');
    data_set($docLookupFields, 'lcno.label', 'LC No.: ');
    data_set($docLookupFields, 'whname.type', 'label');
    data_set($docLookupFields, 'whname.label', 'Source: ');
    data_set($docLookupFields, 'clientname.type', 'label');
    data_set($docLookupFields, 'clientname.label', 'Destination: ');
    data_set($docLookupFields, 'refresh.func', 'loadDocStocksProd');
    data_set($docLookupFields, 'refresh.functype', 'module');

    $btns = ['scan', 'upload'];
    $docLookupButtons = $this->buttonClass->create($btns);
    array_push($cLookupHeadButtons, ['form'=>'tscgldocLookupHeadButtons', 'btns'=>$docLookupButtons]);

    $btns = ['stockinfo'];
    $docLookupTableButtons = $this->buttonClass->create($btns);

    array_push($cLookupTableButtons, ['form'=>'tscgldocLookupTableButtons', 'btns'=>$docLookupTableButtons]);

    array_push($cLookupHeadFields, ['form'=>'tscgldocLookupFields', 'fields'=>$docLookupFields]);

    $fields = ['bundleno', 'itemname', 'itemnetweight', 'itemgrossweight', 'rem'];
    $docLookupTableCols = $this->fieldClass->create($fields);
    data_set($docLookupTableCols, 'itemname.type', 'label');
    data_set($docLookupTableCols, 'itemname.field', 'itemname');
    array_push($cLookupTableCols, ['form'=>'tscgldocLookupTableCols', 'fields'=>$docLookupTableCols]);

    $inputLookupFields = [];
    $fields = ['bundleno', 'itemname', 'itemnetweight', 'itemgrossweight', 'drno', 'rem'];
    $stockLookupFields = $this->fieldClass->create($fields);
    data_set($stockLookupFields, 'bundleno.type', 'input');
    data_set($stockLookupFields, 'itemnetweight.type', 'input');
    data_set($stockLookupFields, 'itemgrossweight.type', 'input');
    data_set($stockLookupFields, 'rem.type', 'input');
    array_push($inputLookupFields, ['form'=>'stockLookupFields', 'fields'=>$stockLookupFields]);

    $inputLookupButtons = [];
    $btns = ['saverecord', 'cancelrecord'];
    $stockLookupButtons = $this->buttonClass->create($btns);
    data_set($stockLookupButtons, 'saverecord.form', 'stockLookupButtons');
    data_set($stockLookupButtons, 'saverecord.func', 'submitDR');
    data_set($stockLookupButtons, 'saverecord.functype', 'global');
    data_set($stockLookupButtons, 'cancelrecord.form', 'stockLookupButtons');
    data_set($stockLookupButtons, 'cancelrecord.func', 'cancelDR');
    data_set($stockLookupButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'stockLookupButtons', 'btns'=>$stockLookupButtons]);

    return ['cfTableCols'=>$cfTableCols, 'cfTableButtons'=>$cfTableButtons, 'cfTableHeadButtons'=>$cfTableHeadButtons, 'cLookupHeadFields'=>$cLookupHeadFields, 'cLookupHeadButtons'=>$cLookupHeadButtons, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupTableButtons'=>$cLookupTableButtons, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons];
  }

  public function getFunc() {
    return '({
      docForm: [],
      tableData: [],
      loadTableData: function () {
        console.log("loadTableData receiving report called");
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          const docbref = sbc.globalFunc.getDocBref();
          let doc = docbref.doc;
          let bref = docbref.bref;
          tx.executeSql("select * from head where doc=? and substr(bref, instr(bref,?),-(length(bref)-INSTR(bref,?)))=?", [doc, "-", "-", bref], function (tx, res) {
            sbc.modulefunc.tableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.tableData.push(res.rows.item(x));
              }
            }
            $q.loading.hide();
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          console.log("zzzzzzzzxczxczxc", err);
        });
      },
      uploadRRStock: function () {
        const datenow = cfunc.getDateTime("datetime");
        cfunc.showLoading();
        cfunc.getTableData("config", ["serveraddr", "username"], true).then(configdata => {
          api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("uploadStockProd"), doc: sbc.doc, access: sbc.selDoc.access, trno: sbc.globalFunc.selDocProd.trno, line: sbc.modulefunc.inputLookupForm.line, data: sbc.modulefunc.inputLookupForm, scantype: sbc.globalFunc.scanType, user: configdata.username, date: datenow }, { headers: sbc.reqheader }).then(res => {
            cfunc.showMsgBox("Stock uploaded", "positive");
            sbc.modulefunc.inputLookupForm.rem = "";
            sbc.modulefunc.inputLookupForm.dr = "";
            sbc.modulefunc.removeStock(sbc.modulefunc.inputLookupForm.trno, sbc.modulefunc.inputLookupForm.line);
          }).catch(err => {
            cfunc.showMsgBox("Error uploading stock, " + err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      removeStock: function (trno, line) {
        sbc.db.transaction(function (tx) {
          tx.executeSql("delete from stock where trno=? and line=?", [trno, line]);
        });
      },
      loadDocStocksProd: function () {
        let scanned = "";
        let filter = "";
        let qry = "select * from stock where trno=?";
        let data = [sbc.globalFunc.selDocProd.trno];
        if (sbc.globalFunc.searchCoil !== "" && sbc.globalFunc.searchCoil !== null && typeof (sbc.globalFunc.searchCoil) !== "undefined") {
          qry += " and bundleno like ?";
          data.push("%" + sbc.globalFunc.searchCoil + "%");
        }
        scanned = " and (rem = ? or isscanned=  0)";
        data.push("");
        if (sbc.globalFunc.scanStat === 1) {
          scanned = " and (rem <> ? or isscanned = 1)";
        }
        qry += scanned;
        sbc.modulefunc.lookupTableData = [];
        sbc.db.transaction(function (tx) {
          tx.executeSql(qry, data, function (tx, res) {
            console.log("bbbbbbbbbbbbb", res.rows.length);
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                console.log("11111111111111111", res.rows.item(x));
                sbc.modulefunc.lookupTableData.push(res.rows.item(x));
              }
            }
            $q.loading.hide();
          }, function (tx, err) {
            console.log("zzzzzzzzzz", err);
            cfunc.showMsgBox("Error loading stocks, " + err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          console.log("lllllllllllll", err);
        });
      }
    })';
  }
}