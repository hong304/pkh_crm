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
    var getClearance = endpoint + '/getClearance.json';


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
        'customerId'		:	'',
        'clientId' : '',
        'customer_group_id' : '',
        'status'        :   '2',
        'bankCode' : '003',
        'deliverydate': '',
        'deliverydate2' : ''
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
    $scope.clearGroup = function(){
        $scope.filterData.customer_group_id = '';
        $scope.filterData.groupname = '';
    }

    $scope.processCustomer = function()
    {
        $http.post(getClearance, {mode:'processCustomer',filterData:$scope.filterData})
            .success(function(res, status, headers, config){
                $scope.payment = res;
                $scope.invoiceinfo = res.data;
                var i = 0;
                $scope.invoiceinfo.forEach(function(item) {
                    $scope.invoicepaid[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.invoicepaid[i]['settle'] = item.realAmount;
                    $scope.invoicepaid[i]['id'] = item.invoiceId;
                    i++;
                });
                $scope.updatePaidTotal();
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
        if(!$scope.filterData.discount)
            if($scope.totalAmount > $scope.filterData.amount) {
                alert('輸入數目大於支票可用餘額');
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

            $scope.totalAmount += Number(item.settle);

    });
$scope.updateDiscount = function(){

    var i = 0;
    $scope.invoiceinfo.forEach(function(item){

        $scope.invoicepaid[i]['settle']= Number(item.realAmount*$scope.filterData.discount);
        $scope.invoicepaid[i]['discount'] = 1;
i++;
    });
    $scope.updatePaidTotal();
}

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


    $scope.updateDelvieryDate = function()
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
                    "data": {mode: "getChequeList",filterData: $scope.filterData},
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
                    { "data": "amount","width":"10%" },
                    { "data": "remain","width":"10%" },
                    { "data": "start_date","width":"10%" },
                    { "data": "end_date","width":"10%" },


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


});