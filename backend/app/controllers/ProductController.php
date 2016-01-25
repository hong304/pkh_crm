<?php

class ProductController extends BaseController {

    public $_shift = '';
    public $message = "";
    private $invoice,$customer,$_date1,$_date2;
    public function jsonGetAllProduct()
    {
        // $time_start = microtime(true);

        $productList = Input::get('productList');

        $pa = [];
        if(count($productList)>0 ){
            foreach ($productList as $p){
                $pa[]= $p['productId'];
            }

        }

        // $products = Cache::remember('AllProducts', 5, function() use($pa)
        //   {
        $products = Product::select('productId', 'productName_chi','productLocation',
            'productPacking_carton', 'productPacking_inner', 'productPacking_unit', 'productPacking_size',
            'productPackingName_inner', 'productPackingName_unit', 'productPackingName_carton',
            'productPackingInterval_carton', 'productPackingInterval_inner', 'productPackingInterval_unit',
            'productMinPrice_carton', 'productMinPrice_inner', 'productMinPrice_unit',
            'productStdPrice_carton', 'productStdPrice_inner', 'productStdPrice_unit','allowNegativePrice',
            'supplierStdPrice_inner','supplierStdPrice_unit','productCost_unit',
            'supplierPackingInterval_carton', 'supplierPackingInterval_inner', 'supplierPackingInterval_unit');

        $products->where('productStatus', 'o');

        if(count($pa)>0){
            $products->orwherein('productId',$pa);
        }
        $products = $products->get()->toArray();


        // Switch to standard array
        $products = Product::compileProductStandardForm($products);

        // if customerid is given, get a compiled products json with customer discount information
        //$products = Product::compileProductAddCustomerDiscount($products, Input::get('customerId'));

        //    return $products;
        //   });

        // $time_end = microtime(true);
        //$time = $time_end - $time_start;
        //syslog(LOG_INFO, "Searched all products in $time seconds");

        return Response::json($products);
    }

    public function queryProduct(){

        $filter = Input::get('filterData');


      //  Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);

        $zone = (isset($filter['zone']['zoneId']))?$filter['zone']['zoneId']:'-1';
        $this->_date1 = (isset($filter['deliveryDate']) ? strtotime($filter['deliveryDate']) : strtotime("today"));
        $this->_date2 = (isset($filter['deliveryDate1']) ? strtotime($filter['deliveryDate1']) : strtotime("today"));
        $this->_shift = (isset($filter['shift']) ? $filter['shift'] : '-1');



        if($filter['name'] =='' && $filter['phone'] == ''&& $filter['customerId'] == ''){
            $empty = true;
            $this->data=[];
        }else{
            $empty = false;
        }

        $customers = Customer::where(function ($query) use ($filter) {
            $query
                ->where('customerName_chi', 'LIKE', $filter['name'] . '%')
                ->where('phone_1', 'LIKE', $filter['phone'] . '%')
                ->where('customerId', 'LIKE', $filter['customerId'] . '%');
        })->lists('customerName_chi','customerId');

        $invoices =  Invoice::select('invoice.invoiceId','deliveryDate','zoneId','customerId','productName_chi','invoiceitem.productId','productPrice','productQty','productUnitName','invoiceStatus')
            ->leftJoin('InvoiceItem', function($join) {
                $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
            })
            //  ->leftJoin('Customer', 'Invoice.customerId', '=', 'Customer.customerId')


            ->leftJoin('Product', function($join) {
                $join->on('InvoiceItem.productId', '=', 'Product.productId');
            });

        if(!$empty){
            $invoices->wherein('customerId',array_keys($customers));
        }

        if($zone != '-1')
            $invoices-> where('zoneId', $zone);
        else
            $invoices-> wherein('zoneId', explode(',', Auth::user()->temp_zone));

        if($this->_shift != '-1')
            $invoices->where('Invoice.shift',$this->_shift);

        $invoices->whereNull('InvoiceItem.deleted_at');

        $invoices->where(function ($query) use ($filter) {
            $query
                ->where('InvoiceItem.productId', 'LIKE', $filter['product'] . '%')
                ->where('productName_chi', 'LIKE', $filter['product_name'] . '%');
        });

        $invoices->whereBetween('Invoice.deliveryDate', [$this->_date1,$this->_date2]);


      //  $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;

        /*    foreach($invoices->get()->toArray() as $v){
                 $cid[]=$v['customerId'];
              }*/


      //  $invoices = $invoices->paginate($page_length);



        /*   $customers = Customer::wherein('customerId',$cid)->where(function ($query) use ($filter) {
               $query
                   ->where('customerName_chi', 'LIKE', '%' . $filter['name'] . '%')
                   ->where('phone_1', 'LIKE', '%' . $filter['phone'] . '%')
                   ->where('Invoice.customerId', 'LIKE', '%' . $filter['customerId'] . '%');
           })->lists('customerName_chi','customerId');*/


    /*    foreach ($invoices as $invoice) {
            $invoice->id = '<a onclick="goEdit(\'' . $invoice->invoiceId . '\')">'.$invoice->invoiceId.'</a>';
            $invoice->customerName_chi = $customers[$invoice->customerId];
        }*/


        return Datatables::of($invoices)
            ->addColumn('link', function ($invoice) {
                return '<a onclick="goEdit(\'' . $invoice->invoiceId . '\')">'.$invoice->invoiceId.'</a>';})
            ->addColumn('customerName_chi', function ($invoice) use($customers) {
                return $customers[$invoice->customerId];
            })->make(true);

                //  return Response::json($invoices);

    }


