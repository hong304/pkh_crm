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
        phone_1 :'',
        status : '',
        countryName:'',
    }
    
    $scope.filterAll = 
    {
        supplierCode : '',
        supplierName:'',
        phoneNum :'',     
    }
    
    
    
    $scope.searchSupplier = function(supplier){
        var target = endpoint + '/jsonSearchSupplier.json';
        $scope.filterAll.supplierCode = supplier;
        $http.post(target, {filterAll : $scope.filterAll})
            .success(function(res, status, headers, config){
                    $scope.pullAll =  res;
            });
    }
    
    $scope.selectSupplier = function(supplier){
          var target = endpoint + '/jsonSearchPo.json';
         $scope.supplier.supplierCode = supplier.supplierCode;
         $scope.supplier.supplierName = supplier.supplierName;
         SharedService.setValue('supplierCode', supplier.supplierCode, 'handleShipPassUpdate');
         SharedService.setValue('supplierName', supplier.supplierName, 'handleShipPassUpdate');
          $http.post(target, {supplierCode : $scope.supplier.supplierCode})
            .success(function(res, status, headers, config){
               $scope.purchaseorder = res;
            });
         
    }
    
    $scope.selectPo = function(po){
        SharedService.setValue('poCode', po.poCode, 'handleShipPassUpdate');
        $('#selectShipModel').hide();
    }
    
    
});