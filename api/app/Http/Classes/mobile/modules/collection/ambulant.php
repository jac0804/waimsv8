<?php
namespace App\Http\Classes\mobile\modules\collection;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class ambulant {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['area', 'transtype', 'amt', 'saveamb'];
    $cfHeadFields = $this->fieldClass->create($fields);
    data_set($cfHeadFields, 'area.type', 'lookup');
    data_set($cfHeadFields, 'area.action', 'arealookup');
    data_set($cfHeadFields, 'area.readonly', 'true');
    data_set($cfHeadFields, 'area.fields', 'area');
    data_set($cfHeadFields, 'transtype.type', 'lookup');
    data_set($cfHeadFields, 'transtype.action', 'transtypeslookup');
    data_set($cfHeadFields, 'transtype.readonly', 'true');
    data_set($cfHeadFields, 'transtype.fields', 'transtype');

    return ['cfHeadFields'=>$cfHeadFields];
  }

  public function getFunc() {
    return '({
      docForm: { area: "", selArea: [], category: "", tenant: "", selTenant: [], transtypeOpts: [], transtype: "", loc: "", rent: "", cusa: "", outstandingbal: "", remarks: "", payment: "", amt: "", balance: "", consumption: "", beginning: "", dateid: "", ending: "" },
      loadTableData: function () {
        sbc.isFormEdit = true;
        sbc.globalFunc.loadAreasLookup();
      },
      saveAmb: function () {
        if (sbc.modulefunc.docForm.selArea.phase === "" && sbc.modulefunc.docForm.selArea.section === "") {
          cfunc.showMsgBox("Please select Area", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.transtype === "") {
          cfunc.showMsgBox("Please select Transaction Type", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.amt === "") {
          cfunc.showMsgBox("Please enter Amount", "negative", "warning");
          return;
        }
        cfunc.showLoading();
        const datenow = cfunc.getDateTime("datetime");
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        let transtype = sbc.globalFunc.getTransType(sbc.modulefunc.docForm.transtype);
        cfunc.getTableData("config", "collectiondate", false).then(collDate => {
          if (collDate === "" || collDate === null || typeof (collDate) === "undefined") {
            cfunc.showMsgBox("Collection date not set", "negative", "warning");
            $q.loading.hide();
          } else {
            const params = {
              clientid: 0,
              amount: sbc.numeral(sbc.modulefunc.docForm.amt).format("0.00"),
              status: "AMB",
              dateid: collDate,
              center: sbc.modulefunc.docForm.selArea.center,
              type: transtype,
              remarks: "",
              collectorid: storage.user.userid,
              collectorname: storage.user.username,
              transtime: datenow,
              stallnum: "",
              transtype: sbc.modulefunc.docForm.transtype,
              outstandingbal: "",
              rent: "",
              cusa: "",
              receiptTitle: "Acknowledgement Receipt",
              receiptType: "payment",
              phase: sbc.modulefunc.docForm.selArea.phase,
              section: sbc.modulefunc.docForm.selArea.section
            };
            sbc.modulefunc.docForm.amt = "";
            sbc.globalFunc.savePayment(params);
          }
        });
      }
    })';
  }
}