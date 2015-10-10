<?php

class shippingController extends BaseController {

    public function jsonSelectPo() {
        $purchase = array();
        $po = Input::get('input');
        $supplier = Input:: get('supplier');
        if ($po !== "")
            $purchase = Purchaseorder::select('poCode', 'poDate', 'etaDate', 'purchaseorders.supplierCode', 'suppliers.supplierName')
                    ->leftJoin('suppliers', function($join) {
                        $join->on('suppliers.supplierCode', '=', 'purchaseorders.supplierCode');
                    })
                    ->where('purchaseorders.supplierCode', $po)
                    ->where('poStatus', 1)
                    ->where('purchaseorders.location', 2)
                    ->get();
        return Response::json($purchase);
    }

    public function newShipment() {
        $newShip = array();
        $shipment = Input:: get('ship');
        $shipItem = Input::get('product');
        $booleanval = isset($shipment['shippingId']) ? $shipment['shippingId'] : false;
        $this->sh = new shippingMan($shipment['shippingId']);
        $this->sh->setShip($shipment);


        $have_item = false;    //fOR UPDATE
        $itemIds = [];
        foreach ($shipItem as $p) {
            if ($p['dbid'] != '' && $p['deleted'] == 0)
                $itemIds[] = $p['dbid'];
            if ($p['dbid'] == '' && $p['containerId'] != '')
                $have_item = true;
        }


        //Below should be uncomment when the update function is ready
        if ($shipment['shippingId'] != '') {  //update
            if (count($itemIds) == 0 && !$have_item)
                return [
                    'result' => false,
                    'status' => 0,
                    'message' => '未有下單貨品',
                ];
            else if (count($itemIds) == 0) // If all the items are deleted
                Shippingitem::where('shippingId', $shipment['shippingId'])->delete();
            else
                Shippingitem::whereNotIn('id', $itemIds)->where('shippingId', $shipment['shippingId'])->delete();
            //If there is no shippingId, not only the records deleted in ui but also records in db will be deleted.
        }
        //pd($shipItem);
        foreach ($shipItem as $k) {
            if($k['deleted'] == 0)
            {    
                 $cost_00 = (isset($k['cost']['cost_00'])) ? $k['cost']['cost_00'] : 0;
                 $cost_01 = (isset($k['cost']['cost_01'])) ? $k['cost']['cost_01'] : 0;
                 $cost_02 = (isset($k['cost']['cost_02'])) ? $k['cost']['cost_02'] : 0;
                 $cost_03 = (isset($k['cost']['cost_03'])) ? $k['cost']['cost_03'] : 0;
                 $cost_04 = (isset($k['cost']['cost_04'])) ? $k['cost']['cost_04'] : 0;
                 $cost_05 = (isset($k['cost']['cost_05'])) ? $k['cost']['cost_05'] : 0;
                 $cost_06 = (isset($k['cost']['cost_06'])) ? $k['cost']['cost_06'] : 0;
                 $cost_07 = (isset($k['cost']['cost_07'])) ? $k['cost']['cost_07'] : 0;
                 $cost_08 = (isset($k['cost']['cost_08'])) ? $k['cost']['cost_08'] : 0;
                 $cost_09 = (isset($k['cost']['cost_09'])) ? $k['cost']['cost_09'] : 0;
   
                 
                 $this->sh->setItems($k['dbid'], $k['containerId'], $k['serial_no'], $k['container_size'], $k['container_Num'], $k['container_weight'], $k['container_capacity'], $k['remark'], $k['deleted'],$k['sale_method'],$cost_00,$cost_01,$cost_02,$cost_03,$cost_04,$cost_05,$cost_06,$cost_07,$cost_08,$cost_09);
            }// clear the deleted record      
        }


        $message = $this->sh->save();

        return Response::json($message);
    }

