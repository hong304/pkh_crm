'use strict';

app.controller('pickingListCtrl', function($scope, $http, SharedService, $location, $timeout) {
	
	var today = new Date();	
	var plus = today.getDay() == 6 ? 2 : 1; 
	
	var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
	if(today.getHours() < 12)
	{
		var nextDay = today;
	}
	else
	{
		var nextDay = currentDate;
	}
	var day = nextDay.getDate();
	var month = nextDay.getMonth() + 1;
	var year = nextDay.getFullYear();
	
	$scope.firstload = true;
	$scope.deliveryDate = year + '-' + month + '-' + day;
	
    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();
        
        $('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true,
            
        });
        
    });
    
    $scope.updateData = function()
    {
    	$scope.getData();    	
    }
    
    $scope.getData = function() {

    	var endpoint = $scope.endpoint + "/generatePickingList.json";
    	 
    	var parm = {
    		date	:	$scope.deliveryDate,
    		zone	:	$scope.selected_zone,
    	};
    	
    	
    	$http.post(endpoint, parm)
    	.success(function(data){
    		$scope.data = data;
    		$scope.availablezone = data.availableZone;
    		console.log(data);   
    		
    		if($scope.firstload)
			{
    			$timeout(function(){
    				SharedService.initTable();
    			}, 500);
    			
    			$scope.firstload = false;
			}
    		else
    		{
    			$('#datatable').dataTable().fnDestroy(); 
    		}
    	});
    }
    
    $scope.showBreakdown = function(productId)
    {
    	$scope.preview = $scope.data.firstF[productId];
    	$("#pickingListBreakdown").modal('toggle');
    }
    
});