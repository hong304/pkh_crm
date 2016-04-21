<?php



class TruckDayEndSummary extends Report{
    
    public $data = [];
    public $reportTitle = "貨車日結列表";
    
    public function __construct($date = false, $zone)
    {
        
        
        $this->date = $date = $date ? $date : strtotime("tomorrow 00:00");
        $this->zone = $zone = $zone;
        
        
        // get all invoice from that date
        $invoices = Invoice::where('deliveryDate', $date)
                            ->where('zoneId', $zone)
                            ->where('invoiceStatus', '2')
                            //->lists('invoiceId');
                            ->get();
        
        foreach($invoices as $invoice)
        {
            $invoiceId[] = $invoice->invoiceId;
            $paymentTerms[$invoice->invoiceId] = $invoice->paymentTerms;
        }
        
        $invoices = InvoiceItem::
                            wherein('invoiceId', $invoiceId)
                            ->orderby('productId')
                            ->with('productDetail')
                            ->get();
                
        
        $items = $invoices->toArray();
        $injection = [];
        $paymentSum = [
            1 => ['count'=> 0, 'sum'=> 0],
            2 => ['count'=> 0, 'sum'=>0],
        ];
        
        foreach($items as $e)
        {     
      
            @$injection[$e['productId']][$e['productQtyUnit']]['sum'] += $e['productQty'];
            @$injection[$e['productId']][$e['productQtyUnit']]['detail'] = $e['product_detail'];
            
            $this_item_price = ceil($e['productPrice'] * $e['productQty'] * (100-$e['productDiscount'])/100);
           
            @$paymentSum[$paymentTerms[$e['invoiceId']]]['sum'] += $this_item_price;
            @@$paymentSum[$paymentTerms[$e['invoiceId']]]['count'] ++;
        }
              
        
        
        
        $Info = [
            'data' => $injection,
            'zone' => Zone::where('zoneId', $zone)->first()->toArray(),
            'paymentSum' => $paymentSum,
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
        
        $items = array_chunk($this->data['data'], 25, true);
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
        //$i = 0;
        foreach($items as $i=>$f)
        {
            //$i++;
            
            $pdf->AddPage();
            
    
            $this->generateHeader($pdf);
            
            $pdf->SetFont('j','',10);
            
            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "編號", 0, 0, "L");
            
            $pdf->setXY(40, 50);
            $pdf->Cell(0, 0, "貨品說明", 0, 0, "L");
            
            $pdf->setXY(178, 50);
            $pdf->Cell(0, 0, "出貨量", 0, 0, "L");
            
            
            $pdf->Line(10, 53, 190, 53);
    
            $y = 60;

            $pdf->setXY(500, $pdf->h-30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, count($items)) , 0, 0, "R");
            
            
            
            foreach($f as $productid=>$e)
            {           
                //print_r($productid);
                foreach($e as $unit=>$ee)
                {
                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, $productid, 0, 0, "L");
                    
                    $pdf->setXY(40, $y);
                    $pdf->Cell(0, 0, $ee['detail']['productName_chi'], 0, 0, "L");
                    
                    $pdf->setXY(178, $y);
                    $pdf->Cell(0, 0, $ee['sum'], 0, 0, "L");
                    
                    $pdf->setXY(185, $y);
                    $pdf->Cell(0, 0, str_replace(' ', '', $ee['detail']['productPackingName_'.$unit]), 0, 0, "L");
                    
                    
                    $y += 6;
                }
                
            }
            
        }
        
        // on last page, print total payment sum
        
        $pdf->setXY(10, $pdf->h-35);
        $pdf->Cell(0, 0, sprintf("現金總數: %s單\t HK$%s", $this->data['paymentSum']['1']['count'], number_format($this->data['paymentSum']['1']['sum'], 0, '.', ',')), 0, 0, "L");
        
        $pdf->setXY(10, $pdf->h-30);
        $pdf->Cell(0, 0, sprintf("月結總數: %s單\t HK$%s", $this->data['paymentSum']['2']['count'], number_format($this->data['paymentSum']['2']['sum'], 0, '.', ',')), 0, 0, "L");
        
        
        $this->pdf = $pdf;
        
        return $this;
    }
    
    public function generateHeader($pdf)
    {

        $pdf->SetFont('j','',18);
        $pdf->Cell(0, 10,"炳記行貿易國際有限公司",0,1,"C");
        $pdf->SetFont('j','U',16);
        $pdf->Cell(0, 10,$this->reportTitle,0,1,"C");
        $pdf->SetFont('j','U',13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->data['zone']['zoneId'], 2, '0', STR_PAD_LEFT) . ' (' . $this->data['zone']['zoneName'] . ')', 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->date), 0, 2, "L");
        
        /*
         $pdf->setXY(150, 12);
         $pdf->SetFont('j','', 8);
         $pdf->Cell(0, 5, "文件日期: " . date("Y-m-d"), 0, 2, "L");
         $pdf->Cell(0, 5, "文件時間: " . date("H:i:s"), 0, 2, "L");
        */
        $pdf->setXY(0, 0);
        $pdf->SetFont('j','', 6);
        $pdf->Cell(0, 5, "系統模組: Report/PickingList", 0, 2, "R");
        
        //$pdf->setXY(0, 0);        
    }
    
    public function show()
    {
        
        $this->pdf->Output();
        exit;
    }
}