    public function jsonQueryShip() {
        $mode = Input::get('mode');
        $filter = Input ::get('filterData');
        $current_sorting = $filter['current_sorting'];
        $sorting = "shippingId";
        if (!$filter['sorting'] == '') {
            $sorting = $filter['sorting'];
        }

        if ($mode == 'collection') {

            $ship = Shipping::select(['shippingId', 'shippings.supplierCode', 'suppliers.supplierName', 'etaDate', 'shippings.status', 'carrier', 'bl_number', 'users.username', 'shippings.updated_at', 'shippings.poCode'])
                    ->leftJoin('suppliers', function($join) {
                        $join->on('suppliers.supplierCode', '=', 'shippings.supplierCode');
                    })
                    ->leftJoin('users', function($join) {
                        $join->on('users.id', '=', 'shippings.updated_by');
                    })
                    ->orderby($sorting, $current_sorting);


            $ship->where('shippings.shippingId', 'LIKE', '%' . $filter['shippingId'] . '%')
                    ->where('shippings.status', 'LIKE', '%' . $filter['status'] . '%')
                    ->where('shippings.supplierCode', 'LIKE', '%' . $filter['supplier'] . '%');




            //Dont add get() here
            return Datatables::of($ship)
                            ->addColumn('link', function ($shi) {
                                return '<span onclick="editShip(\'' . $shi->shippingId . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
                            })
                            ->editColumn('status', function($shi) {
                                $statusValue = "";
                                if ($shi->status == 1) {
                                    $statusValue = "正常";
                                } else if ($shi->status == 30) {
                                    $statusValue = "已完成";
                                } else if ($shi->status == 99) {
                                    $statusValue = "刪除";
                                }
                                return $statusValue;
                            })
                            ->make(true);
        }

        return Response::json($ship);
    }

    public function jsonGetSingleShip() {
        $shippingId = Input::get('shippingId');

        $base = Shipping::where('shippingId', $shippingId);

        $shipping = Shipping::getFullShippment($base);


        $returnInformation = [
            'shipping' => array_values($shipping['shipping'])[0],
            'shippingItem' => array_values($shipping['shipping'])[0]['shippingitem'],
        ];
        return Response::json($returnInformation);
    }

    public function deleteShip() {
        $shippingId = Input::get('shippingId');
        $shipment = new shippingMan($shippingId);
        return Response::json($shipment->setDeleteShip());
    }

    public function loadShip() {
        $id = Input::get('id');
        $ship = Shipping::where('shippingId', $id)->with('Shippingitem')->get();
        return Response::json($ship);
    }

    public function loadPo() {
        $id = Input::get('id');
        $purchaseOrder = Purchaseorder::where('poCode', $id)->where('location', 1)->with(['PoItem' => function($query) {
                        $query->with('productDetail');
                    }])->with('supplier')->get();

        return Response::json($purchaseOrder);
    }

