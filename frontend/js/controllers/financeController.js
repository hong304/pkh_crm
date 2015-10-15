'use strict';

function processCheque(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.processCheque(id);

    });
}

function viewCheque(cheque_id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewCheque(cheque_id);

    });
}

function delCheque(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {

        bootbox.dialog({
            message: "刪除支票後將不能復原，確定要刪除支票嗎？",
            title: "刪除支票",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定刪除",
                    className: "red",
                    callback: function() {
                        scope.delCheque(id);
                    }
                }
            }
        });

    });
}



app.controller('financeController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state,$stateParams) {



    var intarget = endpoint + '/addCheque.json';
    var query = endpoint + '/querryClientClearance.json';
    var getClearance = endpoint + '/getClearance.json';
    var genPDF = endpoint + '/generalFinanceReport.json';

var fetchDataTimer = '';
    $scope.invoice = [];
    var today = new Date();
    var day = today.getDate();
    var month = today.getMonth() + 1;
    var year = today.getFullYear();

    $scope.bankName = '000';
    $scope.payment = [];
    $scope.discount = [];
    $scope.invoicepaid = [];
    $scope.totalAmount = 0;
    $scope.invoiceStructure = {
        'id' :'',
        'settle':'',
        'discount' : ''
    }

    $scope.chequeDetails = [];

    $scope.filterData = {
        'displayName'	:	'',
        'customerId'		:	'',
        'clientId' : '',
        'customer_group_id' : '',
        'status'        :   '2',
        'bankCode' : '003',
        'deliverydate': '',
        'deliverydate2' : '',
        'receiveDate' : '',
        'receiveDate2' : '',
        'ChequeNumber' : '',
        'groupName' : '',

    };

    var today = new Date();
    var plus = today.getDay() == 6 ? 3 : 2;
    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 1);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();

    var day = currentDate.getDate();
    var month = currentDate.getMonth() + 1;
    var year = currentDate.getFullYear();

    $("#receiveDate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#receiveDate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });

    $("#deliverydate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);

    $("#deliverydate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate2").datepicker( "setDate", year + '-' + month + '-' + day );

    $scope.filterData.deliverydate = yyear+'-'+ymonth+'-'+yday;
    $scope.filterData.deliverydate2 = year+'-'+month+'-'+day;

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.action = $location.search().action;

        if($location.search().action == 'success') {
            $scope.success = true;
        }else if ($location.search().action == 'psuccess') {
            $scope.psuccess = true;
        }

        else if($location.search().action == 'process'){
            $scope.getPaymentInfo('invoice');
        }else if($location.search().action == 'newCheque'){
              $('.date-picker').datepicker({
                    rtl: Metronic.isRTL(),
                    orientation: "left",
                    autoclose: true
                });

                $('.date-picker').datepicker( "setDate" , year + '-' + month + '-' + day );
        }else
             $scope.getChequeList();




    });


    $scope.$on('handleCustomerUpdate', function(){
        $scope.filterData.clientId = SharedService.clientId;
        $scope.filterData.displayName = SharedService.clientId + " (" + SharedService.clientName + ")";
        $scope.getChequeList();
    });

    $rootScope.$on('$locationChangeSuccess', function(){
        $scope.action = $location.search().action;
        $scope.getChequeList();
    });

    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.getChequeList();
    }, true);

    $scope.$on('handleCustomerUpdate', function(){
        // received client selection broadcast. update to the invoice portlet

       // $scope.filterData.clientId = SharedService.clientId;
      //  $scope.filterData.displayName = SharedService.clientId + " (" + SharedService.clientName + ")";
        $scope.filterData.customer_group_id = SharedService.GroupId;
        $scope.filterData.groupname = SharedService.GroupName;
        //$scope.filterData.zone = '';
        $scope.getChequeList();

        Metronic.unblockUI();
    });

    $scope.viewCheque = function(cheque_id)
    {
        Metronic.blockUI();
        $http.post(query, {mode: "single", cheque_id: cheque_id})
            .success(function(res, status, headers, config){
                $scope.chequeDetails = res.payment;
                $scope.receiveDate = res.payment.receive_date;
                $scope.chequeCustomers = res.customer;

                Metronic.unblockUI();
                $("#chequeDetails").modal({backdrop: 'static'});

            });
    }

    $scope.clearGroup = function(){
        $scope.filterData.customer_group_id = '';
        $scope.filterData.groupname = '';
    }

    $scope.processCustomer = function()
    {
        $http.post(getClearance, {mode:'processCustomer',filterData:$scope.filterData})
            .success(function(res, status, headers, config){
                $scope.totalAmount = 0;
                if(res.error){
                    Metronic.alert({
                        container: '#cheque_form', // alerts parent container(by default placed after the page breadcrumbs)
                        place: 'prepend', // append or prepent in container
                        type: 'danger',  // alert's type
                        message: '<span style="font-size:16px;">'+res.error + '</strong></span>',  // alert's message
                        close: true, // make alert closable
                        reset: true, // close all previouse alerts first
                        focus: true, // auto scroll to the alert after shown
                        closeInSeconds: 0, // auto close after defined seconds
                        icon: '' // put icon before the message
                    });
                }else{



                $scope.payment = res;
                $scope.invoiceinfo = res.data;
                $scope.invoicepaid = [];

                var i = 0;
                $scope.invoiceinfo.forEach(function(item) {
                    $scope.invoicepaid[i] = $.extend(true, {}, $scope.invoiceStructure);
                    if(item.invoiceStatus != 98)
                        $scope.invoicepaid[i]['settle'] = Number( (100-$scope.payment.discount)/100 * item.realAmount).toFixed(2);
                    else
                        $scope.invoicepaid[i]['settle']= Number(item.realAmount).toFixed(2);
                    $scope.invoicepaid[i]['id'] = item.invoiceId;

                    if($scope.payment.discount > 0 && item.invoiceStatus != 98)
                        $scope.invoicepaid[i]['discount'] = 1;

                    i++;
                });
                $scope.updatePaidTotal();
                }
            });
     //   $location.url("/finance-clientClearance?action=processCustomer");

          /*  $scope.cheque.clientId = $scope.filterData.clientId;

            $http.post(intarget, {info: $scope.cheque})
                .success(function(res, status, headers, config){
                   $location.url("/chequeListing?action=success");
                 });*/


    }

    $scope.showselectgroup = function()
    {
        $("#selectGroupmodel").modal({backdrop: 'static'});
    }




    $scope.autoPost = function(){

        var cheque_no = $scope.filterData.no;
        if(cheque_no.length < 6){
            alert('支票號碼只接受6位數字或以上');
            return false;
        }

        if(!$scope.filterData.discount)
            if($scope.totalAmount != $scope.filterData.amount) {
                alert('需付金額不等於支票銀碼');
                return false;
            }

        var data = $scope.invoicepaid;
        $http({
            method: 'POST',
            url: intarget,
            data: {paid:data,filterData: $scope.filterData}
        }).success(function (res) {
            if(res.length > 0)
                alert(res);
            else
                $location.url("/chequeListing?action=psuccess");
        });

       // $scope.getPaymentInfo('autopost');
    }



