<?php


class Invoice_CustomerBreakdown {
    
    private $_reportTitle = "";
    private $_date = "";
    private $_zone = "";
    private $_invoices = [];
    private $_uniqueid = "";
    
    public function __construct($indata)
    {
        
        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        
        
        $permittedZone = explode(',', Auth::user()->temp_zone);

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
        $this->goods = ['1F9F'=>[]];
        Invoice::select('*')->where('zoneId', $zone)->where('deliveryDate', $date)->with(['invoiceItem'=>function($query){
            $query->orderBy('productLocation')->orderBy('productQtyUnit');
        }])->with('products', 'client')
               ->chunk(50, function($invoicesQuery){
                   
                   // first of all process all products
                   $productsQuery = array_pluck($invoicesQuery, 'products');
                   foreach($productsQuery as $productQuery)
                   {
                       $productQuery = head($productQuery); 
                       foreach($productQuery as $pQ)
                       {
                            $products[$pQ->productId] = $pQ;
                       }
                   }
                   
                   // second process invoices                   
                   foreach($invoicesQuery as $invoiceQ)
                   {
                       $this->_invoices[] = $invoiceQ->invoiceId;

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

                           $customerId = $client->customerId;
                           $this->goods['1F9F'][$customerId]['items'][$productId][$unit] = [
                               'invoiceId' => $invoiceId,
                               'productId' => $productId,
                               'name' => $productDetail->productName_chi,
                               'unit' => $unit,
                               'unit_txt' => str_replace(' ', '', $item->productUnitName),
                               'counts' => (isset($this->goods['1F9F'][$customerId]['items'][$productId][$unit]) ? $this->goods['1F9F'][$customerId]['items'][$productId][$unit]['counts'] : 0) + $item->productQty,
                               'stdPrice' => $productDetail->productStdPrice[$unit],
                               'itemPrice' => $item->productPrice,
                               'discount' => $item->productDiscount,
                           ];

                           $this->goods['1F9F'][$customerId]['customerInfo'] = $client->toArray();
                           $this->goods['1F9F'][$customerId]['totalAmount'] = $invoiceQ->invoiceTotalAmount;
                           $this->goods['1F9F'][$customerId]['invoiceId'] = $invoiceQ->invoiceId;
                           $this->goods['1F9F'][$customerId]['invoiceStatusText'] = $invoiceQ->invoiceStatusText;
                          

                       }
                   }
                   
               });

       $this->data = $this->goods;
       return $this->data;        
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
                'warning'   =>  false,
            ],
        ];
        
        return $downloadSetting;
    }
    
    public function outputPreview()
    {

        return View::make('reports/CustomerBreakdown')->with('data', $this->data)->render();
        
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
        
        $pdf = new PDF();
        $i = 0;

        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
        
        // handle all 1F, 9F goods
        $ninef = $this->data['1F9F'];
        $consec = $j = 0;
      //  pd($ninef);
        foreach($ninef as $c=>$nf)
        {
        
            $consec += count($nf['items']);
            $nf['consec'] = $ninef[$c]['consec'] = $consec;
        
            // we can have 20 items as most per section
            $ninefproducts[$j][] = $nf;
            if($consec > 20)
            {
                $j++;
                $consec = 0;
            }
        }

       // pd($ninefproducts);

        foreach($ninefproducts as $index=>$order)
        {
        //dd($order);   
            // if it is in left section, add a new page
            if($index % 2 == 0)
            {
        
                $pdf->AddPage();
                $this->generateHeader($pdf);
        
              /*  $pdf->SetFont('chi','',10);
                $pdf->setXY(10, $pdf->h-30);
                $pdf->Cell(0, 0, "備貨人", 0, 0, "L");
        
                $pdf->setXY(60, $pdf->h-30);
                $pdf->Cell(0, 0, "核數人", 0, 0, "L");
        
                $pdf->Line(10, $pdf->h-35, 50, $pdf->h-35);
                $pdf->Line(60, $pdf->h-35, 100, $pdf->h-35);*/

                $pdf->SetFont('chi','',10);
                $pdf->setXY(110, $pdf->h-30);
                $pdf->Cell(0, 0, "備貨人", 0, 0, "L");

                $pdf->setXY(170, $pdf->h-30);
                $pdf->Cell(0, 0, "核數人", 0, 0, "L");

                $pdf->Line(110, $pdf->h-35, 150, $pdf->h-35);
                $pdf->Line(170, $pdf->h-35, 210, $pdf->h-35);
        
                $pdf->setXY(0, 0);
        
                // add a straight line
        
                $pdf->Line(105, 45, 105, 280);
        
                $pdf->SetFont('chi','',10);
               $pdf->setXY(500, $pdf->h-22);
               // $pdf->setXY(500, $pdf->h-0);
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
                $pdf->SetFont('chi','',10);
                
                $pdf->Cell(0, 0, sprintf("%s (%s)", $o['invoiceId'], $o['invoiceStatusText']), 0, 0, "L");
                
                $y += 5;
                $pdf->setXY($base_x + 0, $y);
                $pdf->SetFont('chi','',9);
                $pdf->Cell(0, 0, sprintf("%s (%s)", $o['customerInfo']['customerName_chi'], $o['customerInfo']['customerId']), 0, 0, "L");
                
                $pdf->setXY($base_x + 64, $y);
                $pdf->Cell(20, 0, sprintf("TOTAL: HK$%s", $o['totalAmount']), 0, 0, "R");
                 
                $pdf->SetFont('chi','',9);
                 
                $y += 5;
                 
                foreach($o['items'] as $itemUnitlv)
                {
                    foreach($itemUnitlv as $item)
                    {

                        $pdf->setXY($base_x + 0, $y);
                        $pdf->Cell(0, 0, "    " . $item['name'], 0, 0, 'L');
                         
                        $pdf->setXY($base_x + 37, $y);
                        $pdf->Cell(0, 0, "    $" . $item['itemPrice'], 0, 0, 'L');
                        
                        $pdf->setXY($base_x + 53, $y);
                        $pdf->Cell(0, 0, "x", 0, 0, 'L');
                        
                        $pdf->setXY($base_x + 53, $y);
                        $pdf->Cell(0, 0, "    " . sprintf("%s", $item['counts']), 0, 0, 'L');
                         
                        $pdf->setXY($base_x + 63, $y);
                        $pdf->Cell(0, 0, "" . $item['unit_txt'], 0, 0, 'L');
                         
                        $pdf->setXY($base_x + 68, $y);
                        $pdf->Cell(0, 0, "=", 0, 0, 'L');
                        
                        $pdf->setXY($base_x + 70, $y);

                        $pdf->Cell(0, 0, sprintf(" $%s", round($item['itemPrice']*$item['counts']*(100-$item['discount'])/100,2) ), 0, 0, 'L');

                        $pdf->setXY($base_x + 85, $y);
                        $pdf->Cell(0, 0, "(".$item['discount']."%)", 0, 0, 'L');
                        
                        $y +=  5;
                    }
                }
                 
                
                $y += 5;
        
                $pdf->SetDash(1, 1);
                $pdf->Line($base_x + 2, $y-5, $base_x + 85, $y-5);
            }


        }
        
        // output
        return [
            'pdf' => $pdf,
            'remark' => sprintf("Customer Break Down Archive for Zone %s, DeliveryDate = %s created by %s on %s", $this->_zone, date("Y-m-d", $this->_date), Auth::user()->username, date("r")),
            'uniqueId' => $this->_uniqueid,
        ];
    }
}