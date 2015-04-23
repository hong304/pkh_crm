'use strict';

app.controller('financeCashController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state,$stateParams) {

    var query = endpoint + '/querryCashCustomer.json';



    $scope.invoice = [];
    var today = new Date();
    var day = today.getDate();
    var month = today.getMonth() + 1;
    var year = today.getFullYear();

    $scope.payment = [];
    $scope.discount = [];
    $scope.invoicepaid = [];
    $scope.invoiceStructure = {
        'id' :'',
        'paid' : '',
        'collect': ''
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


    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;


             $scope.updateDataSet();




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
                var i = 0;
                res.forEach(function(item) {
                    $scope.invoicepaid[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.invoicepaid[i]['id'] = item.invoiceId;
                    $scope.invoicepaid[i]['paid'] = 0;
                    $scope.invoicepaid[i]['collect'] = 0;
                    $scope.invoicepaid.amount = $scope.invoicepaid.amount + Number(item.amount);
                    $scope.invoicepaid.settle = $scope.invoicepaid.amount;
                    i++;
                });
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
        $scope.filterData.displayName = "";
        $scope.filterData.clientId = "";
        $scope.updateDataSet();
    }




});