$scope.updatePaidTotal = function(){
    $scope.totalAmount = 0;
    $scope.invoicepaid.forEach(function(item){
            $scope.totalAmount += (Number(item.settle));
    });
    $scope.totalAmount = $scope.totalAmount.toFixed(2);
}

$scope.updateDiscount = function(){

    var i = 0;
    $scope.invoiceinfo.forEach(function(item){
        if(item.invoiceStatus != 98)
            $scope.invoicepaid[i]['settle']= Number( (100-$scope.payment.discount)/100 * item.realAmount).toFixed(2);
        else
            $scope.invoicepaid[i]['settle']= Number(item.realAmount).toFixed(2);
        if($scope.payment.discount > 0 && item.invoiceStatus != 98)
            $scope.invoicepaid[i]['discount'] = 1;
        else
            $scope.invoicepaid[i]['discount'] = 0;
        i++;
    });
    $scope.updatePaidTotal();
}

    $scope.addCheque = function()
    {
        $location.url("/finance-newCheque?action=newCheque");

    }

    $scope.processCheque = function(id){
        $location.url("/finance-clientClearance?action=process&id="+id);
    }



    $scope.delCheque = function(id){





                $http({
                    method: 'POST',
                    url: query,
                    data: {mode:'del',cheque_id:id}
                }).success(function () {
                    $scope.del = true;


                   // $state.go('chequeListing',{action:'del'},{ reload: true, inherit: false, notify: true });

                    $scope.getChequeList();
                });




    }


    $scope.updateDeliveryDate = function()
    {
        $scope.filterData.receiveDate = "";
        $scope.filterData.receiveDate2 = "";
        $scope.getChequeList();
    }

    $scope.updateReceiveDate = function()
    {
        $scope.filterData.deliverydate = "";
        $scope.filterData.deliverydate2 = "";
        $scope.getChequeList();
    }

    $scope.updateGroupName = function(){
    fetchDataTimer = $timeout(function () {
        $scope.getChequeList();
    }, 500);

}

    $scope.updateStatus = function()
    {
        $scope.getChequeList();
    }

    $scope.updateChequeNumber = function()
    {
        $scope.getChequeList();
    }


    $scope.clearCustomerSearch = function()
    {
        $scope.filterData.displayName = "";
        $scope.filterData.clientId = "";
        $scope.filterData.receiveDate = "";
        $scope.filterData.receiveDate2 = "";
        $scope.getChequeList();
    }

    $scope.getChequeList = function()
    {


        $(document).ready(function() {

            if(!$scope.firstload)
            {
                $("#datatable_ajax").dataTable().fnDestroy();
            }
            else
            {
                $scope.firstload = false;
            }


            $('#datatable_ajax').dataTable({

                // "dom": '<"row"f<"clear">>rt<"bottom"ip<"clear">>',

                "sDom": '<"row"<"col-sm-6"<"pull-left"p>><"col-sm-6"f>>rt<"row"<"col-sm-12"i>>',

                "bServerSide": true,

                "ajax": {
                    "url": query, // ajax source
                    "type": 'POST',
                    "data": {mode: "getChequeList",action: $location.search().action,filterData: $scope.filterData},
                    "xhrFields": {withCredentials: true}
                    },
                "iDisplayLength": 25,
                "pagingType": "full_numbers",
                "language": {
                    "lengthMenu": "顯示 _MENU_ 項結果",
                    "zeroRecords": "沒有匹配結果",
                    "sEmptyTable":     "沒有匹配結果",
                    "info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
                    "infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "Processing":   "處理中...",
                    "Paginate": {
                        "First":    "首頁",
                        "Previous": "上頁",
                        "Next":     "下頁",
                        "Last":     "尾頁"
                    }
                },
                "columns": [
                    { "data": "customID","width":"10%" },
                    { "data": "customName","width":"30%" },
                    { "data": "customGroup","width":"10%" },
                    { "data": "ref_number","width":"10%" },
                    { "data": "amount","width":"8%" },
                    { "data": "receive_date","width":"8%" },
                    { "data": "start_date","width":"8%" },
                    { "data": "end_date","width":"8%" },
                    { "data": "link","width":"8%" },


                ]

            });
        });
    }
