<?php



class PickingList {
    
    public $data = [];
    public $reportTitle = "備貨單";
    
    public function __construct($date = false, $zone)
    {
        
        
        $this->date = $date = $date ? $date : strtotime("tomorrow 00:00");
        $this->zone = $zone = $zone;
        
        
        // get all invoice from that date
        $invoices = Invoice::where('deliveryDate', $date)->where('zoneId', $zone)->where('invoiceStatus', '2')->with('invoiceItem', 'client')->get();
        
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
        $firstFloorproducts = [];
        $products = ['1F'=>[]];
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
        
        $Info = [
            'firstF' => $products['1F'],
            'nineF' => $k,
            'zone' => Zone::where('zoneId', $zone)->first()->toArray(),
        ];
        $this->data = $Info;
        
    }
    
    public function getJson()
    {
        return json_encode($this->data);
    }
    
    public function generatePDF()
    {
        $pdf = new PDF();
        $i=0;
        $firstF = array_chunk($this->data['firstF'], ($i >0 ? 25 : 20), true);
        
        foreach($firstF as $i=>$f)
        {
            // for first Floor
            $pdf->AddPage();
            $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
    
            $this->generateHeader($pdf);
            
            $pdf->SetFont('chi','',10);
            
            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "編號", 0, 0, "L");
            
            $pdf->setXY(40, 50);
            $pdf->Cell(0, 0, "貨品說明", 0, 0, "L");
            
            $pdf->setXY(120, 50);
            $pdf->Cell(0, 0, "訂單貨量", 0, 0, "L");
            
            $pdf->setXY(145, 50);
            $pdf->Cell(0, 0, "上貨總數貨", 0, 0, "L");
            
            $pdf->setXY(170, 50);
            $pdf->Cell(0, 0, "核數", 0, 0, "L");
            
            $pdf->Line(10, 53, 190, 53);
    
            $y = 60;
            
            $pdf->setXY(10, $pdf->h-30);
            $pdf->Cell(0, 0, "備貨人", 0, 0, "L");
            
            $pdf->setXY(60, $pdf->h-30);
            $pdf->Cell(0, 0, "核數人", 0, 0, "L");
            
            $pdf->Line(10, $pdf->h-35, 50, $pdf->h-35);
            $pdf->Line(60, $pdf->h-35, 100, $pdf->h-35);

            $pdf->setXY(500, $pdf->h-30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, count($firstF)) , 0, 0, "R");
            
            
            
