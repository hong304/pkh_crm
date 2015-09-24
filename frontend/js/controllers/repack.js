'use strict';

Metronic.unblockUI();



app.controller('repack', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
    /* Register shortcut key */
    
    
    $scope.repack = {
        productId:'',
        productName:'',
    };
    
        $scope.$on('handleReUpdate', function(){
            $scope.repack.productId = SharedService.productId;
            $scope.repack.productName = SharedService.productName;
        });
        


});