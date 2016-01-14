'use strict';

Metronic.unblockUI();



app.controller('salesReturnController', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {

    $scope.submited = false;

    /* Register shortcut key */
    $(document).ready(function(){
        var csuggestion = 1;
        $('#order_form').keydown(function (e) {

         /*  if (e.keyCode == 40) //down
            {
                e.preventDefault();
                csuggestion++;
                $("#productCode_" + csuggestion).focus();
            }*/

            if(!$scope.submited){
                if (e.keyCode == 121) {
                    $scope.preSubmitOrder(1);
                    $scope.submited = true;
                }
                if (e.keyCode == 117) {
                    $scope.preSubmitOrder(0);
                    $scope.submited = true;
                }
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



    $scope.order = {
        deliveryDate: '',
        dueDate:'',
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
        amount: 0
    };

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


            $scope.order.deliveryDate = year + '-' + month + '-' + day;
            $scope.order.dueDate = year + '-' + month + '-' + day;
            $scope.order.invoiceDate = $scope.order.deliveryDate;
        });

    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);

    $scope.sameDayInvoice = '';
    $scope.productCode = [];
    $scope.itemlist = [1, 2, 3];
    $scope.retrievedProduct = [];
    $scope.allLastItemPrice = [];
    $scope.product = [];
    $scope.displayName = "";
    $scope.totalAmount = 0;
    $scope.allowSubmission = true;
    $scope.recentProduct = [];
    $scope.editable_row = "";
    $scope.lastinvoice = [];
    $scope.lastitem = [];

    $scope.productStructure = {
        dbid		:	'',
        code		:	'',
        qty			:	1,
        damage_qty	:	0,
        productLocation : '',
        availableunit	:	[],
        unit		:	'',
        damage_unit : '',
        unitprice	:	0,
        unitpricerk	:	false,
        name		:	'',
        spec		:	'',
        itemdiscount	:	0,
        totalprice	:	0,
        remark		:	'',
        approverid	:	0,
        deleted 	: 	'0',
        productPacking : []
    };
    $scope.productTimerStructure = {
        openPanel	:	'',
        closePanel	:	'',
        completedRow:	''
    }

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

    $scope.$on('handleCustomerUpdate', function(){
        // received client selection broadcast. update to the invoice portlet
        $scope.an=true;
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
            limitReachedClass: "label label-danger"
        })

        $scope.timer.selected_client = Date.now();
        Metronic.unblockUI();

    });



    $scope.getSameDayInvoice = function(){
        var target = endpoint + '/getClientSameDayOrder.json';

        $http.post(target, {customerId: $scope.order.clientId, deliveryDate:$scope.order.deliveryDate})
            .success(function(res, status, headers, config){
                $scope.sameDayInvoice = res;
                console.log(res);
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
if(!$scope.systeminfo.permission.sa_up)
        $http.post(endpoint + '/getNoOfOweInvoices.json', {customerId: $scope.order.clientId})
            .success(function(res){
                if(res > 5){
                    Metronic.blockUI({
                        target: '#orderportletbody',
                        boxed: true,
                        message: '此客戶的欠單數量已超過5張,不能開單'
                    });
                }else{
                    $scope.getSameDayInvoice();
                    $scope.getAllLastItemPrice($scope.order.clientId);
                }
            });
else{
    $scope.getSameDayInvoice();
    $scope.getAllLastItemPrice($scope.order.clientId);
}

        //block the order portlet




        // load last time invoice
        //$scope.getClientLastInvoice($scope.order.clientId);

    });

    $scope.getAllLastItemPrice = function(customerId){
        var target = endpoint + '/getAllLastItemPrice.json';
        $scope.allowSubmission = false;
        $("#productCode_1").attr('disabled', 'true');
        $http.post(target, {customerId: customerId})
            .success(function(res){
                $scope.allLastItemPrice = res;
                $scope.allowSubmission = true;
                $("#productCode_1").removeAttr('disabled');
                $('#productCode_1').focus();
            });
    }

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


    $scope.getLastItem = function(productId,i,q){

        var target = endpoint + '/getLastItem.json';
        $http.post(target, {productId: productId, customerId: clientId})
            .success(function (res, status, headers, config) {
                $scope.lastitem = res;

                if(res.qty > 0){
                    $scope.product[i].unitprice = res.price;
                    var pos = $scope.product[i]['availableunit'].map(function(e) {
                        return e.value;
                    }).indexOf(res.unit_level);
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

    $scope.reCalculateTotalAmount = function() {

        $scope.totalAmount = 0;

        $scope.product.forEach(function(item){
            if(item.deleted == 0)
            {
                $scope.totalAmount += item.qty * item.unitprice * (100-item.itemdiscount)/100;
            }
        });

        $scope.totalAmount = $scope.totalAmount * (100-$scope.order.discount)/100;

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

        if($stateParams.action != '') {
            Metronic.alert({
                container: '#salesReturn', // alerts parent container(by default placed after the page breadcrumbs)
                place: 'prepend', // append or prepent in container
                type: 'success',  // alert's type
                message: '<span style="font-size:16px;">車號:'+$stateParams.action+':提交成功</span>',  // alert's message
                close: true, // make alert closable
                reset: true, // close all previouse alerts first
                focus: true, // auto scroll to the alert after shown
                closeInSeconds: 0, // auto close after defined seconds
                icon: '' // put icon before the message
            });
        }

        $scope.loadProduct($location.search().clientId);
    });

    $scope.loadProduct = function(defaultProduct)
    {
        $http.post(endpoint + '/getAllProduct.json', {
            productList : defaultProduct,
        })
            .success(function(res, status, headers, config) {

                $scope.retrievedProduct = res;

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
            $scope.product[i].productLocation = item.productLocation;
            $scope.product[i].spec = '(' + item.productPacking_carton + '*' + item.productPacking_inner + '*' + item.productPacking_unit + '*' + item.productPacking_size + ')';
            $scope.product[i].itemdiscount = item.itemdiscount;
            $scope.product[i].productPacking = item.productPacking;

            // enable product qty
            //$("#spinner_" + i).spinner({value:0.5, step: 0.5, min: 0, max: 999});

            // set product unit
            var availableunit = [];
            //console.log(item);

            if($scope.order.status == '98' || $scope.order.status == '96' || $scope.order.status == '97') {
                if(item.productPackingName_unit != '')
                    availableunit = availableunit.concat([{value: 'unit', label: item.productPackingName_unit}]);
                if(item.productPackingName_inner != '')
                    availableunit = availableunit.concat([{value: 'inner', label: item.productPackingName_inner}]);
                if(item.productPackingName_carton != '')
                    availableunit = availableunit.concat([{value: 'carton', label: item.productPackingName_carton}]);
            }else{
                if(item.productPackingInterval_unit > 0)
                {
                    //$("#unit_" + i).prepend('<option value="unit">Unit</option>');
                    availableunit = availableunit.concat([{value: 'unit', label: item.productPackingName_unit}]);
                }
                if(item.productPackingInterval_inner > 0)
                {
                    //$("#unit_" + i).prepend('<option value="inner">Inner</option>');
                    availableunit = availableunit.concat([{value: 'inner', label: item.productPackingName_inner}]);
                }
                if(item.productPackingInterval_carton > 0)
                {
                    //$("#unit_" + i).prepend('<option value="0">Carton</option>');
                    availableunit = availableunit.concat([{value: 'carton', label: item.productPackingName_carton}]);
                }
            }



            //$scope.product[i].availableunit = availableunit.reverse();
            $scope.product[i].availableunit = availableunit;
            $scope.product[i].unit = $scope.product[i].availableunit[0];
            $scope.product[i].damage_unit = $scope.product[i].availableunit[0];
            $scope.updateStandardPrice(i);

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
                if($scope.order.status != '97' && $scope.order.status != '96')
                    $("#unitprice_" + i).removeAttr('disabled');
            }, 500);

            if(flag != 'unload') {
                // $scope.getLastItem(code, $scope.order.clientId, i, 0);
                if($scope.allLastItemPrice[code.toUpperCase()]) {
                    $scope.lastitem = $scope.allLastItemPrice[code.toUpperCase()];
                   // console.log($scope.lastitem);
                    if( $scope.lastitem.qty > 0){
                        if($scope.order.status == '97' || $scope.order.status == '96'){
                             $scope.product[i].unitprice = 0;
                        }else
                            $scope.product[i].unitprice =  $scope.lastitem.price;

                            var pos = $scope.product[i]['availableunit'].map(function(e) {
                            return e.value;
                        }).indexOf( $scope.lastitem.unit_level);
                        $scope.product[i]['unit'] = $scope.product[i]['availableunit'][pos];
                        $scope.checkPrice(i);
                    }
                }else{
                    $scope.lastitem = [];
                }


            }

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


        if($scope.order.status == '97' || $scope.order.status == '96'){
            $scope.product[i]['unitprice'] = 0;
        }else{
            // *** to be updated - non-hard-coding
            if(unit == 'carton')
                $scope.product[i]['unitprice'] = Number(item.productStdPrice_carton);
            else if(unit == 'inner')
                $scope.product[i]['unitprice'] = Number(item.productStdPrice_inner);
            else if(unit == 'unit')
                $scope.product[i]['unitprice'] = Number(item.productStdPrice_unit);
            $("#unitprice_" + i).removeAttr('disabled');
        }
    }

    $scope.updateZone = function(){
        $scope.getSameDayReturn();
    }

    $scope.updateDeliveryDate = function()
    {
        $scope.getSameDayReturn();
    }
    $scope.getSameDayReturn = function(){

        var target = endpoint + '/getZoneSameDayReturn.json';

        $http.post(target, {zoneId: $scope.order.zone, deliveryDate:$scope.order.deliveryDate})
            .success(function(res, status, headers, config){
               // $scope.sameDayReturn = res;
if(res==1)
                Metronic.alert({
                    container: '#secondSection', // alerts parent container(by default placed after the page breadcrumbs)
                    place: 'prepend', // append or prepent in container
                    type: 'danger',  // alert's type
                    message: '回貨日期:'+$scope.order.deliveryDate+' 車號:'+$scope.order.zone.zoneId,  // alert's message
                    close: true, // make alert closable
                    reset: true, // close all previouse alerts first
                    focus: true, // auto scroll to the alert after shown
                    closeInSeconds: 0, // auto close after defined seconds
                    icon: 'warning' // put icon before the message
                });
else
    Metronic.alert({
        reset: true // close all previouse alerts first
    });
            });
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




    $scope.preSubmitOrder = function(v){
        //   var currentDate = new Date(new Date().getTime());
        //    var day = currentDate.getDate();
        if($scope.order.invoiceNumber !='' && $scope.order.invoiceId == ''){
            $scope.submitOrder(v);
        }
    }

    $scope.submitOrder = function(v)
    {
      //  console.log($scope.product);
     //   return false;
        var generalError = false;


        if(!$scope.allowSubmission)
        {
            Alert('submission Disabled');
            generalError = true;
        }

       // $scope.allowSubmission = false;


        if(!$scope.order.zone || !$scope.order.deliveryDate)
        {
            Metronic.alert({
                container: '#salesReturn', // alerts parent container(by default placed after the page breadcrumbs)
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
            $scope.order.amount = $scope.totalAmount;
            $scope.order.print = v;
            $http.post(
                endpoint + '/placeReturnOrder.json', {
                    product : $scope.product,
                    order : $scope.order
                }).
                success(function(res, status, headers, config) {

                    if(res.result == true)
                    {
                        //  $scope.an=false;
                        //  $scope.statustext = $scope.systeminfo.invoiceStatus[res.status].descriptionChinese;


                            $state.go("salesReturn",{action:res.zoneId},{ reload: true, inherit: false, notify: true });

                    }
                    else if(res.result == false)
                    {
                        Metronic.alert({
                            container: '#salesReturn', // alerts parent container(by default placed after the page breadcrumbs)
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