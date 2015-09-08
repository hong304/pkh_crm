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
    private $_paidInvoice_cheque =[];
    private $_uniqueid = "";
    
    public function __construct($indata)
    {
        if(!Auth::user()->can('view_cashreceiptsummary'))
            pd('Permission Denied');
        
        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        
        
        $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_date1 = (isset($indata['filterData']['deliveryDate2']) ? strtotime($indata['filterData']['deliveryDate2']) : strtotime("today"));
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


        $invoicesQuery = Invoice::whereIn('invoiceStatus',['1','2','20','30','98','97','96'])->where('paymentTerms',1)->where('zoneId', $zone)->where('deliveryDate', $date);
                if($this->_shift != '-1')
                    $invoicesQuery->where('shift',$this->_shift);

        $invoicesQuery = $invoicesQuery->with('client')->get();


                   $acc = 0;
                     $acc1 = 0;
                   foreach($invoicesQuery as $invoiceQ)
                   {

                       $this->_invoices[] = $invoiceQ->invoiceId;
                       $this->_zoneName = $invoiceQ->zone->zoneName;

                       // first, store all invoices
                       $invoiceId = $invoiceQ->invoiceId;
                       $invoices[$invoiceId] = $invoiceQ;
                       $client = $invoiceQ['client'];

                       if($invoiceQ->invoiceStatus == 20){
                           $paid = $invoiceQ->paid;

                           $acc1 +=  ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->remain:$invoiceQ->remain;

                           $this->_backaccount[] = [
                               'customerId' => $client->customerId,
                               'name' => $client->customerName_chi,
                               'invoiceNumber' => $invoiceId,
                               'invoiceTotalAmount' => ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->remain:$invoiceQ->remain ,
                               'accumulator' =>number_format($acc1,2,'.',','),
                               'amount' => number_format(($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->remain:$invoiceQ->remain,2,'.',','),
                           ];

                       }else{
                           $paid = $invoiceQ->remain;
                       }

                           $acc +=  ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$paid:$paid;
                           $this->_account[] = [
                               'customerId' => $client->customerId,
                               'name' => $client->customerName_chi,
                               'invoiceNumber' => $invoiceId,
                               'invoiceTotalAmount' => ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$paid:$paid ,
                               'accumulator' =>number_format($acc,2,'.',','),
                               'amount' => number_format(($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$paid:$paid,2,'.',','),
                           ];

                      }

        //補收
        $invoicesQuery = Invoice::whereIn('invoiceStatus',['30','20'])->where('paid','>',0)->where('paymentTerms',1)->where('zoneId', $zone);

        if($this->_shift != '-1')
            $invoicesQuery->where('shift',$this->_shift);
        $invoicesQuery = $invoicesQuery->with('client','payment')->WhereHas('payment', function($q) use($date)
        {
            $q->where('start_date', '=', date('Y-m-d',$date));
        })->get();

        pd($invoicesQuery);

        $acc = 0;
        $acc1 = 0;
        foreach($invoicesQuery as $invoiceQ)
        {

            foreach($invoiceQ->payment as $v1){
                if($v1->ref_number == 'cash' and $v1->receive_date == date('Y-m-d',$date) and $invoiceQ->deliveryDate < $date){
                    $acc +=  ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$v1->pivot->paid:$v1->pivot->paid;
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
                        'invoiceTotalAmount' => ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$v1->pivot->paid:$v1->pivot->paid ,
                        'accumulator' =>number_format($acc,2,'.',','),
                        'amount' => number_format(($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$v1->pivot->paid:$v1->pivot->paid,2,'.',','),
                    ];
                }else if ($v1->receive_date == date('Y-m-d',$date) and $v1->ref_number != 'cash'){
                    $acc1 +=  ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$v1->pivot->paid:$v1->pivot->paid;
                    $this->_invoices[] = $invoiceQ->invoiceId;
                    $this->_zoneName = $invoiceQ->zone->zoneName;

                    // first, store all invoices
                    $invoiceId = $invoiceQ->invoiceId;
                    $invoices[$invoiceId] = $invoiceQ;
                    $client = $invoiceQ['client'];

                    $this->_paidInvoice_cheque[] = [
                        'customerId' => $client->customerId,
                        'name' => $client->customerName_chi,
                        'deliveryDate' => date('Y-m-d',$invoiceQ->deliveryDate),
                        'invoiceNumber' => $invoiceId,
                        'invoiceTotalAmount' => ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$v1->pivot->paid:$v1->pivot->paid ,
                        'accumulator' =>number_format($acc1,2,'.',','),
                        'amount' => number_format(($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$v1->pivot->paid:$v1->pivot->paid,2,'.',','),
                        'bankCode' => $v1->bankCode,
                        'chequeNo' => $v1->ref_number,
                    ];
                }
            }

        }



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

    public function outputExcel(){
        $invoices = Invoice::where('paymentTerms',1)->where('deliveryDate','<',$this->_date)->where('invoiceStatus',20)->orderBy('zoneId')->get();

        $balance_bf = [];

        foreach($invoices as $v){

            if(!isset($balance_bf[$v->zoneId]))
                $balance_bf[$v->zoneId] = 0;
            $balance_bf[$v->zoneId] += $v->remain;
        }

        $invoices = Invoice::where('paymentTerms',1)->whereIn('invoiceStatus',[20,30])->where('paid','>',0)->with('payment')->WhereHas('payment', function($q)
        {
            $q->where('start_date', date('Y-m-d',$this->_date));
        })->get();




        foreach($invoices as $invoiceQ){
            foreach($invoiceQ->payment as $v1){
                if($v1->start_date == date('Y-m-d',$this->_date)){
                    $previous[$invoiceQ->zoneId] = (isset($previous[$invoiceQ->zoneId]))?$previous[$invoiceQ->zoneId]:0;
                    $previous[$invoiceQ->zoneId] += $v1->pivot->paid;
                }
            }
        }



        $invoices = Invoice::whereIn('invoiceStatus',['1','2','20','30','98','97','96'])->where('paymentTerms',1)->where('deliveryDate',$this->_date)->get();
        $NoOfInvoices = [];
        foreach ($invoices as $invoiceQ){
            if($invoiceQ->invoiceStatus == 20) {
                $paid = $invoiceQ->paid;
            }else{
                $paid = $invoiceQ->remain;
            }
            if(!isset($NoOfInvoices[$invoiceQ->zoneId]))
                $NoOfInvoices[$invoiceQ->zoneId] = 0;

            $NoOfInvoices[$invoiceQ->zoneId] += 1;

            if(!isset( $info[$invoiceQ->zoneId]['totalAmount']))
                $info[$invoiceQ->zoneId]['totalAmount'] = 0;
            if(!isset( $info[$invoiceQ->zoneId]['paid']))
                $info[$invoiceQ->zoneId]['paid'] = 0;

            $info[$invoiceQ->zoneId] = [
                'truck' => $invoiceQ->zoneId,
                'noOfInvoices' => $NoOfInvoices[$invoiceQ->zoneId],
                'balanceBf' => isset($balance_bf[$invoiceQ->zoneId])?$balance_bf[$invoiceQ->zoneId]:0,
                'totalAmount' => $info[$invoiceQ->zoneId]['totalAmount'] += (($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount),
                'previous'=>isset($previous[$invoiceQ->zoneId])?$previous[$invoiceQ->zoneId]:0,
                'paid' => $info[$invoiceQ->zoneId]['paid'] += ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$paid:$paid,
            ];
        }
        ksort($info);

        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';

        $i=3;
        $objPHPExcel = new PHPExcel ();

        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Delivery Date');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', date('Y-m-d',$this->_date));

        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, 'Truck');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, 'No. of invoices');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, 'Balance B/F');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, 'Today sales');
        $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, 'Receive for today sales');
        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, 'Receive for previous sales');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, 'Balance C/F');

        $i += 1;
        foreach ($info as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $v['truck']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $v['noOfInvoices']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $v['balanceBf']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $v['totalAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v['paid']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $v['previous']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, '=C'.$i.'+D'.$i.'-E'.$i.'-F'.$i);
            $i++;
        }

        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }

//        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date('Ymd',$this->_date).'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    public function outputExcel1(){

        while ($this->_date <= $this->_date1) {
            $date[] = $this->_date;
            $this->_date = $this->_date+24*60*60;
        }


        foreach ($date as $d){
            $invoices = Invoice::where('paymentTerms',1)->where('deliveryDate','<',$d)->where('invoiceStatus',20)->get();

            $balance_bf = [];

            foreach($invoices as $v){
                if(!isset($balance_bf[$d]))
                    $balance_bf[$d] = 0;
                $balance_bf[$d] += $v->remain;
            }



            $invoices = Invoice::where('paymentTerms',1)->whereIn('invoiceStatus',[20,30])->where('paid','>',0)->with('payment')->WhereHas('payment', function($q) use($d)
            {
                $q->where('start_date', date('Y-m-d',$d));
            })->get();


            foreach($invoices as $invoiceQ){
                foreach($invoiceQ->payment as $v1){
                    if($v1->start_date == date('Y-m-d',$d)){
                        $previous[$d] = (isset($previous[$d]))?$previous[$d]:0;
                        $previous[$d] += $v1->pivot->paid;
                    }
                }
            }

            $invoices = Invoice::whereIn('invoiceStatus',['1','2','20','30','98','97','96'])->where('paymentTerms',1)->where('deliveryDate',$d)->get();
            $NoOfInvoices = [];
            foreach ($invoices as $invoiceQ){
                if($invoiceQ->invoiceStatus == 20) {
                    $paid = $invoiceQ->paid;
                }else{
                    $paid = $invoiceQ->remain;
                }
                if(!isset($NoOfInvoices[$d]))
                    $NoOfInvoices[$d] = 0;

                $NoOfInvoices[$d] += 1;

                if(!isset( $info[$d]['totalAmount']))
                    $info[$d]['totalAmount'] = 0;
                if(!isset( $info[$d]['paid']))
                    $info[$d]['paid'] = 0;

                $info[$d] = [
                    'noOfInvoices' => $NoOfInvoices[$d],
                    'balanceBf' => isset($balance_bf[$d])?$balance_bf[$d]:0,
                    'totalAmount' => $info[$d]['totalAmount'] += (($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$invoiceQ->amount:$invoiceQ->amount),
                    'previous'=>isset($previous[$d])?$previous[$d]:0,
                    'paid' => $info[$d]['paid'] += ($invoiceQ->invoiceStatus == '98' || $invoiceQ->invoiceStatus == '97')? -$paid:$paid,
                ];
            }
            ksort($info);
        }



        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';

        $i=3;
        $objPHPExcel = new PHPExcel ();

        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Delivery Date');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', date('Y-m-d',$this->_date));
        $objPHPExcel->getActiveSheet()->setCellValue('C1', 'To');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', date('Y-m-d',$this->_date1));


        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, 'Date');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, 'No. of invoices');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, 'Balance B/F');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, 'Today sales');
        $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, 'Receive for today sales');
        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, 'Receive for previous sales');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, 'Balance C/F');

        $i += 1;
        foreach ($info as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, date('Y-m-d',$k));
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $v['noOfInvoices']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $v['balanceBf']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $v['totalAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v['paid']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $v['previous']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, '=C'.$i.'+D'.$i.'-E'.$i.'-F'.$i);
            $i++;
        }

         foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
             $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
         }

