<?php

class ProductController extends BaseController {

    public function jsonGetAllProduct()
    {
        $time_start = microtime(true);
        
        $products = Cache::remember('AllProducts', 5, function()
        {
            $products = Product::select('productId', 'productName_chi','productLocation',
            'productPacking_carton', 'productPacking_inner', 'productPacking_unit', 'productPacking_size',
            'productPackingName_inner', 'productPackingName_unit', 'productPackingName_carton',
            'productPackingInterval_carton', 'productPackingInterval_inner', 'productPackingInterval_unit',
            'productMinPrice_carton', 'productMinPrice_inner', 'productMinPrice_unit',
            'productStdPrice_carton', 'productStdPrice_inner', 'productStdPrice_unit');
        
            $products = $products->where('productStatus', 'o')->get();
            $products = $products->toArray();
            
            // Switch to standard array
            $products = Product::compileProductStandardForm($products);

            // if customerid is given, get a compiled products json with customer discount information
            //$products = Product::compileProductAddCustomerDiscount($products, Input::get('customerId'));
            
            return $products;
        });       

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        syslog(LOG_INFO, "Searched all products in $time seconds");
        
        return Response::json($products);
    }
    
    public function jsonGetProductsfromGroup()
    {
        $time_start = microtime(true);
        
        $departmentid = Input::get('departmentid');
        $groupid = Input::get('groupid');
        $productcode_prefix = $departmentid . '-' . $groupid;
        $products = Cache::remember('Products_' . $productcode_prefix, 5, function() use($departmentid,$groupid)
        {
            $products = Product::select('productId', 'productPacking_carton', 'productPacking_inner', 'productPacking_unit', 'productPacking_size', 'productStdPrice_carton', 'productStdPrice_inner', 'productStdPrice_unit', 'productName_chi')
                                ->where('department', $departmentid)
                                 ->where('group',$groupid)
                                ->where('productStatus', 'o')
                                ->get();
            $products = $products->toArray();
            
            return $products;
        });
        
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        syslog(LOG_INFO, "Searched all products in $time seconds");
        
        return Response::json(array_chunk($products, 500)[0]);
    }
    
    public function jsonGetProductGroups()
    {
        $json = [
            'groups' => ProductGroup::getInheritatedGroupList(),
        ];
    
        return Response::json($json);
    }
    
    public function jsonFindRecentProductsByCustomerId()
    {
        if(!Input::has('customerId'))
        {
            App::abort(500, 'Missing Customer Id.');
        }
        
        $productId = Input::get('customerId');

        $products = null;
        
        
        $invoices = Invoice::where('customerId', $productId)->orderBy('deliveryDate', 'desc')->get();
        foreach($invoices as $invoice)
        {
            $invoiceId[] = $invoice->invoiceId;
            $invoiceDetail[$invoice->invoiceId] = $invoice->toArray();
        }
        
        if($invoices)
        {
            // get all items from invoices db
            $products = InvoiceItem::wherein('invoiceId', $invoiceId)
                        ->orderBy('created_at', 'desc')
                        ->with('productDetail')
                        ->get();
            foreach($products as $product)
            {

                if(!isset($productCustom[$product->productId]))
                {
                    $productCustom[$product->productId] = $product->toArray();
                    $productCustom[$product->productId]['deliveryDate'] = $invoiceDetail[$product->invoiceId]['deliveryDate'];
                }
            }
        }
        //dd(DB::getQueryLog());

        return Response::json($productCustom);
    }
    
