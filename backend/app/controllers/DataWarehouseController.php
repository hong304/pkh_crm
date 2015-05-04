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


        set_time_limit(0);

        $times  = array();
        for($month = 1; $month <= 4; $month++) {
            $first_minute = mktime(0, 0, 0, $month, 1,2015);
            $last_minute = mktime(23, 59, 0, $month, date('t', $first_minute),2015);
            $times[$month] = array($first_minute, $last_minute);
        }

      //  pd ($times);

        // update datawarehouse_custoemr table.
      /*  foreach($times as $k=>$v){

            $info =  DB::select(DB::raw('SELECT COUNT(1) as total, sum(amount) as amount,customer_id FROM data_invoices WHERE deliveryDate BETWEEN '.$v[0].' AND '.$v[1].' GROUP BY customer_id'));

            if(count($info)>0){
                foreach($info as $v1){
                    $save = new datawarehouse_customer();
                    $save->customer_id = $v1->customer_id;
                    $save->amount = $v1->amount;
                    $save->qty = $v1->total;
                    $save->month = $k;
                    $save->year = '2015';
                    $save->save();
                }

                  echo $month."<br>";
            }else{
                echo "no data";
            }

        }*/

//end of update datawarehouse_customer table


 // update datawarehouse_product table;


 foreach($times as $k=>$v){

               $info =  DB::select(DB::raw('SELECT SUM(productQty) as total, sum(productQty*productPrice) as amount,product_id FROM data_invoiceitems WHERE data_invoice_id IN (SELECT id FROM data_invoices WHERE deliveryDate BETWEEN '.$v[0].' AND '.$v[1].') GROUP BY product_id'));

            if(count($info)>0){
                foreach($info as $v1){
                    $save = new datawarehouse_product();
                    $save->data_product_id = $v1->product_id;
                    $save->amount = $v1->amount;
                    $save->qty = $v1->total;
                    $save->month = $k;
                    $save->year = '2015';
                    $save->save();
                }

                //  echo $v2->customerId."<br>";
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

