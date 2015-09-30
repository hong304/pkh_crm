'use strict';

Metronic.unblockUI();



app.controller('repack', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
    /* Register shortcut key */
    
    
    $scope.repack = {
        productId:'',
        productName:'',
    };
    
    $scope.selfdefine = [];
    $scope.selfdefineS = {
        'productId' : '',
        'name' : '',
        'qty' : '',
        'unit'  : '',
        'productlevel' : ''
    }
    
    
        $scope.$on('handleReUpdate', function(){
            $scope.repack.productId = SharedService.productId;
            $scope.repack.productName = SharedService.productName;
        });
        
        $scope.totalline = 0;

    $scope.addRows = function(){
        var j = $scope.totalline;
            $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
            $scope.selfdefine[j]['productId'] = '';
            $scope.selfdefine[j]['productName'] = '';
            $scope.selfdefine[j]['qty'] = '';
            $scope.selfdefine[j]['unit'] = '';
        $scope.totalline += 1;
    }
        

    $scope.submitRepack = function(){
        console.log( $scope.selfdefine);

    }



});