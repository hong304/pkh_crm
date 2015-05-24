'use strict';

app.controller('pushToPrintCtrl', function($scope, $http, SharedService, $timeout, $location, $interval) {
	
	var querytarget = endpoint + "/getAllPrintJobsWithinMyZone.json";
//	var pushtarget = endpoint + "/printAllPrintJobsWithinMyZone.json";
    var checkstatus = endpoint + "/getInvoiceStatusMatchPrint.json";
    var printSelect = endpoint + "/printSelectedJobsWithinMyZone.json";


    $scope.prints = {
        'id' : '',
        'collect' : ''
    }

    $scope.zone = '';
    $scope.shift = '1';
    $scope.checkid = [];

    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();
         /* var intervalPromise = $interval(function(){
                       $scope.updatePrintQueue();
                   }, 15000);*/

    });


    $scope.$on('$destroy', function () {
    	//$interval.cancel(intervalPromise);
    });

    $scope.printToday = function(){


        $http({
            method: 'POST',
            url: checkstatus,
            data: {mode:'today',zone:$scope.zone,shift:$scope.shift}
        }).success(function (res) {
            console.log(res);

            if(res.countInDataMart>0) {

                var  reject = res['3'].countInDataMart;

                var pending = res['1'].countInDataMart;

                bootbox.dialog({
                    message: reject+"張單被拒絕,處理完才可列印<br>"+pending+"張單等待批刻,處理完才可列印",
                    title: "Error!!!",
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
                        $http({
                            method: 'POST',
                            url: printSelect,
                            data: {print:$scope.checkid,mode:'selected',zone:$scope.zone,shift:$scope.shift}
                        }).success(function () {
                            $scope.updatePrintQueue();
                        });
                    }
                }
            }
        });



    }
    $scope.updateZone = function(){
        $scope.updatePrintQueue();
    }

    $scope.updateShift = function(){
        $scope.updatePrintQueue();
    }

    $scope.updatePrintQueue = function()
    {
        $http({
            method: 'POST',
            url: querytarget,
            data: {zone:$scope.zone,shift:$scope.shift}
        }).success(function(res){
    		$scope.queue = res['queued'];
                var i = 0;
                res['queued'].forEach(function(item) {
                    $scope.checkid[i] = $.extend(true, {}, $scope.prints);
                    $scope.checkid[i]['id'] = item.job_id;
                    $scope.checkid[i]['collect'] = 0;

                    i++;
                });


            $scope.printed = res['printed'];
            });
    }

    
});