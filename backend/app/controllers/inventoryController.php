<?php

class inventoryController extends BaseController {

    public $_shift = '';
    public $message = "";

    public function manipulateInventory(){

        $info = Input::get('info');
        $mode = Input::get('mode');

        if($mode == 'stockTake'){

            $date = date('Y-m-d', strtotime($info['expiry_date']));

            $adjusts = new adjust();
            $adjusts->poCode =$info['poCode'];
            $adjusts->receivingId =$info['receivingId'];
            $adjusts->productId =$info['productId'];
            $adjusts->adjusted_good_qty =$info['adjusted_good_qty'];
            $adjusts->adjusted_damage_qty =$info['adjusted_damage_qty'];
            $adjusts->good_qty =$info['good_qty'];
            $adjusts->damage_qty =$info['damage_qty'];
            $adjusts->adjustType = '2';
            $adjusts->save();

            $receivings = Receiving::where('id',$info['id'])->first();
            $difference = $info['adjusted_good_qty']-$receivings->good_qty;
            $receivings->good_qty =$info['adjusted_good_qty'];
            $receivings->damage_qty =$info['adjusted_damage_qty'];
            $receivings->expiry_date = $date;
            $receivings->updated_by = Auth::user()->id;
            $receivings->updated_at = date("Y-m-d H:i:s");
            $receivings->save();
        }else if ($mode == 'salesReturn'){

            $receivings = Receiving::where('productId',$info['productId'])->where('good_qty','>',0)->orderBy('expiry_date','asc')->first();

            $adjusts = new adjust();
            $adjusts->poCode =$receivings->poCode;
            $adjusts->receivingId =$receivings->receivingId;
            $adjusts->productId =$info['productId'];
            $adjusts->adjusted_good_qty = $info['good_qty'] + $info['return_good_qty'];
            $adjusts->adjusted_damage_qty = $info['damage_qty'] + $info['return_damage_qty'];
            $adjusts->good_qty =$info['good_qty'];
            $adjusts->damage_qty =$info['damage_qty'];
            $adjusts->adjustType = '3';
            $adjusts->save();

            $difference = $info['return_good_qty'];
            $receivings->good_qty += $info['return_good_qty'];
            $receivings->damage_qty += $info['return_damage_qty'];
            $receivings->updated_by = Auth::user()->id;
            $receivings->updated_at = date("Y-m-d H:i:s");
            $receivings->save();

        }

        $product = Product::where('productId',$info['productId'])->first();
        $product->total_good_qty += $difference;
        $product->timestamps = false;
        $product->save();
    }

    public function queryInventoryHistory(){

        $mode = Input::get('mode');
        $filterData = Input::get('filterData');
        if($mode == 'collection')
        {

            if(!Auth::user()->can('view_inventoryHistory')){
                return Response::json('Permission Denied');
            }

            $adjusts = Adjust::with('receiving');

     if($filterData['keyword'] != '')
         $adjusts->where('productId',$filterData['keyword']);

               $adjusts=$adjusts->orderby('updated_at','desc');

            return Datatables::of($adjusts)->editColumn('adjustType', function ($p) {
                if($p->adjustType == 1){
                    return 'Repack';
                }else  if($p->adjustType == 2){
                    return 'Stock Take';
                }else  if($p->adjustType == 3){
                    return 'Sale Return';
                }
            })->make(true);

        }
    }

