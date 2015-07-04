'use strict';

function processCheque(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.processCheque(id);

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

    $scope.filterData = {
        'displayName'	:	'',
        'clientId'		:	'0',
        'zone'			:	'',
        'status'        :   ''
    };

    $scope.cheque = {
        'bankCode' : '003'
    }

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;

        if($location.search().action == 'success') {
            $scope.success = true;
        }else if ($location.search().action == 'psuccess') {
            $scope.psuccess = true;
        }


        if($location.search().action == 'process'){
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




    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.getChequeList();
    }, true);

    $scope.$on('handleCustomerUpdate', function(){
        // received client selection broadcast. update to the invoice portlet

        $scope.filterData.clientId = SharedService.clientId;
        $scope.filterData.displayName = SharedService.clientId + " (" + SharedService.clientName + ")";
        $scope.filterData.zone = '';
        $scope.getChequeList();

        Metronic.unblockUI();
    });

    $scope.submitCheque = function()
    {

            $scope.cheque.clientId = $scope.filterData.clientId;

            $http.post(intarget, {info: $scope.cheque})
                .success(function(res, status, headers, config){
                   $location.url("/chequeListing?action=success");
                 });


    }

    $scope.getPaymentInfo = function($mode){
        $http.post(query, {payment_id: $location.search().id, mode:'payment'})
            .success(function(res, status, headers, config){
                $scope.payment = res;
                $scope.updateDataSet($mode);
            });
    }

    $scope.autoPost = function(){
        if($scope.totalAmount > $scope.payment.remain) {
            alert('輸入數目大於支票可用餘額');
            return false;
        }
        var data = $scope.invoicepaid;
        $http({
            method: 'POST',
            url: query,
            data: {paid:data,mode:'posting',cheque_id: $scope.payment.id}
        }).success(function () {
            $location.url("/chequeListing?action=psuccess");
        });

       // $scope.getPaymentInfo('autopost');
    }

    $scope.updateDataSet = function($mode){
        $http.post(query, {start_date: $scope.payment.start_date, end_date:$scope.payment.end_date,mode:$mode,customerId:$scope.payment.customerId, amount:$scope.payment.remain})
            .success(function(res, status, headers, config){

                $scope.invoiceinfo = res;
               console.log(res);
var i = 0;
                res.forEach(function(item) {
                    $scope.invoicepaid[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.invoicepaid[i]['settle'] = item.settle;
                    $scope.invoicepaid[i]['id'] = item.invoiceId;
                    i++;
                });
                $scope.updatePaidTotal();
            });
    }

$scope.updatePaidTotal = function(){
    $scope.totalAmount = 0;
    $scope.invoicepaid.forEach(function(item){

            $scope.totalAmount += Number(item.settle);

    });


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


    $scope.updateZone = function()
    {
        $scope.getChequeList();
    }

    $scope.updateStatus = function()
    {
        $scope.getChequeList();
    }

    $scope.clearCustomerSearch = function()
    {
        $scope.filterData.displayName = "";
        $scope.filterData.clientId = "";
        $scope.getChequeList();
    }

    $scope.getChequeList = function()
    {



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

            },
            onError: function (grid) {
                // execute some code on network or other general error
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
                    "url": query, // ajax source
                    "type": 'POST',
                    "data": {mode: "getChequeList",filterData: $scope.filterData},
                    "xhrFields": {withCredentials: true},
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
                    { "data": "customerId" },
                    { "data": "customer.customerName_chi" },
                    { "data": "ref_number" },
                    { "data": "amount" },
                    { "data": "remain" },
                    { "data": "start_date" },
                    { "data": "end_date" },
                    { "data": "link" },
                    { "data": "delete" }

                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
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


});