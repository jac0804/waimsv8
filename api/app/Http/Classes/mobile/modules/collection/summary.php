<?php
namespace App\Http\Classes\mobile\modules\collection;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class summary {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['sumticket1', 'sumticket2', 'printsum'];
    $cfHeadFields = $this->fieldClass->create($fields);
    return ['cfHeadFields'=>$cfHeadFields];
  }

  public function getFunc() {
    return '({
      docForm: { sumticket1: "", sumticket2: "" },
      loadTableData: function () {},
      printsum: function () {
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        console.log("sumticket1: ", sbc.modulefunc.docForm.sumticket1, "----sumticket2: ", sbc.modulefunc.docForm.sumticket2);
        if (sbc.modulefunc.docForm.sumticket1 === "") {
          cfunc.showMsgBox("Please enter Start Ticket No.", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.sumticket2 === "") {
          cfunc.showMsgBox("Please enter End Ticket No.", "negative", "warning");
          return;
        }
        cfunc.getTableData("config", ["collectiondate", "collectorid", "username"], true).then(config => {
          if (config.collectiondate === "" || config.collectiondate === null || typeof (config.collectiondate) === "undefined") {
            cfunc.showMsgBox("Collection date null, cannot proceed", "negative", "warning");
            return;
          }
          sbc.db.transaction(function (tx) {
            tx.executeSql("select sum(amount) as amt from dailycollection where line between ? and ?", [sbc.modulefunc.docForm.sumticket1, sbc.modulefunc.docForm.sumticket2], function (tx, res) {
              if (res.rows.length > 0) {
                const datenow = cfunc.getDateTime("datetime");
                const params = {
                  receiptType: "summary",
                  receiptTitle: "Summary Report",
                  ticket1: sbc.modulefunc.docForm.sumticket1,
                  ticket2: sbc.modulefunc.docForm.sumticket2,
                  dateid: datenow,
                  collectorid: config.collectorid,
                  collectorname: config.username,
                  amt: sbc.numeral(res.rows.item(0).amt).format("0,0.00"),
                  collDate: config.collectiondate
                };
                sbc.globalFunc.printCollectionReceipt(params, "summary");
              }
            });
          });
        });
      }
    })';
  }
}