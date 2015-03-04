'use strict';


app.controller('updateStatusViaReportId', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	

    $scope.$on('$viewContentLoaded', function() {   
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;   
        
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;  	
  		$scope.statusText = $scope.systeminfo.invoiceStatus[$location.search().status];
  	}, true);
    
    
    
});