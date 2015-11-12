'use strict';

Metronic.unblockUI();



app.controller('repack', function ($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state, $stateParams) {
    /* Register shortcut key */

$scope.itemlist = [0];
    $scope.repack = {
        productId: '',
        productName: '',
        products: '',
    };
    $scope.selfdefine = [];
    $scope.selfdefineS = {
        'productId': '',
        'qty': '',
        'unit': '',
        'productlevel': '',
        'adjustType':'1',  //repack is 1,退貨 is 2,when " " is 3
        'adjustId':'',
        'receivingId':'',
        'good_qty':'',
        deleted : 0
     
    }

    $scope.receiveInclude = [];
    $scope.receive = {
        'receivingId': '',
        'good_qty': '',
    };


    $scope.$on('handleReUpdate', function () {
        $scope.repack.productId = SharedService.productId;
        $scope.repack.productName = SharedService.productName;
        $scope.repack.products = SharedService.products;
        console.log($scope.repack);

    });



    $scope.itemlist.forEach(function(key){
        $scope.selfdefine[key] = $.extend(true, {}, $scope.selfdefineS);
       // console.log( $scope.selfdefine);
    });

    $scope.totalline = 1;

    $scope.addRows = function () {
        var j = $scope.totalline;
        $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
       /* $scope.selfdefine[j]['productId'] = '';
        $scope.selfdefine[j]['productName'] = '';
        $scope.selfdefine[j]['qty'] = '';
        $scope.selfdefine[j]['unit'] = '';
        $scope.selfdefine[j]['adjustId'] = '';
        $scope.selfdefine[j]['receivingId'] = '';*/
        $scope.totalline += 1;
    }

    $scope.deleteRow = function(i)
    {
        console.log(i);
        //console.log($scope.selfdefine);
        $scope.selfdefine[i].deleted = 1;

    }

    $scope.submitRepack = function () {


        if($scope.out.total_normalized_unit != $scope.totalAmount){
            alert('輸出及輸入數量必需相同');
            return false
        }

        if($scope.out.total_normalized_unit > $scope.out.available){
            alert('Repack amount cant more than available amount');
            return false
        }


        insertToAdjust($scope.selfdefine);

        /*
        console.log($scope.selfdefine);

        var accumulate = 0;
        for (var j = 0; j < $scope.selfdefine.length; j++) // Change the value of original product
        {
            if($scope.selfdefine[j].qty !== '')
                accumulate += parseInt($scope.selfdefine[j].qty);
        }
        nextOne($scope.repack.products, accumulate);
        */

       // console.log($scope.receiveInclude);
          
        
    }


    $scope.searchReceiving = function(){
        var target = endpoint + '/outRepackProduct.json';
        $http.post(target, {productId:$scope.out.productId})
            .success(function (res, status, headers, config) {

                $scope.out.productName = res.productName;
                var availableunit = [];
                if(res.productPackingName_carton != '')
                    availableunit = availableunit.concat([{value: 'carton', label: res.productPackingName_carton}]);
                if(res.productPackingName_unit != '')
                    availableunit = availableunit.concat([{value: 'unit', label: res.productPackingName_unit}]);
                $scope.out.unit = availableunit[0];
                $scope.out.available = res.total;
                $scope.out.availableunit = availableunit;
                $scope.out.normalized_unit = res.normalized_unit;

            });
    }

    $scope.calc = function(){

        var finalize_amount = 0;
        if($scope.out.unit.value=='carton')
            finalize_amount  = $scope.out.normalized_unit * $scope.out.qty;
        else if ($scope.out.unit.value=='unit')
            finalize_amount = $scope.out.qty;


        $scope.out.total_normalized_unit =  finalize_amount;
    }

    $scope.calcIn = function(){
        $scope.totalAmount = 0;

        var i = 0;
        $scope.selfdefine.forEach(function(item){
            if(item.deleted == 0)
            {
                var finalize_amount = 0;
                if(item.unit.value=='carton') {
                    finalize_amount = item.normalized_unit * item.qty;
                    $scope.selfdefine[i]['packing_size'] = item.normalized_unit;
                }else if (item.unit.value=='inner'){
                    finalize_amount = item.normalized_inner * item.qty;
                    $scope.selfdefine[i]['packing_size'] = item.normalized_inner;
                }else{
                    finalize_amount = item.qty
                    $scope.selfdefine[i]['packing_size'] = item.qty;
                }
                $scope.totalAmount += Number(finalize_amount);
                $scope.selfdefine[i]['total_finalized_unit'] = finalize_amount;

            }
            i++;
        });
    }

     $scope.searchProduct = function (value,i) 
     {
          var target = endpoint + '/preRepackProduct.json';
            $http.post(target, {productId:value})
            .success(function (res, status, headers, config) {
                if(typeof res == "object")
                {
                     var availableunit = [];
                    if(res.productPackingInterval_unit > 0)
                        availableunit = availableunit.concat([{value: 'unit', label: res.productPackingName_unit}]);
                    if(res.productPackingInterval_inner > 0)
                        availableunit = availableunit.concat([{value: 'inner', label: res.productPackingName_inner}]);
                    if(res.productPackingInterval_carton > 0)
                        availableunit = availableunit.concat([{value: 'carton', label: res.productPackingName_carton}]);

                   // $scope.selfdefine[i]['availableunit'] = availableunit.reverse();
                    $scope.selfdefine[i]['availableunit'] = availableunit;
                    $scope.selfdefine[i]['unit'] = $scope.selfdefine[i]['availableunit'][0];
                    $scope.selfdefine[i]['qty'] = '';
                    $scope.selfdefine[i]['productName'] = res.productName_chi;
                    $scope.selfdefine[i]['normalized_unit'] = res.normalized_unit;
                    $scope.selfdefine[i]['normalized_inner'] = res.productPacking_unit;
                }
            });
     }
    


    function nextOne(ele, accumulate)
    {
        var accum = 0;
        var flag = true;
        var good_qtyStore = parseInt(accumulate);

        var storeTemp = 0;
        var totalStorage = 0;
        for (var y = 0; y < ele.length; y++)
        {
            totalStorage += ele[y].good_qty;
        }

        if(totalStorage < good_qtyStore)
        {
            alert("不夠貨包裝");
        } else
        {
           /* for (var x = 0; x < ele.length; x++)
            { 
                $scope.receiveInclude[x] = $.extend(true, {}, $scope.receive);
                if (ele[x].good_qty > 0)
                {
                    accum = ele[x].good_qty - good_qtyStore;
                    storeTemp = accum;
                    $scope.receiveInclude[x].receivingId = ele[x].receivingId;
                    if (accum > 0 || accum == 0)
                    {
                        $scope.receiveInclude[x].good_qty = accum;
                        return;
                    }
                    else if (accum < 0)
                    {
                        $scope.receiveInclude[x].good_qty = 0;
                        good_qtyStore = Math.abs(storeTemp);
                        continue;
                    }
                }else
                {
                    continue;
                }*/
               // insertToAdjust($scope.selfdefine);
            }
            
            //Match new product to original branch

        //Remeber to send json to backend 
        //fix the bug please
     }
     
     function insertToAdjust(items)
     {
        if(items != "")
        {
            var target = endpoint + '/addAjust.json';
            $http.post(target, {items:items,outProduct:$scope.out})
            .success(function (res, status, headers, config) {
              if(res.result)
                {
                    alert("已成功包裝");
                }
            });
        }
     }
     
 
     
});