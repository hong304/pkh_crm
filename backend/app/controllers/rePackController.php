<?php

class rePackController extends BaseController {

     public function getAllProducts()
    {
        $productId = Input :: get('productId');
        $allProduct = Receiving::where('productId',$productId)->orderby('expiry_date')->first();
        if(count($allProduct) > 0)
        {
            $store = $allProduct;
        }else
        {
            $store = "false";
        }
        return Response::json($store); 
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
    
   
}