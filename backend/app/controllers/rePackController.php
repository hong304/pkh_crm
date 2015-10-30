<?php

class rePackController extends BaseController {

    public function getAllProducts()
    {
        $productId = Input :: get('productId');
        $sql = "Select r.good_qty,p.productName_chi,r.expiry_date,r.receivingId,r.id,p.productPacking_carton,p.productPacking_inner,p.productPacking_unit from receivings as r,product as p where p.productId = r.productId and p.product_flag = 'p' and r.productId ='".$productId."' order by expiry_date asc";
        $info = DB::select(DB::raw($sql));
        //$allProduct = Receiving::where('productId',$productId)->with('product')->orderby('expiry_date','asc')->get();
        if(count($info) > 0)
        {
            $store = $info;
        }else
        {
            $store = "false";
        }
        return Response::json($store); 
    }
    
    public function preRepackProduct()
    {
        $productId = Input :: get('productId');
        $productName = Product :: select ('productName_chi','productPacking_carton','productPackingName_carton','productPacking_inner','productPackingName_inner','productPacking_unit','productPackingName_unit')->where('productId',$productId)->first();
        if(isset($productName))
            return Response::json($productName); 
        else
            return "";
        
    }

    public function queryReceiving(){
        $filter = Input :: get('filterData');
        $mode = Input::get('mode');

        if($mode=='collection'){
            $receivings = Receiving::whereBetween('receiving_date',[$filter['startReceiveDate'],$filter['endReceiveDate']])->with(['purchaseorder'=>function($q){
                $q->with('supplier');
            }]);
            return Datatables::of($receivings)->make(true);
        }
    }

    public function repack(){
        $receivingId = Input::get('receivingId');
        $good_qty = Input::get('good_qty');
        DB::table('receivings')->where('receivingId',$receivingId)->update(['good_qty'=>$good_qty]);
    }

    
    public function addAjust()
    {
        $storeMessage = array();
        //Adjust
        $adjustTable = Input::get('items');
        $rece = new ReceiveMan();
        $adjustMain = new AdjustMain();
        $receiving = new ReceiveRepackMain();
        
        if(isset($adjustTable))
        {
           //$product = Product ::select()
           foreach($adjustTable as $obj)
           {
               $adjustMain->setItems($obj['adjustId'],$obj['adjustType'],$obj['qty'],$obj['productId']);
               $adjustMain->setReceiveItems($obj['adjustId'],$obj['qty'],$obj['productId'],$obj['unit']);
           }
           $storeMessage[] =  $adjustMain->save();
        }
        //Receving
                if(is_array($storeMessage[0]['status']) && isset($storeMessage[0]['status']))
                {  
                    foreach($storeMessage[0]['status'] as $itemss)
                    {
                        $receiving->setAdjustItems($itemss['receivingId'],$itemss['productId'],$itemss['adjust_qty'],"",$itemss['unit'],$itemss['adjustId']);
                    }
                    $storeMessage[] = $receiving->save();
                }

        return $storeMessage;
    }
    
    public function reunit($v)
    {
        $carton = ($v->productPacking_carton) ? $v->productPacking_carton:1;
        $inner = ($v->productPacking_inner) ? $v->productPacking_inner:1;
        $unit = ($v->productPacking_unit) ? $v->productPacking_unit:1;

        if($v->productQtyUnit == 'carton')
            $real_normalized_unit =  $v->productQty*$inner*$unit;
        else if($v->productQtyUnit == 'inner')
            $real_normalized_unit =  $v->productQty*$unit;
        else
            $real_normalized_unit =  $v->productQty;
    }
    
}