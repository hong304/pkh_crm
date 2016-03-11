'use strict';

function editShip(shippingId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewShippment(shippingId);
    });
}

app.controller('shippingListing', function ($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

    var queryShip = $scope.endpoint + "/searchContainer.json";

    $scope.firstload = true;
    $scope.autoreload = false;
    
    $scope.filterData = {
        supplierName: '',
        containerId:'',
        shippingId:'',
        status:'',
        etaDate:'',
        etaDate2: '',
        sorting: '',
        current_sorting: 'desc',
    };
    
    $scope.shipInfo = {
        shippingId :'',
        poCode :'',
        etaDate:'',
        status:'',
        carrier:'',
    };


    var today = new Date();
    var plus = today.getDay() == 6 ? 3 : 2;

    var min = today.getDay() == 1 ? 2 : 1;

    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 30);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();

    var day = currentDate.getDate();
    var month = currentDate.getMonth() + 1;
    var year = currentDate.getFullYear();


    $("#etaDate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#etaDate").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);

    $("#etaDate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#etaDate2").datepicker( "setDate", year + '-' + month + '-' + day );

    $scope.filterData.etaDate = yyear+'-'+ymonth+'-'+yday;
    $scope.filterData.etaDate2 = year+'-'+month+'-'+day;

    $(document).ready(function(){
        $('#queryInfo').keydown(function (e) {
            if (e.keyCode == 13) { //Enter
                $scope.updateDataSet();
            }

        });

    });

 $scope.$on('$viewContentLoaded', function () {
        // initialize core components
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
    });



    $scope.updateDataSet = function () {

        $(document).ready(function() {
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
                    "data": {mode: "collection", filterData: $scope.filterData},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 50,
                "pagingType": "full_numbers",
                //  "fnDrawCallback" : function() {
                //   window.alert = function() {};
                //  },
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
                    {"data": "supplierName", "width": "10%"},
                    {"data": "poTradeTerm", "width": "10%"},
                    {"data": "etdDate", "width": "10%"},
                    {"data": "etaDate", "width": "10%"},
                    {"data": "vessel", "width": "10%"},
                    {"data": "containerId", "width": "10%"},
                    {"data": "productId", "width": "10%"},
                    {"data": "productName_chi", "width": "10%"},
                    {"data": "qty", "width": "10%"},
                    {"data": "amount", "width": "10%"},


                ]

            });

        });
    };

    $scope.$on('handleSupplierUpdate', function(){
        // received client selection broadcast. update to the invoice portlet
        $scope.filterData.supplier = SharedService.supplierCode === undefined ? '' : SharedService.supplierCode;
        $scope.filterData.supplierCode =SharedService.supplierName;
    });

    $scope.$on('doneSupplierUpdate', function(){
        $scope.updateDataSet();
    });

    if($location.search().orderTime)
    {
        $scope.filterData.sorting = "shippings.updated_at";
        $scope.updateDataSet();
    }
    
    $scope.click = function (event)
    {

        $scope.filterData.sorting = event.target.id;

        if ($scope.filterData.current_sorting == 'asc') {
            $scope.filterData.current_sorting = 'desc';
        } else {
            $scope.filterData.current_sorting = 'asc';
        }

        $scope.updateDataSet();
    }
   
    
    $scope.updateStatus = function()
    {
        $scope.updateDataSet();
    }
    
    $scope.updateRadio = function()
    {
        if($scope.filterData.status == 0)
        {
            $scope.filterData.status = '';
        }
         $scope.updateDataSet();
    }
    


    
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
                        alert("這個記錄被刪除");
			$scope.updateDataSet();
			$scope.shipping.status = 99;
			
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
    
        $scope.clearShipSearch = function()
        {
            $scope.filterData.supplierCode = "";
            $scope.filterData.supplier = "";
            $scope.updateDataSet();
        }



});