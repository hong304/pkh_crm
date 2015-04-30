<?php


class Invoice_1FPickingList {
    
    private $_reportTitle = "";
    private $_date = "";
    private $_zone = "";
    private $_invoices = [];
    private $_uniqueid = "";
    private $_version = '';
    
    public function __construct($indata)
    {
        
        $report = Report::where('id', $indata['reportId'])->first();       
        
        $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        
        // check if user has clearance to view this zone        
        if(!in_array($this->_zone, $permittedZone))
        {
            App::abort(401, "Unauthorized Zone");
        }
        // version & id
         $this->_uniqueid = date("Ymd", $this->_date) .  $this->_zone;



      //   $lastid = ReportArchive::where('id', 'like', $this->_uniqueid.'-%-1')->select('id')->orderby('created_at', 'desc')->first();

        $lastid = pickingListVersionControl::where('zone',$this->_zone)->where('date',date("Y-m-d",$this->_date))->first();



      //  $lastid = @explode('-', $lastid->id);

        $this->_version = isset($lastid->f1_version) ? $lastid->f1_version : '1';
         
         $this->_uniqueid = sprintf("%s-%s-1", $this->_uniqueid,  $this->_version);

       $this->_reportTitle = sprintf("%s - v%s", $report->name,  $this->_version);
       // $this->_reportTitle = sprintf("%s", $report->name);
       // $hi = New Debug();
       // $hi->content = $this->_reportTitle;
      //  $hi->save();

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
        $this->goods = ['1F'=>[], 'version'=>[]];
        Invoice::select('*')->where('version', true)->where('zoneId', $zone)->where('deliveryDate', $date)->with(['invoiceItem'=>function($query){
            $query->orderBy('productId')->orderBy('productQtyUnit');
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

                 //  pd($invoicesQuery);

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
                           
                           if($productDetail->productLocation == '1')
                           {
                               $this->goods['1F'][$productId][$unit] = [
                                   'productId' => $productId,
                                   'name' => $productDetail->productName_chi,
                                   'unit' => $unit,
                                   'unit_txt' => $item->productUnitName,
                                   'counts' => (isset($this->goods['1F'][$productId][$unit]) ? $this->goods['1F'][$productId][$unit]['counts'] : 0) + $item->productQty,
                               ];
                           }

                       }
                   }
                   
               });


       ksort($this->goods['1F'],SORT_STRING);

        //pd($this->goods['1F']);

       $this->data = $this->goods;
        $this->data['version'] = $this->_version;

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

        return View::make('reports/pickinglist1f')->with('data', $this->data)->render();
        
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
       /* if(count($this->_invoices) > 0)
        {
            Invoice::wherein('invoiceId', $this->_invoices)->update(['invoiceStatus'=>'4']);
        }*/
        
        
        $pdf = new PDF();
        $i = 0;
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
        // handle 1F goods
        $firstF = array_chunk($this->data['1F'], 25, true);
        
        foreach($firstF as $i=>$f)
        {
            // for first Floor
            $pdf->AddPage();
            
        
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

                foreach($e as $u)
                {
                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, $id, 0, 0, "L");
        
                    $pdf->setXY(40, $y);
                    $pdf->Cell(0, 0, $u['name'], 0, 0, "L");
        
                    $pdf->setXY(120, $y);
                    $pdf->Cell(0, 0, sprintf("%s", $u['counts']), 0, 0, "L");
        
                    $pdf->setXY(130, $y);
                    $pdf->Cell(0, 0, str_replace(' ', '', $u['unit_txt']), 0, 0, "L");
        
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
                for($note=0;$note<=3;$note++)
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
        
        
        // output
        return [
            'pdf' => $pdf,
            'remark' => sprintf("Picking List Archive for Zone %s, DeliveryDate = %s created by %s on %s", $this->_zone, date("Y-m-d", $this->_date), Auth::user()->username, date("r")),
            'uniqueId' => $this->_uniqueid,
            'associates' => json_encode($this->_invoices),
        ];
    }
}