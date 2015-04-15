<?php


class Report_DailySummary {
    
    private $_reportTitle = "";
    private $_date = "";
    private $_zone = "";
    private $_invoices = [];
    private $_uniqueid = "";
    private $_output = '';
    private $_vansell = '';

    private $_sumcredit = 0;
    private $_sumcod = 0;
    private $_countcredit =0;
    private $_countcod = 0;



    public function __construct($indata)
    {


        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        
        
        $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_output = $indata['output'];
        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        
        // check if user has clearance to view this zone        
        if(!in_array($this->_zone, $permittedZone))
        {
            App::abort(401, "Unauthorized Zone");
        }
        $this->_uniqueid = microtime(true);

    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }

    public function compileResults()
    {
        $date = $this->_date;
        $zone = $this->_zone;

        // get invoice from that date and that zone
       $this->goods = [];

        Invoice::select('*')->whereIn('invoiceStatus', ['2','4','11','20','21','22','23','30'])->where('zoneId', $zone)->where('deliveryDate', $date)->with('invoiceItem', 'products', 'client')
               ->chunk(5000, function($invoicesQuery) {


                  // pd($invoicesQuery);

                 //  $this->_count = sizeof($invoicesQuery);
                   // first of all process all products
                   $productsQuery = array_pluck($invoicesQuery, 'products');


                   foreach($productsQuery as $productQuery)
                   {
                       $productQuery = head($productQuery);
                       //pd($productQuery);
                      foreach($productQuery as $pQ)
                       {
                            $products[$pQ->productId] = $pQ;
                       }
                   }
                   
                   // second process invoices                   
                   foreach($invoicesQuery as $invoiceQ)
                   {
                       $this->_invoices[] = $invoiceQ->invoiceId;

                       if($invoiceQ->invoiceStatus == 20) {
                           $this->_sumcredit += $invoiceQ->invoiceTotalAmount;
                           $this->_countcredit += 1;
                       }
                       else {
                           $this->_sumcod += $invoiceQ->invoiceTotalAmount;
                           $this->_countcod += 1;
                       }

                       // first, store all invoices
                       $invoiceId = $invoiceQ->invoiceId;
                       $invoices[$invoiceId] = $invoiceQ;
                       $client = $invoiceQ['client'];
                       
                       // second, separate 1F goods and 9F goods
                       foreach($invoiceQ['invoiceItem'] as $item)
                       {
                           // determin its product location
                           $productId = $item->productId;
                           
                           $productDetail = $products[$productId]; 
                           $unit = $item->productQtyUnit;



                               $this->goods[$productId][$unit] = [
                                   'productId' => $productId,
                                   'name' => $productDetail->productName_chi,
                                   'productPrice' => $item->productPrice,
                                   'unit' => $unit,
                                   'unit_txt' => $item->productUnitName,
                                   'counts' => (isset($this->goods[$productId][$unit]) ? $this->goods[$productId][$unit]['counts'] : 0) + $item->productQty,
                               ];

                         //  pd($item);
                       }
                   }
                   
               });
        if(count($this->goods)>0)
             ksort($this->goods);


      // $this->data = ;

        $this->data['items']=$this->goods;

        $this->data['sumcredit']=$this->_sumcredit;
        $this->data['sumcod']=$this->_sumcod;
        $this->data['countcredit']=$this->_countcredit;
        $this->data['countcod']=$this->_countcod;
       // pd($this->data);

       return [$this->data];
    }
    
    public function registerFilter()
    {       
       /*
        * Type:
        * single-dropdown
        * date-picker
        * date-range
        * single-selection
        * multiple-selection
        * text
        */ 
        $zones = Zone::wherein('zoneId', explode(',', Auth::user()->temp_zone))->get();
        foreach($zones as $zone)
        {
            $availablezone[] = [
                'value' => $zone->zoneId,
                'label' => $zone->zoneName,
            ];
        }        
        $filterSetting = [
            [
                'id' => 'zoneId',
                'type' => 'single-dropdown',
                'label' => '車號',
                'model' => 'zone',
                'optionList' => $availablezone,
                'defaultValue' => $this->_zone,
            ],
            [
                'id' => 'deliveryDate',
                'type' => 'date-picker',
                'label' => '送貨日期',
                'model' => 'deliveryDate',
                'defaultValue' => date("Y-m-d", $this->_date),
            ],
        ];
        
        return $filterSetting;
    }
    
    
    public function beforeCompilingResults() 
    {
        // executes codes before compiling results function is executed
    }
    
    public function afterCompilingResults()
    {
        // executes codes after compiling results function is executed
    }
    
    public function registerDownload()
    {
        $downloadSetting = [
            [
                'type' => 'pdf',
                'name' => '列印  PDF 版本',
                'warning'   =>  '你確定要列印日結單嗎?'
            ],
        ];
        
        return $downloadSetting;
    }
    
