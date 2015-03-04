'use strict';

app.controller('DashboardController', function($rootScope, $scope, $http, $timeout, $location) {
    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();
    });
    
    

    $http.get($scope.endpoint + '/dashboard.json').success(function(data) {
        $scope.highFrequencyClient = data.client;
        $scope.promotionProducts = data.products;
        $scope.availableZones = data.zones;
        Metronic.unblockUI();
    });
    
    $scope.createCustomerInvoice = function(customerId)
    {
    	//console.log(customerId);
    	$location.path('/newOrder').search({clientId: customerId});
    }
    
    
    // set sidebar closed and body solid layout mode
    $rootScope.settings.layout.pageSidebarClosed = false;
});