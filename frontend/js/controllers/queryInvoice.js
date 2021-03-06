'use strict';

function viewInvoice(invoiceId,invoiceStatus)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.viewInvoice(invoiceId,invoiceStatus);
    });
}
function goEdit(invoiceId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.goEdit(invoiceId);
    });
}

app.controller('queryInvoiceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state) {



    $scope.invoiceIdForApprove='';
    $(document).ready(function(){
        $(document).keydown(function (e) {
            if(($state.current.name=='pendingInvoice' || $state.current.name=='queryInvoice') && $scope.invoiceIdForApprove!=''){
                if (e.keyCode == 121) //F10
                {
                  $scope.manipulate('Approve',$scope.invoiceIdForApprove);
                  $scope.invoiceIdForApprove = '';
                }
            }
        });

        $('#queryInfo').keydown(function (e) {
            if (e.keyCode == 13) { //Enter
                $scope.updateDataSet();
            }

        });

    });

    $scope.systeminfo = $rootScope.systeminfo;
	var fetchDataDelay = 500;   // milliseconds
    var fetchDataTimer;
    var querytarget = endpoint + '/queryInvoice.json';
    var reprint = endpoint + '/rePrint.json';

    $scope.invoicepaid= [];
    $scope.invoiceStructure = {
          'paid' : ''
    }
	$scope.firstload = true;
    $scope.filterData = {
        'displayName'	:	'',
        'clientId'		:	'0',
        'status'		:	'101',
        'zone'			:	'',
        'created_by'	:	'0',
        'invoiceNumber' :	'',
        'staffName' : ''
    };

    /*
    var yday;
    var year;
    var month;
    var day;

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
        $.each(res, function( key, value ) {
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

    day = ("0" + (nextDay.getDate())).slice(-2);
    month = ("0" + (nextDay.getMonth() + 1)).slice(-2);
    year = nextDay.getFullYear();


    if(today.getDay() == 0)
        yday  = ("0" + (nextDay.getDate()-2)).slice(-2);
    else
        yday  = ("0" + (nextDay.getDate()-1)).slice(-2);

    $("#deliverydate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate").datepicker( "setDate", year + '-' + month + '-' + yday);

    $("#deliverydate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate2").datepicker( "setDate", year + '-' + month + '-' + day );

    $scope.filterData.deliverydate = year+'-'+month+'-'+yday;
    $scope.filterData.deliverydate2 = year+'-'+month+'-'+day;
        });

    */


    var today = new Date();
    var plus = today.getDay() == 6 ? 3 : 2;
    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 1);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();

    var day = currentDate.getDate();
    var month = currentDate.getMonth() + 1;
    var year = currentDate.getFullYear();


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
    $("#deliverydate2").datepicker( "setDate", year + '-' + month + '-' + day );

    $scope.filterData.deliverydate = yyear+'-'+ymonth+'-'+yday;
    $scope.filterData.deliverydate2 = year+'-'+month+'-'+day;

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
    });



    $scope.checkParm = function()
    {
    	if($location.search().scope)
        {

        	var scope = $location.search().scope;
        	if(scope == "pendingOrder")
        	{
        		$scope.filterData.status = 1;
                if($location.search().date == 'today')
        		    $scope.filterData.deliverydate1 = 'today';
                else if($location.search().date == 'yesterday')
                    $scope.filterData.deliverydate1 = 'yesterday';
        		$scope.filterData.zone = '';
        	}
        	else if(scope == "rejectedOrders")
        	{
        		$scope.filterData.status = 3;
        		$scope.filterData.deliverydate1 = '-1';
        		$scope.filterData.zone = '';
        	}
        }

    	if($location.search().zone)
	    {

	    	var pos = $scope.systeminfo.availableZone.map(function(e) {
				return e.zoneId;
			  }).indexOf(parseInt($location.search().zone));

	    	$scope.filterData.zone = $scope.systeminfo.availableZone[pos];

	    	//console.log("LOG ZONE" + $scope.filterData.zone);
	    }
    }

    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;
  		$scope.checkParm();
  	}, true);

    $rootScope.$on('$locationChangeSuccess', function(){
    	$scope.checkParm();
    	$scope.updateDataSet();

	});
    /*
    $interval(function(){
    	$scope.updateDataSet();
    }, 60000)
    */

    /*
    $scope.$watch(function() {
    	return $scope.filterData;
	}, function() {
		$scope.updateDataSet();
	}, true);
    */

    $scope.clearCustomerSearch = function()
    {
        $scope.filterData = {
            'displayName'	:	'',
            'clientId'		:	'0',
            'status'		:	'0',
            'zone'			:	'',
            deliverydate : yyear+'-'+ymonth+'-'+yday,
            deliverydate2 : year+'-'+month+'-'+day,
            'created_by'	:	'0',
            'invoiceNumber' :	'',
            'staffName' : ''
        };
        $scope.updateDataSet();
    }

    $scope.$on('handleCustomerUpdate', function(){
		$scope.filterData.clientId = SharedService.clientId;
		$scope.filterData.displayName = SharedService.clientId + " (" + SharedService.clientName + ")";
		$scope.updateDataSet();
	});


    $scope.updateZone = function()
    {
    	$scope.updateDataSet();
    }

    $scope.updateDelvieryDate = function()
    {
    	$scope.updateDataSet();
    }

    $scope.updateDelvieryDate2 = function()
    {
        $scope.updateDataSet();
    }

    $scope.updateStatus = function()
    {
    	$scope.updateDataSet();
    }

   /* $scope.updateByDelay = function()
    {
    	$timeout.cancel(fetchDataTimer);
    	fetchDataTimer = $timeout(function () {
    		$scope.updateDataSet();
    	}, fetchDataDelay);
    }*/


 // --------------------- for approval modal
    $scope.toggle = function(index)
    {
    	jQuery("#cost_" + index).toggle();
    	jQuery("#controlcost_" + index).css('display', 'none');
    }

    $scope.manipulate = function(action, invoiceId)
    {
    	var approvalJson = $scope.endpoint + "/manipulateInvoiceStatus.json";

    	$http.post(approvalJson, {
    		action: "approval",
    		status:	action,
    		target:	invoiceId
    	}).success(function(data) {
    		$("#invoiceNumber_" + invoiceId).remove();
    		$scope.updateDataSet();
        });

    	$("#productDetails").modal('toggle');
    }

    $scope.goEdit = function(invoiceId)
    {
    	$location.url("/editOrder?invoiceId=" + invoiceId);
    }

    $scope.voidInvoice = function(invoiceId)
    {
    	bootbox.dialog({
            message: "刪除訂單後將不能復原，確定要刪除訂單嗎？",
            title: "刪除訂單",
            buttons: {
            	success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                  },
                danger: {
	                label: "確定刪除",
	                className: "red",
	                callback: function() {
	                	$http.post($scope.endpoint + "/voidInvoice.json", {
	                		invoiceId	:	invoiceId,
	                	}).success(function(data) {
	                		$scope.updateDataSet();
	                	});

	                	$("#productDetails").modal('hide');
	                }
              }
            }
        });
    }

    $scope.instantPrint = function(jobid)
    {
    	$http.get($scope.endpoint + "/instantPrint.json?jobId=" + jobid).success(function(data){
    		alert('已改為即時列印');
    	});
    }

    $scope.viewInvoice = function(invoiceId,invoiceStatus)
    {
    	Metronic.blockUI();
    	$http.post(querytarget, {mode: "single", invoiceId: invoiceId,invoiceStatus:invoiceStatus})
    	.success(function(res, status, headers, config){
    		$scope.nowUnixTime = Math.round(+new Date()/1000);
            $scope.invoiceinfo = res;
console.log($scope.invoiceinfo);



            $scope.invoiceinfo.invoiceStatus = parseInt($scope.invoiceinfo.invoiceStatus);
                if($scope.invoiceinfo.invoiceStatus == 1)
                    $scope.invoiceIdForApprove = invoiceId;
    		Metronic.unblockUI();
    		$("#productDetails").modal({backdrop: 'static'});

    	});
    }

    // -- unload invoice modal
    $scope.unloadInvoice = function(invoiceId)
    {
    	Metronic.blockUI();
    	$http.post(querytarget, {mode: "single", invoiceId: invoiceId})
    	.success(function(res, status, headers, config){
    		$scope.unloadinvoice = {
    			action	:	"",
    		};
    		Metronic.unblockUI();
    		$("#unloadInvoice").modal({backdrop: 'static'});

    	});
    }
    // -- submit unload invoice modal
    $scope.SubmitUnloadInvoice = function(invoiceId)
    {
    	$http.post(endpoint + "/unloadInvoice.json", {detail: $scope.unloadinvoice, invoiceId: invoiceId})
    	.success(function(res, status, headers, config){
    		alert('已更改資料');
    		$scope.viewInvoice(invoiceId);
    		$("#unloadInvoice").modal('hide');

    	});
    }

    // -- track the action in unload invoice modal
    $scope.unloadinvoice_trackaction = function()
    {
    	var action = $scope.unloadinvoice.action;
    	if(action == "cancel")
    	{
    		$("#unloadinvoice_date").css('display', 'none');
    	}
    	else if(action == "change-deliverydate")
    	{
    		$("#unloadinvoice_date").css('display', '');
			$(".date").datepicker({
	            rtl: Metronic.isRTL(),
	            orientation: "left",
	            autoclose: true
	        });
    	}
    }

    $scope.genA4Invoice = function(invoiceId){
        window.open(endpoint + "/genA4Invoice.json?invoiceId=" + invoiceId);
    }

    $scope.rePrintInvoice = function(invoiceId)
    {

        bootbox.dialog({
            message: "將會重印訂單",
            title: "重印訂單",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定重印",
                    className: "red",
                    callback: function() {
                        $http.post(reprint, {
                            invoiceId	:	invoiceId
                        }).success(function(data) {

                        });
                    }
                }
            }
        });


    }


    $scope.updateDataSet = function()
    {

        $scope.invoicepaid[0] = $.extend(true, {}, $scope.invoiceStructure);
        $scope.invoicepaid[0]['paid'] = 0;

    	Metronic.blockUI();
    	var grid = new Datatable();


    	//var info = grid.page.info();
    	if(!$scope.firstload)
		{
    		$("#datatable_ajax").dataTable().fnDestroy();
		}
    	else
		{
    		$scope.firstload = false;
		}
        grid.init({
            src: $("#datatable_ajax"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
            	Metronic.unblockUI();
            },
            onError: function (grid) {
                // execute some code on network or other general error  
            	Metronic.unblockUI();
            },
            loadingMessage: 'Loading...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options 


                "bStateSave": false, // save datatable state(pagination, sort, etc) in cookie.
                "bServerSide": true,

                "lengthMenu": [
                    [20, 50],
                    [20, 50] // change per page values here
                ],
                "pageLength": 50, // default record count per page

                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData, mode: "collection"},
            		"xhrFields": {withCredentials: true}
                },
                "language": {
                    "lengthMenu": "顯示 _MENU_ 項結果",
                    "zeroRecords": "沒有匹配結果",
                    "sEmptyTable":     "沒有匹配結果",
                    "info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
                    "infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "Processing":   "處理中...",
                    "Paginate": {
                        "First":    "首頁",
                        "Previous": "上頁",
                        "Next":     "下頁",
                        "Last":     "尾頁"
                    }
                },
                "columns": [
                    { "data": "id", "width":"8%" },
                    { "data": "deliveryDate_date", "width":"7%" },
                    { "data": "zoneId", "width":"5%" },
                    { "data": "client.customerName_chi",  "width":"15%"},
                    { "data": "amount", "width":"5%" },
                    { "data": "version", "width":"7%" },
                    { "data": "invoiceStatusText", "width":"6%" },
                    { "data": "shiftText", "width":"6%" },
                    { "data": "laststaff.name", "width":"8%" },
                    { "data": "created_at",  "width":"13%" },
                    { "data": "updated_at",  "width":"13%" },
                    { "data": "link", "width":"5%" }


                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }






});