    public function queryInventory()
    {
        $mode = Input::get('mode');

        if($mode == 'collection')
        {

          /*  DB::update('UPDATE receivings AS t
                INNER JOIN
                (SELECT productId,SUM(good_qty) tqty FROM receivings GROUP BY productId) t1
                ON t.productId = t1.productId
                SET total_qty=tqty');

            $products = receiving::groupBy('productId')
                ->selectRaw('sum(good_qty) as sum, productId')
                ->lists('sum','productId');

            $productId = receiving::groupBy('productId')
                ->lists('productId');

          DB::transaction(function() use ($products)
            {
                foreach ($products as $productId => $productValue) {
                    DB::table('product')
                        ->where('productId', $productId)
                        ->update(array('total_good_qty' => $productValue));
                }
            });*/

           // product::whereIn('productId',$productId)->update(['total_good_qty' => $products[]);
          //  pd($products);
          //  DB::update("UPDATE product SET total_good_qty = ((SELECT SUM(good_qty) FROM receivings WHERE receivings.productId = product.productId GROUP BY productId)/productPacking_inner/productPacking_unit)");

            $filter = Input::get('filterData');

            $receivings = Receiving::select('receivings.updated_at','poCode','productPackingName_carton','productPacking_unit','productPacking_inner','productPackingName_unit','id','receivings.productId','productName_chi','good_qty','total_qty','damage_qty','expiry_date','receivings.updated_by','receivings.updated_at','bin_location','on_hold_qty')->leftJoin('product','receivings.productId','=','product.productId');


            if($filter['keyword'] != '')
            {
                $keyword = str_replace(array('*', '?'), '%', $filter['keyword']);
                $receivings->where(function ($q) use ($keyword) {
                        $q->where('productName_chi', 'LIKE', '%' . $keyword . '%')
                            ->orwhere('receivings.productId', 'LIKE', '%' . $keyword . '%');
                    })->wherein('productStatus', ['o','s']);

            }else{
                $receivings->where('good_qty',$filter['zerofilter'],0);
            }

            if ($filter['status']) {
                $receivings->where('productStatus', $filter['status']);
            }else{
                $receivings->wherein('productStatus', ['o','s']);
            }

            if($filter['group'] != '')
            {
                $groupid =  substr($filter['group']['groupid'], 0, -1);
                $pieces = explode("-",$groupid);

                $receivings->where('department', $pieces[0]);
                $receivings->where('group', $pieces[1]);
            }

            if($filter['productLocation'])
                $receivings->where('productLocation', $filter['productLocation']);


            $filterId = "total_qty";
            if($filter["sorting"] != "")
                $filterId = $filter["sorting"];
            $filterOrder = $filter["current_sorting"];

            $receivings = $receivings->orderBy($filterId,$filterOrder);

          /*  $product = Product::where('deleted',false);





          /*  foreach($product['data'] as $c)
            {

                if($c['productStatus'] == 'o'){
                    $c['productStatus'] = '正常';
                }else{
                    $c['productStatus'] = '暫停';
                }
                //  $c['delete'] = '<span onclick="delCustomer(\''.$c['productId'].'\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';

                $c['link'] =
                $products[] = $c;
            }

            $product['data'] = $products;*/

            return Datatables::of($receivings)
                ->addColumn('link', function ($p) {
                    // if(Auth::user()->can('edit_product'))
                    return '<span onclick="editProduct(\'' . $p->id . '\')" class="btn btn-xs default"><i class="fa fa-search"></i>盤點</span>';
                    //      else
                    //  return '';
                }) ->editColumn('productId', function ($p) {
                    return '<a onclick="viewProduct(\'' . $p->productId . '\')">'.$p->productId.'</a>';
                    //      else
                    //  return '';
                })->addColumn('sales_return', function ($p) {
                        // if(Auth::user()->can('edit_product'))
                        return '<span onclick="salesReturn(\''.$p->id.'\')" class="btn btn-xs default"><i class="fa fa-search"></i>回貨</span>';

                })->editColumn('good_qty', function ($p){
                        return $p->good_qty.$p->productPackingName_unit;
                })->addColumn('qty_carton', function ($p) {
                    return number_format($p->good_qty/$p->productPacking_unit/$p->productPacking_inner,1).$p->productPackingName_carton;
                })->make(true);

        }
        elseif($mode == 'single')
        {
            $product = Receiving::where('id', Input::get('id'))->with('product')->first();
        }elseif($mode == 'checkId'){
            $product = Product::select('productId')->where('productId', Input::get('productId'))->first();
            $product = count($product);
        }elseif($mode == 'getGroupPrefix'){
            $pos = strpos(Input::get('group')['groupid'], '-');
            $prefix = substr(Input::get('group')['groupid'],0,$pos);
            return Response::json(DB::table('product')->select(DB::raw('DISTINCT SUBSTR(productId,1,1) as prefix'))->where('department',$prefix)->get());
        }

        return Response::json($product);
    }



}