    public function outputPreview()
    {

        return View::make('reports/DailyReport')->with('data', $this->data)->render();
        
    }
    
    
    # PDF Section
    public function generateHeader($pdf)
    {
    
        $pdf->SetFont('chi','',18);
        $pdf->Cell(0, 10,"炳記行貿易有限公司",0,1,"C");
        $pdf->SetFont('chi','U',16);
        $pdf->Cell(0, 10,$this->_reportTitle,0,1,"C");
        $pdf->SetFont('chi','U',13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->_zone, 2, '0', STR_PAD_LEFT), 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->_date), 0, 2, "L");
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi','', 9);
        $pdf->Code128(10,$pdf->h-15,$this->_uniqueid,50,10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");

    }
    
    public function outputPDF()
    {

        // Update it as generated into picking list

        if(count($this->_invoices) > 0)
        {
            //Invoice::wherein('invoiceId', $this->_invoices)->update(['invoiceStatus'=>'4']);
        }
        
        
        $pdf = new PDF();
        $j = 0;
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
        // handle 1F goods
        $firstF = array_chunk($this->data['items'], 30, true);
       // pd($this->data);

        $numItems = count($firstF);
        $i = 0;

        foreach($firstF as $i=>$f)
        {
            // for first Floor
            $pdf->AddPage();
            
        
            $this->generateHeader($pdf);
        
            $pdf->SetFont('chi','',10);
        
            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "編號", 0, 0, "L");
        
            $pdf->setXY(50, 50);
            $pdf->Cell(0, 0, "貨品說明", 0, 0, "L");
        

        
            $pdf->setXY(168, 50);
            $pdf->Cell(0, 0, "發表出貨量", 0, 0, "L");
        
            $pdf->Line(10, 53, 190, 53);
        
            $y = 60;
        

        
            $pdf->setXY(500, $pdf->h-30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, count($firstF)) , 0, 0, "R");
        
        
            
            foreach($f as $id=>$e)
            {

                foreach($e as $u)
                {
                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, $id, 0, 0, "L");
        
                    $pdf->setXY(50, $y);
                    $pdf->Cell(0, 0, $u['name'], 0, 0, "L");
        
                    $pdf->setXY(168, $y);
                    $pdf->Cell(0, 0, sprintf("%s", $u['counts']), 0, 0, "L");
        
                    $pdf->setXY(175, $y);
                    $pdf->Cell(0, 0, str_replace(' ', '', $u['unit_txt']), 0, 0, "L");
        

        
                    $y += 6;
                }
            }
        
           // $y += 10;
            // Notes part

            if(++$i === $numItems) {

                $pdf->Line(10, $y+5, 190, $y+5);

                $pdf->setXY(10, $y+10);
                $pdf->Cell(0, 0, "現金總數:", 0, 0, "L");

                $pdf->setXY(30, $y+10);
                $pdf->Cell(0, 0, $this->data['countcod']."單", 0, 0, "L");

                $pdf->setXY(50, $y+10);
                $pdf->Cell(0, 0, "$".number_format($this->data['sumcod'],2,'.',','), 0, 0, "L");

                $pdf->setXY(10, $y+16);
                $pdf->Cell(0, 0, "月結總數:", 0, 0, "L");

                $pdf->setXY(30, $y+16);
                $pdf->Cell(0, 0, $this->data['countcredit']."單", 0, 0, "L");

                $pdf->setXY(50, $y+16);
                $pdf->Cell(0, 0, "$".number_format($this->data['sumcredit'],2,'.',','), 0, 0, "L");

            }
        }

        // handle 9F goods
/*
        if($this->_output != 'van_sell_pdf'){
            $ninef = $this->data['9F'];

            $j = $sum = $consec = 0;
            $ninefproducts = [];
            $number_of_box = count($ninef);

            foreach($ninef as $nf){
                $sum += count($nf['items']);
            }
            $half = explode('.', round($sum / 2));
            $half = (int)$half[0];

            foreach($ninef as $c=>$nf)
            {

                $consec += count($nf['items']);
                $nf['consec'] = $ninef[$c]['consec'] = $consec;

                // we can have 20 items as most per section
                $ninefproducts[$j][] = $nf;
                if($consec > 10 OR $consec > $half)
                {
                    $j++;
                    $consec = 0;
                }
            }

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
                    $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $index/2+1, ceil(count($ninefproducts)/2)) , 0, 0, "R");
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
                    $pdf->Cell(0, 0, sprintf("%s (%s)", $o['customerInfo']['customerName_chi'], $o['customerInfo']['customerId']), 0, 0, "L");

                    $pdf->SetFont('chi','',9);

                    $y += 5;

                    foreach($o['items'] as $itemUnitlv)
                    {
                        foreach($itemUnitlv as $item)
                        {
                            $pdf->setXY($base_x + 0, $y);
                            $pdf->Cell(0, 0, "    " . $item['name'], 0, 0, 'L');

                            $pdf->setXY($base_x + 50, $y);
                            $pdf->Cell(0, 0, "    $" . $item['stdPrice'], 0, 0, 'L');

                            $pdf->setXY($base_x + 70, $y);
                            $pdf->Cell(0, 0, "    " . sprintf("%s", $item['counts']), 0, 0, 'L');

                            $pdf->setXY($base_x + 75, $y);
                            $pdf->Cell(0, 0, "    " . $item['unit_txt'], 0, 0, 'L');

                            $y +=  5;
                        }
                    }

                    $y += 5;

                    $pdf->SetDash(1, 1);
                    $pdf->Line($base_x + 2, $y-5, $base_x + 85, $y-5);
                }


            }
        }
        //end of handel nine floor
        */
        // output


        return [
            'pdf' => $pdf,
            'remark' => sprintf("Van sell list Archive for Zone %s, DeliveryDate = %s created by %s on %s", $this->_zone, date("Y-m-d", $this->_date), Auth::user()->username, date("r")),
            'uniqueId' => $this->_uniqueid,
            'associates' => json_encode($this->_invoices),
        ];
    }
}