<?php


class Invoice_CashReceiptSummary {
    
    private $_reportTitle = "";
    private $_date = "";
    private $_zone = "";
    private $_invoices = [];

    private $data = [];
    private $_account = [];
    private $_backaccount = [];
    private $_paidInvoice = [];
    private $_uniqueid = "";
    
    public function __construct($indata)
    {
        
        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        
        
        $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        $this->_shift = (isset($indata['filterData']['shift']) ? $indata['filterData']['shift']['value'] : '-1');
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

     /*   Invoice::select('*')->whereIn('invoiceStatus',['1','2','98'])->where('return',true)->where('zoneId', $zone)->where('deliveryDate', $date)->with('invoiceItem', 'client')
            ->chunk(5000, function($invoicesQuery) {
                foreach($invoicesQuery as $invoiceQ)
                    $this->_returnaccount[$invoiceQ['client']->customerId] = $invoiceQ->amount;
            });*/


        $invoicesQuery = Invoice::whereIn('invoiceStatus',['1','2','30','98','97','96'])->where('paymentTerms',1)->where('zoneId', $zone)->where('deliveryDate', $date);
                if($this->_shift != '-1')
                    $invoicesQuery->where('shift',$this->_shift);

        $invoicesQuery = $invoicesQuery->with('invoiceItem', 'client')->get();


                   $acc = 0;
                   foreach($invoicesQuery as $invoiceQ)
                   {
                       $acc +=  ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount;

                           $this->_invoices[] = $invoiceQ->invoiceId;
                           $this->_zoneName = $invoiceQ->zone->zoneName;

                           // first, store all invoices
                           $invoiceId = $invoiceQ->invoiceId;
                           $invoices[$invoiceId] = $invoiceQ;
                           $client = $invoiceQ['client'];

                           $this->_account[] = [
                               'customerId' => $client->customerId,
                               'name' => $client->customerName_chi,
                               'invoiceNumber' => $invoiceId,
                               'invoiceTotalAmount' => ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount ,
                               'accumulator' =>number_format($acc,2,'.',','),
                                'amount' => number_format(($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount,2,'.',','),
                           ];
                      }


        $invoicesQuery = Invoice::where('invoiceStatus','20')->where('paymentTerms',1)->where('zoneId', $zone)->where('deliveryDate', $date);
        if($this->_shift != '-1')
            $invoicesQuery->where('shift',$this->_shift);
        $invoicesQuery = $invoicesQuery->with('invoiceItem', 'client')->get();
                 $acc = 0;
                foreach($invoicesQuery as $invoiceQ)
                {
                    $acc +=  ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount;


                    $this->_invoices[] = $invoiceQ->invoiceId;
                    $this->_zoneName = $invoiceQ->zone->zoneName;

                    // first, store all invoices
                    $invoiceId = $invoiceQ->invoiceId;
                    $invoices[$invoiceId] = $invoiceQ;
                    $client = $invoiceQ['client'];

                    $this->_backaccount[] = [
                        'customerId' => $client->customerId,
                        'name' => $client->customerName_chi,
                        'invoiceNumber' => $invoiceId,
                        'invoiceTotalAmount' => ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount ,
                        'accumulator' =>number_format($acc,2,'.',','),
                        'amount' => number_format(($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount,2,'.',','),
                    ];
                }

        $invoicesQuery = Invoice::where('invoiceStatus','30')->where('paid_date',date('Y-m-d',$date))->where('paymentTerms',1)->where('zoneId', $zone);
        if($this->_shift != '-1')
            $invoicesQuery->where('shift',$this->_shift);
        $invoicesQuery = $invoicesQuery->with('invoiceItem', 'client')->get();

        $acc = 0;
        foreach($invoicesQuery as $invoiceQ)
        {
            $acc +=  ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount;


            $this->_invoices[] = $invoiceQ->invoiceId;
            $this->_zoneName = $invoiceQ->zone->zoneName;

            // first, store all invoices
            $invoiceId = $invoiceQ->invoiceId;
            $invoices[$invoiceId] = $invoiceQ;
            $client = $invoiceQ['client'];

            $this->_paidInvoice[] = [
                'customerId' => $client->customerId,
                'name' => $client->customerName_chi,
                'deliveryDate' => date('Y-m-d',$invoiceQ->deliveryDate),
                'invoiceNumber' => $invoiceId,
                'invoiceTotalAmount' => ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount ,
                'accumulator' =>number_format($acc,2,'.',','),
                'amount' => number_format(($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount,2,'.',','),
            ];
        }

//pd($this->_account);

      /*  foreach($this->_account as &$v){
            if(isset($this->_returnaccount[$v['customerId']]))
                 $v['invoiceTotalAmount'] -= $this->_returnaccount[$v['customerId']];
                 $acc += $v['invoiceTotalAmount'];
                 $v['accumulator'] =number_format($acc,2,'.',',');
                 $v['amount'] = number_format($v['invoiceTotalAmount'],2,'.',',');

        }*/


       $this->data = $this->_account;

       return $this->data;
    }


    public function outputCsv(){

        $csv = 'CustomerID,Customer Name,Invoice No.,Total Amount,no. check,db>in,in>db,Invoice No. on hand,Invoice amount on hand,' . "\r\n";
        $totalinvoice = count($this->data)+1;
        foreach ($this->data as $o) {
            $csv .= '"' . $o['customerId'] . '",';
            $csv .= '"' . $o['name'] . '",';
            $csv .= '"' . $o['invoiceNumber'] . '",';
            $csv .= '"' . $o['invoiceTotalAmount'] . '",';
            $csv .= '"' . substr($o['invoiceNumber'], -5) . '",';
            $csv .= '"=VLOOKUP(A2,H$2:H$'.$totalinvoice.',1,FALSE)",';
            $csv .= '"=VLOOKUP(H2,A$2:H$'.$totalinvoice.',1,FALSE)",';
            $csv .= "\r\n";
        }
        $csv .= ',';
        $csv .= ',';
        $csv .= ',';
        $csv .= '=SUM(D2:D'.$totalinvoice.'),';
        $csv .= ',';
        $csv .= ',';
        $csv .= ',';
        $csv .= ',';
        $csv .= '=SUM(I2:I'.$totalinvoice.'),';
        $csv .= "\r\n";

        echo "\xEF\xBB\xBF";

        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="CashReceipt.csv"',
        );

        return Response::make(rtrim($csv, "\n"), 200, $headers);


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
        $ashift =[['value'=>'-1','label'=>'檢視全部'],['value'=>'1','label'=>'早班'],['value'=>'2','label'=>'晚班']];
        $filterSetting = [
            [
                'id' => 'zoneId',
                'type' => 'single-dropdown',
                'label' => '車號',
                'model' => 'zone',
                'optionList' => $availablezone,
                'defaultValue' => $this->_zone,

                'type1' => 'shift',
                'model1' => 'shift',
                'optionList1' => $ashift,
                'defaultValue1' => $this->_shift,
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
            [
                'type' => 'csv',
                'name' => '匯出  Excel 版本',
                'warning'   =>  false,
            ],
        ];
        
        return $downloadSetting;
    }
    
    public function outputPreview()
    {
        return View::make('reports/CashReceiptSummary')->with('data', $this->_account)->with('backaccount',$this->_backaccount)->with('paidInvoice',$this->_paidInvoice)->render();
    }
    
    
    # PDF Section
    public function generateHeader($pdf)
    {
    
        $pdf->SetFont('chi','',18);
        $pdf->Cell(0, 10,"炳記行貿易有限公司",0,1,"C");
        $pdf->SetFont('chi','U',16);
        $pdf->Cell(0, 10,$this->_reportTitle,0,1,"C");
        $pdf->SetFont('chi','U',13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->_zone, 2, '0', STR_PAD_LEFT) . ' (' . $this->_zoneName .')', 0, 2, "L");
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

        $datamart = array_chunk($this->data, 30, true);


        foreach($datamart as $i=>$f)
        {
            // for first Floor
            $pdf->AddPage();
        
            $this->generateHeader($pdf);
        
            $pdf->SetFont('chi','',10);   
        
            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "訂單編號", 0, 0, "L");
        
            $pdf->setXY(40, 50);
            $pdf->Cell(0, 0, "客戶", 0, 0, "L");
        
            $pdf->setXY(130, 50);
            $pdf->Cell(0, 0, "應收金額", 0, 0, "L");
        
            $pdf->setXY(160, 50);
            $pdf->Cell(0, 0, "累計", 0, 0, "L");
                
            $pdf->Line(10, 53, 190, 53);
        
            $y = 60;
        
            $pdf->setXY(10, $pdf->h-30);
            $pdf->Cell(0, 0, "收帳人", 0, 0, "L");
        
            $pdf->setXY(60, $pdf->h-30);
            $pdf->Cell(0, 0, "核數人", 0, 0, "L");
        
            $pdf->Line(10, $pdf->h-35, 50, $pdf->h-35);
            $pdf->Line(60, $pdf->h-35, 100, $pdf->h-35);
        
            $pdf->setXY(500, $pdf->h-30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, count($datamart)) , 0, 0, "R");
        
        
            foreach($f as $id=>$e)
            {
       
                $pdf->setXY(10, $y);
                $pdf->Cell(0, 0, $e['invoiceNumber'], 0, 0, "L");
    
                $pdf->setXY(40, $y);
                $pdf->Cell(0, 0, $e['name'], 0, 0, "L");
    
                $pdf->setXY(130, $y);
                $pdf->Cell(0, 0, sprintf("HK$ %s", $e['amount']), 0, 0, "L");
    
                $pdf->setXY(160, $y);
                $pdf->Cell(0, 0, sprintf("HK$ %s", $e['accumulator']), 0, 0, "L");
                $lt = $e['accumulator'];
                $y += 6;
               
            }

        }
        $pdf->Line(10, $y, 190, $y);
        $pdf->setXY(152, $y+6);
        $pdf->Cell(0, 0, sprintf("總數 HK$ %s", $lt), 0, 0, "L");
        // output
        return [
            'pdf' => $pdf,
            'remark' => sprintf("Cash Receipt Summary, DeliveryDate = %s",date("Y-m-d", $this->_date)),
            'associates' => json_encode($this->_invoices),
            'zoneId' => $this->_zone,
            'shift' => $this->_shift,
            'uniqueId' => $this->_uniqueid,
        ];
    }
}