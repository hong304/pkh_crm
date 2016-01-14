<?php

class SalesreturnController extends Controller {
    
    public function placeReturnOrder(){
        $product = Input::get('product');
        $order = Input::get('order');

        foreach ($product as $k => &$p) {
            if($p['productLocation'] == ''){
                unset($product[$k]);
            }else{

                if ($p['dbid'] != '' && $p['deleted'] == 0 && $p['qty'] != 0)
                    $itemIds[] = $p['dbid'];

                if ($p['dbid'] == '' && $p['code'] != '' && $p['deleted'] == 0 && $p['qty'] != 0){
                    $have_item = true;
                }

               /* if ($p['deleted'] == 0 && $p['qty'] > 0)
                    $ii++;


                if ($p['deleted'] == 0 && $p['qty'] < 0)
                    $j++;*/

            }
        }

        if (!$have_item)
            return [
                'result' => false,
                'status' => 0,
                'invoiceNumber' => 0,
                'invoiceItemIds' => 0,
                'message' => '未有下單貨品(Error:002)',
            ];




        foreach ($product as $p) {
            $receivings = Receiving::where('productId', $p['code'])->where('good_qty', '>', 0)->orderBy('expiry_date', 'asc')->first();



            $return_good_qty = SystemController::NormalizedUnit($p['code'],$p['qty'],$p['unit']['value']);
            $return_damage_qty = SystemController::NormalizedUnit($p['code'],$p['damage_qty'],$p['damage_unit']['value']);

            $adjusts = new adjust();
            $adjusts->poCode = $receivings->poCode;
            $adjusts->receivingId = $receivings->receivingId;
            $adjusts->productId = $p['code'];
            $adjusts->good_qty = $receivings->good_qty;
            $adjusts->damage_qty = $receivings->damage_qty;
            $adjusts->adjusted_good_qty = $receivings->good_qty+ $return_good_qty;
            $adjusts->adjusted_damage_qty = $receivings->damage_qty + $return_damage_qty;
            $adjusts->adjustType = '3';
            $adjusts->save();

            $receivings->good_qty += $return_good_qty;
            $receivings->damage_qty += $return_damage_qty;
            $receivings->updated_by = Auth::user()->id;
            $receivings->updated_at = date("Y-m-d H:i:s");
            $receivings->save();
        }

        return [
            'result' => true,
            'zoneId' => $order['zone']['zoneId']
           ];


    }

}

//Testing