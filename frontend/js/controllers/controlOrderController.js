'use strict';

Metronic.unblockUI();

app.controller('controlOrderController', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state) {
	
	
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
	var j = 1;
	var count = 3000;
		
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
	$scope.order = {
		deliveryDate:	year + '-' + month + '-' + day,
		dueDate		:	year + '-' + month + '-' + day,
		status		:	'2',
		referenceNumber	:	'',
		zoneId		:	'',
        defaultZoneId : '',
		zoneName	:	'',
		route		:	'',
        defaultRoute : '',
		address		:	'',
        invoiceRemark : '',
		clientId	:	'',
		paymentTerms:	'',
		discount	:	'0',
		update		:	false,
		invoiceId	:	'',
        print : 1,
        shift : '',
	};	
	$scope.productStructure = {
		dbid		:	'',
		code		:	'', 
		qty			:	1,
        productLocation : '',
		availableunit	:	[],
		unit		:	'',
		unitprice	:	0,
		unitpricerk	:	false, 
		name		:	'',
		spec		:	'',
		itemdiscount	:	0,
		totalprice	:	0,
		remark		:	'',
		approverid	:	0,
		deleted 	: 	'0',
	};
	$scope.productTimerStructure = {
		openPanel	:	'',
		closePanel	:	'',
		completedRow:	'',
	}
	$scope.order.invoiceDate = $scope.order.deliveryDate;
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


	
	$scope.$on('handleCustomerUpdate', function(){
		// received client selection broadcast. update to the invoice portlet
		
		$scope.order.clientId = SharedService.clientId;
		$scope.order.clientName = SharedService.clientName;
		$scope.order.address = SharedService.clientAddress;
		$scope.order.zoneId = SharedService.clientZoneId;
        $scope.order.defaultZoneId = SharedService.clientZoneId;
		$scope.order.zoneName = SharedService.clientZoneName;
		$scope.order.route = SharedService.clientRoute;
        $scope.order.defaultRoute = SharedService.clientRoute;
		$scope.order.discount = SharedService.clientDiscount;
        $scope.order.shift = SharedService.clientShift;
        $scope.order.invoiceRemark = SharedService.clientRemark;
		$scope.displayName = $scope.order.clientId + " (" + $scope.order.clientName + ")"; 
		
		$scope.order.paymentTerms = SharedService.clientPaymentTermId;
		$scope.updatePaymentTerms();


      //  console.log($scope.order);

		//disable changing payment terms if it is a COD client
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
            limitReachedClass: "label label-danger",
        })

        $scope.timer.selected_client = Date.now();
		
		Metronic.unblockUI();
	});


    $scope.getSameDayInvoice = function(){
        var target = endpoint + '/getClientSameDayOrder.json';

        $http.post(target, {customerId: $scope.order.clientId, dueDate:$scope.order.dueDate})
            .success(function(res, status, headers, config){
                $scope.sameDayInvoice = res;
                //console.log(res);
            });
    }
	// Recalculate the total amount if any part of the product object has been changed. 
	$scope.$watch('product', function() {
		  $scope.reCalculateTotalAmount();
		  
	}, true);

    $scope.$watch('order.discount', function() {
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
                console.log(res);
    	});
	}*/

    $scope.getLastItem = function(productId,clientId,i){

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
                });

    }
	
	$scope.reCalculateTotalAmount = function() {
		
		$scope.totalAmount = 0;
        var temp_number = 0;

		$scope.product.forEach(function(item){
			if(item.deleted == 0)
			{
				$scope.totalAmount += item.qty * item.unitprice * (100-item.itemdiscount)/100;
			}
		});
		
		$scope.totalAmount = $scope.totalAmount * (100-$scope.order.discount)/100;

        temp_number = $scope.totalAmount;

        $scope.totalAmount =temp_number.toFixed(1);


	}
	
	$scope.itemlist.forEach(function(key){	
		$scope.product[key] = $.extend(true, {}, $scope.productStructure);
		$scope.timer.product[key] = $.extend(true, {}, $scope.timerProductStructure);
	});
		
	
    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();
        
         
              
        if(!$location.search().invoiceId && !$location.search().clientId)
    	{
	        $timeout(function(){
                $('#selectclientmodel').modal('show');

                $('#selectclientmodel').on('shown.bs.modal', function () {
                    $('#keyword').focus();
                })


	        	//$('#selectclientmodel').modal({backdrop: 'static'});
	        }, 1000);
    	}
        
        $('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
        
        $('.date-picker').datepicker( "setDate" , year + '-' + month + '-' + day );
        
        
        // if it has invoice id in the url, treat that as editing invoice
        if($location.search().invoiceId)
    	{
        	// block the full page
            Metronic.blockUI({
                boxed: true,
                message: '下載資料中...',
            });
        	// get full invoice information
        	var target = endpoint + '/getSingleInvoice.json';
        	
        	$http.post(target, {invoiceId: $location.search().invoiceId})
        	.success(function(data, status, headers, config){     

        		var res = data.invoice.client;
        		var inf = data.invoice;



        		// set client information
        		$scope.order.clientId = res.customerId;
        		$scope.order.clientName = res.customerName_chi;
        		$scope.order.address = res.address_chi;

                $scope.order.deliveryDate = inf.deliveryDate_date;
                $scope.order.dueDate = inf.dueDateDate;
                $scope.order.status = inf.invoiceStatus;
console.log($scope.order.status);
                $scope.order.zoneId = res.deliveryZone;
        		$scope.order.zoneName = data.entrieinfo;
        		$scope.order.route = res.routePlanningPriority;
        		$scope.order.discount = inf.invoiceDiscount;
        		$scope.displayName = $scope.order.clientId + " (" + $scope.order.clientName + ")"; 
        		$scope.order.paymentTerms = inf.paymentTerms;
                $scope.order.shift = inf.shift;
        		$scope.order.update = true;
        		$scope.order.invoiceNumber = inf.invoiceId;
        		$scope.order.invoiceId = inf.invoiceId;
                $scope.order.invoiceRemark = inf.invoiceRemark;
        		$scope.order.referenceNumber = inf.customerRef;
        		
        		$scope.updatePaymentTerms();
        		
        		if(inf.invoiceStatus == 99)
        		{
        			$scope.allowSubmission = false;
        			
        		}
        		else
        		{
        			// load customer product, first load full db, second load invoice-items
            		$scope.loadProduct($scope.order.clientId, inf.invoice_item);      		

            		Metronic.unblockUI();
        		}
        		
        		
        		
        		
        		$timeout(function(){
        			//$(".productCodeField").inputmask("*");
    			}, 2000);
        	}); 
        	
        	
        	
        	Metronic.blockUI({
                target: '#orderportletbody',
                boxed: true,
                message: '下載資料中...'
            });
    	}
        else if($location.search().clientId)
    	{
        	var target = endpoint + '/findClientById.json';
        	
        	$http.post(target, {customerId: $location.search().clientId})
        	.success(function(res, status, headers, config){     
        		console.log(res);
        		
        		
        		// set client information
        		$scope.order.clientId = res.customerId;
        		$scope.order.clientName = res.customerName_chi;
        		$scope.order.address = res.address_chi;
        		$scope.order.zoneId = res.deliveryZone;
        		$scope.order.zoneName = res.zone.zoneName;
        		$scope.order.route = res.routePlanningPriority;
        		$scope.order.discount = res.invoiceDiscount;
        		$scope.displayName = $scope.order.clientId + " (" + $scope.order.clientName + ")"; 
        		$scope.order.paymentTerms = res.paymentTermId;
        		
        		$scope.updatePaymentTerms();	
        		
        		if(res.paymentTermId == 1)
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

        		Metronic.unblockUI();
        	});
        	
        	$scope.loadProduct($location.search().clientId);
    	}
        else
        {
        	$('#selectclientmodel').modal('show');
            $('#selectclientmodel').on('shown.bs.modal', function () {
                $('#keyword').focus();
            })
        	$scope.loadProduct($location.search().clientId);
        	
        	/*Metronic.blockUI({
                target: '#orderportletbody',
                boxed: true,
                message: '請先選擇客戶, 然後再選擇貨品'
            });*/
        }
        
        /* Register shortcut key */
        document.addEventListener('keydown', function(evt) {
			var e = window.event || evt;
			var key = e.which || e.keyCode;

			if(e.keyCode == 121)
			{

				$scope.submitOrder(1);
			}

            if(e.keyCode == 117)
            {

                $scope.submitOrder(0);
            }
			
		}, false);
        
    });
    
    $scope.loadProduct = function(customerId, defaultProduct)
    {       
        $http.post(endpoint + '/getAllProduct.json', {
        	customerId	:	customerId,
        })        
    	.success(function(res, status, headers, config) {
    		
        	$scope.retrievedProduct = res;
        	if(defaultProduct)
        	{
        		var j = 1;
        		defaultProduct.forEach(function(item) {
        			//console.log(item);
        			
        			
        			
        			$scope.productCode[j] = item.productId;
        			
        			$scope.searchProduct(j, item.productId);
        			       			
        			
        			$scope.product[j]['dbid'] = item.invoiceItemId;
        			$scope.product[j]['qty'] = item.productQty;
        			
        			$scope.product[j]['unitprice'] = item.productPrice;
                    $scope.product[j]['productLocation'] = item.productLocation;
        			$scope.product[j]['itemdiscount'] = item.productDiscount;
        			$scope.product[j]['remark'] = item.productRemark;
        			$scope.product[j]['approverid'] = item.approvedSupervisorId;
        			
        			//$scope.product[j]['unit'] = item.productQtyUnit;
        			var pos = $scope.product[j].availableunit.map(function(e) { 
        					return e.value; 
    					  }).indexOf(item.productQtyUnit);
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
    
   
    
    $scope.searchProduct = function(i, code) {

        if(($scope.order.status != '97') && (code =='Z002')){
            return false;
        }
		var input = $("#productCode_" + i);
		//console.log($scope.retrievedProduct, code.toUpperCase());
		if($scope.retrievedProduct[code.toUpperCase()])
		{
			var item = $scope.retrievedProduct[code.toUpperCase()];
			
			// update product name
			$("#productCode_"+i).val(code);
			$scope.product[i].code = code.toUpperCase();
			$scope.product[i].name = item.productName_chi;
            $scope.product[i].productLocation = item.productLocation;
			$scope.product[i].spec = '(' + item.productPacking_carton + '*' + item.productPacking_inner + '*' + item.productPacking_unit + '*' + item.productPacking_size + ')';
			$scope.product[i].itemdiscount = item.itemdiscount;
			
			// enable product qty
			//$("#spinner_" + i).spinner({value:0.5, step: 0.5, min: 0, max: 999});
			
			// set product unit
			var availableunit = [];
			//console.log(item);
			
			if(item.productStdPrice_unit > 0)
			{
				//$("#unit_" + i).prepend('<option value="unit">Unit</option>');
				availableunit = availableunit.concat([{value: 'unit', label: item.productPackingName_unit}]);
			}			
			if(item.productStdPrice_inner > 0)
			{
				//$("#unit_" + i).prepend('<option value="inner">Inner</option>');
				availableunit = availableunit.concat([{value: 'inner', label: item.productPackingName_inner}]);
			}			
			if(item.productStdPrice_carton > 0)
			{
				//$("#unit_" + i).prepend('<option value="0">Carton</option>');
				availableunit = availableunit.concat([{value: 'carton', label: item.productPackingName_carton}]);		
			}			 
			//$scope.product[i].availableunit = availableunit.reverse();
            $scope.product[i].availableunit = availableunit;
			$scope.product[i].unit = $scope.product[i].availableunit[0];
			$scope.updateStandardPrice(i);

            if(!$location.search().invoiceId)
                $scope.getLastItem(code,$scope.order.clientId,i);

          // console.log($scope.lastitem);

           // $scope.lastItemUnit = '5';

			//--  check if last time invoice
		/*	if($scope.lastinvoice[code.toUpperCase()])
			{
				
				//var linv = $scope.lastinvoice[code.toUpperCase()][0];
				
				// unit
				
				//var pos = $scope.product[i]['availableunit'].map(function(e) {
				//	return e.value;
				//  }).indexOf(linv.productQtyUnit);
				
				//$scope.product[i]['unit'] = $scope.product[i]['availableunit'][pos];
			
				// qty
				
				//$scope.product[i].qty = linv.productQty;

				// price
				//$scope.product[i].unitprice = linv.productPrice;
				//$scope.checkPrice(i);
			}*/
					
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

    
    $scope.updateStandardPrice = function (i)
    {
    	
    	var code = $scope.product[i]['code'];
    	var item = $scope.retrievedProduct[code];
    	var unit = $scope.product[i]['unit'].value;
    	
    	// *** to be updated - non-hard-coding
    	if(unit == 'carton')
    		$scope.product[i]['unitprice'] = Number(item.productStdPrice_carton);
    	else if(unit == 'inner')
    		$scope.product[i]['unitprice'] = Number(item.productStdPrice_inner);
    	else if(unit == 'unit')
    		$scope.product[i]['unitprice'] = Number(item.productStdPrice_unit);
    	
    	$("#unitprice_" + i).removeAttr('disabled');
    	

    }
    
    $scope.checkPrice = function(i)
    {
    	var code = $scope.product[i]['code'];
    	var item = $scope.retrievedProduct[code];
    	//console.log($scope.product[i]);
    	var unit = $scope.product[i]['unit']['value'];  
    	
    	
    	$scope.submitButtonText = '提交 (F10)';
    	$scope.submitButtonColor = 'blue';
    
    	/*
    	if(unit == 'carton')
    		var stdprice = Number(item.productStdPrice_carton);
    	else if(unit == 'inner')
    		var stdprice = Number(item.productStdPrice_inner);
    	else if(unit == 'unit')
    		var stdprice = Number(item.productStdPrice_unit);
    	*/
    	
    	var stdprice = Number(item.productStdPrice[unit]);
    	var minprice = Number(item.productMinPrice[unit]);
    	
    	// check if number
    	if(isNaN($scope.product[i]['unitprice']))
    	{
    		$scope.product[i]['unitprice'] = stdprice;
    	}
    	
    	$("#requireapprove_" + i).remove();
    	
    	var saleprice = $scope.product[i]['unitprice'] * (100-$scope.product[i]['itemdiscount'])/100;
    	
    	// if saleprice < std price, need approval
    	if(saleprice < stdprice && $scope.product[i].approverid == 0 && $scope.product[i].deleted == 0)
		{
    		$("#unitpricediv_" + i).prepend('<i id="requireapprove_'+i+'" class="fa fa-info-circle" style="color:red;"></i>');
    		$scope.submitButtonText = '提交 (需批核) (F10)'; 
    		$scope.submitButtonColor = 'green';
		}
    	
    	// if saleprice < min price, deny submission
    	/*
    	if(saleprice < minprice && minprice > 0)
    	{
    		$("#unitpricediv_" + i).prepend('<i id="requireapprove_'+i+'" class="" style="color:red;">X</i>');
    		$scope.allowSubmission = false;
    	} 
    	*/
    	
    	// if he got permission of bypassing approval, eventually display no approval button
    	if($scope.systeminfo.permission.allow_by_pass_invoice_approval == true)
    	{
    		$scope.submitButtonText = '提交';
        	$scope.submitButtonColor = 'blue'; 
    	} 
    }

    $scope.updateDiscount = function()
    {

        $scope.reCalculateTotalAmount();
    }

    $scope.updateQty = function(i)
    {
//var org_qty = $scope.product[i]['qty'];
        // check if number
        var qty = $scope.product[i]['qty'];

        if(isNaN(qty))
        {
            $scope.product[i]['qty'] = 1;
        }

    	/*
    	var code = $scope.product[i]['code'];
    	var item = $scope.retrievedProduct[code];
    	if(item.productPackingAllowDecimal == 0)
		{
    		if(
        			($scope.product[i].qty != '0') &&
        			($scope.product[i].qty != '0.') &&
        			($scope.product[i].qty != '0.5') &&
        			($scope.product[i].qty != '.') &&
        			($scope.product[i].qty != '.5')
        	)
    		{

    	    	if(($scope.product[i].qty / 0.5) % 1 === 0)
    	    	{
    	    		$scope.product[i].qty = $scope.product[i].qty.replace(/^0+/, '');
    	    	}
    	    	else
    	    	{
    	    		// automatically round up 
    	    		$scope.product[i].qty = Math.ceil($scope.product[i].qty);
    	    	}
    	    	
    		}
		}
    	*/
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
    	// console.log(interval, qty);
    	//console.log(qty, unit, interval);
    	if(qty % interval > 0 )
    	{
    		$scope.product[i]['qty'] = Math.ceil( qty / interval ) * interval;
    	}
    }

    $scope.statusChange = function(){

        if($scope.order.status == '98')
            $scope.order.invoiceRemark = '退貨單'
        if($scope.order.status == '97')
            $scope.order.invoiceRemark = '退款單';

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
        
        console.log($scope.order);

        if(!$scope.order.invoiceDate || !$scope.order.deliveryDate || !$scope.order.dueDate || !$scope.order.status || !$scope.order.address || !$scope.order.clientId)
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


            $scope.order.print = v;
        	$http.post(
            	endpoint + '/placeOrder.json', {
            	product : $scope.product,
            	order : $scope.order,
            	timer	:	$scope.timer,
            }).
            success(function(res, status, headers, config) {
                    console.log(res);

            	if(res.result == true)
            	{
                    $scope.statustext = $scope.systeminfo.invoiceStatus[res.status].descriptionChinese;
            		if(res.status == 2)
            		{
            			
            			Metronic.alert({
                    	    container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                    	    place: 'prepend', // append or prepent in container 
                    	    type: 'success',  // alert's type
                    	    message: '<span style="font-size:16px;">已新增訂單 編號: <strong>' + res.invoiceNumber + '</strong></span>',  // alert's message
                    	    close: true, // make alert closable
                    	    reset: true, // close all previouse alerts first
                    	    focus: true, // auto scroll to the alert after shown
                    	    closeInSeconds: 0, // auto close after defined seconds
                    	    icon: '' // put icon before the message
                    	});
            		}
            		else if(res.status == 1)
            		{
            			
            			Metronic.alert({
                    	    container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                    	    place: 'prepend', // append or prepent in container 
                    	    type: 'info',  // alert's type
                    	    message: '<span style="font-size:16px;">訂單需要批核  編號: <strong>' + res.invoiceNumber + '</strong></span>',  // alert's message
                    	    close: true, // make alert closable
                    	    reset: true, // close all previouse alerts first
                    	    focus: true, // auto scroll to the alert after shown
                    	    closeInSeconds: 0, // auto close after defined seconds
                    	    icon: 'warning' // put icon before the message
                    	});
            		}

                    if(res.action == 'update'){
                       $state.go("queryInvoice", {}, {reload: true});
                    }else{
                        $("#successModal").modal('toggle');

                        document.addEventListener('keydown', function(evt) {
                            var e = window.event || evt;
                            var key = e.which || e.keyCode;
                            if(key == 27)
                            {
                                $scope.counter.stop();
                            }
                        }, false);

                        $scope.counter = new $scope.Countdown({
                            seconds:1,  // number of seconds to count down
                            onUpdateStatus: function(sec){
                                $scope.countdown = sec;
                            }, // callback for each second
                            onCounterEnd: function(){
                                // $window.location.reload();
                                //consolg.log('123');
                              $state.go("newOrder", {}, {reload: true});
                            } // final action
                        });

                        $scope.counter.start();

                        $scope.order.invoiceNumber = res.invoiceNumber;
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
    	console.log($scope.product.length);
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
    	/*
    	for(var key = i; key<=$scope.itemlist.length; key++)
		{
    		
    		$scope.product[key] = $.extend(true, {}, $scope.product[key+1]);
    		$scope.productCode[key] = $scope.productCode[key+1];
    		
		}

    	$scope.product[$scope.itemlist.length] = $.extend(true, {}, $scope.productStructure);
    	$scope.productCode[$scope.itemlist.length] = '';
    	*/
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
    		//console.log(d, $scope.order.dueDate);
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