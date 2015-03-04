'use strict';


app.controller('dummyCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	

    $scope.$on('$viewContentLoaded', function() {   
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;        
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;  		
  	}, true);
    
    
    
});