'use strict';

Metronic.unblockUI();



app.controller('shipping', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {

    $scope.shippingCost = {
        cost_01:'',
        cost_02:''
    }

    $scope.shipping = {
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
        container_numbers:'0',
        fsp:'0',
        remark:'',
        status:1,
        feight_payment:'',
        supplierName:'',

    };
    
       $scope.containerCost = {
           containerId :'',
           receiveDate:'',
           container_size:'',
           sale_method:'',
           shippingId:'',
       };
    
    $scope.totalCost = 0;
    $scope.showOrNot = 0;
     $scope.costsName = {'0':{'name':'運費','index':1},'1':{'name':'碼頭處理費','index':2},'2':{'name':'拖運費','index':3},'3':{'name':'卸貨費','index':4},'4':{'name':'外倉費','index':5},'5':{'name':'過期櫃租','index':6},'6':{'name':'過期交吉租','index':7},'7':{'name':'稅金','index':8},'8':{'name':'雜項','index':9},'9':{'name':'其他','index':10}};
    
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

    $scope.shipping.etaDate  = yyear+'-'+ymonth+'-'+yday;
    
   
    $scope.sameDayInvoice = '';
    $scope.productCode = [];
    $scope.itemlist = [1, 2, 3];
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
        cost : ''


    };

    $scope.shippingCostStructure={
        cost_01 : '',
        cost_02 : ''
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
   
                }
                if(q==0 && $("#productCode_"+i).val() != productId){
                        $scope.getLastItem($("#productCode_"+i).val(),$scope.order.clientId,i,1);
                       // $scope.updateStandardPrice(i);
                    if (typeof res[0] == 'undefined'){
                        $scope.updateStandardPrice(i);
                    }
                }
            });

    }
    

    $scope.itemlist.forEach(function(key){
        $scope.product[key] = $.extend(true, {}, $scope.productStructure);
        $scope.timer.product[key] = $.extend(true, {}, $scope.timerProductStructure);
    });


    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();
        $scope.determineAction();


        if(!$location.search().shippingId)
        {
            $timeout(function(){
                $('#selectShipModel').modal('show');
              
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
					$scope.shipping.supplierName = data.shipping.supplier[0].supplierName;
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
                        $scope.loadProduct($scope.shipping.shippingId, $scope.shippingItems);
                });



            Metronic.blockUI({
                target: '#orderportletbody',
                boxed: true,
                message: '載入產品中...'
            });
        }
        
        else 
        {
            $('#selectPoModel').modal('show');
            $('#selectPoModel').on('shown.bs.modal', function () {
                $('#keyword').focus();
            })
        }


    });


    $scope.loadProduct = function(shippingId, defaultProduct)
    {
                   if(defaultProduct.length > 0)
	           {
			var j = 1; // j should be put here		    
                    defaultProduct.forEach(function(item) {
   
                      //  $scope.productCode[j] = item.productId;
                        
                        
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
    }



    $scope.searchProduct = function(i) {

        var input = $("#productCode_" + i);

        if($scope.product[i]['containerId'] !== "")
      {

            // UX Auto Add Next COlumn
            if(typeof $scope.product[i+1] == 'undefined')
            {
                $scope.newkey = $scope.itemlist.length + 1;
                $scope.itemlist.push($scope.newkey);
                $scope.product[$scope.newkey] = $.extend(true, {}, $scope.productStructure);
                $scope.timer.product[$scope.newkey] = $.extend(true, {}, $scope.timerProductStructure);
            }



            // enable delete button, but delay that with 2 seconds
            $timeout(function(){
                $("#deletebtn_" + i).css('display', '');
                $("#remarkbtn_" + i).css('display', '');
            }, 1000);


        }
        else
        {
            // reset the whole structure
            $scope.product[i] = $.extend(true, {}, $scope.productStructure);

            $("#deletebtn_" + i).css('display', 'none');

            $("#remarkbtn_" + i).css('display', 'none');
        }

        $scope.timer.product[(i-1 < 1 ? 1 : i-1)]['completedRow'] = Date.now();
    };

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
        
        console.log($scope.product);

        $scope.timer.submit = Date.now();

        if(!$scope.allowSubmission)
        {
            Alert('submission Disabled');
            generalError = true;
        }

        $scope.allowSubmission = false;


        if(!$scope.shipping.poCode || !$scope.shipping.supplierCode || !$scope.shipping.departure_date || !$scope.shipping.etaDate)
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

       /*for(var key = i; key<=$scope.itemlist.length; key++)
         {

         $scope.product[key] = $.extend(true, {}, $scope.product[key+1]);
         $scope.productCode[key] = $scope.productCode[key+1];

         }

         $scope.product[$scope.itemlist.length] = $.extend(true, {}, $scope.productStructure);
         $scope.productCode[$scope.itemlist.length] = '';*/
        
        $scope.product[i].deleted = 1;
       
     
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
        $("#costDetails").modal('show');
        SharedService.setValue('containerId', $scope.product[i]['containerId'], 'costPassUpdate');
        SharedService.setValue('receiveDate', $scope.product[i]['receiveDate'], 'costPassUpdate');
        SharedService.setValue('container_size', $scope.product[i]['container_size'], 'costPassUpdate');
        SharedService.setValue('sale_method', $scope.product[i]['sale_method'], 'costPassUpdate'); 
        SharedService.setValue('shippingId', $scope.shipping.shippingId, 'costPassUpdate'); 
      //  $scope.editablecost[cost.index] = 0;

        console.log($scope.product);

        for (var j = 1; j < 11; j++) {
            if(typeof $scope.shippingCost[j] == 'undefined')
            {
                $scope.shippingCost[j] = $.extend(true, {}, $scope.shippingCostStructure);

            }
        }


        $scope.shippingCost[i].cost_01 = $scope.product[i].cost_01;
        $scope.shippingCost[i].cost_02 = $scope.product[i].cost_02;

        $scope.editable_rowcost = i;
        
    }
    
    $scope.saveCost = function(r)
    {
        console.log($scope.shippingCost);

        $("#costDetails").modal('hide');
        $scope.product[$scope.editable_rowcost].cost =  $scope.shippingCost[$scope.editable_rowcost][1].cost;
        $scope.product[$scope.editable_rowcost].cost =    $scope.shippingCost[$scope.editable_rowcost][2].cost;

    }
    
    $scope.costNum = function(v,i) {
        if(i.index == '1')
        {
            $scope.product[$scope.editable_rowcost].cost_01 = v;
        }else if(i.index == '2')
        {
            $scope.product[$scope.editable_rowcost].cost_02 = v;
        }else if(i.index == '3')
        {
            $scope.product[$scope.editable_rowcost].cost_03 = v;
        }else if(i.index == '4')
        {
            $scope.product[$scope.editable_rowcost].cost_04 = v;
        }else if(i.index == '5')
        {
            $scope.product[$scope.editable_rowcost].cost_05 = v;
        }else if(i.index == '6')
        {
            $scope.product[$scope.editable_rowcost].cost_06 = v;
        }else if(i.index == '7')
        {
            $scope.product[$scope.editable_rowcost].cost_07 = v;
        }else if(i.index == '8')
        {
            $scope.product[$scope.editable_rowcost].cost_08 = v;
        }else if(i.index == '9')
        {
            $scope.product[$scope.editable_rowcost].cost_09 = v;
        }else if(i.index == '10')
        {
            $scope.product[$scope.editable_rowcost].cost_10 = v;
        }
        var total = 0;
        var product = $scope.product[$scope.editable_rowcost];
        console.log(product);
        total = product.cost_01 + product.cost_02 + product.cost_03 + product.cost_04 + product.cost_05 + product.cost_06 + product.cost_07 + product.cost_08 + product.cost_09 + product.cost_10;
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