    public function queryProductExcel(){



        $filter = Input::get('filterData');


        //  Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);

        $zone = (isset($filter['zone']['zoneId']))?$filter['zone']['zoneId']:'-1';
        $this->_date1 = (isset($filter['deliveryDate']) ? strtotime($filter['deliveryDate']) : strtotime("today"));
        $this->_date2 = (isset($filter['deliveryDate1']) ? strtotime($filter['deliveryDate1']) : strtotime("today"));
        $this->_shift = (isset($filter['shift']) ? $filter['shift'] : '-1');



        if($filter['name'] =='' && $filter['phone'] == ''&& $filter['customerId'] == ''){
            $empty = true;
            $this->data=[];
        }else{
            $empty = false;
        }

        $customers = Customer::where(function ($query) use ($filter) {
            $query
                ->where('customerName_chi', 'LIKE', $filter['name'] . '%')
                ->where('phone_1', 'LIKE', $filter['phone'] . '%')
                ->where('customerId', 'LIKE', $filter['customerId'] . '%');
        })->lists('customerName_chi','customerId');

        $invoices =  Invoice::select('invoice.invoiceId','deliveryDate','zoneId','customerId','productName_chi','invoiceitem.productId','productPrice','productQty','productUnitName','invoiceStatus')
            ->leftJoin('InvoiceItem', function($join) {
                $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
            })
            //  ->leftJoin('Customer', 'Invoice.customerId', '=', 'Customer.customerId')


            ->leftJoin('Product', function($join) {
                $join->on('InvoiceItem.productId', '=', 'Product.productId');
            });

        if(!$empty){
            $invoices->wherein('customerId',array_keys($customers));
        }

        if($zone != '-1')
            $invoices-> where('zoneId', $zone);
        else
            $invoices-> wherein('zoneId', explode(',', Auth::user()->temp_zone));

        if($this->_shift != '-1')
            $invoices->where('Invoice.shift',$this->_shift);

        $invoices->whereNull('InvoiceItem.deleted_at');

        $invoices->where(function ($query) use ($filter) {
            $query
                ->where('InvoiceItem.productId', 'LIKE', $filter['product'] . '%')
                ->where('productName_chi', 'LIKE', $filter['product_name'] . '%');
        });

        $invoices->whereBetween('Invoice.deliveryDate', [$this->_date1,$this->_date2]);


        $this->invoice = $invoices->get();
        $this->customer = $customers;


        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';

        $i=4;
        $objPHPExcel = new PHPExcel ();

        $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '鎖售產品');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,)
        );

        $objPHPExcel->getActiveSheet()->setCellValue('A2', 'Delivery Date');
        $objPHPExcel->getActiveSheet()->setCellValue('B2', date('Y-m-d',$this->_date1));
        $objPHPExcel->getActiveSheet()->setCellValue('C2', 'To');
        $objPHPExcel->getActiveSheet()->setCellValue('D2', date('Y-m-d',$this->_date2));


        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '訂單編號');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, '送貨日期');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, '車線');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, '客戶名稱');
        $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, '產品編號');
        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, '品產名稱');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, '價錢');
        $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, '數量');
        $objPHPExcel->getActiveSheet()->setCellValue('I'.$i, '單位');



        $i += 1;
        foreach ($this->invoice as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $v['invoiceId']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, date('Y-m-d',$v['deliveryDate']));
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $v['zoneId']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $this->customer[$v['customerId']]);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v['productId']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $v['productName_chi']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $v['productPrice']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $v['productQty']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $v['productUnitName']);
            $i++;

            $longest[] = strlen($this->customer[$v['customerId']]);
            $longestF[] = strlen($v['productName_chi']);
        }

       // $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, '總計:');
       // $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, sprintf("HK$ %s",end($this->data)['accumulator']));
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(13);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(max($longest));
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(max($longestF));

      /*  foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            if($col != 'D' && $col != 'F')
                $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }*/



        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date('Ymd',$this->_date1).'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
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

        $customerId = Input::get('customerId');

        $products = null;


        $invoices = Invoice::where('customerId', $customerId)->orderBy('deliveryDate', 'desc')->limit(20)->get();


        foreach($invoices as $invoice)
        {
            $invoiceId[] = $invoice->invoiceId;
            $invoiceDetail[$invoice->invoiceId] = $invoice->toArray();
        }

        if($invoices)
        {
            // get all items from invoices db
            $products = InvoiceItem::wherein('Invoice.invoiceId', $invoiceId)->leftJoin('invoice', function($join) {
                $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
            })
                ->orderBy('deliveryDate', 'desc')
                ->with('productDetail')
                ->get();
            foreach($products as $product)
            {

                if(!isset($productCustom[$product->productId]))
                {
                    $productCustom[$product->productId] = $product->toArray();
                    $productCustom[$product->productId]['deliveryDate'] = $invoiceDetail[$product->invoiceId]['deliveryDate'];
                    $productCustom[$product->productId]['productStatus'] = ($productCustom[$product->productId]['product_detail']['productStatus'] == "o") ? "": "(暫停)";
                }
            }

        }
        //dd(DB::getQueryLog());

        return Response::json($productCustom);
    }

    public function jsonSearchProductOrHotItem()
    {
        $keyword = Input::has('keyword') && Input::get('keyword') != '' ? Input::get('keyword') : 'na';
        $customerId = Input::has('customerId') && Input::get('customerId') != '' ? Input::get('customerId') : 'na';
        # Process
        if($keyword == 'na')
        {

            $productData = "";
            if($customerId != 'na')
            {
                $iicm = ProductSearchCustomerMap::where('customerId', $customerId)->with('productDetail')->limit(20)->orderBy('sumation', 'desc')->get();


                if($iicm->count() > 0)
                {
                    foreach($iicm as $i)
                    {
                        $productData[] = $i->productDetail->toArray();
                    }

                }
            }

        }
        else
        {
            $keyword = str_replace('*', '%', $keyword);
            $productData = Product::select('productName_chi','productId')
                ->where(function ($query) use ($keyword) {
                    $query->where('productName_chi', 'LIKE', '%' . $keyword . '%')
                        ->orwhere('productId', 'LIKE', '%' . $keyword . '%');
                })->where('productStatus','o')
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
        if(Input::get('mode') == 'getNewId'){
                  return Response::json(Product::select('pattern_key')->where('productId','LIKE',Input::get('groupPrefix').'%')->orderBy('pattern_key','desc')->limit(1)->lists('pattern_key')[0]+1);
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

          //  Paginator::setCurrentPage((Input::get('start')+10) / Input::get('length'));
            $product = Product::where('deleted',false);

            if($filter['keyword'] != '')
            {
                $keyword = str_replace(array('*', '?'), '%', $filter['keyword']);
                $product->where(function ($query) use ($keyword) {
                    $query->where('productName_chi', 'LIKE', '%' . $keyword . '%')
                        ->orwhere('productId', 'LIKE', '%' . $keyword . '%');
                })->wherein('productStatus', ['o','s']);
            }

            if($filter['group'] != '')
            {
                $groupid =  substr($filter['group']['groupid'], 0, -1);
                $pieces = explode("-",$groupid);

                $product->where('department', $pieces[0]);
                $product->where('group', $pieces[1]);
            }

            if ($filter['status']) {
                $product->where('productStatus', $filter['status']);
            }else{
                $product->wherein('productStatus', ['o','s']);
            }

            if($filter['productLocation'])
                $product->where('productLocation', $filter['productLocation']);


          //  $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $product = $product->orderBy('updated_at','desc');

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

            return Datatables::of($product)
                ->addColumn('link', function ($p) {
                  //  if(Auth::user()->can('edit_product'))
                    if(Auth::user()->id == 8 || Auth::user()->id == 9 || Auth::user()->can('edit_product'))
                    return '<span onclick="editProduct(\''.$p->productId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                        else
                    return '';
                })->editColumn('productStatus', function ($p) {
                    if($p->productStatus == 'o'){
                        return '正常';
                    }else{
                        return '暫停';
                    }
                })->editColumn('hasCommission', function ($p) {
                    if($p->hasCommission == 1){
                        return '有';
                    }else{
                        return '';
                    }
                })->editColumn('productStdPrice_carton', function ($p) {
                    return $p->productStdPrice_carton .' / '. $p->productPackingName_carton;
                })->editColumn('productStdPrice_inner', function ($p) {
                    return $p->productStdPrice_inner .' / '. $p->productPackingName_inner;
                })->editColumn('productStdPrice_unit', function ($p) {
                    return $p->productStdPrice_unit .' / '. $p->productPackingName_unit;
                })
                ->make(true);

        }
        elseif($mode == 'single')
        {
            $product = Product::where('productId', Input::get('productId'))->first();
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

    public function jsonQueryProductDepartment()
    {

        $mode = Input::get('mode');
        $sorting = "productDepartmentId";
        $currentSorting = "";
        $filterData = Input ::get('filterData');
        if($filterData['sorting'] != "")
        {
            $sorting = $filterData['sorting'];
        }
        $currentSorting = $filterData['current_sorting'];

        if($mode == 'collection')
        {

            $product = ProductGroup::select('*')->orderBy($sorting,$currentSorting);

              return Datatables::of($product)
                ->addColumn('link', function ($produc) {
                    if(Auth::user()->can('edit_productGroup'))
                         return '<span onclick="editProductGroup(\''.$produc['productDepartmentId'].'\',\''.$produc['productGroupId'].'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                    else
                        return '';
                })
                ->make(true);

        }
        elseif($mode == 'single')
        {
            $product = Product::where('productId', Input::get('productId'))->first();
        }elseif($mode == 'dropdown')
        {
            $product = ProductGroup::distinct()->select('productDepartmentId','productDepartmentName')->get()->toArray();
        }elseif($mode == 'queryItemInfo')
        {
            $info = Input :: get('info');
            $product = ProductGroup :: select('*')->where('productDepartmentId',$info['productDepartmentId'])->where('productGroupId',$info['productGroupId'])->first();
        }

        return Response::json($product);
    }
    
    public function jsonManProductDepartment()
    {
        $gpObject = Input::get('info');
        $departmentId = (!$gpObject['productDepartmentId'] == "") ? $gpObject['productDepartmentId'] :false ;
        $groupId = (!$gpObject['productGroupId'] == "") ? $gpObject['productGroupId'] : false;
        $gp = new ProductGroupManipulation($departmentId,$groupId,$gpObject);
       
       if(empty($this->validation($gpObject)))
       {
           $id = $gp->save($gpObject);
           return Response::json(['id' => $gp]);
       }else
       {
            $errorMessage = "";
          //  foreach($this->message as $a)
          //  {
           //     $errorMessage .= "$a\n";
          //  }
            return $this->message;
       }
        
    }
    
       public function validation($e)
    {
        $rules = [
	            'productDepartmentId' => 'required',
	            'productGroupName' => 'required',
                  //  'productGroupId' => 'required',
                //    'productDepartmentName' => 'required',
	        ];
         
      
         $validator = Validator::make($e, $rules);
	 if ($validator->fails())
	  {
             $this->message = "請輸入所需信息";
	       //$this->message = $validator->messages()->all();
               //
	           // return Redirect::action('UserController@authenticationProcess')->with('flash_error', 'Invalid Credential. Please try again');
	  }
       
          return $this->message;
          
    }
    


}