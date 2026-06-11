<?php
namespace App\Http\Classes\mobile\modules\ordering;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class reports {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['startdate', 'enddate', 'refresh'];
    if($this->company == 'marswin') array_push($fields, 'searchdoc');
    $cfHeadFields = $this->fieldClass->create($fields);
    data_set($cfHeadFields, 'refresh.func', 'loadTableData');
    data_set($cfHeadFields, 'refresh.functype', 'module');

    $fields = ['docno'];
    if($this->company == 'marswin') array_push($fields, 'doctype');
    array_push($fields, 'clientname', 'dateid', 'itemcount', 'total');
    $cfTableCols = $this->fieldClass->create($fields);
    if($this->company == 'marswin') {
      data_set($cfTableCols, 'doctype.type', 'label');
      data_set($cfTableCols, 'doctype.field', 'rem');
    }
    data_set($cfTableCols, 'clientname.label', 'Customer');
    data_set($cfTableCols, 'itemcount.label', 'Item Count');
    data_set($cfTableCols, 'docno.field', 'docno');
    data_set($cfTableCols, 'clientname.field', 'clientname');
    data_set($cfTableCols, 'dateid.field', 'dateid');
    data_set($cfTableCols, 'itemcount.field', 'itemcount');
    data_set($cfTableCols, 'total.field', 'grandtotal');

    $btns = ['vieworder'];
    $cfTableButtons = $this->buttonClass->create($btns);

    $cLookupHeadFields = [];
    $fields = [];
    if($this->company == 'marswin') $fields = ['doctype'];
    $orderDetailHeadFields = $this->fieldClass->create($fields);
    if($this->company == 'marswin') {
      data_set($orderDetailHeadFields, 'doctype.type', 'input');
      data_set($orderDetailHeadFields, 'doctype.label', 'Document Type');
      data_set($orderDetailHeadFields, 'doctype.readonly', true);
    }
    array_push($cLookupHeadFields, ['form'=>'orderDetailHeadFields', 'fields'=>$orderDetailHeadFields]);

    $cLookupTableCols = [];
    $fields = ['itemname', 'isqty', 'isamt', 'disc', 'uom', 'ext', 'rem', 'barcode'];
    if($this->company == 'marswin') array_push($fields, 'sjstatus', 'sjref');
    $orderDetailCols = $this->fieldClass->create($fields);
    data_set($orderDetailCols, 'barcode.type', 'label');
    data_set($orderDetailCols, 'barcode.field', 'barcode');
    data_set($orderDetailCols, 'itemname.type', 'label');
    data_set($orderDetailCols, 'itemname.field', 'itemname');
    data_set($orderDetailCols, 'uom.type', 'label');
    data_set($orderDetailCols, 'uom.field', 'uom');
    data_set($orderDetailCols, 'disc.type', 'label');
    data_set($orderDetailCols, 'disc.field', 'disc');
    data_set($orderDetailCols, 'isqty.label', 'Qty');
    data_set($orderDetailCols, 'isamt.type', 'label');
    data_set($orderDetailCols, 'isamt.field', 'isamt');
    data_set($orderDetailCols, 'ext.label', 'Total');
    array_push($cLookupTableCols, ['form'=>'orderDetailCols', 'fields'=>$orderDetailCols]);

    $cLookupButtons = [];
    $btns = ['close'];
    $orderDetailButtons = $this->buttonClass->create($btns);
    array_push($cLookupButtons, ['form'=>'orderDetailButtons', 'btns'=>$orderDetailButtons]);

    $cLookupFooterFields = [];
    $fields = ['itemcount', 'total'];
    $orderDetailFooterFields = $this->fieldClass->create($fields);
    data_set($orderDetailFooterFields, 'total.type', 'label');
    data_set($orderDetailFooterFields, 'total.label', 'Total: ');
    array_push($cLookupFooterFields, ['form'=>'orderDetailFooterFields', 'fields'=>$orderDetailFooterFields]);

