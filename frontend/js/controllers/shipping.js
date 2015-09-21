'use strict';

Metronic.unblockUI();



app.controller('shipping', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
    /* Register shortcut key */

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
        pol:'0',
        pod:'0',
        container_numbers:'0',
        fsp:'0',
        remark:'',
        status:1,
        feight_payment:'',
        supplierName:'',
    };

    
 //Sunday is not allowed
    var today = new Date();
    var plus = today.getDay() == 6 ? 2 : 1;
    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 1);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate()+4;

    var day = currentDate.getDate() - 2;
    var month = currentDate.getMonth() + 1;
    var year = currentDate.getFullYear();
    
    var day3 = currentDate.getDate() - 2;
    var month3 = currentDate.getMonth() + 1;
    var year3 = currentDate.getFullYear();
    
    var day4 = currentDate.getDate() + 4;
    var month4 = currentDate.getMonth() + 1;
    var year4 = currentDate.getFullYear();

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
    $("#deliverydate1").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday );

    //departure date
    $("#deliverydate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });

    $("#deliverydate2").datepicker("setDate", year + '-' + month + '-' + day );
   
   //receive date
     $("#deliverydate3").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "right",
        autoclose: true
    });
    $("#deliverydate3").datepicker( "setDate", year4 + '-' + month4 + '-' + day4 );

    $scope.shipping.etaDate =  $scope.shipping.actualDate = yyear+'-'+ymonth+'-'+yday;
    $scope.shipping.departure_date = year+'-'+month+'-'+day;
    $scope.shipping.receiveDate = year4+'-'+month4+'-'+day4;
    
   
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
        container_receiveDate		:      '',
        container_Num		        :	0,
        container_weight		:	0,
        container_capacity		:	0,
        feight_currency                 :      '',
        feight_amount                   :      0,
        deleted 	                :     0,
        remark:''
    };
   

    $scope.submitButtonText = '提交 (F10)';
    $scope.submitButtonColor = 'blue';
    $scope.countdown = "1";
    $scope.timer = {
        start		:	Date.now(),
        selected_client	:	'',
        product		:	[],
        submit		:	''
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
    });


    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();


        if(!$location.search().shippingId)
        {
            $timeout(function(){
                $('#selectPoModel').modal('show');
              
                $('#selectPoModel').on('shown.bs.modal', function () {
                    $('#keyword').focus();
                })

            }, 1000);
            
        $scope.$on('handleSupplierUpdate', function(){
        // received client selection broadcast. update to the invoice portlet
        $scope.an=true;
        $scope.shipping.supplierCode = SharedService.supplierCode;
        $scope.shipping.supplierName = SharedService.supplierName;

        $scope.displayName = $scope.shipping.supplierCode + " (" + $scope.shipping.supplierName + ")";
        if($scope.shipping.supplierCode === undefined)
        {
            $scope.displayName = "";
        }

        Metronic.unblockUI();

       });
       
        $scope.$on('handlePoUpdate', function(){
           $scope.an=true;
           $scope.shipping.poCode = SharedService.supplierPoCode;
           
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
                    $("#deletebtn_4").css('display', '');
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
                        $timeout(function(){
                            $("#deletebtn_" + j).css('display', '');
                            $("#remarkbtn_" + j).css('display', '');
                        }, 500);

                        console.log($scope.product);

                        var value = 3 + j;
                        $("#deliverydate"+ value).datepicker({
                            rtl: Metronic.isRTL(),
                            orientation: "right",
                            autoclose: true
                        })



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
    

    // set sidebar closed and body solid layout mode
    $rootScope.settings.layout.pageSidebarClosed = false;
});