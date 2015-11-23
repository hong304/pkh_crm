'use strict';

Metronic.unblockUI();



app.controller('receiveCtrl', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
    /* Register shortcut key */
    
    
    $scope.orders = {
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
    
    
    $scope.orders.receiveDate = yyear + '-' + ymonth + '-' + yday;
     
    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);

    $scope.sameDayInvoice = '';
    $scope.productCode = [];
    $scope.itemlist = [1, 2, 3];
    //$scope.retrievedProduct = [];
    $scope.product = [];
    $scope.displayName = "";
    $scope.totalAmount = 0;
    $scope.allowSubmission = true;
    $scope.recentProduct = [];
    $scope.editable_row = "";
    $scope.lastinvoice = [];
    $scope.lastitem = [];
    $scope.poCodeAfter = "";

    $scope.productStructure = {
        dbid            :       '',
        productId       :	'',
        productName     :''              ,
        qty             :       '',
        unit		:	'',
        receive_qty	:	0,
        good_qty	:	0,
        damage_qty	:	0,
        on_hold_qty     :       0,
        expiryDate	:	'',
        bin_location		:	'',
        remark		:	'',
        deleted 	: 	'0',    // If comment this row ,all rows will disappear 
        unit_cost:0,
    };
    
    $scope.productTimerStructure = {
        openPanel	:	'',
        closePanel	:	'',
        completedRow:	'',
    }
    

    $scope.submitButtonText = '提交 (F10)';
    $scope.submitButtonColor = 'blue';
    $scope.countdown = "1";
    $scope.timer = {
        start		:	Date.now(),
        selected_client	:	'',
        product		:	[],
        submit		:	'',
    }

    // product: select, change_qty, change_unit

$scope.an = false;

    $scope.$on('$locationChangeStart', function( event ) {
        if($scope.an){
           var answer = confirm("訂單還沒提交，確定要離開此頁？")
            if (!answer) {
               event.preventDefault();
            }
        }
    });
      $scope.$on('handleShippingUpdate', function(){
            $scope.countryDataList = SharedService.allCountry;
            $scope.allCurrencyList = SharedService.allCurrency;
            $scope.orders.supplierCode = SharedService.supplierCode;
            $scope.orders.currencyId = SharedService.currencyId;
            $scope.orders.countryId = SharedService.countryId;
            $scope.orders.supplierName = SharedService.supplierName;
            $scope.orders.shippingId = SharedService.shippingId;
            $scope.orders.containerId = SharedService.containerId;
            $scope.orders.poCode = SharedService.poCode;
            $scope.orders.countryName = SharedService.countryName;
            $scope.orders.currencyName = SharedService.currencyName;
            $scope.orders.shippingdbid = SharedService.shippingdbid;
            $scope.orders.location = SharedService.location;
            $scope.items = SharedService.items;
 
            if($scope.countryDataList !== undefined)
            {
                 for(var t = 0;t<$scope.countryDataList.length;t++)
                {
                    if($scope.countryDataList[t].countryName == $scope.orders.countryName)
                    {
                        $scope.countryDatas = $scope.countryDataList[t];
                    }
                }
            }
            
            if($scope.allCurrencyList !== undefined)
            {
                 for(var t = 0;t<$scope.allCurrencyList.length;t++)
                 {
                    if($scope.allCurrencyList[t].currencyName == $scope.orders.currencyName)
                    {
                        $scope.currencyDatas = $scope.allCurrencyList[t];
                    }
             }
            }
            
             
            if(typeof $scope.items != 'undefined')
            {
                $scope.product[i] = $.extend(true, {}, $scope.productStructure);
                var i = 1;
                $scope.items.forEach(function (item) {
                    
                    $scope.product[i].productId = item.productId;
                    $scope.product[i].productName = item.product_detail.productName_chi;
                    $scope.product[i].qty = item.productQty;
                    $scope.product[i]['good_qty'] = item.productQty;
                  //  $scope.product[i].unit = item.productQty;
                    addUnit(item,i);
                  //  
                 
                  
                  i++;
               });
            }
          
           
     
     
        });
        
        function addUnit(item,i)
        {
            var availableunit = [];
            var storeUnit = [];
             if(item.product_detail.supplierPackingInterval_carton > 0)
                  {
                      availableunit = availableunit.concat([{value: 'carton', label: item.product_detail.productPackingName_carton}]);
                      storeUnit[0] = 'carton';
                  }else if(item.product_detail.supplierPackingInterval_inner > 0)
                  {
                       availableunit = availableunit.concat([{value: 'inner', label: item.product_detail.productPackingName_inner}]);
                       storeUnit[1] = 'inner';
                  }else if(item.product_detail.supplierPackingInterval_unit > 0)
                  {
                       availableunit = availableunit.concat([{value: 'unit', label: item.product_detail.productPackingName_unit}]);
                       storeUnit[2] = 'unit';
                  }
   
                  $scope.product[i].availableunit = availableunit;
                  var indexNum = storeUnit.indexOf(item.productQtyUnit);
                  $scope.product[i]['unit'] = availableunit[indexNum];
        }



    $scope.getLastItem = function(productId,clientId,i,q){

        var target = endpoint + '/getLastItem.json';
        $http.post(target, {productId: productId, customerId: clientId})
            .success(function (res, status, headers, config) {
                $scope.lastitem = res;
            
                if(res.productQty > 0){
                    $scope.product[i].unitprice = res.productPrice;
                    var pos = $scope.product[i]['availableunit'].map(function(e) {
                        
                        return e.value;
                    }).indexOf(res.productQtyUnit);
                    $scope.product[i]['unit'] = $scope.product[i]['availableunit'][pos];
                    $scope.checkPrice(i);
                }
                if(q==0 && $("#productCode_"+i).val() != productId){
                        $scope.getLastItem($("#productCode_"+i).val(),$scope.orders.clientId,i,1);
                       // $scope.updateStandardPrice(i);
                    if (typeof res[0] == 'undefined'){
                        $scope.updateStandardPrice(i);
                    }
                }
            });

    }
    
    $scope.updateCost = function()
    {
        $scope.orders.feight_local_cost = $scope.orders.feight_cost * $scope.orders.exchangeRate;
    }
    
    $scope.updateTotal = function()
    {
        $scope.orders.total_cost = parseInt($scope.orders.feight_local_cost) + parseInt($scope.orders.local_cost) + parseInt($scope.orders.hk_local_cost);
    }
    
    
    $scope.updateDiscount = function()
    {

        $scope.reCalculateTotalAmount();
    }

    $scope.itemlist.forEach(function(key){
        $scope.product[key] = $.extend(true, {}, $scope.productStructure);
        $scope.timer.product[key] = $.extend(true, {}, $scope.timerProductStructure);
    });


    $scope.$on('$viewContentLoaded', function() {
        
    Metronic.initAjax();
         if($scope.instatusmsg !== undefined && $stateParams.invoiceNumber !==undefined)
         {
              Metronic.alert({
                container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                place: 'prepend', // append or prepent in container
                type: 'success',  // alert's type
                message: '<span style="font-size:16px;">'+$scope.instatusmsg+' 編號: <strong>' + $stateParams.invoiceNumber + '</strong></span>',  // alert's message
                close: true, // make alert closable
                reset: true, // close all previouse alerts first
                focus: true, // auto scroll to the alert after shown
                closeInSeconds: 0, // auto close after defined seconds
                icon: '' // put icon before the message
            });
         }
        if(!$location.search().receivingId)
        {
            $timeout(function(){
               // $('#selectShipModel').modal('show');

              //  $('#selectShipModel').on('shown.bs.modal', function () {
               //     $('#keyword').focus();
              //  })
              
              $("#selectShipModel").modal({backdrop: 'static'});

                //$('#selectclientmodel').modal({backdrop: 'static'});
            }, 1000);
            
      
        }
      else if($location.search().receivingId !="undefined")
        {

        }
        else 
        {
            $('#selectclientmodel').modal('show');
            $('#selectclientmodel').on('shown.bs.modal', function () {
                $('#keyword').focus();
            })
            $scope.loadProduct($location.search().poCode);

        
        }

 

    });



    $scope.$on('updateProductSelected', function(){

        $scope.timer.product[$scope.currentSelectProductRow]['closePanel'] = Date.now();
        $scope.selectedProduct = SharedService.selectedProductId;

        $scope.searchProduct($scope.currentSelectProductRow, $scope.selectedProduct);



        $("#selectProduct").modal('hide');



        $('#selectProduct').on('hidden.bs.modal', function () {
            $("#qty_" + $scope.currentSelectProductRow).focus().select();
        })
    });
   
 
   


    $scope.preSubmitOrder = function(v){

    /*    bootbox.dialog({
            message: '是否確定輸入無誤?',
            title: "提交訂單",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定",
                    className: "red",
                    callback: function() {*/
                        $scope.submitOrder(v);
                  /*  }
                }
            }
        });*/
    }
    
    function alertBox()
    {
         Metronic.alert({
                container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                place: 'prepend', // append or prepent in container
                type: 'danger',  // alert's type
                message: '請輸入所有欄位',  // alert's message
                close: true, // make alert closable
                reset: true, // close all previouse alerts first
                focus: true, // auto scroll to the alert after shown
                closeInSeconds: 0, // auto close after defined seconds
                icon: 'warning' // put icon before the message
            });
    }
    



    $scope.submitOrder = function(v)
    {
        var generalError = false;
      
        if($scope.orders.location == '2')
        {
             if(!$scope.orders.poCode || !$scope.orders.supplierCode||!$scope.orders.shippingId||!$scope.orders.containerId)
             {
                alertBox();
             }else
             {
                generalError = true;
             }
        }else if(!$scope.orders.poCode || !$scope.orders.supplierCode)
        {
            alertBox();
        }else
        {
            generalError = false;
        }

        if(!checkSumCal($scope.product))
        {
             Metronic.alert({
                container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                place: 'prepend', // append or prepent in container
                type: 'danger',  // alert's type
                message: '好貨,壞貨,保留貨大於原本貨品數量',  // alert's message
                close: true, // make alert closable
                reset: true, // close all previouse alerts first
                focus: true, // auto scroll to the alert after shown
                closeInSeconds: 0, // auto close after defined seconds
               // icon: 'warning' // put icon before the message
            });
        }else
        {
            $http.post(
                endpoint + '/newReceive.json', {
                    product : $scope.product,
                    order : $scope.orders,
                    shippingdbid : $scope.orders.shippingdbid,
                    location : $scope.orders.location,
              //  timer	:	$scope.timer,
                }).
                success(function(res, status, headers, config) {
                   $scope.receiveId = res.receiveid;
                    if(res.result == true)
                    {
                       if(res.action == 'create')
             
                           //  $('#selectclientmodel').modal({backdrop: 'static'});
                              Metronic.alert({
                container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                place: 'prepend', // append or prepent in container
                //type: 'danger',  // alert's type
                message: '<span style="font-size:20px;">收貨編號:'+ $scope.receiveId + '</span>',  // alert's message
                close: true, // make alert closable
                reset: true, // close all previouse alerts first
                focus: true, // auto scroll to the alert after shown
                closeInSeconds: 0, // auto close after defined seconds
               // icon: 'warning' // put icon before the message
            });
             
                        }
                    
                    else if(res.result == false)
                    {
                        Metronic.alert({
                            container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                            place: 'prepend', // append or prepent in container
                            type: 'danger',  // alert's type
                            message: '<span style="font-size:16px;">' + res.message + '</span>',  // alert's message
                            close: true, // make alert closable
                            reset: true, // close all previouse alerts first
                            focus: true, // auto scroll to the alert after shown
                            closeInSeconds: 0, // auto close after defined seconds
                            icon: 'warning' // put icon before the message
                        });
                    }

                }).
                error(function(res, status, headers, config) {
                    // called asynchronously if an error occurs
                    // or server returns response with an error status.
                    $scope.allowSubmission = true;

                });
            }
        
        

    }
    
    function checkSumCal(productObject)
    {
        var flag = true;
        for(var k = 1;k<=productObject.length -1;k++)
        {
            if(typeof $scope.product[k]['good_qty'] != 'undefined' && typeof $scope.product[k]['damage_qty'] != 'undefined' && typeof $scope.product[k]['on_hold_qty'] != 'undefined' && typeof $scope.product[k]['qty'] != 'undefined')
            {
                var sum = 0;
                sum = parseInt($scope.product[k]['good_qty']) +  parseInt($scope.product[k]['damage_qty']) +  parseInt($scope.product[k]['on_hold_qty']);
                if(sum > parseInt($scope.product[k]['qty']))
                    flag = false;
            }
        }
        return flag;
    }

    
    $scope.checkProduct = function(h,value)
    {
        if($scope.items !== undefined && $scope.items != "")
        {
            for(var i = 0;i < $scope.items.length;i++)
            {
                if(value != $scope.items[i].productId)
                {
                    $("#warning"+h).text("沒有訂這貨物");
                    $scope.product[h]['productName'] = "";
                    $scope.product[h]['qty'] = "";
                    $scope.product[h]['unit'] = "";
                    $scope.product[h]['unit_cost'] = "";
                   // $scope.product[h]['availableunit'] = 
                }
                else if(value == $scope.items[i].productId)
                {
                      $("#warning"+h).html("");
                      $scope.product[h]['productName'] = $scope.items[i].product_detail.productName_chi;
                      $scope.product[h]['qty'] = $scope.items[i].productQty;
                      
                      $scope.a = $scope.items[i].product_detail.productPackingName.carton;
                      $scope.b = $scope.items[i].product_detail.productPackingName.inner;
                      $scope.c = $scope.items[i].product_detail.productPackingName.unit;
                      
                      var availableunit = [];
                      
                      if($scope.a != undefined && $scope.a != "")
                        availableunit = availableunit.concat([{value: 'carton', label: $scope.a}]);
                      if($scope.b != undefined && $scope.b != "")
                        availableunit = availableunit.concat([{value: 'inner', label: $scope.b}]);
                      if($scope.c != undefined && $scope.c != "")
                        availableunit = availableunit.concat([{value: 'unit', label: $scope.c}]);
             
                      $scope.product[h].availableunit = availableunit;
                      //if($scope.items[i].productUnitName == "")
                      var storeValue;
                      for(var k = 0;k<$scope.product[h].availableunit ;k++)
                      {
                         if($scope.items[i].productUnitName == $scope.product[h].availableunit[k]['label'])
                         {
                             storeValue = k;
                         }
                      }
                      $scope.product[h].unit = $scope.product[h].availableunit[k];
                      $scope.product[h]['unit_cost'] = $scope.items[i].unitprice;
                      break;
                }
            }
        }
        
        if(value == "")
        {
           $("#warning"+h).html("");
           $scope.product[h]['productName'] = "";
           $scope.product[h]['qty'] = "";
           $scope.product[h]['unit'] = "";
           $scope.product[h]['unit_cost'] = "";
          // $scope.product[h]['availableunit'] = 
        }
    }
    
  

    $scope.addRows = function()
    {
        $scope.newkey = $scope.itemlist.length + 1;
        $scope.itemlist.push($scope.newkey);
        $scope.product[$scope.newkey] = $.extend(true, {}, $scope.productStructure);
        $scope.timer.product[$scope.newkey] = $.extend(true, {}, $scope.timerProductStructure);
        
        // if it is the fifth row, make the portlets to be full screen
        if($scope.newkey == 5)
        {
            $("#productsFullScreen").trigger('click');
        }
        $timeout(function(){
            //$(".productCodeField").inputmask("*");
        }, 1000);

    }
 
    $scope.deleteRow = function(i)
    {
        $scope.product[i].deleted = 1;
    }
    
    $scope.productDetect = function(value,i)
    {
       if(typeof $scope.items != 'undefined')
       {
                $scope.product[i]['qty'] = "";
                $scope.product[i]['unit'] = "";
                $scope.product[i]['productName'] = "";
                $scope.product[i]['good_qty'] = "";
                $scope.items.forEach(function (item) {
                    if(item.productId == value)
                    {    
                        $scope.product[i]['qty'] = item.productQty;
                        $scope.product[i]['unit'] = item.productQtyUnit;
                        $scope.product[i]['productName'] = item.product_detail.productName_chi;
                        $scope.product[i]['good_qty'] = item.productQty;
                        addUnit(item,i);
                    }
                });
       }
    }
});