'use strict';

app.controller('DashboardController', function($rootScope, $scope, $http, $timeout, $location) {

    $scope.picking = {
        'date'	:	'',
        'zone'	:	'',
        'shift' : '1'
    };

    var iutarget = $scope.endpoint + '/generalPickingStatus.json';

    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();

    });
    
    

    $http.get($scope.endpoint + '/dashboard.json').success(function(data) {
        $scope.highFrequencyClient = data.client;
        $scope.promotionProducts = data.products;
        $scope.availableZones = data.zones;
        $scope.currentZone = data.current_zone;
        Metronic.unblockUI();
        console.log($scope.highFrequencyClient);
    });
    
    $scope.createCustomerInvoice = function(customerId)
    {
    	console.log(customerId);
   $location.path('/newOrder///').search({clientId: customerId});
     //   $location.path('/newOrder///?clientId='+customerId);
    }

    $scope.generalPickingStatus = function(){

        var today = new Date();
        var plus = today.getDay() == 6 ? 2 : 1;

        var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
        if(today.getHours() < 12)
        {
            var nextDay = today;
        }
        else
        {
            var nextDay = currentDate;
        }
        var day = nextDay.getDate();
        var month = nextDay.getMonth() + 1;
        var year = nextDay.getFullYear();

        $('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });

        $('.date-picker').datepicker( "setDate" , year + '-' + month + '-' + day );

        $("#generalPickingModal").modal({backdrop: 'static'});


    }

    $scope.updateZone = function(){

      $http.post(iutarget, {info: $scope.picking, mode: 'get'})
            .success(function(res, status, headers, config){
                    $scope.version = res;
                    console.log($scope.version);
            });

        document.getElementById('shift').style.display='block';
        document.getElementById('datepicker').style.display='block';

    }

    $scope.updateDate = function(){
        $http.post(iutarget, {info: $scope.picking, mode: 'get'})
            .success(function(res, status, headers, config){
                $scope.version = res;
            });
    }

    $scope.updateShift = function(){
        $http.post(iutarget, {info: $scope.picking, mode: 'get'})
            .success(function(res, status, headers, config){
                $scope.version = res;
            });
    }

    $scope.submitStaffForm = function(){



        $http.post(iutarget, {info: $scope.version, mode : 'check'})
            .success(function(res, status, headers, config){
                    if(parseInt(res) == 0){
                        alert('產生備貨單前請列印之前的版本');
                    }else{
                        $("#generalPickingModal").modal('hide');
                        $http.post(iutarget, {info: $scope.picking, mode : 'post'})
                            .success(function(res, status, headers, config){
                                $scope.version = res;
                                $scope.picking = true;
                            });
                    }
            });


    }

    
    // set sidebar closed and body solid layout mode
    $rootScope.settings.layout.pageSidebarClosed = false;
});