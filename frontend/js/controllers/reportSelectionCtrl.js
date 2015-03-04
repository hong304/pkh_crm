'use strict';

app.controller('reportSelectionCtrl', function($scope, $http, SharedService, $timeout, $location) {
	
    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();
        
        var querytarget = endpoint + "/getAvailableReportsType.json";
        
        $http.post(querytarget, {})
    	.success(function(res, status, headers, config){    
    		$scope.reports = res;
    		
    	});
    });
    
    $scope.viewReport = function(id)
    {
    	$location.url('/reportFactory?id=' + id);
    }
    
    
});