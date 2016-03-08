<?php

class rePackController extends BaseController {

    public $adjustTable = "";
    public $remain = 0;
   // public $expiry_date = '';
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

       //

       // pd($recevings);

        foreach($recevings as $k => $v){
            $result = [
              'total' =>   (isset($result['total'])?$result['total']:0) + $v->good_qty,
              'productName' => $v->product->productName_chi,
                'productPackingName_unit' => $v->product->productPackingName_unit,
                'productPackingName_inner' => $v->product->productPackingName_inner,
                'productPackingName_carton' => $v->product->productPackingName_carton,
                'normalized_unit' => $v->product->normalized_unit,
            ];
        }

        return Response::json($result);
    }

    public function preRepackProduct()
    {
        $productId = Input :: get('productId');
        $productName = Product :: select ('productName_chi','productPacking_carton','productPackingName_carton','productPacking_inner','productPackingName_inner','productPacking_unit','productPackingName_unit','productPackingInterval_inner','productPackingInterval_unit','productPackingInterval_carton')->where('productId',$productId)->first();

        if(isset($productName))
            $productName->normalized_unit = $productName->normalized_unit;


        if(isset($productName))
            return Response::json($productName); 
        else
            return "";
        
    }

    public function queryReceiving(){
        $filter = Input :: get('filterData');
        $mode = Input::get('mode');

        if($mode=='collection'){
            $receivings = Receiving::whereBetween('receiving_date',[$filter['startReceiveDate'],$filter['endReceiveDate']])
                ->leftJoin('purchaseorders', function($join) {
                    $join->on('purchaseorders.poCode', '=', 'receivings.poCode');
                })->with(['purchaseorder'=>function($q){
                $q->with('supplier');
            }])->with('product');

            if($filter['supplier']!='')
                $receivings->where('purchaseorders.supplierCode', '=', $filter['supplier']);

            if($filter['productId']!='')
                $receivings->where('productId', '=', $filter['productId']);

                if($filter['poCode']!='')
                    $receivings->where('receivings.poCode', 'LIKE', $filter['poCode'] . '%');
            $receivings = $receivings->orderby('receivings.poCode','desc');
            return Datatables::of($receivings)
                ->editColumn('unit_cost', function ($p) {
                    return '$'.number_format($p->unit_cost,2,'.',',');
                })  ->editColumn('rec_good_qty', function ($p) {
                    return $p->rec_good_qty.$p->product->productPackingName_unit;
                })->addColumn('lineAmount',function($p){
                    return '$'.number_format($p->rec_receiveQty*$p->rec_receivePrice,2,'.',',');
                })->editColumn('supplierName',function($p){
                    return isset($p->purchaseorder->supplier->supplierName)?$p->purchaseorder->supplier->supplierName:'';
                })->editColumn('countryId',function($p){
                    return isset($p->purchaseorder->supplier->countryId)?$p->purchaseorder->supplier->countryId:'';
                })


                ->make(true);
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
       // $storeMessage = array();
        //Adjust
        //$this->adjustTable =

            $items = Input::get('items');
            $outProduct = Input::get('outProduct');
            $totalQty = 0;

        foreach ($items as $k => $v){
            if($v['deleted'] == 0){
                $undeductUnit = $v['total_finalized_unit'];
                //$this->expiry_date  = '';
                $this->remain = 0;
                while($undeductUnit > 0 ){
                    $receiving = Receiving::where('productId',$outProduct['productId'])->where('good_qty','>=',$v['packing_size'])->orderBy('expiry_date','asc')->first();

                    if(count($receiving)==0){
                        return [
                            'status' => 'error',
                            'msg' => 'not enough soruce product',
                        ];
                    }
                    $org_good_qty = $receiving->good_qty;
                    $receiving->good_qty+=$this->remain;
                    if($undeductUnit > $receiving->good_qty){
                        $this->remain = $receiving->good_qty % $v['packing_size'];

                       // if($this->expiry_date == '')
                        //    $this->expiry_date = $receiving->expiry_date;

                        $actual_deduct_qty = $receiving->good_qty;
                        $ava_qty = ($receiving->good_qty-$this->remain);
                        $undeductUnit -= $ava_qty;
                    }else{
                        $actual_deduct_qty = $undeductUnit;
                        $ava_qty = $undeductUnit;
                        $undeductUnit = 0;
                    }
                    //$totalQty += $actual_deduct_qty;
                    $receiving->good_qty -= $actual_deduct_qty;
                    $receiving->save();

                    // Raw source deduction
                    $adjustsRaw = new adjust();
                    $adjustsRaw->receivingId = $receiving->receivingId;
                    $adjustsRaw->adjustType = 1;
                    $adjustsRaw->good_qty = $org_good_qty;
                    $adjustsRaw->adjusted_good_qty = $receiving->good_qty;
                    $adjustsRaw->productId = ucwords($receiving->productId);
                    $adjustsRaw->save();
                    //end

                    $adjusts = new adjust();
                    $adjusts->adjustId = $receiving->id;
                    $reId = Receiving::where('receivingId','LIKE',"P%")->orderBy('receivingId','desc')->first();

                    if(count($reId)>0){
                        $id = substr($reId->receivingId,1);
                        $id += 1;
                    }else{
                        $id = 1;
                    }
                    $adjusts->receivingId = 'P'.$id;
                    $adjusts->adjustType = 1;
                    $adjusts->good_qty = 0;
                    $adjusts->adjusted_good_qty = $ava_qty;
                    $adjusts->productId = ucwords($v['productId']);

                    $new_receiving = new Receiving();
                    $new_receiving->receivingId = 'P'.$id;
                    $new_receiving->adjustId = $receiving->id;
                    $new_receiving->good_qty = $ava_qty;
                    $new_receiving->productId = ucwords($v['productId']);
                    $new_receiving->rec_good_qty = $ava_qty;
                    $new_receiving->rec_damage_qty = 0;
                    $new_receiving->damage_qty = 0;
                    $new_receiving->on_hold_qty = 0;
                    $new_receiving->expiry_date = $receiving->expiry_date;
                    $new_receiving->unit_cost = $receiving->unit_cost;
                    $new_receiving->created_by = Auth::user()->id;
                    $new_receiving->save();

                    $adjusts->save();
                }
            }
        }
die();
        $rece = new ReceiveMan();
        $adjustMain = new AdjustMain();
        $sql = "Select r.good_qty,p.productName_chi,r.expiry_date,r.receivingId,r.id,r.poCode,p.productPacking_carton,p.productPacking_inner,p.productPacking_unit from receivings as r,product as p where p.productId = r.productId and p.product_flag = 'p' and r.productId ='".$originalItem."' order by expiry_date asc";
        $info = DB::select(DB::raw($sql));
        
        $count = 0;
      
       // if(count($info)>0)
        //{
            $store = $info;
            $instorage = 0;
            $require = 0;

            if(isset($this->adjustTable))
            {
               /* foreach($adjustTable as $k=>$v)
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
                }*/
                
                $nextLopp = 0;
                $flag = 0; 
   
                
                foreach($this->adjustTable as $k2=>$v3)
                {
                    $storeAccum = $v3['qty'] * $v3['normalized_unit'];
                    foreach($store as $k1=>$v1)
                    {
                        pd($storeAccum);
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
                   // }             
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