/*
    $scope.updateDataSet1 = function($mode)
    {

        Metronic.blockUI();
        var grid = new Datatable();


        //var info = grid.page.info();
        if(!$scope.firstload)
        {
            $("#datatable_ajax").dataTable().fnDestroy();
        }
        else
        {
            $scope.firstload = false;
        }
        grid.init({
            src: $("#datatable_ajax"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
                Metronic.unblockUI();
            },
            onError: function (grid) {
                // execute some code on network or other general error
                Metronic.unblockUI();
            },
            loadingMessage: 'Loading...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options


                "bStateSave": true, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [
                    [10, 20, 50],
                    [10, 20, 50] // change per page values here
                ],
                "pageLength": 20, // default record count per page
                "ajax": {
                    "url": querry, // ajax source
                    "type": 'POST',
                    "data": {start_date: $scope.payment.start_date, end_date:$scope.payment.end_date,mode:$mode,customerId:$scope.payment.customerId, amount:$scope.payment.amount},
                    "xhrFields": {withCredentials: true}
                },
                "language": {
                    "lengthMenu": "顯示 _MENU_ 項結果",
                    "zeroRecords": "沒有匹配結果",
                    "sEmptyTable":     "沒有匹配結果",
                    "info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
                    "infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "Processing":   "處理中...",
                    "Paginate": {
                        "First":    "首頁",
                        "Previous": "上頁",
                        "Next":     "下頁",
                        "Last":     "尾頁"
                    }
                },
                "columns": [
                    { "data": "invoiceId" },
                    { "data": "deliveryDate_date" },
                    { "data": "invoiceTotalAmount" },
                    { "data" : "settle"},
                    { "data" : "checkbox"},

                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }*/

    $scope.sendRealFile = function()
    {


                var queryObject = {
                    filterData	:	$scope.filterData
                };
                var queryString = $.param( queryObject );

                window.open(endpoint + "/generalFinanceReport.json?" + queryString);

    }

});