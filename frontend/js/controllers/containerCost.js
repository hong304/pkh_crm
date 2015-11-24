'use strict';

Metronic.unblockUI();



app.controller('containerCost', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
       
       $scope.containerCost = {
           containerId :'',
           receiveDate:'',
           container_size:'',
           sale_method:'',
           shippingId:'',
           totalCost:0,
       };
       
       $scope.$on('$viewContentLoaded', function() {
        // initialize core components
            Metronic.initAjax();
       });
       
       
       
       //$scope.costsName = ["運費","碼頭處理費","拖運費","卸貨費","外倉費","過期櫃租","過期交吉租","稅金","雜項","其他"];
       
       $scope.costsName = {'0':{'name':'運費'},'1':{'name':'碼頭處理費'},'2':{'name':'拖運費'},'3':{'name':'卸貨費'},'4':{'name':'外倉費'},'5':{'name':'過期櫃租'},'6':{'name':'過期交吉租'},'7':{'name':'稅金'},'8':{'name':'雜項'},'9':{'name':'其他'}};
      
        $scope.$on('costPassUpdate', function(){
            $scope.containerCost.containerId = SharedService.containerId;
            $scope.containerCost.receiveDate = SharedService.receiveDate;
            $scope.containerCost.container_size = SharedService.container_size;
            if(SharedService.sale_method == "1")
                $scope.containerCost.sale_method = "入倉";
            else if(SharedService.sale_method == "2")
                $scope.containerCost.sale_method = "貿易部";
            $scope.containerCost.shippingId = SharedService.shippingId;
        });

        $scope.costNum = function(v,i) {
            if(isNaN(v))
            {
                $scope.product[i]['costNum'] = 0;
            }
        };
        
        $scope.getTotal = function()
        {
            var total = 0;
        }

});