'use strict';

function editShip(shippingId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewShippment(shippingId);
    });
}

app.controller('searchship', function ($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {


    $scope.firstload = true;
    $scope.autoreload = false;
    
    $scope.keyword = {
        supplierName:'',
        supplier:'',
        shippingId:'',
        status:'',
        etaDate:'',  
        sorting: '',
        current_sorting: 'desc',
        
    };
    
    $scope.shipInfo = {
        shippingId :'',
        poCode :'',
        supplierName:'',
        etaDate:'',
        status:'',
        carrier:'',
    };
    
 $scope.$on('$viewContentLoaded', function () {
        // initialize core components
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
    });
    
    var queryShip = $scope.endpoint + "/jsonQueryShip.json";
    
     $scope.updateDataSet = function () {
        $(document).ready(function () {

            if (!$scope.firstload)
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
                    "url": queryShip, // ajax source
                    "type": 'POST',
                    "data": {mode: "collection", filterData: $scope.keyword},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 50,
                "pagingType": "full_numbers",
                "language": {
                    "lengthMenu": "顯示 _MENU_ 項結果",
                    "zeroRecords": "沒有匹配結果",
                    "sEmptyTable": "沒有匹配結果",
                    "info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
                    "infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "Processing": "處理中...",
                    "Paginate": {
                        "First": "首頁",
                        "Previous": "上頁",
                        "Next": "下頁",
                        "Last": "尾頁"
                    }
                },
                "columns": [
                    {"data": "shippingId", "width": "15%"},
                    {"data": "poCode", "width": "10%"},
                    {"data": "supplierName", "width": "15%"},
                    {"data": "etaDate", "width": "10%"},
                    {"data": "status", "width": "10%"},
                    {"data": "carrier", "width": "10%"},
                    {"data": "username", "width": "10%"},
                    {"data": "updated_at", "width": "10%"},
                    {"data": "link", "width": "10%"}
                ]

            });

        });
    };
    
    if($location.search().orderTime)
    {
        $scope.keyword.sorting = "shippings.updated_at";
        $scope.updateDataSet();
    }
    
      $scope.click = function (event)
    {

        $scope.keyword.sorting = event.target.id;

        if ($scope.keyword.current_sorting == 'asc') {
            $scope.keyword.current_sorting = 'desc';
        } else {
            $scope.keyword.current_sorting = 'asc';
        }

        $scope.updateDataSet();
    }
    
     $scope.updateStatus = function()
    {
        $scope.updateDataSet();
    }
    
    $scope.updateRadio = function()
    {
        if($scope.keyword.status == 0)
        {
            $scope.keyword.status = '';
        }
        console.log($scope.keyword.status);
         $scope.updateDataSet();
    }
    
    $scope.$on('handleSupplierUpdate', function(){
        // received client selection broadcast. update to the invoice portlet
        $scope.keyword.supplier = SharedService.supplierCode === undefined ? '' : SharedService.supplierCode;
          $scope.updateDataSet();
    });

    
    $scope.viewShippment = function (shippingId) {

		$("#shipDetails").modal({backdrop: 'static'});
         $http.post($scope.endpoint + "/jsonGetSingleShip.json", {
                shippingId: shippingId,
            }).success(function (data) {

               $scope.shipping = data.shipping;
			   $scope.shippingItem = data.shippingItem;
            });
  
    }
	
	
	$scope.voidShip = function(shippingId)
	{
		if($scope.shipping.status == 1)
		{
			 $http.post($scope.endpoint + "/deleteShip.json", {
                shippingId: shippingId
            }).success(function (data) {
					$scope.updateDataSet();
					$scope.shipping.status = 99;
					alert("這個記錄被刪除");
            });
		}else{
			
			alert("此記錄無法刪除");
		}
		
	}
	
	$scope.goEdit = function (shippingId)
    {
        $(document).ready(function () {
            if ($scope.shipping.status == 99)
            {
                alert("此記錄無法修改");

            } else
            {
                 $location.url("/shipping?shippingId=" + shippingId);
            }
        });
    }
     

});