    // $fields = ['total'];
    // $footerFields = $this->fieldClass->create($fields);
    // data_set($footerFields, 'total.type', 'label');
    // data_set($footerFields, 'total.label', 'Total: ');
    return ['cfHeadFields'=>$cfHeadFields, 'cfTableCols'=>$cfTableCols, 'cfTableButtons'=>$cfTableButtons, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupButtons'=>$cLookupButtons, 'cLookupFooterFields'=>$cLookupFooterFields, 'cLookupHeadFields'=>$cLookupHeadFields];
  }

  public function getFunc() {
    return '({
      company: "'.$this->company.'",
      docForm: { startdate: cfunc.getDateTime("date", 7), enddate: cfunc.getDateTime("date"), searchdoc: "", ordercount: 0, total: 0, doctype: "" },
      tableData: [],
      tableGrid: true,
      cLookupForm: [],
      lookupTableData: [],
      loadTableData: function () {
        console.log("loadTableData called");
        const thiss = this;
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        sbc.isFormEdit = true;
        cfunc.showLoading();
        cfunc.getTableData("config", "serveraddr").then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            return;
          }
          api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("loadOrders"), doc: "SJ", datefrom: thiss.docForm.startdate, dateto: thiss.docForm.enddate, username: storage.user.username, str: thiss.docForm.searchdoc }, { headers: sbc.reqheader }).then(res => {
            for (var b in sbc.footerbuttons) sbc.footerbuttons[b].show = "false";
            thiss.tableData = res.data.docs;
            thiss.docForm.ordercount = res.data.ordercount;
            thiss.docForm.total = sbc.numeral(res.data.total).format("0,0.00");
            if (thiss.tableData.length > 0) {
              for (var o in thiss.tableData) {
                thiss.tableData[o].grandtotal = sbc.numeral(thiss.tableData[o].grandtotal).format("0,0.00");
                thiss.tableData[o].itemcount = sbc.numeral(thiss.tableData[o].itemcount).value();
              }
            }
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      viewOrder: function (order, index) {
        const thiss = this;
        cfunc.getTableData("config", "serveraddr").then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            return;
          }
          cfunc.showLoading();
          sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "orderDetailHeadFields", "inputFields");
          sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "orderDetailHeadFields", "inputPlot");
          sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "orderDetailCols", "inputFields");
          sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "orderDetailCols", "inputPlot");
          sbc.selclookupfooterfields = sbc.globalFunc.getLookupForm(sbc.clookupfooterfields, "orderDetailFooterFields", "inputFields");
          sbc.selclookupfooterfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupfooterfieldsplot, "orderDetailFooterFields", "inputPlot");
          // sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "orderDetailButtons", "buttons");
          sbc.selclookuptablebuttons = [];
          sbc.selclookupbuttons = [];
          thiss.cLookupForm = thiss.docForm;
          sbc.cLookupTitle = order.orderno;
          sbc.showCustomLookup = true;
          sbc.lookupData = [];
          api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("loadOrderItems"), doc: "SJ", data: order }, { headers: sbc.reqheader }).then(res => {
            thiss.docForm.itemcount = order.itemcount;
            thiss.docForm.total = order.grandtotal
            thiss.docForm.doctype = order.rem;
            thiss.lookupTableData = [];
            if (res.data.length > 0) {
              for (var x = 0; x < res.data.length; x++) {
                thiss.lookupTableData.push(res.data[x]);
                thiss.lookupTableData[x].isamt = sbc.numeral(thiss.lookupTableData[x].isamt).format("0,0.00");
                thiss.lookupTableData[x].ext = sbc.numeral(thiss.lookupTableData[x].ext).format("0,0.00");
              }
              $q.loading.hide();
            }
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      closeDialog: function () {
        sbc.showCustomLookup = false;
      }
    })';
  }
}