//        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date('Ymd',$this->_date).'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    public function outputCsv(){

        $csv = 'CustomerID,Customer Name,Invoice No.,Total Amount,no. check,db>in,in>db,Invoice No. on hand,Invoice amount on hand,Duplication check' . "\r\n";
        $totalinvoice = count($this->data)+1;
        $ii = 2;
        foreach ($this->data as $o) {
            $csv .= '"' . $o['customerId'] . '",';
            $csv .= '"' . $o['name'] . '",';
            $csv .= '"' . $o['invoiceNumber'] . '",';
            $csv .= '"' . $o['invoiceTotalAmount'] . '",';
            $csv .= '"' . substr($o['invoiceNumber'], -5) . '",';
            $csv .= '"=VLOOKUP(E'.$ii.',H$2:H$'.$totalinvoice.',1,FALSE)",';
            $csv .= '"=VLOOKUP(H'.$ii.',E$2:E$'.$totalinvoice.',1,FALSE)",';
            $csv .= ",";
            $csv .= ",";
            $csv .= '"=COUNTIF(H2:H$'.$totalinvoice.',H'.$ii.')>1",';
            $csv .= "\r\n";
            $ii++;
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
                'type' => 'date-picker1',
                'label' => '送貨日期',
                'model' => 'deliveryDate',
                'id1' => 'deliveryDate2',
                'model1' => 'deliveryDate2',
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
            [
                'type' => 'excel',
                'name' => '收帳日結表',
                'warning'   =>  false,
            ],
            [
                'type' => 'excel1',
                'name' => '收帳總結表',
                'warning'   =>  false,
            ],


        ];
        
        return $downloadSetting;
    }
    
    public function outputPreview()
    {
        return View::make('reports/CashReceiptSummary')->with('data', $this->_account)->with('backaccount',$this->_backaccount)->with('paidInvoice',$this->_paidInvoice)->with('paidInvoiceCheque',$this->_paidInvoice_cheque)->render();
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