    public function outputPreview() {
        $deliverydate5 = Input ::get('filterData')['deliverydate5'];

        $first_date = date("Y/m/d");

        $s = shipping::where('actualDate', '!=', '')->with('Supplier', 'Shippingitem')->where('status','!=',99)->get();

        $eta = shipping::whereNull('actualDate')->with('Supplier', 'Shippingitem')->where('status','!=',99)->get();

        $sfsp = shipping::Select('fsp', 'actualDate', 'shippingId','supplierCode','poCode')->with('Supplier')->where('actualDate', '!=', '')->where('fsp', '>', 0)->where('status','!=',99)->get();
 
        //sql that can accummulate the number of containers each day


        $date1 = ($deliverydate5 !== '') ? strtotime($deliverydate5) : strtotime($first_date) - 24 * 60 * 60 * 7;
        //$date3 = $date1 - 24 * 60 * 60 * 7; // other day range

        $sDate = $date1 + 24 * 60 * 60 * 14;  //Total days
      //  $sDate3 = $date3 + 24 * 60 * 60 * 14; //end  day
        //  $sDate2 = $sDate - 24*60*60*7;

      /*  while ($date3 <= $sDate3) {
            $date2[] = date('Y-m-d', $date3);
            $date3 = $date3 + 24 * 60 * 60;  //Add one more date
        }*/

        foreach ($sfsp as $key => $value) {
            $fspValue = $value['fsp'];
            $dateAdd = strtotime($value['actualDate']);
            for ($h = 0; $h < $fspValue; $h++) {
                $dateAdd = $dateAdd + 60 * 60 * 24;
                $daterange[$value['shippingId']]['actualDate'] = $value['actualDate'];
                $supplierWord = (isset($value['Supplier']->toArray()[0]['supplierName'])) ? $value['Supplier']->toArray()[0]['supplierName'] : "";
                $daterange[$value['shippingId']]['supplier'] = "採購單編號:".$value['poCode'] ."<br/>供應商名稱:". $supplierWord;
                $daterange[$value['shippingId']][$h] = date('Y-m-d', $dateAdd);
            }
        }
       


        while ($date1 <= $sDate) {
            $date[] = date('Y-m-d', $date1);
            $wstoreAll = 0;
            $tstoreAll = 0;
            $storeAll = 0;
            $storeAllData = 0;
            foreach ($s as $v) {
                if ($v->actualDate == date('Y-m-d', $date1)) {
                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['no'] = count($v->Shippingitem->toArray());

                    $storeAllData = $storeAllData + count($v->Shippingitem->toArray());
    
                    $supplierWord = (isset($v->supplier->toArray()[0]['supplierName'])) ? $v->supplier->toArray()[0]['supplierName'] : "";
                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['supplier'] = "採購單編號:".$v->poCode ."<br/>供應商名稱:". $supplierWord;

                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['fsp'] = $v->fsp;

                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['mode'] = 'actual';
                    $wholestore = 0;
                    $tradestore = 0;
                    $store = 0;
                    foreach ($v->shippingitem as $k => $v) {
                        if (isset($v->container_receiveDate)) {
                            $store++;
                            if($v->sale_method == 1)
                            {
                                $wholestore++;
                            }else if($v->sale_method == 2)
                            {
                                $tradestore++;
                            }
                        }
                    }
                    
                    
                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['receive'] = $wholestore ."+".$tradestore;

                    $wstoreAll = $wstoreAll + $wholestore;
                    $tstoreAll = $tstoreAll + $tradestore;

                    $other[date('Y-m-d', $date1)]['storeAll'] = $wstoreAll . "+". $tstoreAll;
                    $other[date('Y-m-d', $date1)]['storeAllData'] = $storeAllData;
   
                }
            }

            foreach ($eta as $v) {
                if ($v->etaDate == date('Y-m-d', $date1)) {
                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['no'] = count($v->Shippingitem->toArray());
                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['mode'] = 'eta';
                    $supplierWord = (isset($v->supplier->toArray()[0]['supplierName'])) ? $v->supplier->toArray()[0]['supplierName'] : "";
                        $sarr[$v->shippingId][date('Y-m-d', $date1)]['supplier'] = "採購單編號:".$v->poCode ."<br/>供應商名稱:". $supplierWord;
                    $storeAllData = $storeAllData + count($v->Shippingitem->toArray());
                    $other[date('Y-m-d', $date1)]['storeAll'] = $wstoreAll . "+". $tstoreAll;
                    $other[date('Y-m-d', $date1)]['storeAllData'] = $storeAllData;
                    $sarr[$v->shippingId][date('Y-m-d', $date1)]['fsp'] = $v->fsp;
                   
                }
            }

            $date1 = $date1 + 24 * 60 * 60;  //Add one more date
        }
  
        if (isset($sarr)) {
            $this->data = $sarr;
            return View::make('shippingTable')->with(['data' => $this->data, 'date' => $date, 'other' => $other, 'daterange' => $daterange])->render();
        } else {
            return View::make('shippingTable')->with(['date' => $date])->render();
        }
    }
    
    public function outputShipNote()
    {
        $aad = "";
        $outputAad ="";
        $outputEta ="";
        $today = strtotime(date("Y/m/d"));
        $weekArray = $this->createWeek($today);
        foreach($weekArray as $k=>$v)
        {
            if($k !== "last_last_week")
            {
                 $s[$k] = shipping::where('actualDate', '!=', '')->whereBetween('actualDate',array($v[1],$v[0]))->with('Shippingitem')->where('status','!=',99)->get()->toArray();
                 $eta[$k] = shipping::whereNull('actualDate')->whereBetween('etaDate',array($v[1],$v[0]))->where('status','!=',99)->get()->toArray();
            }else
            {
                 $s[$k] = shipping::where('actualDate', '!=', '')->where('actualDate','<=',$v[0])->with('Shippingitem')->where('status','!=',99)->get()->toArray();
                 
                 $eta[$k] = shipping::whereNull('actualDate')->where('etaDate','<=',$v[0])->where('status','!=',99)->get()->toArray();
                 
            }
            
        }      
        
        foreach($eta as $etaKey=>$etaValue)
        {
            $outputEta[$etaKey] = count($etaValue);
        }
     
        foreach($s as $key=>$value)
        {
            $count = 0;
            if(count($value) > 0)
            {
                $outputAad[$key] = count($value);
            
            /* if(count($value) > 0)
            {
                for($p = 0;$p<count($value);$p++) //level of shiipingId
                {
                    $countCargo = 1;
                    $countRecive = 1;
                    for($g = 0;$g<count($value[$p]['shippingitem']);$g++) //level of shippingitem
                    {
                         $aad[$key][$value[$p]['shippingId']] = $countCargo++;
                        if(isset($value[$p]['shippingitem'][$g]['container_receiveDate']))
                        {
                            $countReceive[$key][$value[$p]['shippingId']] = $countRecive++;
                        }else
                        {
                            $countReceive[$key][$value[$p]['shippingId']] = 0;
                        }
                    }
                } 
            }
                    if(isset($aad[$key]))
                    {
                        
                        $weekCount = 0;
                        $aadCount = $aad[$key];
                   
                        $weekCount = count($aadCount);
                        $outputAad[$key] = $weekCount;
                       if(isset($countReceive[$key]))
                        {
                        $countReceiveCount = $countReceive[$key];
                        } 
                        foreach($aadCount as $shipid=>$num)
                        {
                            if($aadCount[$shipid] != $countReceiveCount[$shipid])
                            {
                                $outputAad[$key] = ++$weekCount; 
                            }else
                            {
                                $outputAad[$key] = 0;
                            }
                        }*/
                    }else
                    {
                        $outputAad[$key] = 0;
                    }

        }
            if(isset($aad))
                return View::make('shippingNote')->with(['shipTable' =>$aad,'outputAad'=>$outputAad,'eta'=>$outputEta,'createweek'=>$weekArray])->render();
                
    }
    
