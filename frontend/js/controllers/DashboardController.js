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

        $http.get($scope.endpoint + '/getOweInvoices.json')
            .success(function(res){
                $scope.debt = res;
            });

    });
    
    

    $http.get($scope.endpoint + '/dashboard.json').success(function(data) {
        $scope.highFrequencyClient = data.client;
        $scope.promotionProducts = data.products;
        $scope.availableZones = data.zones;
        $scope.currentZone = data.current_zone;
        Metronic.unblockUI();
    });
    
    $scope.createCustomerInvoice = function(customerId)
    {
   $location.path('/newOrder///').search({clientId: customerId});
     //   $location.path('/newOrder///?clientId='+customerId);
    }

    $scope.generalPickingStatus = function(){

        var target = endpoint + '/getHoliday.json';

        $http.get(target)
            .success(function(res){

                var today = new Date();
                var plus = today.getDay() == 6 ? 2 : 1;

                var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
                if(today.getHours() > 11 || today.getDay() == 0)
                {
                    var nextDay = currentDate;
                }
                else
                {
                    var nextDay = today;
                }

                var flag = true;
                var working_date = ("0" + (nextDay.getMonth() + 1)).slice(-2)+'-'+("0" + (nextDay.getDate())).slice(-2);
                do{
                    flag= true;
                    $.each( res, function( key, value ) {
                        if(value == working_date){
                            flag = false;
                            var today = new Date(nextDay.getFullYear()+'-'+working_date);
                            nextDay = new Date(today);
                            nextDay.setDate(today.getDate()+1);

                            if(nextDay.getDay() == 0)
                                nextDay.setDate(today.getDate()+2);

                            working_date = ("0" + (nextDay.getMonth() + 1)).slice(-2)+'-'+("0" + (nextDay.getDate())).slice(-2);
                        }
                    });
                }while(flag == false);

                var day = ("0" + (nextDay.getDate())).slice(-2);
                var month = ("0" + (nextDay.getMonth() + 1)).slice(-2);
                var year = nextDay.getFullYear();

                $('.date-picker').datepicker({
                    rtl: Metronic.isRTL(),
                    orientation: "left",
                    autoclose: true
                });

                $('.date-picker').datepicker( "setDate" , year + '-' + month + '-' + day );

                $("#generalPickingModal").modal({backdrop: 'static'});
            });


    }

    $scope.updateZone = function(){

      $http.post(iutarget, {info: $scope.picking, mode: 'get'})
            .success(function(res, status, headers, config){
                    $scope.version = res;
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

        $scope.filterData = {
            'deliveryDate' : '',
            'zone' : {
                'value' : '',
                'label' : ''
            },
            'shift' : {
                'value' : ''
            }
        }
        $scope.filterData.deliveryDate = $scope.picking.date;
        $scope.filterData.zone.value = $scope.picking.zone.zoneId;
        $scope.filterData.zone.label = $scope.picking.zone.zoneName;
        $scope.filterData.shift.value = $scope.picking.shift;

      /*  $http.post(iutarget, {info: $scope.version, mode : 'check'})
            .success(function(res, status, headers, config){
                    if(parseInt(res) == 0){
                        alert('產生備貨單前請列印之前的版本');
                    }else{*/
                        $("#generalPickingModal").modal('hide');
                        $http.post(iutarget, {info: $scope.picking, mode : 'post'})
                            .success(function(res, status, headers, config){
                                $scope.picking_gen = true;
                                if(res == 'true'){
                                    var queryObject = {
                                        filterData	:	$scope.filterData,
                                        reportId	:	'pickinglist9f',
                                        output		:	'pdf'
                                    };
                                    var queryString = $.param( queryObject );
                                    window.open(endpoint + "/getReport.json?" + queryString);
                                }
                            });
                   // }
          //  });


    }

    
    // set sidebar closed and body solid layout mode
    $rootScope.settings.layout.pageSidebarClosed = false;
});

