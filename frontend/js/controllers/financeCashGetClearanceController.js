'use strict';

function processCheque(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.processCheque(id);

    });
}

app.controller('financeCashGetClearanceController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state,$stateParams) {



    var intarget = endpoint + '/addCashCheque.json';
    var getClearance = endpoint + '/getCashClearance.json';

    $scope.invoice = [];
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
        'status'        :   '2',
        'bankCode' : '003',
        'deliverydate': '',
        'deliverydate2' : '',
        'ChequeNumber' : '',
        'groupName' : '',
        'remark' : ''

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


    $scope.$on('$viewContentLoaded', function() {

        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;

        $('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });

        $('.date-picker').datepicker( "setDate" , yyear + '-' + ymonth + '-' + yday );



    });


    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);

    $scope.$on('handleCustomerUpdate', function(){
        // received client selection broadcast. update to the invoice portlet

        $scope.filterData.customerId = SharedService.clientId;
        $scope.filterData.displayName = SharedService.clientId + " (" + SharedService.clientName + ")";

         Metronic.unblockUI();
    });


    $scope.processCustomer = function()
    {
        $http.post(getClearance, {mode:'processCustomer',filterData:$scope.filterData})
            .success(function(res, status, headers, config){

                if(res.error){
                    Metronic.alert({
                        container: '#financeCashGetClearance', // alerts parent container(by default placed after the page breadcrumbs)
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
                    $scope.invoicepaid = [];
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
                }
            });
    }

    $scope.showselectCustomer = function()
    {
        $("#selectclientmodel").modal({backdrop: 'static'});
    }




    $scope.autoPost = function(){

      //  console.log($scope.filterData);
      //  return false;

       // if(!$scope.filterData.discount){
       if($scope.filterData.paymentType == 'cash'){
           if($scope.totalAmount != $scope.filterData.cashAmount) {
                alert('需付金額不等於現金數目');
                return false;
        }
       }else if ($scope.filterData.paymentType == 'cheque'){
            if($scope.totalAmount != $scope.filterData.amount) {
                alert('需付金額不等於支票銀碼');
                return false;
            }
       }
        // }
        var data = $scope.invoicepaid;
        $http({
            method: 'POST',
            url: intarget,
            data: {paid:data,filterData: $scope.filterData}
        }).success(function (res) {
            if(res.length > 0)
                alert(res);
            else
                $location.url("/financeCashListing?action=success");
        });

    }



$scope.updatePaidTotal = function(){
    $scope.totalAmount = 0;
    console.log($scope.invoicepaid);
    $scope.invoicepaid.forEach(function(item){
            $scope.totalAmount += Number(item.settle);
    });
}

$scope.updateDiscount = function(){

    var i = 0;
    $scope.invoiceinfo.forEach(function(item){

        $scope.invoicepaid[i]['settle']= Number(item.realAmount*$scope.filterData.discount);
        $scope.invoicepaid[i]['discount'] = 1;
i++;
    });
    $scope.updatePaidTotal();
}

});