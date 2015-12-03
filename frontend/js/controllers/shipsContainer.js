'use strict';

app.controller('shipsContainer', function ($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$sce) {
    
     $scope.$on('$viewContentLoaded', function () {
        // initialize core components
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.shippingContainerReport();

    });
    
    $scope.filterData = {
        shippingDeliverDate: '',
    };
    
    $("#shippingDeliverDate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    
    $scope.shippingContainerReport = function(){
         $http.post($scope.endpoint + "/outputShipContainer.json",{filters: $scope.filterData.shippingDeliverDate
            }).success(function (data) {
                //if(data !== "")
               // {
                     $scope.shippingContainerReport = $sce.trustAsHtml(data);
              //  }else
              //  {
                  //  $scope.shippingContainerReport = $sce.trustAsHtml(data);
              //  }
            });     
    }
    
    

});

