'use strict';

function viewInvoice(invoiceId,invoiceStatus)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewInvoice(invoiceId,invoiceStatus);
    });
}

function goEdit(invoiceId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.goEdit(invoiceId);
    });
}

app.controller('batchApproval', function($scope, $http, $rootScope,SharedService, $timeout, $location, $interval) {
	
	var querytarget = endpoint + "/getApprovalList.json";
    var checkstatus = endpoint + "/getInvoiceStatusMatchPrint.json";
    var printSelect = endpoint + "/printSelectedJobsWithinMyZone.json";



    $scope.systeminfo = $rootScope.systeminfo;

    $scope.zone = '';
    $scope.group = '';
    $scope.orderbatch = '1';
    $scope.checkid = {};

    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();

        if($location.search().date != '')
            $scope.orderbatch = $location.search().date;

        if($location.search().zone != '') {
            var zone = $location.search().zone;
           $scope.zoneId = parseInt(zone);
        }

        $scope.updateApprovalList();
         /* var intervalPromise = $interval(function(){
                       $scope.updatePrintQueue();
                   }, 15000);*/

    });

    $rootScope.$on('$locationChangeSuccess', function(){
        $scope.checkParm();
        $scope.updateApprovalList();

    });

    $scope.checkParm = function()
    {
        if($location.search().date != '')
            $scope.orderbatch = $location.search().date;

        if($location.search().zone != '')
            $scope.zoneId = $location.search().zone;
    }

    $scope.viewInvoice = function(invoiceId,invoiceStatus)
    {
        Metronic.blockUI();
        $http.post(endpoint + '/queryInvoice.json', {mode: "single", invoiceId: invoiceId,invoiceStatus:invoiceStatus})
            .success(function(res, status, headers, config){
                $scope.nowUnixTime = Math.round(+new Date()/1000);
                $scope.invoiceinfo = res;
                console.log($scope.invoiceinfo);



                $scope.invoiceinfo.invoiceStatus = parseInt($scope.invoiceinfo.invoiceStatus);
                if($scope.invoiceinfo.invoiceStatus == 1)
                    $scope.invoiceIdForApprove = invoiceId;
                Metronic.unblockUI();
                $("#productDetails").modal({backdrop: 'static'});

            });
    }

    $scope.batchApproval = function()
    {
        var approvalJson = $scope.endpoint + "/batchApproval.json";

        console.log($scope.checkid);
        $http.post(approvalJson, {
            target:	$scope.invoices,
            exception: $scope.checkid,
        }).success(function(data) {
            $scope.updateApprovalList();
        });
    }


    $scope.goEdit = function(invoiceId)
    {
        $location.url("/editOrder?invoiceId=" + invoiceId);
    }

    $scope.manipulate = function(action, invoiceId)
    {
        var approvalJson = $scope.endpoint + "/manipulateInvoiceStatus.json";

        $http.post(approvalJson, {
            action: "approval",
            status:	action,
            target:	invoiceId
        }).success(function(data) {
            $scope.updateApprovalList();
        });

        $("#productDetails").modal('toggle');

    }

    $scope.generalOtherInvoices = function(){

        bootbox.dialog({
            message: "列印發票後將不能復原，確定要列印發票嗎？",
            title: "列印發票",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定",
                    className: "red",
                    callback: function() {



                     /*  var queryObject = {
                            shift	:	$scope.shift,
                            mode	:	'96-98'
                        };
                        var queryString = $.param( queryObject );

                        window.open(endpoint + "/printSelectedJobsWithinMyZone.json?" + queryString); */



                     Metronic.blockUI({
                     target : '#printArea',
                     boxed: true,
                     message: '退貨單,補貨單資料整合中'
                     });

                     $http({
                            method: 'POST',
                            url: printSelect,
                            data: {mode:'96-98',zone:$scope.zone,shift:$scope.shift}
                        }).success(function(res, status, headers, config){
                            $scope.updatePrintQueue();
                        });
                    }
                }
            }
        });

    }

    $scope.printToday = function(){



        $http({
            method: 'POST',
            url: checkstatus,
            data: {zone:$scope.zone,shift:$scope.shift,group:$scope.group}
        }).success(function (res) {

            if(res.countInDataMart>0) {

                var reject = res['3'].countInDataMart;

                var pending = res['1'].countInDataMart;

                var version = res['0'].countInDataMart;

                bootbox.dialog({
                    message: reject+"張單被拒絕,處理完才可列印<br>"+pending+"張單等待批刻,處理完才可列印<br>"+version+"張單還沒產生備貨單,處理完才可列印",
                    title: "警告!!!",
                    buttons: {
                        success: {
                            label: "取消",
                            className: "green",
                            callback: function() {

                            }
                        }
                    }
                });
            }else{
                bootbox.dialog({
                    message: "列印發票後將不能復原，確定要列印發票嗎？",
                    title: "列印發票",
                    buttons: {
                        success: {
                            label: "取消",
                            className: "green",
                            callback: function() {

                            }
                        },
                        danger: {
                            label: "確定",
                            className: "red",
                            callback: function() {

                                if(!$scope.allowSubmission)
                                    return false;
                                $scope.allowSubmission= false;

                                Metronic.blockUI({
                                    target : '#printArea',
                                    boxed: true,
                                    message: '資料整合中,需時2分鐘...'
                                });

                                $http({
                                    method: 'POST',
                                    url: printSelect,
                                    data: {mode:'today',zone:$scope.zone,shift:$scope.shift}
                                }).success(function(res, status, headers, config){

                                            $scope.updatePrintQueue();
                                        // alert('正在準備傳送至印表機...')
                                    });
                            }
                        }
                    }
                });
            }
        });

    }

    $scope.printSelect = function(){

        if(!$scope.allowSubmission)
            return false;
        $scope.allowSubmission= false;

        bootbox.dialog({
            message: "列印發票後將不能復原，確定要列印發票嗎？",
            title: "列印發票",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定",
                    className: "red",
                    callback: function() {

                        Metronic.blockUI({
                            target : '#printArea',
                            boxed: true,
                            message: '資料整合中'
                        });

                        $http({
                            method: 'POST',
                            url: printSelect,
                            data: {print:$scope.checkid,mode:'selected',zone:$scope.zone,shift:$scope.shift}
                        }).success(function (res) {
                                    $scope.checkid = [];
                                    $scope.updatePrintQueue();
                        });

                    }
                }
            }
        });



    }
/*
    $scope.printGroup = function(){

        bootbox.dialog({
            message: "列印發票後將不能復原，確定要列印發票嗎？",
            title: "列印發票",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定",
                    className: "red",
                    callback: function() {


                        Metronic.blockUI({
                            target : '#printArea',
                            boxed: true,
                            message: '資料整合中'
                        });

                        $http({
                            method: 'POST',
                            url: printSelect,
                            data: {mode:'group',group:$scope.group}
                        }).success(function (res) {
                            $scope.updatePrintQueue();
                        });

                    }
                }
            }
        });



    }*/

    $scope.updateZone = function(){
        $scope.zoneId = $scope.zone.zoneId;
        $scope.updateApprovalList();
    }

 /*   $scope.updateGroup = function(){
        $scope.updatePrintQueue();
    }*/

    $scope.updateShift = function(){
        $scope.updateApprovalList();
    }

    $scope.updateOrder = function(){
        $scope.updateApprovalList();
    }

    $scope.updateApprovalList = function()
    {
        $http({
            method: 'POST',
            url: querytarget,
            data: {zoneId:$scope.zoneId,orderbatch:$scope.orderbatch}
        }).success(function(res){
    		$scope.invoices = res;
            console.log($scope.invoices);
            });
    }

    
});