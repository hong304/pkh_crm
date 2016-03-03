'use strict';

Metronic.unblockUI();



app.controller('shipping', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {

    $scope.shippingCost = {
       cost_00 : 0,
       cost_01 : 0,
        cost_02 : 0,
        cost_03 : 0,
        cost_04 : 0,
        cost_05 : 0,
        cost_06 : 0,
        cost_07 : 0,
        cost_08 : 0,
        cost_09 : 0,
    }

    $scope.shipping = {
        shippingitem_id : '',
        shippingId:'',
        poCode: '',
        supplierCode:'',
        carrier:'',
        etaDate:'',
        actualDate:'',
        departure_date:'',
        vessel:'',
        voyage:'',
        bl_number:'',
        pol:'',
        pod:'',
        container_numbers:0,
        fsp:7,
        remark:'',
        status:1,
        feight_payment:'',
        supplierName:'',
        shipCompany:'',

    };
    
       $scope.containerCost = {
           containerId :'',
           receiveDate:'',
           container_size:'',
           sale_method:'',
           shippingId:'',
       };
       
       $scope.selfdefine = [];
       
       $scope.selfdefineS = {
           productId : '',
           qty : '',
           unit :'',
           unitName :'',
           deleted : '0',
       };
       
    
    $scope.totalCost = 0;
    $scope.showOrNot = 0;
     $scope.costsName = {'0':{'name':'運費','index':'0'},'1':{'name':'碼頭處理費','index':'1'},'2':{'name':'拖運費','index':'2'},'3':{'name':'卸貨費','index':'3'},'4':{'name':'外倉費','index':'4'},'5':{'name':'過期櫃租','index':'5'},'6':{'name':'過期交吉租','index':'6'},'7':{'name':'稅金','index':'7'},'8':{'name':'雜項','index':'8'},'9':{'name':'其他','index':'9'}};
    
 //Sunday is not allowed
    var today = new Date();
    var plus = today.getDay() == 6 ? 2 : 1;
    var start_date = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();


  //eta date
    $("#deliverydate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);
    
    //actual date
    $("#deliverydate1").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });

    //departure date
    $("#deliverydate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });

   // $scope.shipping.etaDate  = yyear+'-'+ymonth+'-'+yday;
    
   
    $scope.sameDayInvoice = '';
    $scope.productCode = [];
    $scope.itemlist = [1, 2, 3];

    $scope.productRow = [1];
    $scope.retrievedProduct = [];
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
        containerId:'',
        serial_no:'',
        container_size:'',
        container_Num		        :	0,
        container_weight		:	0,
        container_capacity		:	0,
        feight_currency                 :      '',
        feight_amount                   :      0,
        deleted 	                :     0,
        remark:'',
        receiveDate:'',
        cost : '',
        sale_method:1,
        containerProductDetails :'',
        defaultContainerProduct : 1
    };

    $scope.shippingCostStructure={
        cost_00 : 0,
        cost_01 : 0,
        cost_02 : 0,
        cost_03 : 0,
        cost_04 : 0,
        cost_05 : 0,
        cost_06 : 0,
        cost_07 : 0,
        cost_08 : 0,
        cost_09 : 0,
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





    $scope.itemlist.forEach(function(key){
        $scope.product[key] = $.extend(true, {}, $scope.productStructure);
        $scope.timer.product[key] = $.extend(true, {}, $scope.timerProductStructure);
    });


$scope.getPoProduct = function(poCode) {

    $http.post(endpoint + '/getPoProduct.json', {
            poCode: poCode,
        })
        .success(function (res, status, headers, config) {
            $scope.retrievedProduct = res;
          //  console.log('inside');
          //  console.log(res);
        });
}

    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();
        $scope.determineAction();


        if(!$location.search().shippingId)
        {
            $timeout(function(){
                $('#selectShipModel').modal('show'); //model_selectShip.html|selectShipControl
              
                $('#selectShipModel').on('shown.bs.modal', function () {
                    $('#keyword').focus();
                })

            }, 1000);
            
        $scope.$on('handleShipPassUpdate', function(){
        // received client selection broadcast. update to the invoice portlet
        $scope.shipping.supplierCode = SharedService.supplierCode;
        $scope.shipping.supplierName = SharedService.supplierName;
        $scope.shipping.poCode = SharedService.poCode;

        $scope.displayName = $scope.shipping.supplierCode + " (" + $scope.shipping.supplierName + ")";
        if($scope.shipping.supplierCode === undefined)
        {
            $scope.displayName = "";
        }
            $scope.getPoProduct($scope.shipping.poCode);

        Metronic.unblockUI();

       });
       
     
        }
      else if($location.search().shippingId !="undefined")
        {
            

            // block the full page
            Metronic.blockUI({
                boxed: true,
                message: '下載資料中...'
            });
            // get full shipping information
            var target = endpoint + '/jsonGetSingleShip.json';

            $http.post(target, {shippingId: $location.search().shippingId})
                .success(function(data, status, headers, config){



					$scope.shipping = data.shipping;
                    $scope.getPoProduct($scope.shipping.poCode);



					 if($scope.shipping.supplier.length > 0)
                                        {
                                            $scope.shipping.supplierName = data.shipping.supplier[0].supplierName;
                                        }else
                                        {
                                            $scope.shipping.supplierName = "";
                                        }           
					$scope.shippingItems = data.shippingItem;

   
                    if($scope.shipping.status != 99)
                    {
                        $("#submitbutton").attr('disabled',false);
                    }

                    if($scope.shipping.status == 99)
                    {
                        //$scope.allowSubmission = false;
                        $("#submitbutton").attr('disabled',true);
                    }
                    
                        // load customer product, first load full db, second load invoice-items
                        
                        Metronic.unblockUI();
                        $scope.showOrNot = 1
                        $scope.loadProduct($scope.shipping.shippingId, $scope.shippingItems);

                });



            Metronic.blockUI({
                target: '#orderportletbody',
                boxed: true,
                message: '載入產品中...'
            });
        }
        



    });


    $scope.loadProduct = function(shippingId, defaultProduct)
    {
                   if(defaultProduct.length > 0)
	           {
			var j = 1; // j should be put here		    
                    defaultProduct.forEach(function(item) {
   
                      //  $scope.productCode[j] = item.productId;



                 $scope.product[j].containerProductDetails = item.containerproduct;


//console.log($scope.product[j].containerProductDetails);

                        $scope.product[j]['dbid'] = item.id;

                        $scope.product[j]['containerId'] = item.containerId;
                        $scope.product[j]['serial_no'] = item.serial_no;
                        $scope.product[j]['receiveDate'] = item.container_receiveDate;
                        $scope.product[j]['container_size'] = item.container_size;
                        $scope.product[j]['container_Num'] = item.container_Num;
                        $scope.product[j]['container_weight'] = item.container_weight;
                        $scope.product[j]['container_capacity'] = item.container_capacity;
                        $scope.product[j]['feight_currency'] = item.feight_currency;
			            $scope.product[j]['feight_amount'] = item.feight_amount;
                        $scope.product[j]['remark'] = item.remark;
                        $scope.product[j]['sale_method'] = item.sale_method;

                        $scope.shippingCost = $.extend(true, {}, $scope.shippingCostStructure);
                        
                        $scope.product[j].cost = $scope.shippingCost;
                        
                        $scope.shippingCost.cost_00 = item.cost_00;
                        $scope.product[j]['cost']['cost_00'] = $scope.shippingCost.cost_00;
                        
                        $scope.shippingCost.cost_01 = item.cost_01;
                        $scope.product[j]['cost']['cost_01'] = $scope.shippingCost.cost_01;
                        
                        $scope.shippingCost.cost_02 = item.cost_02;
                        $scope.product[j]['cost']['cost_02'] = $scope.shippingCost.cost_02;
                        
                        $scope.shippingCost.cost_03 = item.cost_03;
                        $scope.product[j]['cost']['cost_03'] = $scope.shippingCost.cost_03;
                        
                        $scope.shippingCost.cost_04 = item.cost_04;
                        $scope.product[j]['cost']['cost_04'] = $scope.shippingCost.cost_04;
                        
                        $scope.shippingCost.cost_05 = item.cost_05;
                        $scope.product[j]['cost']['cost_05'] = $scope.shippingCost.cost_05;
                        
                        $scope.shippingCost.cost_06 = item.cost_06;
                        $scope.product[j]['cost']['cost_06'] = $scope.shippingCost.cost_06;
                        
                        $scope.shippingCost.cost_07 = item.cost_07;
                        $scope.product[j]['cost']['cost_07'] = $scope.shippingCost.cost_07;
                        
                        $scope.shippingCost.cost_08 = item.cost_08;
                        $scope.product[j]['cost']['cost_08'] = $scope.shippingCost.cost_08;
                        
                        $scope.shippingCost.cost_09 = item.cost_09;
                        $scope.product[j]['cost']['cost_09'] = $scope.shippingCost.cost_09;
    
                        //Maybe one day refine it by a loop
          
                        if(typeof $scope.product[j+1] == 'undefined')
                        {
                            $scope.newkey = $scope.itemlist.length + 1;
                            $scope.itemlist.push($scope.newkey);
                            $scope.product[$scope.newkey] = $.extend(true, {}, $scope.productStructure);
                        }
                        var value = 3 + j;
                        $("#deliverydate"+ value).datepicker({
                            rtl: Metronic.isRTL(),
                            orientation: "right",
                            autoclose: true
                        })
            
                        $("#deletebtn_" + j).css('display', 'block');
                        $("#remarkbtn_" + j).css('display', 'block');

                        j++;

                    });

                    }else{
			alert('沒有貨櫃');
                    }
                Metronic.unblockUI('#orderportletbody');
    }

    $scope.selectProduct = function(i) {
        $scope.timer.product[i]['openPanel'] = Date.now();

        $('#selectProduct').modal('show');


        $('#selectProduct').on('shown.bs.modal', function () {
            $("#productSearchField").focus().select();
        })


        $scope.currentSelectProductRow = i;
        SharedService.setValue('currentSelectProductRow', i, 'updateProductSelection');
    }




    //Capitalize all characters
    $scope.capital = function(i)
    {
        $scope.product[i]['serial_no'] = $scope.product[i]['serial_no'].toUpperCase();
        $scope.product[i]['containerId'] = $scope.product[i]['containerId'].toUpperCase();
    }




    $scope.$on('updateProductSelected', function(){

        $scope.timer.product[$scope.currentSelectProductRow]['closePanel'] = Date.now();
        $scope.selectedProduct = SharedService.selectedProductId;

        $scope.searchProduct($scope.currentSelectProductRow, $scope.selectedProduct);



        $("#selectProduct").modal('hide');



        $('#selectProduct').on('hidden.bs.modal', function () {
            $("#qty_" + $scope.currentSelectProductRow).focus().select();
        })
    });

    $scope.searchPoProduct = function(code,j) {


        if ($scope.retrievedProduct[code.toUpperCase()]) {

            var item = $scope.retrievedProduct[code.toUpperCase()];
            $scope.selfdefine[j]['productId'] = code.toUpperCase();
            $scope.selfdefine[j]['productName'] = item.productName_chi;

            var availableunit = [];
            if(item.productPackingInterval_unit > 0)
            {
                availableunit = availableunit.concat([{value: 'unit', label: item.productPackingName_unit}]);
            }
            if(item.productPackingInterval_inner > 0)
            {
                availableunit = availableunit.concat([{value: 'inner', label: item.productPackingName_inner}]);
            }
            if(item.productPackingInterval_carton > 0)
            {
                availableunit = availableunit.concat([{value: 'carton', label: item.productPackingName_carton}]);
            }

            $scope.selfdefine[j].availableunit = availableunit;
            $scope.selfdefine[j].unit = $scope.selfdefine[j].availableunit[0];


        }
    };


    $scope.preSubmitOrder = function(v){



        bootbox.dialog({
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
                    callback: function() {
                        $scope.submitOrder(v);
                    }
                }
            }
        });
       
    }
    


    $scope.submitOrder = function(v)
    {
        var generalError = false;
        $scope.timer.submit = Date.now();

        if(!$scope.allowSubmission)
        {
            Alert('submission Disabled');
            generalError = true;
        }

        //$scope.allowSubmission = false;

        if(!$scope.shipping.poCode || !$scope.shipping.supplierCode || !$scope.shipping.departure_date || !$scope.shipping.etaDate || !$scope.shipping.fsp)
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
            generalError = true;
            $scope.allowSubmission = true;
        }
        if(!generalError)
        {
            $http.post(
                endpoint + '/jsonNewShip.json', {
                    product : $scope.product,
                    ship : $scope.shipping,
                  //  timer	:	$scope.timer,
                }).
                success(function(res, status, headers, config) {
                 //  console.log(res);
                    if(res.result == true)
                    {
                        $scope.an=false;
                       // $scope.statustext = $scope.systeminfo.invoiceStatus[res.status].descriptionChinese;

                        if(res.action == 'update'){
                          //  $state.go("searchship/", {}, {reload: true});
                          $location.url('/searchship?orderTime');
                        }else{
                             $scope.poCodeAfter = res.shipCode;
                              Metronic.alert({
                container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                place: 'prepend', // append or prepent in container
                //type: 'danger',  // alert's type
                message: '<span style="font-size:20px;">新船務編號:'+ $scope.poCodeAfter + '</span>',  // alert's message
                close: true, // make alert closable
                reset: true, // close all previouse alerts first
                focus: true, // auto scroll to the alert after shown
                closeInSeconds: 0, // auto close after defined seconds
               // icon: 'warning' // put icon before the message
            });
             
                        }
                        
                        
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
                        $scope.allowSubmission = true;
                    }
                    //$("#selectProduct").animate({ scrollTop: 0 }, "slow");
                }).
                error(function(res, status, headers, config) {
                    // called asynchronously if an error occurs
                    // or server returns response with an error status.
                    $scope.allowSubmission = true;

                });
      
      }

    }
    
    $scope.checkFsp = function(value)
    {
        if(value <= 0)
        {
            $scope.shipping.fsp = 1;
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
    
    $scope.deleteProductRow = function(k)
    {
        $scope.selfdefine[k].deleted = 1;

    }

    $scope.openRemarkPanel = function(i)
    {
        $("#remarkModal").modal('toggle');
        $scope.editable_remark = $scope.product[i].remark;
        $scope.editable_row = i;
    }

    $scope.saveRemark = function(r)
    {
        $("#remarkModal").modal('hide');
        $scope.product[$scope.editable_row].remark = $scope.editable_remark;
    }
    
    $scope.openCost = function(i)
    {
        $scope.totalCost = 0;
        $("#costDetails").modal('toggle');
        $scope.containerCost.shippingId = $scope.shipping.shippingId;
        $scope.containerCost.containerId = $scope.product[i]['containerId'];
        $scope.containerCost.receiveDate = $scope.product[i]['receiveDate'];
        $scope.containerCost.container_size = $scope.product[i]['container_size'];
        if($scope.product[i]['sale_method'] == 1)
        {
            $scope.containerCost.sale_method = "入倉";
        }else 
        {
            $scope.containerCost.sale_method = "貿易部";
        }

       // for (var j = 1; j < 11; j++) {
       //     if(typeof $scope.shippingCost[j] == 'undefined')
        //    {
              //  $scope.shippingCost[j] = $.extend(true, {}, $scope.shippingCostStructure);
        //    }
       // }
        $scope.shippingCost = $.extend(true, {}, $scope.shippingCostStructure);
       // $scope.shippingCost = $scope.product[i].cost;
       // $scope.shippingCost.cost_01 = $scope.product[i].cost.cost_01;
        if($scope.product[i].cost == null)
        {
             $scope.product[i].cost = $scope.shippingCost;
        }
        $scope.shippingCost.cost_00 = $scope.product[i].cost.cost_00;
        $scope.shippingCost.cost_01 = $scope.product[i].cost.cost_01;
        $scope.shippingCost.cost_02 = $scope.product[i].cost.cost_02;
        $scope.shippingCost.cost_03 = $scope.product[i].cost.cost_03;
        $scope.shippingCost.cost_04 = $scope.product[i].cost.cost_04;
        $scope.shippingCost.cost_05 = $scope.product[i].cost.cost_05;
        $scope.shippingCost.cost_06 = $scope.product[i].cost.cost_06;
        $scope.shippingCost.cost_07 = $scope.product[i].cost.cost_07;
        $scope.shippingCost.cost_08 = $scope.product[i].cost.cost_08;
        $scope.shippingCost.cost_09 = $scope.product[i].cost.cost_09;
       // $scope.shippingCost['cost_01'] = $scope.product[i].cost.cost_01;
      //  $scope.shippingCost['cost_02'] = $scope.product[i].cost.cost_02;
       $scope.editable_rowcost = i;
      for(var k = 0;k<=9;k++)
      {
          var string = "$scope.shippingCost.cost_0"+k;
          var g = eval(string);
          $scope.totalCost += g;
      }
      if(isNaN($scope.totalCost))
      {
          $scope.totalCost = 0.00;
      }
      
       
    }
    
    var target = endpoint + '/getPurchaseAll.json';

    $scope.isEmptyObj = function(obj) {
        for(var prop in obj) {
            if(obj.hasOwnProperty(prop))
                return false;
        }

        return true;
    }

    $scope.openProductDetails = function(i)
    {
        $("#containerProduct").modal('toggle');

        $scope.selfdefine = [];

        $scope.itemlistContainerProducts = [];

        $scope.itemlistContainerProducts[i] = [0, 1];

        $scope.itemlistContainerProducts[i].forEach(function(key){
            $scope.selfdefine[key] = $.extend(true, {}, $scope.selfdefineS);
        });

       //
        if(!$scope.isEmptyObj($scope.product[i].containerProductDetails)){

           // console.log($scope.product[i].containerProductDetails);
var j = 0;
            var containerProducts = $scope.product[i].containerProductDetails;
            containerProducts.forEach(function(item){
                if((item.deleted == 0 && item.productId != '')||item.id>0){

                    $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
                    $scope.searchPoProduct(item.productId,j);
                    $scope.selfdefine[j].qty = item.qty;

                    if(typeof $scope.selfdefine[j+1] == 'undefined')
                    {

                        var list = $scope.itemlistContainerProducts[i];
                        $scope.newkey = list.length + 1;
                        //console.log('newkey:'+$scope.newkey);
                        list.push($scope.newkey);
                        $scope.selfdefine[$scope.newkey] = $.extend(true, {}, $scope.selfdefineS);
                    }

                    j++;

                }
            });
        }

        //console.log('open:');
        //console.log($scope.selfdefine);

        // if($scope.product[i].containerProductDetails == null)
        // {
          // $scope.product[i].containerProductDetails = $scope.selfdefine;
        // }


         
    
         /*   $scope.selfdefine.productId = $scope.product[i].containerProductDetails.productId;
            $scope.selfdefine.productName = $scope.product[i].containerProductDetails.productName;
            $scope.selfdefine.qty = $scope.product[i].containerProductDetails.qty;
            $scope.selfdefine.unit = $scope.product[i].containerProductDetails.unit;
            $scope.selfdefine.unitName = $scope.product[i].containerProductDetails.unitName;*/
        
          //  console.log($scope.selfdefine);
        
        $scope.editable_rowProduct = i;
        if($scope.shipping.poCode != "")
        {
           $http.post(target, {poCode : $scope.shipping.poCode})
           .success(function (res, status, headers, config) {
         
            
           /* if(res[0]['poitem'] != undefined)
            {
                var k = 1;
                res[0]['poitem'].forEach(function(item){       
                  
                    $scope.selfdefine[k]['productId'] = item.productId;
                    $scope.selfdefine[k]['productName'] = item.product_detail.productName_chi;
                    $scope.selfdefine[k]['qty'] = item.productQty;
                    addUnit(item,k);
                    k++;
                });
            }*/
           });
        } 
    }
    

    
    $scope.addProductRow = function(){
        $scope.newkey = $scope.itemlistContainerProducts[ $scope.editable_rowProduct].length + 1;
        $scope.itemlistContainerProducts[ $scope.editable_rowProduct].push($scope.newkey);
        $scope.selfdefine[$scope.newkey] = $.extend(true, {}, $scope.selfdefineS);
    }
    
        
    $scope.saveProductDetails = function()
    {
        $("#containerProduct").modal('hide');
        $scope.product[$scope.editable_rowProduct].containerProductDetails = $scope.selfdefine;
        $scope.product[$scope.editable_rowProduct].defaultContainerProduct = 0;
        //console.log($scope.product[$scope.editable_rowProduct].containerProductDetails);
       /* $scope.product[$scope.editable_rowProduct].containerProductDetails.productId =  $scope.selfdefine.productId;
        $scope.product[$scope.editable_rowProduct].containerProductDetails.productName =  $scope.selfdefine.productName;
        $scope.product[$scope.editable_rowProduct].containerProductDetails.qty =  $scope.selfdefine.qty;
        $scope.product[$scope.editable_rowProduct].containerProductDetails.unit =  $scope.selfdefine.unit;
        $scope.product[$scope.editable_rowProduct].containerProductDetails.unitName =  $scope.selfdefine.unitName;   */
    }
    
    
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
   
                  $scope.selfdefine[i].availableunit = availableunit;
                  var indexNum = storeUnit.indexOf(item.productQtyUnit);
                  $scope.selfdefine[i]['unit'] = availableunit[indexNum];
        }

    

    

    $scope.totalline = 1;
    
    $scope.addRows = function () {
        var j = $scope.totalline;
        $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
      
        $scope.totalline += 1;
    }
    
   
    
    $scope.saveCost = function(r)
    {
        $("#costDetails").modal('hide');
  
        $scope.product[$scope.editable_rowcost].cost = $scope.shippingCost;
        $scope.product[$scope.editable_rowcost].cost.cost_00 =  $scope.shippingCost.cost_00;
        $scope.product[$scope.editable_rowcost].cost.cost_01 =  $scope.shippingCost.cost_01;
        $scope.product[$scope.editable_rowcost].cost.cost_02 =  $scope.shippingCost.cost_02;
        $scope.product[$scope.editable_rowcost].cost.cost_03 =  $scope.shippingCost.cost_03;
        $scope.product[$scope.editable_rowcost].cost.cost_04 =  $scope.shippingCost.cost_04;
        $scope.product[$scope.editable_rowcost].cost.cost_05 =  $scope.shippingCost.cost_05;
        $scope.product[$scope.editable_rowcost].cost.cost_06 =  $scope.shippingCost.cost_06;
        $scope.product[$scope.editable_rowcost].cost.cost_07 =  $scope.shippingCost.cost_07;
        $scope.product[$scope.editable_rowcost].cost.cost_08 =  $scope.shippingCost.cost_08;
        $scope.product[$scope.editable_rowcost].cost.cost_09 =  $scope.shippingCost.cost_09;
      //  $scope.product[$scope.editable_rowcost].cost.cost_01 =  $scope.shippingCost[$scope.editable_rowcost].cost_01;
        //$scope.product[$scope.editable_rowcost].cost.cost_02 =    $scope.shippingCost[$scope.editable_rowcost].cost_02;

    }
    
    $scope.costNum = function() {
        var total = 0;
        var shippingCost = $scope.shippingCost;
        if(!isNaN(shippingCost.cost_00))
            total += shippingCost.cost_00;
        if(!isNaN(shippingCost.cost_01))
            total += shippingCost.cost_01;
        if(!isNaN(shippingCost.cost_02))
            total += shippingCost.cost_02;
        if(!isNaN(shippingCost.cost_03))
            total += shippingCost.cost_03;
        if(!isNaN(shippingCost.cost_04))
            total += shippingCost.cost_04;
        if(!isNaN(shippingCost.cost_05))
            total += shippingCost.cost_05;
        if(!isNaN(shippingCost.cost_06))
            total += shippingCost.cost_06;
        if(!isNaN(shippingCost.cost_07))
            total += shippingCost.cost_07;
        if(!isNaN(shippingCost.cost_08))
            total += shippingCost.cost_08;
        if(!isNaN(shippingCost.cost_09))
            total += shippingCost.cost_09;
        $scope.totalCost = total; 
    };
        
    $scope.determineAction = function()
    {
        if($window.location.href.search('shippingId') > -1)
        {
            $scope.showOrNot = 1;
        } 
    }
    
    

    // set sidebar closed and body solid layout mode
    $rootScope.settings.layout.pageSidebarClosed = false;
});