            foreach($f as $id=>$e)
            {           
                foreach($e['qty'] as $u)
                {
                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, $id, 0, 0, "L");
                    
                    $pdf->setXY(40, $y);
                    $pdf->Cell(0, 0, $e['productDetail']['productName_chi'], 0, 0, "L");
                    
                    $pdf->setXY(120, $y);
                    $pdf->Cell(0, 0, $u['qty'], 0, 0, "L");
                    
                    $pdf->setXY(130, $y);
                    $pdf->Cell(0, 0, str_replace(' ', '', $e['productDetail']['productPackingName_'.$u['unit']]), 0, 0, "L");
                    
                    $pdf->setXY(145, $y);
                    $pdf->Cell(0, 0, "________", 0, 0, "L");
                    
                    $pdf->setXY(170, $y);
                    $pdf->Cell(0, 0, "________", 0, 0, "L");
                    
                    $y += 6;
                }
            }      
            
            $y += 10;
            // Notes part
            if($i == 0)
            {
                for($note=0;$note<=4;$note++)
                {                
                    $pdf->Line(10, $y, 27, $y);
                    $pdf->Line(40, $y, 100, $y);
                    $pdf->Line(120, $y, 135, $y);
                    $pdf->Line(146, $y, 160, $y);
                    $pdf->Line(171, $y, 185, $y);
                    
                    
                    $y += 8;
                }
            }
            
        }
        
        $ninef = $this->data['nineF'];
        
        $j = $sum = $consec = 0;
        $ninefproducts = [];
        $number_of_box = count($ninef);
        foreach($ninef as $nf){
            $sum += count($nf['invoice_item']);
        }
        $half = explode('.', round($sum / 2));
        $half = (int)$half[0];
        
        foreach($ninef as $c=>$nf)
        {
            
            $consec += count($nf['invoice_item']);
            $nf['consec'] = $ninef[$c]['consec'] = $consec;
            //var_dump($consec, $half);
            // we can have 20 items as most per section
            $ninefproducts[$j][] = $nf;
            if($consec >20 OR $consec > $half)
            {
                $j++;
                $consec = 0;
            }
        }
        //exit;
        foreach($ninefproducts as $index=>$order)
        {
            
            // if it is in left section, add a new page
            if($index % 2 == 0)
            {
                
                $pdf->AddPage();
                $this->generateHeader($pdf);
                
                $pdf->SetFont('chi','',10);
                $pdf->setXY(10, $pdf->h-30);
                $pdf->Cell(0, 0, "備貨人", 0, 0, "L");
                
                $pdf->setXY(60, $pdf->h-30);
                $pdf->Cell(0, 0, "核數人", 0, 0, "L");
                
                $pdf->Line(10, $pdf->h-35, 50, $pdf->h-35);
                $pdf->Line(60, $pdf->h-35, 100, $pdf->h-35);
                
                $pdf->setXY(0, 0);
                
                // add a straight line
                
                $pdf->Line(105, 45, 105, 280);
                
                $pdf->SetFont('chi','',10);
                $pdf->setXY(500, $pdf->h-30);
                $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $index/2+1, count($ninefproducts)/2) , 0, 0, "R");
            }
            
            //$pdf->Cell(50, 50, "NA", 0, 0, "L");
            
            // define left right position coordinate x differences
            $y = 55;
            if($index % 2 == 0)
            {
                $base_x = 10;
            }
            else
            {
                $base_x = 110;
            }
            
            foreach($order as $o)
            {
               $pdf->setXY($base_x + 0, $y);
               $pdf->SetFont('chi','',11);
               $pdf->Cell(0, 0, sprintf("%s (%s)", $o['client']['customerName_chi'], $o['client']['customerId']), 0, 0, "L");
               
               $pdf->SetFont('chi','',9);
               
               $y += 5;
               
               foreach($o['invoice_item'] as $item)
               {
                   
                   $pdf->setXY($base_x + 0, $y);
                   $pdf->Cell(0, 0, "    " . $item['detail']['productName_chi'], 0, 0, 'L');
                   
                   $pdf->setXY($base_x + 50, $y);
                   $pdf->Cell(0, 0, "    $" . $item['detail']['productStdPrice'][$item['productQtyUnit']], 0, 0, 'L');
                    
                   $pdf->setXY($base_x + 70, $y);
                   $pdf->Cell(0, 0, "    " . $item['productQty'], 0, 0, 'L');
                   
                   $pdf->setXY($base_x + 75, $y);
                   $pdf->Cell(0, 0, "    " . $item['detail']['productPackingName_' . $item['productQtyUnit']], 0, 0, 'L');
                   
                   $y +=  5;
               }
               
               $y += 5;
                              
               $pdf->SetDash(1, 1);
               $pdf->Line($base_x + 2, $y-5, $base_x + 85, $y-5);
            }
            
            
        }
        
        
        $this->pdf = $pdf;
        
        return $this;
    }
    
    public function generateHeader($pdf)
    {
        
        $pdf->SetFont('chi','',18);
        $pdf->Cell(0, 10,"炳記行貿易國際有限公司",0,1,"C");
        $pdf->SetFont('chi','U',16);
        $pdf->Cell(0, 10,$this->reportTitle,0,1,"C");
        $pdf->SetFont('chi','U',13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->data['zone']['zoneId'], 2, '0', STR_PAD_LEFT) . ' (' . $this->data['zone']['zoneName'] . ')', 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->date), 0, 2, "L");
        
        /*
        $pdf->setXY(150, 12);
        $pdf->SetFont('chi','', 8);
        $pdf->Cell(0, 5, "文件日期: " . date("Y-m-d"), 0, 2, "L");
        $pdf->Cell(0, 5, "文件時間: " . date("H:i:s"), 0, 2, "L");       
        */
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi','', 6);
        $pdf->Cell(0, 5, "系統模組: Report/PickingList", 0, 2, "R");
       
        //$pdf->setXY(0, 0);        
    }
    
    public function show()
    {
        
        $this->pdf->Output();
        exit;
    }
}