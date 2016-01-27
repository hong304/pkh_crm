<?php

class receiveController extends BaseController {

    public function searchSupplier() {
        $filterData = Input::get('filterData');
        $location = 0;
        if (Input ::get('location') != '') {
            $location = Input ::get('location');
        }
        $supplier = Supplier::select('supplierCode', 'supplierName', 'countries.countryName', 'countries.countryId', 'phone_1', 'status')->where('location', $location)->where('status', 1)->take(10)
                        ->leftJoin('countries', function($join) {
                            $join->on('suppliers.countryId', '=', 'countries.countryId');
                        })
                        ->where(function ($query) use ($filterData) {
                            if (isset($filterData['supplierCode']))
                                $query->where('supplierCode', 'LIKE', '%' . $filterData['supplierCode'] . '%');
                            if (isset($filterData['supplierName']))
                                $query->where('supplierName', 'LIKE', '%' . $filterData['supplierName'] . '%');
                            if (isset($filterData['phone_1']))
                                $query->orwhere('phone_1', 'LIKE', '%' . $filterData['phone'] . '%');
                            if (isset($filterData['phone_2']))
                                $query->orwhere('phone_2', 'LIKE', '%' . $filterData['phone'] . '%');
                            if (isset($filterData['country']['countryId']))
                                $query->where('suppliers.countryId', 'LIKE', '%' . $filterData['country']['countryId'] . '%');
                        })->get();
        return Response::json($supplier);
    }

    public function searchPo() {
        $id = Input::get('poCode');
        $location = 1;
        if (Input ::get('location') != '') {
            $location = Input ::get('location');
        }
        $po = Purchaseorder::select('poCode', 'suppliers.supplierName', 'poDate', 'etaDate', 'phone_1', 'suppliers.status')->where('suppliers.supplierCode', $id)->where('suppliers.location', $location)->where('poStatus', '=','1')
                ->leftJoin('suppliers', function($join) {
                    $join->on('suppliers.supplierCode', '=', 'purchaseorders.supplierCode');
                })
                ->get();
        return Response::json($po);
    }

    public function searchShipping() {
        $id = Input::get('poCode');
        $shipping = Shipping :: select('shippingId')->with('Shippingitem')->where('poCode', $id)->where('status', '!=', '99')->get();
        $formatShipping = $shipping->toArray();
        $countCount = 0;
        $storeShip = "";
        for ($g = 0; $g < count($formatShipping); $g++) {
            for ($start = 0; $start < count($formatShipping[$g]['shippingitem']); $start++) {

                $storeShip[$countCount]['shippingId'] = $formatShipping[$g]['shippingId'];
                $storeShip[$countCount]['containerId'] = $formatShipping[$g]['shippingitem'][$start]['containerId'];
                $storeShip[$countCount]['dbid'] = $formatShipping[$g]['shippingitem'][$start]['id'];
                $countCount++;
            }
        }
        return Response::json($storeShip);
    }

    public function getPurchaseAll() {
        $poCode = Input::get('poCode');
        $poThings = Purchaseorder :: where('poCode', $poCode)->with(['Poitem' => function($query) {
                        $query->with('productDetail');
                    }])->get();
        return Response::json($poThings);
    }


    public function doValidation($product) {
        $flag = true;
        foreach ($product as $k => $v) {
            if(isset($v) && $v != "")
            {
                if(isset($v['productName']) && $v['productName'] != "")
                {
                    if(isset($v['expiryDate']) && $v['expiryDate'] == "")
                    {
                        $flag = false;
                    }
                }
            }
        }
        return $flag;
    }

