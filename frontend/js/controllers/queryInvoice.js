'use strict';

// due to datatable limitation, call scope function from outside javascript function
// ref: http://jsfiddle.net/austinnoronha/nukRe/light/
function viewInvoice(invoiceId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.viewInvoice(invoiceId);
    });
}

document.addEventListener('keydown', function(evt) {
    var e = window.event || evt;
    var key = e.which || e.keyCode;

    if(e.keyCode == 115)
    {
        window.location.replace("/#/newOrder");
    }

}, false);

app.controller('queryInvoiceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	
	var fetchDataDelay = 500;   // milliseconds
    var fetchDataTimer;
    var querytarget = endpoint + '/queryInvoice.json';
    var reprint = endpoint + '/rePrint.json';
	
	$scope.firstload = true;

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

    var yday = nextDay.getDate()-1;
	$scope.filterData = {
		'displayName'	:	'',
		'clientId'		:	'0',
		'status'		:	'0',
		'zone'			:	'',
        deliverydate : year+'-'+month+'-'+yday,
        deliverydate2 : year+'-'+month+'-'+day,
		'created_by'	:	'0',
		'invoiceNumber' :	''
	};
	
    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;

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

    });

    $scope.clearCustomerSearch = function()
    {
        $scope.filterData = {
            'displayName'	:	'',
            'clientId'		:	'0',
            'status'		:	'0',
            'zone'			:	'',
            deliverydate : year+'-'+month+'-'+yday,
            deliverydate2 : year+'-'+month+'-'+day,
            'created_by'	:	'0',
            'invoiceNumber' :	'',
        };
    	$scope.updateDataSet();
    }
    
    $scope.checkParm = function()
    {
    	if($location.search().scope)
        {
    		
        	var scope = $location.search().scope;
        	if(scope == "pendingOrder")
        	{
        		$scope.filterData.status = 1;
        		$scope.filterData.deliverydate1 = '-1';
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
			  }).indexOf($location.search().zone);
	    	 
	    	$scope.filterData.zone = $scope.systeminfo.availableZone[pos];
	    	//console.log("LOG ZONE" + $scope.filterData.zone);
	    }
    }
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;
  		$scope.checkParm();
  		$scope.updateDataSet();
  		
  		
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
    
    $scope.updateInvoiceNumber = function()
    {
    	$timeout.cancel(fetchDataTimer);
    	fetchDataTimer = $timeout(function () {
    		$scope.updateDataSet();
    	}, fetchDataDelay);
    }
    
    
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
    
    $scope.viewInvoice = function(invoiceId)
    {
    	Metronic.blockUI();
    	$http.post(querytarget, {mode: "single", invoiceId: invoiceId})
    	.success(function(res, status, headers, config){    
    		$scope.nowUnixTime = Math.round(+new Date()/1000);
    		
    		$scope.invoiceinfo = res;
                $scope.invoiceinfo.invoiceStatus = parseInt($scope.invoiceinfo.invoiceStatus);

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
    
    
    $scope.rePrintInvoice = function(invoiceId)
    {
    	//alert('已排序到列印隊伍上');
        $http.post(reprint, {
            invoiceId	:	invoiceId
        }).success(function(data) {

        });
    }

    
    $scope.updateDataSet = function()
    {
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

                "lengthMenu": [
                    [20, 50],
                    [20, 50] // change per page values here
                ],
                "pageLength": 50, // default record count per page
                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData, mode: "collection"},
            		"xhrFields": {withCredentials: true},
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
                    { "data": "invoiceId" },
                    { "data": "deliveryDate_date" },
                    { "data": "zoneId" },
                    { "data": "routePlanningPriority" },
                    { "data": "client.customerName_chi" },
                    { "data": "amount" },
                    { "data": "invoiceStatusText" },
                    { "data": "version" },
                    { "data": "shiftText" },
                    { "data": "staff.name" },
                    { "data": "createdat_full" },
                    { "data": "link" }

                            
                ],           
                
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }
    
    
    
    
    
    
});