'use strict';

app.controller('reportSelectionCtrl', function($scope, $http, SharedService, $timeout, $location) {
	
    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();
        
        var querytarget = endpoint + "/getAvailableReportsType.json";
        
        $http.get(querytarget)
    	.success(function(res, status, headers, config){

    		$scope.reports = res;

            });
    });
    
    $scope.viewReport = function(id)
    {

        console.log(id);

        if(id == 'vanselllist'){
            $location.url('/reportvansell');
        }else if(id == 'commission'){
            $location.url('/reportvansell');
        }else
            $location.url('/reportFactory?id=' + id);

    }
    
    
});