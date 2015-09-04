'use strict';

app.controller('pushToPrintCtrl', function($scope, $http, SharedService, $timeout, $location, $interval) {
	
	var querytarget = endpoint + "/getAllPrintJobsWithinMyZone.json";
    var checkstatus = endpoint + "/getInvoiceStatusMatchPrint.json";
    var printSelect = endpoint + "/printSelectedJobsWithinMyZone.json";


    $scope.prints = {
        'collect' : ''
    }

    $scope.zone = '';
    $scope.group = '';
    $scope.shift = '1';
    $scope.checkid = [];

    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();
        $scope.updatePrintQueue();
         /* var intervalPromise = $interval(function(){
                       $scope.updatePrintQueue();
                   }, 15000);*/

    });


    $scope.$on('$destroy', function () {
    	//$interval.cancel(intervalPromise);
    });

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
        $scope.updatePrintQueue();
    }

 /*   $scope.updateGroup = function(){
        $scope.updatePrintQueue();
    }*/

    $scope.updateShift = function(){
        $scope.updatePrintQueue();
    }

    $scope.updatePrintQueue = function()
    {
        $http({
            method: 'POST',
            url: querytarget,
            data: {zone:$scope.zone,shift:$scope.shift,group:$scope.group}
        }).success(function(res){
    		$scope.queue = res['queued'];
            $scope.job9698 = res['queued9698'];
            Metronic.unblockUI('#printArea');
            });
    }

    
});