    public function jsonSearchProductOrHotItem()
    {
        $keyword = Input::has('keyword') && Input::get('keyword') != '' ? Input::get('keyword') : 'na';
        $productId = Input::has('customerId') && Input::get('customerId') != '' ? Input::get('customerId') : 'na';
        # Process
        if($keyword == 'na')
        {
            
            $productData = "";
            if($productId != 'na')
            {
                $iicm = ProductSearchCustomerMap::where('customerId', $productId)->with('productDetail')->limit(20)->orderBy('sumation', 'desc')->get();
                if($iicm->count() > 0)
                {
                    foreach($iicm as $i)
                    {
                        $productData[] = $i->product_detail->toArray();
                    }
                    
                }
            }
             
        }
        else
        {
            $keyword = str_replace('*', '%', $keyword); 
            $productData = Product::select('productName_chi','productId')->where('productName_chi', 'LIKE', '%' . $keyword . '%')
                                    ->orwhere('productId', 'LIKE', '%' . $keyword . '%')
                                    ->where('productStatus','o')
                                    ->limit(30)->get();
             
        }
        
        return Response::json($productData);
    }


    
    public function jsonManiulateProduct()
    {

        if(Input::get('mode') == 'del'){
            // pd(Input::get('customer_id'));
            Product::where('productId',Input::get('customer_id'))->update(['productStatus'=>'s','deleted'=>1]);
            // p(Input::get('customer_id'));
            return [];
        }


        $i = Input::get('info');

        $cm = new ProductManipulation($i['productId'], (isset($i['group']['groupid']) ? $i['group']['groupid'] : false), (isset($i['productnewId']) ? $i['productnewId']: false));
      //  $cm = new ProductManipulation($i['productId'], (isset($i['productId']) ? false : $i['group']['groupid']));
        $id = $cm->save($i);
        
        return Response::json(['mode'=>($i['productId'] == $id ? 'update' : 'create'), 'id'=>$id]);
    }
    
public function jsonQueryProduct()
    {
    
    
        $mode = Input::get('mode');
    
        if($mode == 'collection')
        {
            $filter = Input::get('filterData');
            Paginator::setCurrentPage((Input::get('start')+10) / Input::get('length'));
            $product = Product::select('*')->where('deleted',false);





            if($filter['keyword'] != '')
            {
                $filter['keyword'] = str_replace(array('*', '?'), '%', $filter['keyword']);
                $product->where('productId', 'LIKE', '%'.$filter['keyword'].'%')
                        ->orwhere('productName_chi', 'LIKE', '%'.$filter['keyword'].'%');
            } 
            elseif($filter['group'] != '')
            {
                $groupid =  substr($filter['group']['groupid'], 0, -1);
                $pieces = explode("-",$groupid);

                $product->where('department', $pieces[0]);
                $product->where('group', $pieces[1]);
            }
            // query
    
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $product = $product->paginate($page_length);
    
            $product = $product->toArray();
            
            foreach($product['data'] as $c)
            {

                if($c['productStatus'] == 'o'){
                    $c['productStatus'] = '正常';
                }else{
                    $c['productStatus'] = '暫停';
                }
                $c['delete'] = '<span onclick="delCustomer(\''.$c['productId'].'\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';

                $c['link'] = '<span onclick="editProduct(\''.$c['productId'].'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                $products[] = $c;
            }
            
            $product['data'] = $products;
            
        }
        elseif($mode == 'single')
        {
            $product = Product::where('productId', Input::get('productId'))->first();
        }elseif($mode == 'checkId'){
            $product = Product::select('productId')->where('productId', Input::get('productId'))->first();
            $product = count($product);
        }
    
        return Response::json($product);
    }
    
    public function jsonQueryProductDepartment()
    {
    
    
        $mode = Input::get('mode');
    
        if($mode == 'collection')
        {
            $filter = Input::get('filterData');
            Paginator::setCurrentPage((Input::get('start')+10) / Input::get('length'));
            $product = ProductGroup::select('*');
    
    
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $product = $product->paginate($page_length);
    
            $product = $product->toArray();
    
            foreach($product['data'] as $c)
            {
    
                $c['link'] = '<span onclick="editProduct(\''.$c['productDepartmentId'].'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                $products[] = $c;
            }
    
            $product['data'] = $products;
    
        }
        elseif($mode == 'single')
        {
            $product = Product::where('productId', Input::get('productId'))->first();
        }
    
        return Response::json($product);
    }
    
}