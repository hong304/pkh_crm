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

    public function outRepackProduct(){
        $productId = Input :: get('productId');
        $recevings = Receiving::where('productId',$productId)->where('good_qty','>',0)->with('product')->get();
        foreach($recevings as $k => $v){
            //$result =
        }
    }

    public function preRepackProduct()
    {
        $productId = Input :: get('productId');
        $productName = Product :: select ('productName_chi','productPacking_carton','productPackingName_carton','productPacking_inner','productPackingName_inner','productPacking_unit','productPackingName_unit','productPackingInterval_inner','productPackingInterval_unit','productPackingInterval_carton')->where('productId',$productId)->first();
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
    
    public function transaction($id,$good_qty,$receivingId,$poCode,$storeAccum,$originalItem)
    {
         DB::table('receivings')->where('id',$id)->where('good_qty','>',0)->update(['good_qty'=>$good_qty]);
         $AjustSql = "Insert into adjusts(receivingId,poCode,adjustType,adjust_qty,productId) values ('".$receivingId."','".$poCode."','1','".$storeAccum."','".$originalItem."')"; 
         DB::insert(DB::raw($AjustSql)); 
    }

    
    public function addAjust()
    {
        $storeMessage = array();
        //Adjust
        $adjustTable = Input::get('items');
        $rece = new ReceiveMan();
        $adjustMain = new AdjustMain();

        $originalItem = Input::get('originalProductid');
        $sql = "Select r.good_qty,p.productName_chi,r.expiry_date,r.receivingId,r.id,r.poCode,p.productPacking_carton,p.productPacking_inner,p.productPacking_unit from receivings as r,product as p where p.productId = r.productId and p.product_flag = 'p' and r.productId ='".$originalItem."' order by expiry_date asc";
        $info = DB::select(DB::raw($sql));
        $count = 0;
        if(count($info)>0)
        {
            $store = $info;
            $instorage = 0;
            $require = 0;
            if(isset($adjustTable))
            {
                foreach($adjustTable as $k=>$v)
                {
                    $repackedProductSql = "Select productPacking_carton,productPacking_inner,productPacking_unit from Product where productId=".$v['productId'];
                    $repackedProduct = DB::select(DB::raw($repackedProductSql));
                    $smallUnit = $this->reunit($repackedProduct[0],$v['good_qty'],$v['productlevel']['value']);
                    $adjustTable[$count]['good_qty'] = $smallUnit;
                    $count++;
                    $require += $smallUnit;
                }
                //accumulate storage
                foreach($store as $k1=>$v1)
                {
                    $instorage += $v1->good_qty;
                }
                if($require > $instorage)
                {
                    return "不夠貨包裝";
                }
                
                $nextLopp = 0;
                $flag = 0; 
                foreach($adjustTable as $k2=>$v3)
                {
                    $storeAccum = $v3['good_qty'];
                    foreach($store as $k1=>$v1)
                    {
                        if($v1->good_qty > 0)
                        {
                            $accum = $v1->good_qty - $storeAccum;
                            $storeAccum = $accum;
                            if($accum >= 0)
                            {
                                DB::table('receivings')->where('id',$v1->id)->where('good_qty','>',0)->update(['good_qty'=>$accum]); // withdraw storage,update original record 
                                $posAjustSql = "Insert into adjusts(receivingId,poCode,adjustType,adjust_qty,productId) values ('".$v1->receivingId."','".$v1->poCode."','1','".abs($storeAccum)."','".$originalItem."')"; 
                                 //create adjust record for original record
                                DB::insert(DB::raw($posAjustSql));
                                
                               // if($flag == 0)
                                    $adjustTable[$nextLopp]['adjustId'] = $v1->id;
                               // else if($flag > 0)
                                break; 
                            }else
                            {
                                 DB::table('receivings')->where('id',$v1->id)->where('good_qty','>',0)->update(['good_qty'=>0]);
                                 $negAjustSql = "Insert into adjusts(receivingId,poCode,adjustType,adjust_qty,productId) values ('".$v1->receivingId."','".$v1->poCode."','1','".$storeAccum."','".$originalItem."')"; 
                                 DB::insert(DB::raw($negAjustSql)); 
                               //  $flag = 1;
                                 $storeAccum = abs($storeAccum);
                                 continue;
                            }   
                        }else 
                        {
                            continue;
                        }
                            //$updateReceivingSql = "Update receivings set good_qty = (CASE good_qty WHEN good_qty >= ".$v3['good_qty']." THEN good_qty - ".$v3['good_qty']." ELSE good_qty END) where good_qty > 0 and id = ".$v1->id;
                    }             
                    $nextLopp++;
                  
                }
    
                  //DB::table('receivings')->where('id',$v1->id)->where('good_qty','>',0)->update(['good_qty'=>$storageminus]);
                
            }
        }else
        {
             return "empty";
        }
          
       
       /* 
        $receiving = new ReceiveRepackMain();
        
        if(isset($adjustTable))
        {
         //  $product = Product ::select()
           foreach($adjustTable as $obj)
           {
               $adjustMain->setItems($obj['adjustId'],$obj['adjustType'],$obj['good_qty'],$obj['productId']);
               $adjustMain->setReceiveItems($obj['adjustId'],$obj['good_qty'],$obj['productId'],$obj['unit']);
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

        return $storeMessage;*/
    }
    
    public function reunit($v,$productQty,$qty)
    {
        $carton = ($v->productPacking_carton) ? $v->productPacking_carton:1;
        $inner = ($v->productPacking_inner) ? $v->productPacking_inner:1;
        $unit = ($v->productPacking_unit) ? $v->productPacking_unit:1;
        $real_normalized_unit = 0;
        if($qty == 'carton')
            $real_normalized_unit =  $productQty*$inner*$unit;
        else if($qty == 'inner')
            $real_normalized_unit =  $productQty*$unit;
        else
            $real_normalized_unit =  $productQty;
        return $real_normalized_unit;
    }
    
}