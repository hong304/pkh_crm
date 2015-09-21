'use strict';

Metronic.unblockUI();

app.controller('selectShipControl', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {

   $scope.$on('$viewContentLoaded', function() {
   
    Metronic.initAjax();

   });
   
    $scope.supplier = 
    {
        supplierCode : '',
        supplierName : '',
        phone :'',
    }
    
    
    $scope.searchSupplier = function(supplier){
        var target = endpoint + '/jsonSearchSupplier.json';
        $http.post(target, {id : $scope.supplier.supplierCode})
            .success(function(res, status, headers, config){
                 console.log(res);
            });
    }
    
    
});