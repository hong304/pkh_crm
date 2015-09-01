'use strict';


function editCountry(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.editCountry(id);
    });
}

function editInvoicePayment(invoiceId,customerId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.editInvoicePayment(invoiceId,customerId);
    });
}

function viewInvoicePayment(invoiceId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewInvoicePayment(invoiceId);
    });
}



app.controller('financeCashController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state,$stateParams) {

    var query = endpoint + '/querryCashCustomer.json';



    $scope.invoice = [];

    var fetchDataTimer;
    var fetchDataDelay = 500;

    $scope.payment = [];
    $scope.discount = 0;
    $scope.invoicepaid = [];
    $scope.invoiceStructure = {
        'id' :'',
        'paid' : '',
        'collect': '',
        'date' : ''
    }

    $scope.filterData = {
        'displayName'	:	'',
        'clientId'		:	'',
        'status'		:	'20',
        'zone'			:	'',
         'created_by'	:	'0',
        'invoiceNumber' :	'',
        'bankCode' : '003',
        'cashAmount' : '0',
        'amount' : '0',
        'paid' : '0',
        'no' : '',
        'remain' : 0,
        'customerId':'',
        'customerName':''
    };


    var today = new Date();
    var nextDay = today;

    var day = nextDay.getDate();
    var month = nextDay.getMonth() + 1;
    if (month < 10) { month = '0' + month; }
    var year = nextDay.getFullYear();

    var d = new Date();
    d.setMonth( d.getMonth( ) - 1 );
    var lastMonth = d.getMonth( ) + 1;

    $('#deliverydate').datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate").datepicker( "setDate", d.getFullYear() + '-' + lastMonth + '-' + d.getDate());

    $scope.filterData.deliverydate = d.getFullYear()+'-'+lastMonth+'-'+d.getDate();


    $("#deliverydate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate2").datepicker( "setDate", year + '-' + month + '-' + day );
    $scope.filterData.deliverydate2 = year+'-'+month+'-'+day;



    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();

    });




    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
       // $scope.updateDataSet();
    }, true);

    $scope.$on('handleCustomerUpdate', function(){
        // received client selection broadcast. update to the invoice portlet

        $scope.filterData.clientId = SharedService.clientId;
        $scope.filterData.displayName = SharedService.clientId + " (" + SharedService.clientName + ")";
        $scope.filterData.zone = '';
        $scope.updateDataSet();

        Metronic.unblockUI();
    });

    $scope.editCountry = function(id){
        $http.post(endpoint + "/delPayment.json", { id: id})
            .success(function(res, status, headers, config){
                $scope.del = res;
                if(res) {
                    $('#invoiceDetails').modal('hide');
                    Metronic.alert({
                        container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
                        place: 'prepend', // append or prepent in container
                        type: 'success',  // alert's type
                        message: '<span style="font-size:16px;">刪除成功</span>',  // alert's message
                        close: true, // make alert closable
                        reset: true, // close all previouse alerts first
                        focus: true, // auto scroll to the alert after shown
                        closeInSeconds: 0, // auto close after defined seconds
                        icon: 'warning' // put icon before the message
                    });
                }
            });
    }

    $scope.editInvoicePayment = function(invoiceId,customerId)
    {

        var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 1);

        var ymonth = start_date.getMonth() + 1;
        var yyear = start_date.getFullYear();
        var yday = start_date.getDate();

        $scope.filterData.invoiceId = invoiceId;

        $http.post(query, {mode: "paymentHistory", customerId: customerId,invoiceId:invoiceId})
            .success(function(res){
                $scope.paymentDetails = res;

            });

        $('#invoicePayment').modal('show');

        $('#invoicePayment').on('shown.bs.modal', function () {

            $('#date-picker').datepicker({
                rtl: Metronic.isRTL(),
                orientation: "left",
                autoclose: true
            });
            $("#date-picker").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);
        })

    }

    $scope.viewInvoicePayment = function(invoiceId)
    {


        Metronic.blockUI();
        $http.post(query, {mode: "single", invoiceId: invoiceId})
            .success(function(res, status, headers, config){

        $scope.invoiceDetails = res;

              Metronic.unblockUI();
                $("#invoiceDetails").modal({backdrop: 'static'});

            });

    }

    $scope.checkChequeExist = function (){

        $http({
            method: 'POST',
            url: query,
            data: {paidinfo:$scope.filterData,mode:'checkChequeExist'}
        }).success(function (res) {
            $scope.cheque = res;
            console.log($scope.cheque);
            if($scope.cheque.amount > 0){
                $scope.filterData.amount = $scope.cheque.amount;
                $scope.filterData.remain = $scope.cheque.remain;
            }
        });

    }

    $scope.sendRealFile = function()
    {
       /* $http({
            method: 'POST',
            url: endpoint + '/getPaymentDetails.json',
            data: {filterData:$scope.filterData,mode:'excel'}
        }).success(function (res) {

        });*/
        var queryObject = {
            filterData	:	$scope.filterData
        };
         var queryString = $.param( queryObject );
          window.open(endpoint + "/getPaymentDetails.json?" + queryString);


    }

    $scope.updateInvoiceNumber = function()
    {
        $scope.cheque = {
            'remain' : 0,
            'amount' : 0
        }
        $scope.filterData.bankCode = '003';
        $scope.filterData.cashAmount = '0';
        $scope.filterData.amount = '0';
        $scope.filterData.paid = '0';
        $scope.filterData.no = '';
        $scope.filterData.status = '20';


        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        }, 1000);

    }

    $scope.updateCustomer = function(){
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        }, fetchDataDelay);
    }

    $scope.autoPost = function(){

        var owe = 0;
        $scope.paymentDetails.forEach(function(item) {
            if(item.invoiceId==$scope.filterData.invoiceId)
                owe = item.owe;
        });

if(($scope.filterData.cashAmount>0 && $scope.filterData.cashAmount != owe) || ($scope.filterData.paid>0 && $scope.filterData.paid != owe) ){

        bootbox.dialog({
            message: "支付數目與尚欠款項有差異，請按下列選擇",
            title: "提交付款",
            buttons: {

                main: {
                    label: " 給予折扣",
                    className: "btn-primary",
                    callback: function() {

                        $http({
                            method: 'POST',
                            url: query,
                            data: {paidinfo:$scope.filterData,mode:'posting',discount:$scope.paymentDetails[0]['owe'],paymentStatus:'30'}
                        }).success(function () {
                            $('#invoicePayment').modal('hide');
                            Metronic.alert({
                                container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
                                place: 'prepend', // append or prepent in container
                                type: 'success',  // alert's type
                                message: '<span style="font-size:16px;">提交成功(給予折扣)</span>',  // alert's message
                                close: true, // make alert closable
                                reset: true, // close all previouse alerts first
                                focus: true, // auto scroll to the alert after shown
                                closeInSeconds: 0, // auto close after defined seconds
                                icon: 'warning' // put icon before the message
                            });
                            $scope.updateDataSet();
                        });

                    }
                },

                success: {
                    label: " 部分支付",
                    className: "green",
                    callback: function() {
                        $http({
                            method: 'POST',
                            url: query,
                            data: {paidinfo:$scope.filterData,mode:'posting',paymentStatus:'20'}
                        }).success(function () {
                            $('#invoicePayment').modal('hide');
                            Metronic.alert({
                                container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
                                place: 'prepend', // append or prepent in container
                                type: 'success',  // alert's type
                                message: '<span style="font-size:16px;">部分支付成功</span>',  // alert's message
                                close: true, // make alert closable
                                reset: true, // close all previouse alerts first
                                focus: true, // auto scroll to the alert after shown
                                closeInSeconds: 0, // auto close after defined seconds
                                icon: 'warning' // put icon before the message
                            });
                            $scope.updateDataSet();
                        });

                    }
                },

                danger: {
                    label: "返回",
                    className: "red",
                    callback: function() {

                    }
                }
            }
        });
}else if($scope.filterData.paid==0 && $scope.filterData.cashAmount ==0){

    $http({
        method: 'POST',
        url: query,
        data: {paidinfo:$scope.filterData,mode:'posting',paymentStatus:'20'}
    }).success(function () {
        $('#invoicePayment').modal('hide');
        Metronic.alert({
            container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
            place: 'prepend', // append or prepent in container
            type: 'success',  // alert's type
            message: '<span style="font-size:16px;">提交成功</span>',  // alert's message
            close: true, // make alert closable
            reset: true, // close all previouse alerts first
            focus: true, // auto scroll to the alert after shown
            closeInSeconds: 0, // auto close after defined seconds
            icon: 'warning' // put icon before the message
        });
        $scope.updateDataSet();
    });
}else{
    $http({
        method: 'POST',
        url: query,
        data: {paidinfo:$scope.filterData,mode:'posting',paymentStatus:'30'}
    }).success(function () {
        $('#invoicePayment').modal('hide');
        Metronic.alert({
            container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
            place: 'prepend', // append or prepent in container
            type: 'success',  // alert's type
            message: '<span style="font-size:16px;">成功全數支付</span>',  // alert's message
            close: true, // make alert closable
            reset: true, // close all previouse alerts first
            focus: true, // auto scroll to the alert after shown
            closeInSeconds: 0, // auto close after defined seconds
            icon: 'warning' // put icon before the message
        });
        $scope.updateDataSet();
    });
}
/*

 */
       // $scope.getPaymentInfo('autopost');
    }

   /* $scope.updateDataSet = function(){


        Metronic.alert('close');
        $http.post(query, {mode:'collection', filterData: $scope.filterData})
            .success(function(res){


                $scope.invoiceinfo = res;
                $scope.invoicepaid.amount = 0;
                $scope.invoicepaid.settle = 0;
                $scope.invoicepaid = [];
                var i = 0;
                res.forEach(function(item) {
                    $scope.invoicepaid[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.invoicepaid[i]['id'] = item.invoiceId;
                    $scope.invoicepaid[i]['customerId'] = item.customerId;
                    $scope.invoicepaid[i]['paid'] = 0;
                    $scope.invoicepaid[i]['date'] = year + '-' + month + '-' + day;
                    $scope.invoicepaid[i]['collect'] = 0;
                    $scope.invoicepaid.amount = $scope.invoicepaid.amount + Number(item.amount);
                    $scope.invoicepaid.settle = $scope.invoicepaid.amount;
                    i++;
                    $scope.invoicepaidcount = i;
                });

                if($scope.invoicepaidcount == 1){
                    $('#paidform').show();
                }
            });
    }*/

    $scope.updateDataSet = function()
    {

        $scope.invoicepaid[0] = $.extend(true, {}, $scope.invoiceStructure);
        $scope.invoicepaid[0]['paid'] = 0;

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


                "bStateSave": false, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [
                    [20, 50],
                    [20, 50] // change per page values here
                ],
                "pageLength": 50, // default record count per page

                "ajax": {
                    "url": query, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData, mode: "collection"},
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
                    { "data": "invoiceId", "width":"8%" },
                    { "data": "deliveryDate_date", "width":"7%" },
                    { "data": "zoneId", "width":"5%" },
                    { "data": "customerName",  "width":"15%"},
                    { "data": "amount", "width":"5%" },
                    { "data": "remain", "width":"7%" },
                    { "data": "invoiceStatusText", "width":"6%" },
                    { "data": "paymentTermsText", "width":"6%" },
                     { "data": "link", "width":"5%" },
                    { "data": "details", "width":"5%" }


                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }

    $scope.updateNumSum = function()
    {
        $scope.invoicepaid.settle = 0;
var i =0;
        $scope.invoiceinfo.forEach(function(item){
           if($scope.invoicepaid[i]['paid'])
            $scope.invoicepaid.settle = $scope.invoicepaid.settle + Number(item.amount);
               // console.log(item.amount);
            i++;
          });
      //  console.log($scope.invoiceinfo);

    }

    $scope.updateZone = function()
    {
        $scope.updateDataSet();
    }

    $scope.updateStatus = function()
    {
        $scope.updateDataSet();
    }

    $scope.updateDelvieryDate = function()
    {
        $scope.updateDataSet();
    }



    $scope.clearCustomerSearch = function()
    {
        $scope.filterData = {
            'displayName'	:	'',
            'clientId'		:	'',
            'status'		:	'0',
            'zone'			:	'',
            'deliverydate'	:	'last day',
            'created_by'	:	'0',
            'invoiceNumber' :	'',
        };
        $scope.updateDataSet();
    }




});