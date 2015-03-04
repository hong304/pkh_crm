<?php

class DeliveryController extends BaseController {

    public function jsonGeneratePickingList()
    {
        $date = Input::get('date') ? strtotime(Input::get('date')) : strtotime('22-01-2015');
        $zone = Input::has('zone.zoneId') ? Input::get('zone.zoneId') : Session::get('zone');
        $products = ['1F'=>[]];
        $k = [];
        //dd(date("Y-m-d", 1422115200));
        // get all invoice from that date
        $invoices = Invoice::where('deliveryDate', $date)->where('zoneId', $zone)->where('invoiceStatus', '2')->with('invoiceItem', 'client')->get();
        if($invoices->count() > 0)
        {
            //dd(DB::getQueryLog());
            foreach($invoices as $invoice)
            {
                foreach($invoice->invoiceItem as $item)
                {
                    $productsid[] = $item->productId;
                    $productCalculation[$item->productId][$item->productQtyUnit] = [
                        'qty' => isset($productCalculation[$item->productId][$item->productQtyUnit]) ? 
                                $productCalculation[$item->productId][$item->productQtyUnit]['qty'] + $item->productQty :
                                $item->productQty,
                        'unit'=>$item->productQtyUnit
                    ]; 
                    // for further injection use
                    $injection[$item->productId][$item->productQtyUnit][] = [
                        'customerId' => $invoice->customerId,
                        'customerName' => $invoice->client->customerName_chi,
                        'invoiceId' => $invoice->invoiceId,
                        'qty' => $item->productQty,
                        'unit' => $item->productQtyUnit,
                    ];
                }
            }
            
            // get product from database
            $product = Product::wherein('productId', $productsid)->get();
            
            // seperate into 1/F goods and 9/F goods
            
            // handle 1/F goods first
            foreach($product as $p)
            {
                if($p->productLocation == '1')
                {
                    $products['1F'][$p->productId] = [
                        'qty' => $productCalculation[$p->productId],
                        'productDetail' => $p->toArray(),  
                        'breakdown' => $injection[$p->productId],
                    ];
                    
                    $firstFloorproducts[] = $p->productId;
    
                }
                $productsInfo[$p->productId] = $p;
            }
            // check every single invoices and remove 1F Product
            
            foreach($invoices as $j=>$invoice)
            {
                foreach($invoice->invoiceItem as $k=>$item)
                {
                    if(in_array($item->productId, $firstFloorproducts))
                    {
                        unset($invoices[$j]->invoiceItem[$k]);
                        
                    }
                    else
                    {
                        $invoices[$j]->invoiceItem[$k]['detail'] = $productsInfo[$item->productId]->toArray();
                    }
                }
            } 
            
            // check if the that 9/F entries has item. Remove it otherwise.
            foreach($invoices as $j=>$invoice)
            {
                if(count($invoice->invoiceItem) <= 0)
                {
                    unset($invoices[$j]);
                }
            }
            
            $k = $invoices->toArray();
                    
            usort($k, function($a, $b) {
               //var_dump($a['client']['routePlanningPriority'], $b['client']['routePlanningPriority']);
               return $a['client']['routePlanningPriority'] - $b['client']['routePlanningPriority'];
            });
        }
        $returnInfo = [
            'firstF' => $products['1F'],
            'nineF' => $k,
            'availableZone' => Zone::wherein('zoneId', explode(',', Auth::user()->temp_zone))->get()->toArray(),
        ];
        return Response::json($returnInfo);
    }

}