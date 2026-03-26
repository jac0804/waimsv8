<?php

namespace App\Http\Classes\sbcscript;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class sbcscript
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


  // 1.head
  // state.headerdata = data sa header
  // state.headercols = object sa header

  // 2.stock
  // state.griddata = data
  // state.tabs = object
  // state.tabbtn 

  // 3.report
  // state.reportdata.params = data
  // state.reportobject.txtfield = object

  // 4.printbtn

  // 5. headtable
  // state.headtabledata = data
  // state.headtableobject = object

  // 6. headbtnclick
  // state.headerbuttons

  // 7. loadhead

  // 8. tableentry
  // 8.1. tableentrybtnbefore - triggered before btn click function
  // 8.2. tableentrybtnafter - triggered after btn click function
  // tableentrydata
  // tableentryobject.tab[0][tableentryobject.gridname].columns
  // tableentryobject.tabbuttons - head buttons ng tableentry
  // 8.3 tableentryload - triggered on load


  public function hq($config)
  {
    return [
      'head' => '
          if(payload.field!==undefined){
            if (payload.field === "reasontype"){
                if (payload.value === "Leave of Absence of"){
                    state.headercols.col2.prdstart.readonly = false
                    state.headercols.col2.prdend.readonly = false
                } else {
                    state.headercols.col2.prdstart.readonly = true
                    state.headercols.col2.prdend.readonly = true
                }
            }
            if (payload.field === "empstattype"){
                if (payload.value === "Contractual/Temporary"){
                    state.headercols.col2.empmonths.readonly = false
                    state.headercols.col2.empdays.readonly = false
                } else {
                    state.headercols.col2.empmonths.readonly = true
                    state.headercols.col2.empdays.readonly = true
                }
            }
          } else {
            state.headercols.col2.prdstart.readonly = true
            state.headercols.col2.prdend.readonly = true
            state.headercols.col2.empmonths.readonly = true
            state.headercols.col2.empdays.readonly = true
          }
        '
    ];
  }

  public function hn($config)
  {
    return [
      'head' => '
          if(payload.field!==undefined){
            if (payload.field === "iswithhearing"){
                if (payload.value === "1"){
                    state.headercols.col3.start.readonly = false;
                     state.headercols.col3.htime.type = "time";
                    state.headercols.col3.htime.readonly = false;
                    state.headercols.col3.position.readonly = false;
                } else {
                    state.headercols.col3.start.readonly = true;
                    state.headercols.col3.htime.readonly = true;
                    state.headercols.col3.position.readonly = true;
                }
            }
          }
        '
    ];
  }

  public function hs($config)
  {
    return [
      'head' => '
          if(payload.field!==undefined){
            if (payload.field === "chkcopy"){
                if (payload.value === "1"){
                  state.headerdata["salarytype"] = state.headerdata["fsalarytype"]
                  state.headerdata["hsperiod"] = state.headerdata["fhsperiod"]
                  state.headerdata["tbasicrate"] = state.headerdata["fbasicrate"]
                } 
            }
          } 
        ',
      'headbtnclick' => '
        console.log(payload.payload.name)
        if (payload.payload.name === "new") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = false;
                }
            }
        }
            
         if(payload.payload.name === "save" || payload.payload.name === "cancel") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = true;
                }
            }
        }

      '
    ];
  }

  public function leaveapplicationportal($config)
  {
    return [
      'head' => '
          if(payload.field!==undefined){
            if (payload.field == "hours"){
                if (payload.value == "0.5" || payload.value == ".5"){
                    state.headercols.col3.statrem.readonly = false
               }
            }
          }else{
           state.headercols.col3.statrem.readonly = true
          }
        '
    ];
  }

  public function itinerary($config)
  {
    return [
      'head' => '
          if(payload.field!==undefined){
            if (payload.field === "expensetype"){
                if (payload.value === "Gasoline"){
                    state.headercols.col2.gas.readonly = false
                    state.headercols.col2.texpense.readonly = true
                } else {
                    state.headercols.col2.gas.readonly = true
                    state.headercols.col2.texpense.readonly = false
                }
            }
          } else {
             state.headercols.col2.gas.readonly = true
             state.headercols.col2.texpense.readonly = true
          }
        '
    ];
  }
  public function loanapplicationportal()
  {
    return [
      'head' => '
          if(payload.field!==undefined){
            if (payload.field === "acno"){
                if (payload.value === "PT119"){
                    state.headercols.col2.purpose.style = "display:none";
                    state.headercols.col2.purpose1.style = "display:block";
                    state.headercols.col2.licenseno.style = "display:block";
                    state.headercols.col2.licensetype.style = "display:block";

                    state.headercols.col2.purpose1.readonly = false;
                    state.headercols.col2.licenseno.readonly = false;
                    state.headercols.col2.licensetype.readonly = false
                }else {
                  state.headercols.col2.purpose.style = "display:block"; 
                  state.headercols.col2.purpose1.style = "display:none";
                  state.headercols.col2.licenseno.style = "display:none";
                  state.headercols.col2.licensetype.style = "display:none";
                }
            }
          }else {
              if(payload.acno !== undefined){
                 if(payload.acno === "PT119"){
                    state.headercols.col2.purpose.style = "display:none";
                    state.headercols.col2.purpose1.style = "display:block";
                    state.headercols.col2.licenseno.style = "display:block";
                    state.headercols.col2.licensetype.style = "display:block";
                 }else{
                    state.headercols.col2.purpose.style = "display:block";
                    state.headercols.col2.purpose1.style = "display:none";
                    state.headercols.col2.licenseno.style = "display:none";
                    state.headercols.col2.licensetype.style = "display:none";
                 }
              }
          }

        ',
      'report' => '
        '
    ];
  }

  public function rc($config)
  {
    return ['stock' => '
        try {
            if (payload.field !== undefined) {
                if (payload.field === "amount") {
                    let amt = state.griddata.inventory[payload.index].amount;
                    if (state.griddata.inventory[payload.index].amount !== "") {
                        amt = amt.replace(/,/g, "");
                        // amt = amt.replaceAll(",", "");
                        state.griddata.inventory[payload.index].amount = new Intl.NumberFormat().format(parseFloat(amt).toFixed(2));
                    }
                }
            }
        } catch (e) {
            console.log("error: ", e);
        }
    '];
  }

  public function codeconduct($config)
  {
    return [
      'headbtnclick' => '
        if (payload.payload.name === "new") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = false;
                }
            }
        }
            
         if(payload.payload.name === "save") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = true;
                }
            }
        }
      '
    ];
  }

  public function obapplication($config)
  {
    return [
      'head' => '
           if(payload.field !== undefined){
               if(payload.field === "type"){
                  if(payload.value === "Official Business"){
                   state.headercols.col1.itime1.type = "time";
                   state.headercols.col1.itime1.label = "Time Out";
                   state.headercols.col1.itime.label = "Time In";
                   state.headercols.col1.itime1.style = "display:block";
                 
                  } else {
                  state.headercols.col1.itime1.type = "input";
                  state.headercols.col1.itime1.readonly = true;
                  state.headercols.col1.itime1.style = "display:none";
                  }
               }

           }else{
              if(payload.type !== undefined){
                if(payload.type === "Official Business"){
                 state.headercols.col1.itime1.type = "time";
                 state.headercols.col1.itime1.label = "Time Out";
                 state.headercols.col1.itime.label = "Time In";
                 state.headercols.col1.itime1.style = "display:block";
                
                }else {
                state.headercols.col1.itime.label = "Time";
                state.headercols.col1.itime1.type = "input";
                state.headercols.col1.itime1.readonly = true;
                state.headercols.col1.itime1.style = "display:none";
                }
              }
           }
      ',
      'loadhead' => '
        if (state.trno !== 0) { 
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = true;
                }
            }
        }
        if (state.headerdata.status !== undefined) {
            switch (state.headerdata.status) {
                case "ENTRY":
                    state.headerbuttons.print.visible = false
                    break;
                case "DISAPPROVED": case "APPROVED":
                    state.headerbuttons.edit.visible = false
                    state.headerbuttons.delete.visible = false
                    break;
            }
        }
      ',
      'headbtnclick' => '
        if (payload.payload.name === "new") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = false;
                }
            }
        }
            
         if(payload.payload.name === "save") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = true;
                }
            }
        }

      ',
      'tableentryheadbtn' => '
        console.log("-----------tableentryheadbtn called")
      ',
      'tableentryload' => '
        console.log("--tableentryload called");
      '
    ];
  }

  public function obapplicationcamera($config)
  {
    return [
      'head' => '
      if(payload.field !== undefined){
       if(payload.field == "type"){

        switch (payload.value) {
          case "Off-setting":
            state.headercols.col1.itime1.type = "time";
            state.headercols.col1.itime1.label = "Time Out";
            state.headercols.col1.itime1.readonly = false;
            state.headercols.col1.itime1.style = "display:block";
                    

            state.headercols.col1.itime.type = "time";
            state.headercols.col1.itime.readonly = false;
            state.headercols.col1.itime.style = "display:block";
            state.headercols.col1.itime.label = "Time In";
            
            break;
          case "Time-In":
          case "Time-In at the Place Visited":
            state.headercols.col1.itime.type = "time";
            state.headercols.col1.itime.readonly = false;
            state.headercols.col1.itime.style = "display:block";
            state.headercols.col1.itime.label = "Time In";

            state.headercols.col1.itime1.type = "input";
            state.headercols.col1.itime1.readonly = true;
            state.headercols.col1.itime1.style = "display:none";
            
            break;
          case "Time-Out":
          case "Time-Out at the Place Visited":

            state.headercols.col1.itime.type = "input";
            state.headercols.col1.itime.readonly = true;
            state.headercols.col1.itime.style = "display:none";

            state.headercols.col1.itime1.type = "time";
            state.headercols.col1.itime1.readonly = false;
            state.headercols.col1.itime1.style = "display:block";
            state.headercols.col1.itime1.label = "Time Out";
            break;
          default:
            state.headercols.col1.itime.type = "time";
            state.headercols.col1.itime.readonly = false;
            state.headercols.col1.itime.style = "display:block";

            state.headercols.col1.itime1.type = "input";
            state.headercols.col1.itime1.readonly = true;
            state.headercols.col1.itime1.style = "display:none";
          break;
            
        }

    }

           }else{
              if(payload.type !== undefined){
                 switch (payload.type) {
                  case "Off-setting":         
                    state.headercols.col1.itime1.type = "time";
                    state.headercols.col1.itime1.label = "Time Out";
                    state.headercols.col1.itime1.readonly = false;
                    state.headercols.col1.itime1.style = "display:block";
                    

                    state.headercols.col1.itime.type = "time";
                    state.headercols.col1.itime.readonly = false;
                    state.headercols.col1.itime.style = "display:block";
                    state.headercols.col1.itime.label = "Time In";
      
                    break;
                  case "Time-In":
                  case "Time-In at the Place Visited":
                    state.headercols.col1.itime.type = "time";
                    state.headercols.col1.itime.readonly = false;
                    state.headercols.col1.itime.style = "display:block";
                    state.headercols.col1.itime.label = "Time In";

                    state.headercols.col1.itime1.type = "input";
                    state.headercols.col1.itime1.readonly = true;
                    state.headercols.col1.itime1.style = "display:none";
                    break;
                  case "Time-Out":
                  case "Time-Out at the Place Visited":
                    state.headercols.col1.itime.type = "input";
                    state.headercols.col1.itime.readonly = true;
                    state.headercols.col1.itime.style = "display:none";

                    state.headercols.col1.itime1.type = "time";
                    state.headercols.col1.itime1.readonly = false;
                    state.headercols.col1.itime1.label = "Time Out";
                    state.headercols.col1.itime1.style = "display:block";
                    break;
                  default:
                    state.headercols.col1.itime.type = "time";
                    state.headercols.col1.itime.readonly = false;
                    state.headercols.col1.itime.style = "display:block";

                    state.headercols.col1.itime1.type = "input";
                    state.headercols.col1.itime1.readonly = true;
                    state.headercols.col1.itime1.style = "display:none";
                  break;
                }
              }
           }
      ',
      'loadhead' => '
            if (state.headerdata.status !== undefined) {
                switch (state.headerdata.status) {
                    case "ENTRY":
                        state.headerbuttons.print.visible = false
                        break;
                    case "DISAPPROVED": case "APPROVED":
                        state.headerbuttons.edit.visible = false
                        state.headerbuttons.delete.visible = false
                        break;
                }
            }
        '
    ];
  }

  public function entrymodel($config)
  {
    return [
      'tableentryload' => 'console.log("tableentryload called");'
    ];
  }

  public function customer($config)
  {
    return [
      'tableentryload' => '
            console.log(state.tableentryobject);
            if (state.tableentryobject.doc !== undefined) {
                if (state.tableentryobject.doc === "entrysku") {
                    console.log("tableentryload called: entrysku");
                }
            }
        '
    ];
  }

  public function mtpr($config)
  {
    return [
      'functtableentry2close' => '
        this.loadheaderdata(this.gettrno);
        '
    ];
  }
  public function  obapplication_cdohris($config)
  {
    return [
      'head' => '
      
          if(payload.field !== undefined){
            if(payload.field === "trackingtype"){
              switch(payload.value){
              case "DIRECT FIELD IN ONLY":
              case "KEY CUSTODIANS LATE":
              case "LATE TIME IN":
                  state.headercols.col1.itime1.type = "input";
                  state.headercols.col1.itime1.readonly = true;
                  state.headercols.col1.itime1.style = "display:none";

                  state.headercols.col1.itime.type = "time";
                  state.headercols.col1.itime.readonly = false;
                  state.headercols.col1.itime.style = "display:block";
                  state.headercols.col1.itime.label = "Time In";
                  break;
              case "DIRECT FIELD OUT ONLY":
              case "EARLY TIME OUT":
                  state.headercols.col1.itime.type = "input";
                  state.headercols.col1.itime.readonly = true;
                  state.headercols.col1.itime.style = "display:none";
               
                  state.headercols.col1.itime1.type = "time";
                  state.headercols.col1.itime1.readonly = false;
                  state.headercols.col1.itime1.style = "display:block";
                  state.headercols.col1.itime1.label = "Time Out";
                  break;
              case "BLACK OUT (1 ATTLOG)":
              case "BLACK OUT WHOLEDAY":
              case "RELIEVER FOR CASHIER (WHOLE DAY)":
              case "DAMAGE BIOMETRIC":
              case "PRORATE":
              case "NEW EMPLOYEE":
                  state.headercols.col1.itime.type = "time";
                  state.headercols.col1.itime.readonly = false;
                  state.headercols.col1.itime.style = "display:block";
                  state.headercols.col1.itime.label = "Time In";

                  state.headercols.col1.itime1.type = "time";
                  state.headercols.col1.itime1.readonly = false;
                  state.headercols.col1.itime1.style = "display:block";
                  state.headercols.col1.itime1.label = "Time Out";
                  break;
              default:
                  state.headercols.col1.itime1.type = "input";
                  state.headercols.col1.itime1.readonly = true;
                  state.headercols.col1.itime1.style = "display:none";

                  state.headercols.col1.itime.type = "time";
                  state.headercols.col1.itime.readonly = false;
                  state.headercols.col1.itime.style = "display:block";
                  state.headercols.col1.itime.label = "Time In";
                   
                  break;
                  }
           }
       }else {

        if(payload.trackingtype !== undefined){
            switch(payload.trackingtype){
              case "DIRECT FIELD IN ONLY":
              case "KEY CUSTODIANS LATE":
              case "LATE TIME IN":
                  state.headercols.col1.itime1.type = "input";
                  state.headercols.col1.itime1.readonly = true;
                  state.headercols.col1.itime1.style = "display:none";

                  state.headercols.col1.itime.type = "time";
                  state.headercols.col1.itime.readonly = false;
                  state.headercols.col1.itime.style = "display:block";
                  state.headercols.col1.itime.label = "Time In";
                  break;
              case "DIRECT FIELD OUT ONLY":
              case "EARLY TIME OUT":
                  state.headercols.col1.itime.type = "input";
                  state.headercols.col1.itime.readonly = true;
                  state.headercols.col1.itime.style = "display:none";
               
                  state.headercols.col1.itime1.type = "time";
                  state.headercols.col1.itime1.readonly = false;
                  state.headercols.col1.itime1.style = "display:block";
                  state.headercols.col1.itime1.label = "Time Out";
                  break;
              case "BLACK OUT (1 ATTLOG)":
              case "BLACK OUT WHOLEDAY":
              case "RELIEVER FOR CASHIER (WHOLE DAY)":
              case "DAMAGE BIOMETRIC":
              case "PRORATE":
              case "NEW EMPLOYEE":

                  state.headercols.col1.itime.type = "time";
                  state.headercols.col1.itime.readonly = false;
                  state.headercols.col1.itime.style = "display:block";
                  state.headercols.col1.itime.label = "Time In";

                  state.headercols.col1.itime1.type = "time";
                  state.headercols.col1.itime1.readonly = false;
                  state.headercols.col1.itime1.style = "display:block";
                  state.headercols.col1.itime1.label = "Time Out";
                  break;
              default:
                  state.headercols.col1.itime1.type = "input";
                  state.headercols.col1.itime1.readonly = true;
                  state.headercols.col1.itime1.style = "display:none";

                  state.headercols.col1.itime.type = "time";
                  state.headercols.col1.itime.readonly = false;
                  state.headercols.col1.itime.style = "display:block";
                  state.headercols.col1.itime.label = "Time In";
                   
                  break;
                  }
              }
           }
               
      '
    ];
  }

  public function taskmonitoring($config)
  {
    return [
      'headbtnclick' => '  
          if (payload.payload.name === "new") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = false;
                }
            }
        }

         if(payload.payload.name === "save") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = true;
                }
            }
        }

      '
    ];
  }
  public function hd($config)
  {
    return ['headbtnclick' => '
        if (payload.payload.name === "new") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = false;
                }
            }
        }
         if(payload.payload.name === "save") {
            if (state.tableentryobject.tabbuttons !== undefined) {
                for (var x in state.tableentryobject.tabbuttons) {
                    state.tableentryobject.tabbuttons[x].visible = true;
                }
            }
        }

      '];
  }

  public function uploadingutility($config)
  {
    return [
      'head' => '
          console.log(payload.field);
          if(payload.field !== undefined){
            if (payload.field === "optionuploading"){
                if (payload.value === "uploaddbtable"){
                    state.headercols.col1.clientname.style = "display:block";
                } else {
                    state.headercols.col1.clientname.style = "display:none";
                }
            }
          }
        '
    ];
  }

  public function ch($config)
  {
    return ['loadhead' => '
        if (state.trno !== 0) {             
                console.log("load",payload);
            
        }'];
  }

  public function skcustomform($config)
  {
    return [
      'loaddata' => 'const btn = document.getElementById("REFRESH"); btn.click()'
    ];
  }

  public function loaditembal($config)
  {
    return [
      'loadenterqty' => 'this.getitembalance()'
    ];
  }

  public function tk($config)
  {
    return [
      'loadhead' => '
        console.log("load",payload);'
    ];
  }
} // end class