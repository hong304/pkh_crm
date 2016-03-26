'use strict';


function editStaff(StaffId, RoleId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editStaff(StaffId);
    });
}

function delCustomer(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {

        bootbox.dialog({
            message: "刪除客戶後將不能復原，確定要刪除客戶嗎？",
            title: "刪除客戶",
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
                        scope.delCustomer(id);
                    }
                }
            }
        });

    });
}

app.controller('staffMaintenanceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval, $state) {
	
	var querytarget = endpoint + '/queryStaff.json';
	var iutarget = endpoint + '/manipulateStaff.json';
    var addStaffquery = endpoint + '/UserManipulation.json';
    var fetchDataTimer;
    var fetchDataDelay = 500;   // milliseconds

	$scope.info_def = {
			'id'		:	false,
			'username'	:	'',
			'password'	:	'',
			'name'	:	'',
			'disabled'	:	'0',
			'permission'	:	'',
			'group'	:	''
	};

    $scope.filterData = {
        'ceritera' : ''
    };
	
	$scope.info = {};
	$scope.zone = {};
	$scope.permission = {};
	
    $scope.$on('$viewContentLoaded', function() {   
    	
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;   
        $scope.updateDataSet();
        $scope.loadStaffProfile();
        
        
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
  	}, true);
       
    $scope.editStaff = function(StaffId)
    {
    	/*
    	$scope.submitbtn = true;
    	$http.post(querytarget, {mode: "single", StaffId: StaffId, RoleId: RoleId})
    	.success(function(res, status, headers, config){    
    		$scope.info = $.extend(true, {}, $scope.info_def);
    		$scope.info = res.account;
    		$scope.permission = res.permission;
    		$scope.zone = res.zone;
    		
        	
    		$("#StaffFormModal").modal({backdrop: 'static'});
    		$('.date-picker').datepicker({
                rtl: Metronic.isRTL(),
                orientation: "left",
                autoclose: true
            });
    	});
    	*/
    	
    	// $state.go("userProfile", {userid: StaffId}, {reload: true});
    	$location.url("/user-maintenance/form?staffId=" + StaffId);
    	
    	
    }

    $scope.delCustomer = function(id){
        $http({
            method: 'POST',
            url: iutarget,
            data: {mode:'del',customer_id:id}
        }).success(function () {
            $scope.del = true;
            $scope.updateDataSet();
        });
    }

    // ---- profile page
    $scope.loadStaffProfile = function()
    {
    	$scope.staffId = $location.search().staffId;
    	
    	$http.post(querytarget, {mode: "single", StaffId: $scope.staffId}) //queryStaff.json
    	.success(function(res, status, headers, config){    
    		
    		
    		$scope.user = res.account;
    		$scope.availableroles = res.available_roles;

                var status = [];
                status = status.concat([{value: 0, label: "正常"}]);
                status = status.concat([{value: 1, label: "暫停"}]);
                $scope.status = status;

                var pos = status.map(function(e) {
                    return e.value;
                }).indexOf(parseInt(res.account.disabled));

                $scope.user.status = status[pos];


    		var pos = $scope.availableroles.map(function(e) { 
				return e.id; 
			  }).indexOf(res.account.roles[0].id);
    		$scope.user.roles = $scope.availableroles[pos];
    		
    		$scope.zones = res.zones;
    		
    		$scope.loginrecords = res.loginrecords;
    		
    	});
    	
    	
    }
    $scope.saveProfile = function() {
        $scope.staffId = $location.search().staffId;
        if ($scope.user.password && $scope.user.password.length < 8)
               alert('密碼太短');
                    else
                    $http.post(iutarget, {StaffId: $scope.staffId, account: $scope.user, zone: $scope.zones})
                        .success(function(res, status, headers, config){
                            alert('已更新用戶資料');
                        });



    }
    
    // -- kick user
    $scope.logout = function(auditId)
    {
    	//console.log(auditId);
    	$http.post(querytarget, {mode: "forcelogout", hash: auditId})
    	.success(function(res, status, headers, config){    
    		$("#forcebtn_" + auditId).css('display', 'none');
    		alert('已登出及解鎖');
                $scope.loadStaffProfile();
    	});
    }
    
    
    $scope.addStaff = function()
    {

      //  console.log($scope.systeminfo);

    	$scope.submitbtn = true;
    	$scope.info = $.extend(true, {}, $scope.info_def);
    	
    	var groups = [];
    	//groups = groups.concat([{value: '3', label: "Manager"}]);
        //groups = groups.concat([{value: '5', label: "Supervisor"}]);
    	//groups = groups.concat([{value: '2', label: "System Administrator"}]);
    	groups = groups.concat([{value: '4', label: "Telesales"}]);
    	
    	$scope.groups = groups;
    	
    	var disabled = [];
    	disabled = groups.concat([{value: '0', label: "Normal"}]);
    	disabled = groups.concat([{value: '1', label: "Suspended"}]);
    	
    	$scope.disabled = disabled;

        $scope.zones = $scope.systeminfo.availableZone;

    	$("#StaffFormModal").modal({backdrop: 'static'});
    	$('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
    	
    }
    
    $scope.submitStaffForm = function()
    {
       // console.log($scope.zones);

    	if($scope.info.username.length < 3 ||
    			$scope.info.name == "" || $scope.info.password.length < 8 || typeof($scope.info.groups) == 'undefined')
    		alert('請輸入所需資料');
    	else
    	{

    		$http.post(addStaffquery, {info: $scope.info, zone: $scope.zones})
        	.success(function(res, status, headers, config){    
        		//$scope.submitbtn = false;
        		if(res.mode == 'error')
    			{
    			
    			}
        		else if(res.mode == 'update')
        		{
        			$("#StaffFormModal").modal('hide');
        			
        		}
        		else
        		{
                    $("#StaffFormModal").modal('hide');
        			//$scope.editStaff(res.id, $scope.info.groups.value);
                    $scope.updateDataSet();
        		}
        		

        		
        	});
    	}
    	
    }
    
    $scope.searchStaff = function(){
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        }, fetchDataDelay);
    }


    $scope.updateDataSet = function () {
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

                // "dom": '<"row"f<"clear">>rt<"bottom"ip<"clear">>',

            dataTable: {
                "bStateSave": false, // save datatable state(pagination, sort, etc) in cookie.


                "lengthMenu": [
                    [20, 50],
                    [20, 50] // change per page values here
                ],
                "pageLength": 50, // default record count per page

                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData,mode: "collection"},
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
                    { "data": "username"},
                    { "data": "name" },
                    { "data": "role.0.name" },
                    { "data": "disabled" },
                    { "data": "link" },
                    { "data": "delete" },
                ]

            }
        });

    }



  /*  $scope.updateDataSet = function()
    {
    	
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

            },
            onError: function (grid) {
                // execute some code on network or other general error  
            },
            loadingMessage: 'Loading...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options 

                
                "bStateSave": false, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [[10, 25, 50], [10, 25, 50]],
                "pageLength": 25, // default record count per page
                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData,mode: "collection"},
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
                            { "data": "username" },
                            { "data": "name" },
                            { "data": "m_role" },
                            { "data": "disabled" },
                            { "data": "link" },
                            { "data": "delete" },
                          ],
                
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }*/
    
});