    public function newReceive() {
        $location = Input :: get('location');

        if (isset($location)) {
            $object = Input::get('order');
            $product = Input::get('product');




            foreach ($product as $k => &$p) {
                if ($p['deleted'] == 1 || $p['productId'] == '') {
                    unset($product[$k]);
                }
            }

         //   pd($product);

            if ($this->doValidation($product)) {
                $receiveId = isset($object['receivingId']) ? $object['receivingId'] : '';

                if ($location == 2) {
                    $input = Input :: get('shippingdbid');
                    $shippingId = Input::get('order')['shippingId'];

                    //Insert the details into shippingitems
                    $this->re = new shippingMan($shippingId);

                    if (isset($input)) {
                        $this->re->setOtherItems($input, $object['receiveDate']);
                        $this->re->saveOtherItems($input);
                    }
                    //return Response::json($this->sh); 
                }

                $have_item = false;    //fOR UPDATE
                $itemIds = [];
                foreach ($product as $p) {
                    if ($p['dbid'] != '' && $p['productId'] != '' && $p['deleted'] == 0)
                        $itemIds[] = $p['dbid'];   //valid originally existed
                    if ($p['dbid'] == '' && $p['productId'] != '' && $p['deleted'] == 0)
                        $have_item = true;   //valid newly created
                }

                //Below should be uncomment when the update function is ready
                if ($receiveId !== '') {  //update
                    if (count($itemIds) == 0 && !$have_item)
                        return [
                            'result' => false,
                            'status' => 0,
                            'message' => '未有貨品',
                        ];
                    else if (count($itemIds) == 0) // If all the items are deleted
                        Receiving::where('receiveId', $receiveId)->delete();
                    else
                        Receiving::whereNotIn('id', $itemIds)->where('receiveId', $receiveId)->delete();
                    //If there is no shippingId, not only the records deleted in ui but also records in db will be deleted.
                }

                //Insert records into receiving tables
                $this->new = new ReceiveMan($receiveId);
                if ($location == 2) {
                    foreach ($product as $k => $v) {
                        if ($v['deleted'] == 0 && isset($v['productId']) && $v['productId'] !== "" && isset($v['unit']['value']))
                            $this->new->setItemss($v['dbid'], $object['poCode'], $object['shippingId'], $object['containerId'], $object['receivingId'], $v['productId'], $v['good_qty'], $v['damage_qty'], $v['on_hold_qty'], date('Y-m-d', strtotime($v['expiryDate'])), $v['good_qty'], $v['damage_qty'], $object['receiveDate'], $v['unit_cost'], $v['bin_location'], $v['deleted'], $v['unit']['value'],$v['qty'],$v['unit']['label']);
                    }
                }else if ($location == 1) {


                    foreach ($product as $k => $v) {
                        if ($v['deleted'] == 0 && isset($v['productId']) && $v['productId'] !== "" && isset($v['unit']['value'])) {
                            $this->new->setItemss($v['dbid'], $object['poCode'], "", "", $object['receivingId'], $v['productId'], $v['good_qty'], $v['damage_qty'], $v['on_hold_qty'], date('Y-m-d', strtotime($v['expiryDate'])), $v['good_qty'], $v['damage_qty'], $object['receiveDate'], $v['unit_cost'], $v['bin_location'], $v['deleted'], $v['unit']['value'],$v['qty'],$v['unit']['label']);
                        }
                    }
                }
                $message = $this->new->save();

                return Response::json($message);
            } else {
                 return [
                'result' => false,
                'status' => 0,
                'message' => '有效日期不能空置或格式錯誤',
                ];
            }
        } else {
             return [
                    'result' => false,
                    'status' => 0,
                    'message' => '請重新選擇',
                ];
                
        }
    }

    public function getAllProducts() {
        $productId = Input :: get('productId');
        $allProduct = Receiving::where('productId', $productId)->orderby('expiry_date')->first();
        if (count($allProduct) > 0) {
            $store = $allProduct;
        } else {
            $store = "false";
        }
        return Response::json($store);
    }

    public function getProductCost() {
        $containerId = Input :: get('containerId');
        $shippingItem = Shippingitem :: where('containerId', $containerId)->first();
        return Response::json($shippingItem);
    }

    public function addProductContainer() {
        $containerId = Input :: get('containerId');
        $containerProduct = containerproduct :: where('containerId', $containerId)
                ->leftJoin('product', function($join) {
                    $join->on('product.productId', '=', 'containerproducts.productId');
                })
                ->get();
        return Response::json($containerProduct);
    }

}
