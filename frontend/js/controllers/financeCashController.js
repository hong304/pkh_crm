'use strict';


app.controller('financeCashController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state,$stateParams) {

    var query = endpoint + '/querryCashCustomer.json';



    $scope.invoice = [];

    var fetchDataTimer;
    var fetchDataDelay = 500;

    $scope.payment = [];
    $scope.discount = [];
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
        'no' : ''
    };


    var today = new Date();
    var nextDay = today;

    var day = nextDay.getDate();
    var month = nextDay.getMonth() + 1;
    if (month < 10) { month = '0' + month; }
    var year = nextDay.getFullYear();



    $('#date-picker').datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#date-picker").datepicker( "setDate", year + '-' + month + '-' + day);

    $('#deliverydate').datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate").datepicker( "setDate", year + '-' + month + '-' + day);

    $scope.filterData.receiveDate = year+'-'+month+'-'+day;
    $scope.filterData.deliverydate = year+'-'+month+'-'+day;



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


    $scope.updateInvoiceNumber = function()
    {
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        }, fetchDataDelay);
    }

    $scope.autoPost = function(){
        var data = $scope.invoicepaid;

     $http({
            method: 'POST',
            url: query,
            data: {paid:data,paidinfo:$scope.filterData,mode:'posting'}
        }).success(function () {
            // $scope.updateDataSet();
         $scope.invoicepaidcount = 0;

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

        });

       // $scope.getPaymentInfo('autopost');
    }

    $scope.updateDataSet = function(){


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