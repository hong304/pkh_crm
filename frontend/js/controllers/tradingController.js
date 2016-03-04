'use strict';

app.controller('tradingController', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {

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
    $scope.itemlist = [1];
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


        Metronic.unblockUI();

    });


    // Recalculate the total amount if any part of the product object has been changed.
    $scope.$watch('product', function() {
        $scope.reCalculateTotalAmount();

    }, true);

    $scope.$watch('order.discount', function() {
        $scope.reCalculateTotalAmount();
    }, true);

    $scope.$on('doneCustomerUpdate', function(){

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

    $scope.$on('$viewContentLoaded', function() {
        // initialize core components
        Metronic.initAjax();

        if($location.search().invoiceId){





            $scope.order.clientId = res.customerId;
            $scope.order.clientName = res.customerName_chi;
            $scope.order.address = res.address_chi;

            $scope.order.deliveryDate = inf.deliveryDate_date;
            $scope.order.dueDate = inf.dueDateDate;
            $scope.order.status = inf.invoiceStatus;




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
            $scope.getAllLastItemPrice($scope.order.clientId);
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


        }else if($location.search().container_id)
        {



          /*  Metronic.blockUI({
                target: '#orderportletbody',
                boxed: true,
                message: '載入產品中...'
            });*/

            // block the full page
            Metronic.blockUI({
                boxed: true,
                message: '下載資料中...'
            });
            // get full invoice information
            var target = endpoint + '/getfullContainerInfo.json';

            $http.post(target, {container_id:$location.search().container_id})
                .success(function(res, status, headers, config){


                    console.log(res);
                    $scope.order.poCode = res.shipping.purchase_order.poCode;
                    $scope.order.containerNumber = res.containerId;
                    $scope.order.supplierName = res.shipping.supplier.supplierName;

                    var i = 1;
                    res.containerproduct.forEach(function(item) {
                        $scope.product[i] = $.extend(true, {}, $scope.productStructure);
                        $scope.product[i].itemdiscount = item.itemdiscount;
                        $scope.product[i].productPacking = item.productPacking;
                        $scope.product[i].qty = item.qty;
                        $scope.product[i].unit = item.unit;
                        $scope.product[i].unitName = item.unitName;
                        $scope.product[i].itemdiscount =0;
                        $scope.productCode[i] = item.productId;
                        $scope.product[i].name = item.product.productName_chi;
                        $scope.product[i].spec = '(' + item.product.productPacking_carton + '*' + item.product.productPacking_inner + '*' + item.product.productPacking_unit + '*' + item.product.productPacking_size + ')';

                        i++;
                        $scope.itemlist.push(i);
                    });


                    Metronic.unblockUI();

                });




        }



    });


 /*   $scope.preSubmitOrder = function(v){

        //   var currentDate = new Date(new Date().getTime());
        //    var day = currentDate.getDate();

        var currentDate = new Date(new Date().getTime());
        var day = currentDate.getDate();
        day = ("0" + day).slice(-2);
        var month = currentDate.getMonth()+1;
        month = ("0" + month).slice(-2);
        var year = currentDate.getFullYear();

        var dates = ""+year+month+day;

        var orderDate = new Date($scope.order.deliveryDate);
        var day = orderDate.getDate();
        day = ("0" + day).slice(-2);
        var month = orderDate.getMonth()+1;
        month = ("0" + month).slice(-2);
        var year = orderDate.getFullYear();

        var order_dates = ""+year+month+day;

        if(v){
            var bool = (Number(order_dates) < Number(dates));
            var msg = 'F10! 此訂單將會被列印';
        }else{
            var bool = (Number(order_dates) >= Number(dates));
            var msg = 'F6! 此訂單將不會被列印';
        }
        if(bool)
            bootbox.dialog({
                message: msg,
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
*/
    $scope.submitOrder = function()
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
            $scope.reCalculateTotalAmount();

            $scope.order.amount = $scope.totalAmount;
            if(isNaN(parseFloat($scope.order.amount))){
                Metronic.alert({
                    container: '#orderinfo', // alerts parent container(by default placed after the page breadcrumbs)
                    place: 'prepend', // append or prepent in container
                    type: 'danger',  // alert's type
                    message: '沒有效的訂單金額',  // alert's message
                    close: true, // make alert closable
                    reset: true, // close all previouse alerts first
                    focus: true, // auto scroll to the alert after shown
                    closeInSeconds: 0, // auto close after defined seconds
                    icon: 'warning' // put icon before the message
                });
                $scope.allowSubmission = true;
                return false;
            }


            $http.post(
                endpoint + '/placeTradingOrder.json', {
                    product : $scope.product,
                    order : $scope.order,
                }).
                success(function(res, status, headers, config) {

                    if(res.result == true)
                    {
                        $scope.an=false;
                        // $scope.statustext = $scope.systeminfo.invoiceStatus[res.status].descriptionChinese;

                        if(res.action == 'update'){
                            $state.go("queryInvoice", {}, {reload: true});
                        }else{
                            $state.go("newOrder",{action:'success',instatus:res.status ,invoiceNumber:res.invoiceNumber},{ reload: true, inherit: false, notify: true });

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
                    console.log(res);
                    // called asynchronously if an error occurs
                    // or server returns response with an error status.
                    //$scope.allowSubmission = true;

                });
        }


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