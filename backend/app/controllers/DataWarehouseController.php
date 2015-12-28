<?php

class DataWarehouseController extends BaseController {

    public function jsonSearchDataProduct()
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
            $productData = data_product::where('productName_chi', 'LIKE', '%' . $keyword . '%')
                ->orwhere('id', 'LIKE', '%' . $keyword . '%')
                ->limit(20)->get();

        }

        return Response::json($productData);
    }

    public function getInvoice(){

        ini_set('memory_limit', '-1');
        set_time_limit(600);
        ini_set('mysql.connect_timeout','0');
        ini_set('max_execution_time', '0');

        $times  = array();
        $current_year = date('Y');
        $current_month = date("n");
        for($month = 8; $month <= 8; $month++) {
            $first_minute = mktime(0, 0, 0, $month, 1,$current_year);
            $last_minute = mktime(23, 59, 59, $month, date('t', $first_minute),$current_year);
            $times[$month] = array($first_minute, $last_minute);
        }





        // update datawarehouse_custoemr table.
   foreach($times as $k=>$v){

            $info =  DB::select(DB::raw('SELECT COUNT(1) as total, sum(amount) as amount,customerId FROM invoice WHERE invoiceStatus !=99 and invoiceStatus !=98 and invoiceStatus !=97 and invoiceStatus !=96 and deliveryDate BETWEEN '.$v[0].' AND '.$v[1].' GROUP BY customerId'));
            $info_return =  DB::select(DB::raw('SELECT COUNT(1) as total, sum(amount) as amount,customerId FROM invoice WHERE invoiceStatus =98 and deliveryDate BETWEEN '.$v[0].' AND '.$v[1].' GROUP BY customerId'));

            foreach($info_return as $v){
                $arr[$v->customerId]['total'] = $v->total;
                $arr[$v->customerId]['amount'] = $v->amount;
            }
            if(count($info)>0){
               datawarehouse_customer::where('month',$k)->where('year',$current_year)->delete();
                foreach($info as $v1){
                    $save = new datawarehouse_customer();
                    $save->customer_id = $v1->customerId;

                    if(isset($arr[$v1->customerId])){
                        $save->amount = $v1->amount-$arr[$v1->customerId]['amount'];
                        $save->qty = $v1->total-$arr[$v1->customerId]['total'];
                    }else{
                        $save->amount = $v1->amount;
                        $save->qty = $v1->total;
                    }

                    $save->month = $k;
                    $save->year = $current_year;
                    $save->save();
                }
               echo $k."月<br>";
            }else{
                echo "no data";
            }

        }

//end of update datawarehouse_customer table


 // update datawarehouse_product table;


 foreach($times as $k=>$v){
     $invoiceQ = [];
      // $info =  DB::select(DB::raw('SELECT SUM(productQty) as total, sum(productQty*productPrice) as amount,productId FROM invoiceitem WHERE invoiceId IN (SELECT invoiceId FROM invoice WHERE invoiceStatus !=99 and invoiceStatus !=98 and invoiceStatus !=97 and invoiceStatus !=96 and deliveryDate BETWEEN '.$v[0].' AND '.$v[1].') GROUP BY productId'));

   /*  $invoices = Invoice::whereNoIn('invoiceStatus',[98,97,96])->wherebetween('deliveryDate',[$v[0],$v[1]])->lists('invoiceId');
     $info = InvoiceItem::leftJoin('Product', function ($join) {
         $join->on('InvoiceItem.productId', '=', 'Product.productId');
     })->whereIn('invoiceId',$invoices)->get();*/



    // $invoices = Invoice::whereNotIn('invoiceStatus',[97,96])->wherebetween('deliveryDate',[$v[0],$v[1]])->lists('invoiceId');


     $invoiceitems = invoiceitem::select('invoiceitem.productId','invoiceitem.invoiceId','invoiceStatus','productPrice','productQty','productPacking_carton','productPacking_inner','productPacking_unit','productPackingName_unit','productPackingName_carton','productQtyUnit')
         ->leftJoin('Product', function ($join) {
         $join->on('invoiceitem.productId', '=', 'Product.productId');
        })
         ->leftJoin('Invoice', function ($join) {
             $join->on('invoiceitem.invoiceId', '=', 'Invoice.invoiceId');
         })->whereNotIn('invoiceStatus',[99,96,97,3])->wherebetween('deliveryDate',[$v[0],$v[1]])->where('invoiceitem.productId','222')
         ->orderBy('deliveryDate','asc')->orderBy('invoiceItemId','asc')
         ->get();


     foreach($invoiceitems as $k2 => $v){
         $invoiceQ[$v->productId]['productId'] = $v->productId;
         $invoiceQ[$v->productId]['amount'] = (isset($invoiceQ[$v->productId]['amount'])?$invoiceQ[$v->productId]['amount']:0) + $v->productPrice* $v->productQty;

         if(!isset($invoiceQ[$v->productId]['normalizedQty'])){
             $invoiceQ[$v->productId]['normalizedQty'] = 0;
         }

         $carton = ($v->productPacking_carton) ? $v->productPacking_carton:1;
         $inner = ($v->productPacking_inner) ? $v->productPacking_inner:1;
         $unit = ($v->productPacking_unit) ? $v->productPacking_unit:1;


             if($v->productQtyUnit == 'carton')
                 $real_normalized_unit =  $v->productQty*$inner*$unit;
            else if($v->productQtyUnit == 'inner')
                 $real_normalized_unit =  $v->productQty*$unit;
             else
                 $real_normalized_unit =  $v->productQty;


         $invoiceQ[$v->productId]['normalizedQty'] +=  $real_normalized_unit;
         $invoiceQ[$v->productId]['normalizedUnitName'] = $v->productPackingName_unit;
         $invoiceQ[$v->productId]['unitPerCarton'] = $carton*$inner*$unit;
         $invoiceQ[$v->productId]['cartonName'] = $v->productPackingName_carton;
     }


pd($invoiceQ);

     foreach($invoiceQ as &$vv){
         $vv['cartonQtys'] = number_format($vv['normalizedQty']/$vv['unitPerCarton'],1,'.','');
     }

            if(count($invoiceQ)>0){
                datawarehouse_product::where('month',$k)->where('year',$current_year)->delete();
                foreach($invoiceQ as $k1 => $v1){
                    $save = new datawarehouse_product();
                    $save->data_product_id = $v1['productId'];
                    $save->amount = $v1['amount'];
                    $save->qty = $v1['cartonQtys'];
                    $save->unitName = $v1['cartonName'];
                    $save->month = $k;
                    $save->year = $current_year;
                    $save->save();
                }
                  echo $k."月<br>";
            }

        }



//update invoice amount to invoices table;
        /*
        $start = 500000;
        $taken = 20000;

        $count = DB::select('SELECT COUNT(DISTINCT data_invoice_id) as total FROM data_invoiceitems');
       $j = ceil(($count[0]->total-$start) / $taken);

       for ($i =0; $i<$j ; $i++){


            $data_invoiceitems = DB::select('SELECT SUM(productQty*productPrice) as amount,data_invoice_id FROM data_invoiceitems GROUP BY data_invoice_id Order by data_invoice_id limit '.$start.','.$taken);


            foreach($data_invoiceitems as $v){

                $user = data_invoice::find($v->data_invoice_id);

                if($user !== null){
                    $user->amount = $v->amount;
                    $user->save();
                }
            }

           echo $start."<br>";

           $start += 20000;
       }*/
//end of update invoice amount to invoices table;


        /*
$i = Customer::orderBy('customerId')->get();

        foreach ($i as $v8){
            if(data_invoice::where('customer_id',$v8->customerId)->exists())
                $customerid[]=$v8->customerId;
        }
*/
      /*  data_invoiceitem::select('data_invoice_id', DB::raw('sum(productQty*productPrice) as amount'))
            ->groupBy('data_invoice_id')
            ->orderBy('data_invoice_id')
            ->get()
            ->toArray()
        ->chunk('10000',function($q){
            $invoiceitems[]=$q;
            pd($invoiceitems);
        });*/

die;

        $data_invoices = data_invoice::select('customer_id', DB::raw('count(customer_id) as total'))
            ->groupBy('customer_id')
            ->orderBy('customer_id')
            ->get();


        foreach($data_invoices as $v){

            $customers[]=$v->customer_id;
           // $info[$i][$v->total]=$v->total;
            //$i++;
        }
       // pd($info);



       // pd($customerid);
/*
      foreach($times as $k=>$v){

            foreach($customers as $v2){

                $amount='';

                $info = data_invoice::where('customer_id',$v2)->with('data_invoiceitem')->whereBetween('deliveryDate',[$v[0],$v[1]])->get();

                if(count($info)>0){
                    foreach($info as $v1){
                        $amount += $v1->total_amount;
                    }
                    $save = new datawarehouse_customer();
                    $save->customer_id = $v2;
                    $save->amount = $amount;
                    $save->month = $k;
                    $save->year = '2014';
                    $save->invoice = sizeof($info);
                    $save->save();

                  //  echo $v2->customerId."<br>";
                }
            }

        }
*/
     // Customer::orderBy('customerId')->chunk('1000',function($q) use($times){


/*



*/
      //  });





       // echo date("Y-m-t", strtotime("-1 month") ) ;

      //  echo $amount;
    }
}

