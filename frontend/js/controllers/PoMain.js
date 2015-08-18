'use strict';

Metronic.unblockUI();



app.controller('PoMain', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
    /* Register shortcut key */
    $(document).ready(function(){
        $('#order_form').keydown(function (e) {
            if (e.keyCode == 121) {
                $scope.preSubmitOrder(1);
            }
            if (e.keyCode == 117) {
                $scope.preSubmitOrder(0);
            }
        });

        /*
        var form = $('#orderinfo'),
            original = form.serialize()

        form.submit(function(){
            window.onbeforeunload = null
        })

        window.onbeforeunload = function(){
            if (form.serialize() != original)
                return 'hi';
        }*/
    });


    laodCountry();
    
    
    $scope.order = {
        poCode: '',
        supplierName:'',
        countryName:'',
        etaDate:'',
        actualDate:'',
        receiveDate:'',
        currencyId:'',
        status:'',
        payment:'',
        discount_1:'0',
        discount_2:'0',
        allowance_1:'0',
        allowance_2:'0',
        poRemark:'',
        poStatus:'1',
        supplierCode:'',
        poDate:'',
       totalAmount:$scope.totalAmount,
       poReference:'',
       contactPerson_1:'',
    };

   // var target = endpoint + '/poMain.json';

 /*   $http.get(target)
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


            $scope.order.deliveryDate = year + '-' + month + '-' + day;
            $scope.order.dueDate = year + '-' + month + '-' + day;
            $scope.order.invoiceDate = $scope.order.deliveryDate;
        });
*/

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


    $("#deliverydate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);

    $("#deliverydate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
  //   $('#deliverydate2').datepicker('option','minDate', new Date('15/08/2015'));
    $("#deliverydate2").datepicker("setDate", year + '-' + month + '-' + day );
   
    
     $("#deliverydate1").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate1").datepicker( "setDate", year3 + '-' + month3 + '-' + day3 );

     $("#deliverydate3").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate3").datepicker( "setDate", year4 + '-' + month4 + '-' + day4 );

    $scope.order.etaDate = yyear+'-'+ymonth+'-'+yday;
    $scope.order.poDate = year+'-'+month+'-'+day;
    $scope.order.actualDate = year3+'-'+month3+'-'+day3;
    $scope.order.receiveDate = year4+'-'+month4+'-'+day4;


    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);

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
        poCode		:	'',
        unit            :       '',
        code		:	'',
        qty			:	1,
        availableunit	:	[],
        unitprice	:	0,
        unitpricerk	:	false,
        name		:	'',
        spec		:	'',
        discount_1	:	0,
        discount_2	:	0,
        discount_3	:	0,
        allowance_1	:	0,
        allowance_2	:	0,
        allowance_3	:	0,
        remark		:	'',
        approverid	:	0,
        deleted 	: 	'0',
        currencyId      :      '',
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

   

    $scope.getSameDayInvoice = function(){
        var target = endpoint + '/getClientSameDayOrder.json';

        $http.post(target, {customerId: $scope.order.clientId, deliveryDate:$scope.order.deliveryDate})
            .success(function(res, status, headers, config){
                $scope.sameDayInvoice = res;

            });
    }
    // Recalculate the total amount if any part of the product object has been changed.
    $scope.$watch('product', function() {
        $scope.reCalculateTotalAmount();

    }, true);

    $scope.$watch('order', function() {
        $scope.reCalculateTotalAmount();
    
    }, true);

    $scope.$on('doneCustomerUpdate', function(){

        // get all products
        // $scope.loadProduct($scope.order.clientId);

        //block the order portlet
        /*
         Metronic.blockUI({
         target: '#orderportletbody',
         boxed: true,
         message: '取得貨品資料中...'
         });
         */

        // load last time invoice
        //$scope.getClientLastInvoice($scope.order.clientId);
        $scope.getSameDayInvoice();

    });

    $scope.relocate = function(){
        if($scope.order.zoneId != $scope.order.defaultZoneId)
            $scope.order.route = 0;
        else
            $scope.order.route = $scope.order.defaultRoute;
    }

    /*$scope.getClientLastInvoice = function(clientId)
     {
     var target = endpoint + '/getClientLastInvoice.json';

     $http.post(target, {customerId: clientId})
     .success(function(res, status, headers, config){
     $scope.lastinvoice = res;
     });
     }*/

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
                        $scope.getLastItem($("#productCode_"+i).val(),$scope.order.clientId,i,1);
                       // $scope.updateStandardPrice(i);
                    if (typeof res[0] == 'undefined'){
                        $scope.updateStandardPrice(i);
                    }
                }
            });

    }
    
    $scope.updateDiscount = function()
    {

        $scope.reCalculateTotalAmount();
    }

    $scope.reCalculateTotalAmount = function() {

        $scope.totalAmount = 0;

        $scope.product.forEach(function(item){
           
            if(item.code != "")
            { 
                $scope.totalAmount += (item.qty * item.unitprice * (100-item.discount_1)/100 * (100-item.discount_2)/100 * (100-item.discount_3)/100) - item.allowance_1 - item.allowance_2 - item.allowance_3;
            } 
        });
        
        

       $scope.totalAmount = ($scope.totalAmount * (100-$scope.order.discount_1)/100 * (100-$scope.order.discount_2)/100) - $scope.order.allowance_1 - $scope.order.allowance_2;
 
        var temp_number = $scope.totalAmount;

        $scope.totalAmount =temp_number.toFixed(1);

    }

    $scope.itemlist.forEach(function(key){
        $scope.product[key] = $.extend(true, {}, $scope.productStructure);
        $scope.timer.product[key] = $.extend(true, {}, $scope.timerProductStructure);
    });


    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();

        if($stateParams.action == 'success') {
            if($stateParams.instatus=='2'){
                $scope.instatusmsg = '已新增訂單';
            }else if($stateParams.instatus=='1'){
                $scope.instatusmsg = '訂單需要批核';
            }else if($stateParams.instatus=='98'){
                $scope.instatusmsg = '已新增退貨單';
            }
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

        if(!$location.search().poCode)
        {
            $timeout(function(){
                $('#selectclientmodel').modal('show');

                $('#selectclientmodel').on('shown.bs.modal', function () {
                    $('#keyword').focus();
                })


                //$('#selectclientmodel').modal({backdrop: 'static'});
            }, 1000);
            
         $scope.$on('handleSupplierUpdate', function(){
        // received client selection broadcast. update to the invoice portlet
        $scope.an=true;
        $scope.countryDataList = SharedService.allCountry;
        $scope.allCurrencyList = SharedService.allCurrency;
        $scope.order.supplierCode = SharedService.supplierCode;
        $scope.order.supplierName = SharedService.supplierName;
        $scope.order.countryName = SharedService.countryName;
        $scope.order.address = SharedService.address;
        $scope.order.currencyName = SharedService.currencyName;
        $scope.order.currencyId = SharedService.currencyId;
        $scope.productStructure.currencyId = SharedService.currencyId;
        $scope.order.contactPerson_1 = SharedService.contactPerson_1;
        $scope.order.status = SharedService.status;
        $scope.order.payment = SharedService.payment;
        $scope.displayName = $scope.order.supplierCode + " (" + $scope.order.supplierName + ")";
        if($scope.order.supplierCode === undefined)
        {
            $scope.displayName = "";
        }
        

      for(var t = 0;t<$scope.countryDataList.length;t++)
      {
          if($scope.countryDataList[t].countryName == $scope.order.countryName)
          {
              $scope.countryData = $scope.countryDataList[t];
          }
      }
      for(var t = 0;t<$scope.allCurrencyList.length;t++)
      {
          if($scope.allCurrencyList[t].currencyName == $scope.order.currencyName)
          {
              $scope.currencyData = $scope.allCurrencyList[t];
          }
      }
      
  
        if(SharedService.clientPaymentTermId == 1)
        {
            $("#paymentTerms").attr('disabled', 'true');
            $("#duedatepicker").datepicker('remove');
        }
        else
        {
            $("#paymentTerms").removeAttr('disabled');
            $("#duedatepicker").datepicker({
                rtl: Metronic.isRTL(),
                orientation: "left",
                autoclose: true
            });
        }

        //$(".productCodeField").inputmask("*");
        $('#maxlength_defaultconfig').maxlength({
            limitReachedClass: "label label-danger"
        })

        $scope.timer.selected_client = Date.now();
        Metronic.unblockUI();

       });
        }
      else if($location.search().poCode !="undefined")
        {
            
           $("#actualDate,#receiveDate,#supplierConfirmation").show();
   
         
               
            // block the full page
            Metronic.blockUI({
                boxed: true,
                message: '下載資料中...'
            });
            // get full invoice information
            var target = endpoint + '/getSinglePo.json';

            $http.post(target, {poCode: $location.search().poCode})
                .success(function(data, status, headers, config){
                  if(data.count > 0) {
                    $scope.poData = data.pos[0];
                  

                    $scope.poitems = data.pos[0].poitem;
                    $scope.supplierData = data.pos[0].supplier;
                    $scope.order.poCode = $scope.poData.poCode;
                    $scope.order.supplierName = $scope.supplierData.supplierName;
                    $scope.order.payment = $scope.poData.payment;
                    $scope.order.poReference = $scope.poData.poReference;
                    $scope.order.actualDate = $scope.poData.actualDate == '' ? '' :$scope.poData.actualDate;
                    $scope.order.receiveDate = $scope.poData.receiveDate == '' ? '' :$scope.poData.receiveDate;
                    $scope.order.etaDate = $scope.poData.etaDate;
                    $scope.order.poAmount = $scope.poData.poAmount;
                    $scope.order.poDate = $scope.poData.poDate;

                    $scope.order.poStatus = $scope.poData.poStatus;

                    $scope.order.contactPerson_1 = $scope.supplierData.contactPerson_1;
                    
                    $scope.order.discount_1 = $scope.poData.discount_1;
                    $scope.order.discount_2 = $scope.poData.discount_2;
                    $scope.order.allowance_1 = $scope.poData.allowance_1;
                    $scope.order.allowance_2 = $scope.poData.allowance_2;
                    
                    $scope.displayName = $scope.poData.supplierCode + " (" + $scope.order.supplierName + ")";
                    $scope.order.supplierCode = $scope.poData.supplierCode;
                    $scope.order.poRemark = $scope.poData.poRemark;
                    
                    if($scope.order.poStatus != 99)
                    {
                        $("#statusField").attr('disabled',false);
                    }
                 
             $scope.$on('handleSupplierUpdate', function(){

                    $scope.an=true;
                    $scope.countryDataList = SharedService.allCountry;
                    $scope.allCurrencyList = SharedService.allCurrency;
         
                      for(var t = 0;t<$scope.countryDataList.length;t++)
                      {
                        if($scope.countryDataList[t].countryId == $scope.supplierData.countryId)
                        {
                            $scope.countryData = $scope.countryDataList[t];
                        }
                       }
                  
                    for(var t = 0;t<$scope.allCurrencyList.length;t++)
                    {
                        if($scope.allCurrencyList[t].currencyId == $scope.supplierData.currencyId)
                        {
                            $scope.currencyData = $scope.allCurrencyList[t];
                        }
                    }
                      $scope.order.currencyId = $scope.currencyData.currencyId;
                    

                });
                  
                    if($scope.order.poStatus == 99)
                    {
                        //$scope.allowSubmission = false;
                        $("#submitbutton").attr('disabled',true);
                    }
                    
                        // load customer product, first load full db, second load invoice-items
                        $scope.loadProduct($scope.order.poCode, $scope.poitems);
                        Metronic.unblockUI();
                  

                    $timeout(function(){
                        //$(".productCodeField").inputmask("*");
                    }, 2000);
                }
                });



            Metronic.blockUI({
                target: '#orderportletbody',
                boxed: true,
                message: '載入產品中...'
            });
        }
        
        else 
        {
            $('#selectclientmodel').modal('show');
            $('#selectclientmodel').on('shown.bs.modal', function () {
                $('#keyword').focus();
            })
            $scope.loadProduct($location.search().poCode);

        
        }

       $scope.loadProduct($location.search().poCode);

    });


    $scope.loadProduct = function(poCode, defaultProduct)
    {
  
        $http.post(endpoint + '/getAllProduct.json', {
            poCode	:	poCode,
            productList : defaultProduct,
        })
            .success(function(res, status, headers, config) {
                $scope.retrievedProduct = res;
                if(defaultProduct)
                {
                    var j = 1;
                    defaultProduct.forEach(function(item) {
                        
                        $scope.productCode[j] = item.productId;
                        
                        $scope.searchProduct(j, item.productId,'unload');
                        
                        $scope.product[j]['dbid'] = item.id;
                        $scope.product[j]['qty'] = item.productQty;
                        $scope.product[j]['unitprice'] = item.unitprice;
                        $scope.product[j]['discount_1'] = item.discount_1;
                        $scope.product[j]['discount_2'] = item.discount_2;
                        $scope.product[j]['discount_3'] = item.discount_3;
                        $scope.product[j]['allowance_1'] = item.allowance_1;
                        $scope.product[j]['allowance_2'] = item.allowance_2;
                        $scope.product[j]['allowance_3'] = item.allowance_3;
                        $scope.product[j]['remark'] = item.remark;
                   //     $scope.product[j]['approverid'] = item.approvedSupervisorId;
                        $scope.product[j]['productQtyUnit'] = item.productQtyUnit;
                        var pos = $scope.product[j].availableunit.map(function(e) {
                            return e.value;
                        }).indexOf(item.productQtyUnit);
                        //If there is no productQty , -1 will be returned.
                        if(typeof $scope.product[j]['availableunit'][pos] == 'undefined'){
                            var pos = $scope.product[j].availableunit.map(function(e) {
                                return e.value;
                            }).indexOf('unit');
 
                        }
                        $scope.product[j]['unit'] = $scope.product[j]['availableunit'][pos];
                        $scope.checkPrice(j);
                       

                        j++;
                    });
                }
                Metronic.unblockUI('#orderportletbody');

            })
            .error(function(res, status, headers, config) {
                // called asynchronously if an error occurs
                // or server returns response with an error status.
                alert('Failed to load products. Please reload or contact system administrator');
                //location.reload();
            });
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



    $scope.searchProduct = function(i, code,flag) {

        var input = $("#productCode_" + i);
        if($scope.retrievedProduct[code.toUpperCase()])
        {
            var item = $scope.retrievedProduct[code.toUpperCase()];

            // update product name
            $("#productCode_"+i).val(code);
            $scope.product[i].code = code.toUpperCase();
            $scope.product[i].name = item.productName_chi;
            $scope.product[i].spec = '(' + item.productPacking_carton + '*' + item.productPacking_inner + '*' + item.productPacking_unit + '*' + item.productPacking_size + ')';
          //  $scope.product[i].itemdiscount = item.itemdiscount;
            // enable product qty
            //$("#spinner_" + i).spinner({value:0.5, step: 0.5, min: 0, max: 999});

            // set product unit
            var availableunit = [];

                if(item.supplierPackingInterval_unit > 0)
                {
                    //$("#unit_" + i).prepend('<option value="unit">Unit</option>');
                    availableunit = availableunit.concat([{value: 'unit', label: item.productPackingName_unit}]);
                }
                if(item.supplierPackingInterval_inner > 0)
                {
                    //$("#unit_" + i).prepend('<option value="inner">Inner</option>');
                    availableunit = availableunit.concat([{value: 'inner', label: item.productPackingName_inner}]);
                }
                if(item.supplierPackingInterval_carton > 0)
                {
                    //$("#unit_" + i).prepend('<option value="0">Carton</option>');
                    availableunit = availableunit.concat([{value: 'carton', label: item.productPackingName_carton}]);
                }
           

            //$scope.product[i].availableunit = availableunit.reverse();
            $scope.product[i].availableunit = availableunit;
            $scope.product[i].unit = $scope.product[i].availableunit[0];
            $scope.updateStandardPrice(i);

            if(flag != 'unload')
                $scope.getLastItem(code,$scope.order.clientId,i,0);

            // console.log($scope.lastitem);

            // $scope.lastItemUnit = '5';

            //--  check if last time invoice


            // -- check if last time invoice



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
                $("#unitprice_" + i).removeAttr('disabled');
            }, 1000);


            //	console.log(i);
            // Focus to the qty input box

            // $("#qty_" + i).focus().select();





        }
        else
        {
            // reset the whole structure
            $scope.product[i] = $.extend(true, {}, $scope.productStructure);

            $("#unitprice_" + i).attr('disabled', 'true');

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
    
     function laodCountry()
    {
        $http(
	    	{
			method	:	"POST",
			url		: endpoint + '/queryCountry.json', 
			data	:	{mode: 'collection'},
			        	cache	:	true,
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
                  $scope.countryData = res.aaData;

	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
                
    }


    $scope.updateStandardPrice = function (i)
    {
        
        var code = $scope.product[i]['code'];
        var item = $scope.retrievedProduct[code];
        console.log($scope.retrievedProduct[code]);
        var unit = $scope.product[i]['unit'].value;
      //  console.log(item);
        // *** to be updated - non-hard-coding
        if(unit == 'carton')
            $scope.product[i]['unitprice'] = Number(item.productCost_unit);
        else if(unit == 'inner')
            $scope.product[i]['unitprice'] = Number(item.supplierStdPrice_inner);
        else if(unit == 'unit')
            $scope.product[i]['unitprice'] = Number(item.supplierStdPrice_unit);

     //   $("#unitprice_" + i).removeAttr('disabled');


    }

    $scope.checkPrice = function(i)
    {
        var code = $scope.product[i]['code'];
        var item = $scope.retrievedProduct[code];  //There is a loaded array, if u want to retrieve tthe product by productId ,just put the id here

        var unit = $scope.product[i]['unit']['value'];

        $scope.submitButtonText = '提交 (F10)';
        $scope.submitButtonColor = 'blue';


        var stdprice = Number(item.productStdPrice[unit]); // If the number is eg 44.00 , it 
        
        if(isNaN($scope.product[i]['unitprice']) && item.allowNegativePrice != '1')
        {
            $scope.product[i]['unitprice'] = stdprice;
        }

        $("#requireapprove_" + i).remove();

        var saleprice = ($scope.product[i]['unitprice'] * getDiscount($scope.product[i]['discount_1']) * getDiscount($scope.product[i]['discount_2']) * getDiscount($scope.product[i]['discount_3'])) - $scope.product[i]['allowance_1'] - $scope.product[i]['allowance_2'] - $scope.product[i]['allowance_3'];
        // if saleprice < std price, need approval
       /* if(saleprice < stdprice  && $scope.product[i].deleted == 0)
        {
            $("#unitpricediv_" + i).prepend('<i id="requireapprove_'+i+'" class="fa fa-info-circle" style="color:red;"></i>');
            $scope.submitButtonText = '提交 (需批核) (F10)';
            $scope.submitButtonColor = 'green';
        }


        // if he got permission of bypassing approval, eventually display no approval button
        if($scope.systeminfo.permission.allow_by_pass_invoice_approval == true)
        {
            $scope.submitButtonText = '提交';
            $scope.submitButtonColor = 'blue';
        }*/

    }
    
    function getDiscount(discount)
    {
        return (100 - discount)/100;
    }
    
    $scope.updateQtyy = function(i,discountCon)
    {
         if(discountCon == "discount1")
         {
           $scope.order.discount_1 =   isNaN(i) ?   1 : $scope.order.discount_1;
         }else if(discountCon == "discount2")
         {
             $scope.order.discount_2 =   isNaN(i) ?   1 : $scope.order.discount_2;
         }else if(discountCon  == "allowance1")
         {
             $scope.order.allowance_1 =   isNaN(i) ?   1 : $scope.order.allowance_1;
         }else if(discountCon == "allowance2")
         {
             $scope.order.allowance_2 =   isNaN(i) ?   1 : $scope.order.allowance_2;
         }
    }

    $scope.updateUnit = function(i)
    {
        $scope.updateStandardPrice(i);
        $scope.checkQtyInterval(i);
    }

    $scope.checkQtyInterval = function(i)
    {
        var code = $scope.product[i]['code'];
        var item = $scope.retrievedProduct[code];
        var unit = $scope.product[i]['unit']['value'];
        var qty = $scope.product[i]['qty'];

        /*if(isNaN(qty))
         {
         $scope.product[i]['qty'] = 1;
         }*/

        var interval = $scope.retrievedProduct[code]['productPackingInterval'][unit];

        if(qty % interval > 0 )
        {
            $scope.product[i]['qty'] = Math.ceil( qty / interval ) * interval;
        }
    }

    $scope.statusChange = function(){

        if($scope.order.status == '98')
            $scope.order.invoiceRemark = '退貨單'
        if($scope.order.status == '96')
            $scope.order.invoiceRemark = '補貨單';
        if($scope.order.status == '2')
            $scope.order.invoiceRemark = ''
    }


    $scope.preSubmitOrder = function(v){

        var currentDate = new Date(new Date().getTime());
        var day = currentDate.getDate();
        
        day = ("0" + day).slice(-2);

        var month = currentDate.getMonth()+1;
        month = ("0" + month).slice(-2);
        var year = currentDate.getFullYear();

        var dates = ""+year+month+day;

         var bool = true;

      /*  if(v){
            var bool = (Number(order_dates) < Number(dates));
            var msg = 'F10! 此訂單將會被列印';
        }else{
            var bool = (Number(order_dates) >= Number(dates));
            var msg = 'F6! 此訂單將不會被列印';
        }*/
if(bool)
        bootbox.dialog({
            message: '提交訂單',
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
        else
            

$scope.submitOrder(v);
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


        if(!$scope.order.poDate || !$scope.order.etaDate || !$scope.order.supplierName || !$scope.order.poStatus)
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
        $scope.order.totalAmount = $scope.totalAmount;
        for(var k = 1;k<$scope.product.length;k++)
        {
                $scope.product[k].currencyId = $scope.order.currencyId;
           
        }
        
        if(!generalError)
        {
            $scope.order.print = v;
            $http.post(
                endpoint + '/newPoOrder.json', {
                    product : $scope.product,
                    order : $scope.order,
                  //  timer	:	$scope.timer,
                }).
                success(function(res, status, headers, config) {
                    $scope.poCode = res.poCode;
                    if(res.result == true)
                    {
                        $scope.an=false;
                       // $scope.statustext = $scope.systeminfo.invoiceStatus[res.status].descriptionChinese;

                        if(res.action == 'update'){
                            $state.go("searchPo", {}, {reload: true});
                        }else{
                             $scope.poCodeAfter = res.poCode;
             
                           //  $('#selectclientmodel').modal({backdrop: 'static'});
                              Metronic.alert({
                container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                place: 'prepend', // append or prepent in container
                //type: 'danger',  // alert's type
                message: '<span style="font-size:20px;">採購單編號:'+ $scope.poCodeAfter + '</span>',  // alert's message
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

    $scope.showRecentPurchases = function()
    {
        if(!$scope.recentProduct || $scope.recentProductClient != $scope.order.clientId)
        {
            $http.post(
                endpoint + '/findRecentProductsByCustomerId.json', {
                    customerId	:	$scope.order.clientId
                }).
                success(function(res, status, headers, config) {
                    $scope.recentProduct = res;
                    $scope.recentProductClient = $scope.order.clientId;
                }).
                error(function(res, status, headers, config) {
                    // called asynchronously if an error occurs
                    // or server returns response with an error status.
                });
        }
        $("#recentProductModal").modal('toggle');
    }

    $scope.selectRecentProduct = function(productId)
    {
        for(var i = 1; i<= $scope.product.length-1; i++)
        {
            if($scope.product[i].code == '')
            {
                $scope.searchProduct(i, productId);
                $("#recentProductModal").modal('toggle');
                break;
            }
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

    $scope.addMaskToProductField = function()
    {
        //$(".productCodeField").inputmask("*");
    }

    $scope.deleteRow = function(i)
    {
        
         for(var key = i; key<=$scope.itemlist.length; key++)
         {

         $scope.product[key] = $.extend(true, {}, $scope.product[key+1]);
         $scope.productCode[key] = $scope.productCode[key+1];

         }

         $scope.product[$scope.itemlist.length] = $.extend(true, {}, $scope.productStructure);
         $scope.productCode[$scope.itemlist.length] = '';
        
        $scope.product[i].deleted = 1;
     
        $scope.checkPrice(i);


    }

    $scope.updatePaymentTerms = function(i)
    {
        if($scope.order.paymentTerms == '1')
        {
            // COD
            $scope.order.dueDate = $scope.order.deliveryDate;

        }
        else if($scope.order.paymentTerms == '2')
        {
            // Credit
            var currentDate = new Date(new Date().getTime());
            var day = currentDate.getDate();
            var month = currentDate.getMonth();
            var year = currentDate.getFullYear();

            var d = new Date(year, month + 2, 0);
            var month = d.getMonth() + 1;
            month = ("0" + month).slice(-2);
            $scope.order.dueDate = d.getFullYear() + '-' + month + '-' + d.getDate();
        }
    }

    $scope.updateDeliveryDate = function()
    {
        if($scope.order.paymentTerms == '1')
        {
            $scope.order.dueDate = $scope.order.deliveryDate
        }
        $scope.getSameDayInvoice();
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

    $scope.Countdown = function (options) {
        var timer,
            instance = this,
            seconds = options.seconds || 10,
            updateStatus = options.onUpdateStatus || function () {},
            counterEnd = options.onCounterEnd || function () {};

        function decrementCounter() {
            updateStatus(seconds);
            if (seconds === 0) {
                counterEnd();
                instance.stop();
            }
            seconds--;
        }

        this.start = function () {
            clearInterval(timer);
            timer = 0;
            seconds = options.seconds;
            timer = setInterval(decrementCounter, 1000);
        };

        this.stop = function () {
            clearInterval(timer);
        };
    }

    $scope.sm_goto = function(option)
    {
        if(option == 'myinvoice')
        {
            $location.url('/queryInvoice');
        }
        else if(option == 'editinvoice')
        {
            $location.url('/editOrder?invoiceId=' + $scope.order.invoiceNumber);
        }
        else if(option == 'newinvoice')
        {
            $window.location.reload();
        }
    }

    // set sidebar closed and body solid layout mode
    $rootScope.settings.layout.pageSidebarClosed = false;
});