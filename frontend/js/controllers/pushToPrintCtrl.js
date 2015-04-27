'use strict';

app.controller('pushToPrintCtrl', function($scope, $http, SharedService, $timeout, $location, $interval) {
	
	var querytarget = endpoint + "/getAllPrintJobsWithinMyZone.json";
	var pushtarget = endpoint + "/printAllPrintJobsWithinMyZone.json";
	
    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();
         /* var intervalPromise = $interval(function(){
                       $scope.updatePrintQueue();
                   }, 15000);*/

    });


    $scope.$on('$destroy', function () {
    	$interval.cancel(intervalPromise);
    });
    
    $scope.updatePrintQueue = function()
    {    	
    	$http.get(querytarget)
    	.success(function(res){
    		$scope.queue = res;    		
    	});
    }
    
    $scope.pushPrintQueue = function()
    {
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
                        $http.get(pushtarget)
                            .success(function(res, status, headers, config){
                               $scope.updatePrintQueue();
                                //alert('正在準備傳送至印表機...')
                            });
                    }
                }
            }
        });

    }
    
    
});