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
        'clientId'		:	'0',
        'status'		:	'0',
        'zone'			:	'',
        'deliverydate'	:	'last day',
        'created_by'	:	'0',
        'invoiceNumber' :	'',
    };


    var today = new Date();
    var nextDay = today;

    var day = nextDay.getDate();
    var month = nextDay.getMonth() + 1;
    if (month < 10) { month = '0' + month; }
    var year = nextDay.getFullYear();




    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;

           //  $scope.updateDataSet();
    });




    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
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
            data: {paid:data,mode:'posting'}
        }).success(function () {
             $scope.updateDataSet();
        });

       // $scope.getPaymentInfo('autopost');
    }

    $scope.updateDataSet = function(){
        $http.post(query, {mode:'collection', filterData: $scope.filterData})
            .success(function(res, status, headers, config){


                $scope.invoiceinfo = res;
                $scope.invoicepaid.amount = 0;
                $scope.invoicepaid.settle = 0;
                $scope.invoicepaid = [];
                var i = 0;
                res.forEach(function(item) {
                    $scope.invoicepaid[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.invoicepaid[i]['id'] = item.invoiceId;
                    $scope.invoicepaid[i]['paid'] = 0;
                    $scope.invoicepaid[i]['date'] = year + '-' + month + '-' + day;
                    $scope.invoicepaid[i]['collect'] = 0;
                    $scope.invoicepaid.amount = $scope.invoicepaid.amount + Number(item.amount);
                    $scope.invoicepaid.settle = $scope.invoicepaid.amount;
                    i++;
                });
                console.log($scope.invoicepaid);
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
            'clientId'		:	'0',
            'status'		:	'0',
            'zone'			:	'',
            'deliverydate'	:	'last day',
            'created_by'	:	'0',
            'invoiceNumber' :	'',
        };
        $scope.updateDataSet();
    }




});