    public function createWeek($startDate)
    {
        $this_week[0] = date('Y-m-d',$startDate); //latest
        $this_week[1] = date('Y-m-d',$startDate - 6 * 24 * 60 * 60);
        
        $next_week[0] = date('Y-m-d',$startDate + 7 * 24 * 60 * 60);
        $next_week[1] = date('Y-m-d',$startDate +  24 * 60 * 60);
        
        $last_week[0] = date('Y-m-d',$startDate - 7 * 24 * 60 * 60);
        $last_week[1] = date('Y-m-d',$startDate - 13 * 24 * 60 * 60);
        
        $last_last_week[0] = date('Y-m-d',$startDate - 14 * 24 * 60 * 60);
        $last_last_week[1] = date('Y-m-d',$startDate - 20 * 24 * 60 * 60);

        return ['last_last_week'=>$last_last_week,'last_week'=>$last_week,'this_week'=>$this_week,'next_week'=>$next_week];
    }
    

    public function outputPo() {
        $deliverydate5 = Input ::get('filterData')['deliverydate6'];

        $first_date = date("Y/m/d");

        $poActualDate = Purchaseorder::whereNotNull('actualDate')->with('Supplier')->get();

        $poEtaDate = Purchaseorder::whereNull('actualDate')->with('Supplier')->get();

        $date1 = ($deliverydate5 !== '') ? strtotime($deliverydate5) : strtotime($first_date) - 24 * 60 * 60 * 7;

        $sDate = $date1 + 24 * 60 * 60 * 14;  //Total days

        while ($sDate >= $date1) {
            $date[] = date('Y-m-d', $date1);

            foreach ($poActualDate->toArray() as $a) {
                if ($a['actualDate'] == date('Y-m-d', $date1)) {
                    if (isset($a['receiveDate'])) {
                        $sarr[$a['poCode']][date('Y-m-d', $date1)]['mode'] = 'receive';
                    } else {
                        $sarr[$a['poCode']][date('Y-m-d', $date1)]['mode'] = 'actual';
                    }

                    $sarr[$a['poCode']][date('Y-m-d', $date1)]['supplier'] = $a['supplier']['supplierName'];
                }
            }
            foreach ($poEtaDate as $b) {
                if ($a['etaDate'] == date('Y-m-d', $date1)) {
                    $sarr[$b['poCode']][date('Y-m-d', $date1)]['mode'] = 'eta';
                    $sarr[$b['poCode']][date('Y-m-d', $date1)]['supplier'] = $a['supplier']['supplierName'];
                }
            }
            $date1 = $date1 + 24 * 60 * 60;
        }
        if (isset($sarr)) {
            $this->data = $sarr;
            return View::make('poTable')->with(['data' => $this->data, 'date' => $date])->render();
        }
    }
    
    public function jsonSearchSupplier()
    {
        $supplier = Input::get('filterAll');
        $querySupplier = Supplier :: where('supplierCode', 'LIKE', '%' . $supplier['supplierCode'] . '%')->where('location',2)->with('country')->get();
        return Response::json($querySupplier);
    }
    
    
    public function jsonSearchPo()
    {
        $supplierCode = Input::get('supplierCode');
        $queryPo = Purchaseorder :: where('supplierCode',$supplierCode)->where('location',2)->with('supplier')->get();
        return Response::json($queryPo);
    }

}
