'use strict';

function editProductGroup(departmentId,groupId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editProductGroup(departmentId,groupId);
    });
    
}

app.controller('productDepartment', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
	var querytarget = endpoint + '/queryProductDepartment.json';
	var iutarget = endpoint + '/manipulateProductDepartment.json';
	
     $scope.departmantIds = loadTableId();
        
	$scope.filterData = {
	     'sorting' : '',
            'current_sorting' : 'asc',
		};
	
	$scope.info_def = {
            'productDepartmentIdOther' : '',
            'productDepartmentName' : '',
            'productGroupId' : '',
            'productGroupName' : '',
            'productDepartmentId' : '',
           
	};
        
  
        
        function loadTableId()
        {
             $http.post(querytarget, {mode: "dropdown"})
        	.success(function(res, status, headers, config){    
                    $scope.sizes = res;
             });
             return $scope.sizes;
        }
        
  
	$scope.submitbtn = true;
	$scope.newId = "";
        $scope.newGroupId = "";
	
	$scope.info = {};
	
    $scope.$on('$viewContentLoaded', function() {   
    	
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;

  	}, true);
    
    
    $scope.editProductGroup = function(departmentId,groupId)
    {
         $("#submitButton").attr("disabled",false);
        $scope.info.productDepartmentId = departmentId;
        $scope.info.productGroupId = groupId;
        	$http.post(querytarget, {mode : 'queryItemInfo',info: $scope.info})
        	.success(function(res, status, headers, config){    
                  $scope.info  = res;
        	});
        $("#address_cht").attr("disabled",true);
        $(".updateThing").show();
        $(".increase").hide();
        $("#productDepartmentFormModal").modal({backdrop: 'static'});
   
    }
   
     $scope.keydown = function() {
           $scope.departmantIds = loadTableId();
          if($scope.departmantIds != "" && $scope.departmantIds != "undefined" && $scope.departmantIds != [])
          {
              var keepGoing = true;
              angular.forEach($scope.departmantIds, function(value, key) {
                  
                  if(keepGoing)
                  {
                       if($scope.info.productDepartmentId == value.productDepartmentId) 
                       {
                             keepGoing = false;
                             $scope.info.departmentIdWrongMsg = "這部門ID已存在";
                             $("#submitButton").attr("disabled",true);
                       }else
                       {
                            keepGoing = true;
                           $scope.info.departmentIdWrongMsg = "";
                           $("#submitButton").attr("disabled",false);
                       }
                  }   
              });
              
          }

     };
    
    $scope.addDepartment = function()
    {
         $("#submitButton").attr("disabled",false);
        $scope.currentAction = "addDepartment";
    	$scope.info = $.extend(true, {}, $scope.info_def);
    	$scope.newId = "";
	
    	$scope.submitbtn = true;
        $scope.info.productStatus = status[0];
        $("#address_cht").attr("disabled",false);
        $(".updateThing").hide();
        $(".increase").show();
    	$("#productDepartmentFormModal").modal({backdrop: 'static'});

    }
    $scope.click = function(event)
    {
       //  alert(event.target.id);
         $scope.filterData.sorting = event.target.id;
       console.log($scope.filterData.current_sorting);
            if ($scope.filterData.current_sorting == 'asc'){
                $scope.filterData.current_sorting = 'desc';
            }else{
               $scope.filterData.current_sorting = 'asc';
               
            }

         $scope.updateDataSet();
    }
    

    $scope.addCategory = function()
    {
         $("#submitButton").attr("disabled",false);
        loadTableId();
        $scope.currentAction = "addCategory";
        $scope.info = $.extend(true, {}, $scope.info_def);
    	$scope.newId = "";
	
    	$scope.submitbtn = true;
        $scope.info.productStatus = status[0];
   
    	$("#productGroupFormModal").modal({backdrop: 'static'});
    }
    
    $scope.submitProductForm = function()
    {
        if($scope.currentAction == "addCategory")
        {
             $scope.info.productDepartmentId = ($scope.info.productDepartmentIdOther.productDepartmentId === undefined ) ? "" : $scope.info.productDepartmentIdOther.productDepartmentId;
        }
   $http.post(iutarget, {info: $scope.info})
        	.success(function(res, status, headers, config){    
                    if(typeof res === "object")
                    {
                        $("#submitButton").attr("disabled",true);
                        $scope.newGroupId = res.id._productGroupId;
                        $scope.updateDataSet();	
                        
                    }else
                    {
                        alert(res);
                    }
                });
    }
    
  $scope.updateDataSet = function () {
        $(document).ready(function() {

            if(!$scope.firstload)
            {
                $("#datatable_ajax").dataTable().fnDestroy();
            }
            else
            {
                $scope.firstload = false;
            }


            $('#datatable_ajax').dataTable({

                // "dom": '<"row"f<"clear">>rt<"bottom"ip<"clear">>',

                "sDom": '<"row"<"col-sm-6"<"pull-left"p>><"col-sm-6"f>>rt<"row"<"col-sm-12"i>>',

                "bServerSide": true,

                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData, mode: "collection"},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 25,
                "pagingType": "full_numbers",
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
                            { "data": "productDepartmentId" ,"width" : "20%"},
                            { "data": "productGroupId" ,"width" : "20%"},
                            { "data": "productDepartmentName" ,"width" : "20%"},
                            { "data": "productGroupName","width" : "20%" },
                            { "data": "link" ,"width" : "20%"},
                            
                ],           
               

            });



        });
    };
   
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

                
                "bStateSave": true, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [
                    [10, 20, 50],
                    [10, 20, 50] // change per page values here
                ],
                "pageLength": 20, // default record count per page
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
                            { "data": "productDepartmentId" },
                            { "data": "productGroupId" },
                            { "data": "productDepartmentName" },
                            { "data": "productGroupName" },
                            { "data": "link" },
                            
                ],           
                
     
            }
        });

    }*/
    
});