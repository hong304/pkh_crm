'use strict';

function clickShip(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    var scope = angular.element(document.getElementById("queryInfo")).scope();scope.$apply(function () {
    	scope.clickShip(id);
    });
}

function clickPo(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    var scope = angular.element(document.getElementById("queryInfo")).scope();scope.$apply(function () {
    	scope.clickPo(id);
    });
}

function chooseForm(formEle)
{
    $(".shipform").hide();
    $(".dateR").hide();
    $("#"+formEle).show();
    if(formEle == "shipmentSch")
    {
        $("#gg").show();
    }else
    {
        $("#hh").show();
    }
}



app.controller('ships', function ($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$sce) {


    $scope.firstload = true;
    $scope.autoreload = false;

    $scope.$on('$viewContentLoaded', function () {
        // initialize core components
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.viewShip();
        $scope.viewPo();
        $scope.viewShippingNote();
       // $scope.viewPoNote();
        
    });

    var queryPo = $scope.endpoint + "/queryPo.json";
    
 

    $scope.keyword = {
        supplier: '',
        poCode: '',
        poStatus: '',
        poDate: '',
        sorting: '',
        current_sorting: 'desc',
        endPodate:'',
        startPodate:'',
    };

    $scope.invoiceinfo = {
        poCode: '',
        poDate: '',
        etaDate: '',
        actualDate: '',
        receiveDate: '',
        remark: '',
        poStatus: '',
        poAmount: '',
        invoice_item: '',
        invoice: '',
    };
    
    $scope.filterData = {
        deliverydate5 :'',
        deliverydate6 :''
    };
    
      $("#deliverydate5,#deliverydate6").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    
    
    
    
   $scope.viewShip = function(){
         $http.post($scope.endpoint + "/outputPreview.json", {filterData: $scope.filterData 
         }).success(function (data) {
               // $scope.cols = Object.keys(data);
             // $scope.rows = data;	
            if(data !== "")
            {
                $scope.report = $sce.trustAsHtml(data);
            }
            else
            { 
                var dateObject = '';
                var start_date = '';
                if($scope.filterData.deliverydate5 !== '')
                {
                    dateObject = new Date($scope.filterData.deliverydate5);
                    start_date = new Date(dateObject.getTime() + 24 * 60 * 60 * 1000 * 14);   
                }else 
                {
                    dateObject = new Date();
                    var obj_date = new Date(dateObject.getTime() - 24 * 60 * 60 * 1000 * 7);
                    $scope.filterData.deliverydate5 = dateFormat(obj_date);
                    start_date = new Date(dateObject.getTime() + 24 * 60 * 60 * 1000 * 7);
                }
                $scope.report = $sce.trustAsHtml("<span style='font-size:20px;'>"+$scope.filterData.deliverydate5+" 至 "+ dateFormat(start_date) + "沒有船務紀錄</span>");
            }
          });
   }
   
   function dateFormat(date)
   {
        var month = '' + (date.getMonth() + 1),
        day = '' + date.getDate(),
        year = date.getFullYear();

        if(month.length < 2) month = "0" + month;
        if(day.length < 2) day = "0" +day;
        
        return year + "-" + month + "-" + day;
   }
   
    $scope.viewPo = function(){
         $http.post($scope.endpoint + "/outputPo.json", {filterData: $scope.filterData 
            }).success(function (data) {
                if(data !== "")
                {
                     $scope.report1 = $sce.trustAsHtml(data);
                }
                else
                {
                    var dateObject = '';
                    var start_date = '';
                    if($scope.filterData.deliverydate6 !== '')
                    {
                        dateObject = new Date($scope.filterData.deliverydate6);
                        start_date = new Date(dateObject.getTime() + 24 * 60 * 60 * 1000 * 14);
                        
                    }else 
                    {
                        dateObject = new Date();
                        var obj_date = new Date(dateObject.getTime() - 24 * 60 * 60 * 1000 * 7);
                        $scope.filterData.deliverydate6 = dateFormat(obj_date);
                        start_date = new Date(dateObject.getTime() + 24 * 60 * 60 * 1000 * 7);
                    }
                    
                    $scope.report1 = $sce.trustAsHtml("<span style='font-size:20px;'>"+$scope.filterData.deliverydate6+ " 至 " +dateFormat(start_date) +"沒有採購紀錄</span>");
                } 
            });              
   }
   
     $scope.viewShippingNote = function(){
           $http.post($scope.endpoint + "/outputShipNote.json").success(function (data) {
                if(data !== "")
                {
                     $scope.shippingNote = $sce.trustAsHtml(data);
                }
            });     
     }
     
     $scope.viewPoNote = function()
     {
         
     }
   
   	
   
   $scope.clickShip = function(id){
              $http.post(endpoint + '/loadShip.json', {id:id})
             .success(function(data, status, headers, config){
                 $scope.eachShip = data[0];
                 $scope.eachShip.container_numbers = $scope.eachShip.shippingitem.length;
              });
         $("#scheduleDetails").modal({backdrop: 'static'});
   }
   
   $scope.clickPo = function(id){
       $http.post(endpoint + '/loadPo.json', {id:id})
        .success(function(data, status, headers, config){
            $scope.eachPo = data[0];
        });
       $("#poDetails").modal({backdrop: 'static'});
   }

   



});