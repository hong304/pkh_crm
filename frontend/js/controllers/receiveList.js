'use strict';

Metronic.unblockUI();



app.controller('receiveList', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
    /* Register shortcut key */
    
    
    $scope.receive = {
        receivingId:'',
        receiveDate:'',
        poCode: '',
        supplierName:'',
        countryName:'',
        currencyId:'',
        status:'',
        poRemark:'',
        poStatus:'1',
        supplierCode:'',
        feight_cost:0,
        feight_local_cost:0,
        local_cost:0,
        total_cost:0,
        exchangeRate:0,
        shippingId:'',
        containerId:'',
        currencyName:'',
        hk_local_cost:0,
        shippingdbid:'',
        location:'',
    };
    
    $scope.filterData  = {
        startReceiveDate:'',
        endReceiveDate:'',
        location:'',
        supplierCode:'',
        productId:''
    };
 

 //Sunday is not allowed
    var today = new Date();
    var start_date = new Date(new Date().getTime());
    //- 24 * 60 * 60 * 1000

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();
    

    $("#deliverydate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    
    $("#deliverydate").datepicker("setDate", yyear + '-' + ymonth + '-' + yday);

        
        $scope.checkProduct = function(ele)
        {
              var target = endpoint + '/getAllProducts.json';
              $http.post(target, {productId:ele}).success(function(res) {
                  if(typeof res == "object")
                  {
                     for (ele in res) {
                        $scope.filterData.productName = res[ele].productName_chi;
                     }
                     
                     SharedService.setValue('productId', $scope.filterData.productId, 'handleReUpdate');
                     SharedService.setValue('productName', $scope.filterData.productName, 'handleReUpdate');
                     $("#selectR").attr('disabled',false);
                  }else 
                  {
                      $("#selectR").attr('disabled',true);
                      $scope.filterData.productName = "";
                  }
                      
              });
        }
        

        


});