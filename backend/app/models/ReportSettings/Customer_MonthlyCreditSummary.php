<?php


class Customer_MonthlyCreditSummary { 
    
    private $_reportTitle = "";

    private $_uniqueid = "";
    
    private $_unPaid = [];

    private $_monthly = [];

    private $_reportMonth = '';

    private $_acc = [];
     
    public function __construct($indata)
    {

//pd($indata);
        
        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        $this->_indata = $indata;

      //  $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_zone =  Auth::user()->temp_zone;

        $this->_group = (isset($indata['filterData']['group']) ? $indata['filterData']['group'] : '');

        $this->_date1 = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_date2 = (isset($indata['filterData']['deliveryDate2']) ? strtotime($indata['filterData']['deliveryDate2']) : strtotime("today"));
        $this->_uniqueid = microtime(true);
    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }
    
    public function compileResults()
    {

        $filter = $this->_indata['filterData'];

if($this->_group == '' && $filter['name'] =='' && $filter['phone'] == ''&& $filter['customerId'] == ''){
    $empty = true;
    $this->data=[];
}else{
    $empty = false;
}

if(!$empty){

    //select('Customer.customerId','customer.phone_1','account_tel','account_fax','account_contact','customer.address_chi','customerName_chi','invoiceDate','amount','paid','invoiceId','customerRef','invoiceStatus','customer_groups.name')

        $invoices = Invoice::leftJoin('Customer', function($join) {
            $join->on('Customer.customerId', '=', 'Invoice.customerId');
        })->leftJoin('customer_groups', function($join) {
            $join->on('customer_groups.id', '=', 'Customer.customer_group_id');
        })->where('Invoice.deliveryDate','<=',$this->_date2);

            //->whereBetween('Invoice.deliveryDate', [$this->_date1,$this->_date2]);

        if($this->_group != '')
            $invoices->where('customer_groups.name','LIKE',$this->_group.'%');

    if($filter['name'] != '' || $filter['phone'] != '' || $filter['customerId'] !=''){
                 $invoices->where(function ($query) use ($filter) {
            $query
                ->where('customerName_chi', 'LIKE', $filter['name'] . '%')
                ->where('Customer.phone_1', 'LIKE', $filter['phone'] . '%')
                ->where('Customer.customerId', 'LIKE', $filter['customerId'] . '%');
        });
        }

    $invoices = $invoices->where('paymentTerms',2)->where('amount','!=','paid')->OrderBy('invoice.customerId','asc')->orderBy('deliveryDate')->get();



            foreach($invoices as $invoice)
            {
                if(!isset($this->_acc[$invoice->customerId]))
                    $this->_acc[$invoice->customerId] = 0;

                if($invoice->deliveryDate < $this->_date1){
                    $this->_acc[$invoice->customerId] += $invoice->realAmount-$invoice->paid;
                }elseif($invoice->deliveryDate >= $this->_date1){
                    $customerId = $invoice->customerId;
                    $this->_unPaid[$customerId]['customer'] = [
                        'customerId' => $customerId,
                        'customerName' => $invoice->customerName_chi,
                        'customerAddress' => $invoice->address_chi,
                          'account_tel' => $invoice['client']->account_tel,
                        'account_fax' => $invoice['client']->account_fax,
                       'account_contact' => $invoice['client']->account_contact,

                    ];
                    if($invoice->amount != $invoice->paid)
                        $this->_unPaid[$customerId]['breakdown'][] = [
                            'invoiceDate' => $invoice->invoiceDate,
                            'invoice' => $invoice->invoiceId,
                            'customerRef' => $invoice->customerRef,
                            'invoiceAmount' => ($invoice->invoiceStatus == '98')? 0:$invoice->amount ,
                            'paid' =>($invoice->invoiceStatus == '98')? $invoice->amount: $invoice->paid,
                            'accumulator' => $this->_acc[$customerId] += (($invoice->invoiceStatus == '98' || $invoice->invoiceStatus == '97')? -$invoice->amount:$invoice->amount-$invoice->paid)
                        ];
                }
            }

        $this->data = $this->_unPaid;

}
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
       // array_unshift($availablezone,['value'=>'-1','label'=>'檢視全部']);
      //  $ashift =[['value'=>'-1','label'=>'檢視全部'],['value'=>'1','label'=>'早班'],['value'=>'2','label'=>'晚班']];
        $filterSetting = [
            [
                'id' => 'group',
                'type' => 'search_group',
                'label' => '集團名稱',
                'model' => 'group',
            ],

            [
                'id' => 'customer',
                'type' => 'search_customer',
                'label' => '客户資料',
                'model' => 'customer',
            ],

            [
                'id' => 'deliveryDate',
                'type' => 'date-picker1',
                'label' => '送貨日期',
                'model' => 'deliveryDate',
                'id1' => 'deliveryDate2',
                'model1' => 'deliveryDate2',
            ],

          /*  [
                'id' => 'year',
                'type' => 'single-dropdown',
                'label' => '年份',
                'model' => 'year',
                'optionList' => [date("Y")-1 => date("Y")-1, date("Y") => date("Y"), date("Y")+1 => date("Y")+1],
                'defaultValue' => date("Y"),
            ],*/
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
                'name' => '匯出帳齡搞要',
                'warning'   =>  false,
            ],
        ];
        
        return $downloadSetting;
    }
    
    public function outputPreview()
    {

        return View::make('reports/MonthlyCreditSummary')->with('data', $this->data)->render();
        
    }


    public function outputCsv(){

        $this->_reportMonth = date("n",$this->_date2);

        $times  = array();
        for($month = 1; $month <= 12; $month++) {
            $first_minute = mktime(0, 0, 0, $month, 1,date('Y'));
            $last_minute = mktime(23, 59, 0, $month, date('t', $first_minute),date('Y'));

            if($this->_reportMonth==$month)
                $last_minute =  $this->_date2;
            $times[$month] = array($first_minute, $last_minute);
        }
        $csv = date('Y-m-d',$this->_date1).',To,'.date('Y-m-d',$this->_date2);
        $csv .= "\r\n";
        $csv .= 'CustomerID,Customer Name,Total Amount,Paid,Remain,'.date('Y') . '/' . ($this->_reportMonth).','.date('Y') . '/' . ($this->_reportMonth - 1).','.date('Y') . '/' . ($this->_reportMonth - 2).','.date('Y') . '/' . ($this->_reportMonth - 3) . "\r\n";

$j=2;

        foreach($this->data as $client) {

            for ($i = $this->_reportMonth; $i > 0; $i--) {
                $data[$i] = Invoice::whereBetween('deliveryDate', $times[$i])->where('paymentTerms', 2)->where('Invoice.customerId', $client['customer']['customerId'])->OrderBy('deliveryDate')->get();

                foreach($data[$i] as $invoice)
                {
                    $customerId = $invoice->customerId;
                    $this->_monthly[$i][$customerId][]= [
                        'accumulator' => (isset($this->_monthly[$i][$customerId]) ? end($this->_monthly[$i][$customerId])['accumulator'] : 0) + $invoice->realAmount-$invoice->paid
                    ];
                }
            }

            $amount = 0;
            $paid = 0;
            $accu = 0;

            foreach ($client['breakdown'] as $k => $v) {

                    $amount += $v['invoiceAmount'];
                    $paid += $v['paid'];
                    $accu = $v['accumulator'];

            }


            $csv .= '"' . $client['customer']['customerId'] . '",';
            $csv .= '"' . $client['customer']['customerName'] . '",';
            $csv .= '"' . $amount . '",';
            $csv .= '"' . $paid . '",';
            $csv .= '"' . $accu . '",';
            $csv .= '"' . number_format(isset($this->_monthly[$this->_reportMonth][$customerId])?end($this->_monthly[$this->_reportMonth][$customerId])['accumulator']:0, 2, '.', ',') . '",';
            $csv .= '"' . number_format(isset($this->_monthly[$this->_reportMonth-1][$customerId])?end($this->_monthly[$this->_reportMonth-1][$customerId])['accumulator']:0, 2, '.', ',') . '",';
            $csv .= '"' . number_format(isset($this->_monthly[$this->_reportMonth-2][$customerId])?end($this->_monthly[$this->_reportMonth-2][$customerId])['accumulator']:0, 2, '.', ',') . '",';
            $csv .= '"' . number_format(isset($this->_monthly[$this->_reportMonth-3][$customerId])?end($this->_monthly[$this->_reportMonth-3][$customerId])['accumulator']:0, 2, '.', ',') . '",';
            $csv .= "\r\n";
            $j++;
        }
        $csv .= '"",';
        $csv .= '"合共總額",';
        $csv .= '=SUM(C3:C'.$j.'),';
        $csv .= '=SUM(D3:D'.$j.'),';
        $csv .= '=SUM(E3:E'.$j.'),';
        $csv .= '=SUM(F3:F'.$j.'),';
        $csv .= '=SUM(G3:G'.$j.'),';
        $csv .= '=SUM(H3:H'.$j.'),';
        $csv .= '=SUM(I3:I'.$j.'),';
        $csv .= "\r\n";

        echo "\xEF\xBB\xBF";

        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="aging.csv"',
        );

        return Response::make(rtrim($csv, "\n"), 200, $headers);


    }
    
    # PDF Section
    public function generateHeader($pdf)
    {

        $pdf->SetFont('chi','',18);
        $pdf->setXY(45, 10);
        $pdf->Cell(0, 0,"炳 記 行 貿 易 有 限 公 司",0,1,"L");

        $pdf->SetFont('chi','',18);
        $pdf->setXY(45, 18);
        $pdf->Cell(0, 0,"PING KEE HONG TRADING COMPANY LTD.",0,1,"L");

        $pdf->SetFont('chi','',9);
        $pdf->setXY(45, 25);
        $pdf->Cell(0, 0,"Flat B, 9/F., Wang Cheung Industrial Building, 6 Tsing Yeung St., Tuen Mun, N.T. Hong Kong.",0,1,"L");

        $pdf->SetFont('chi','',9);
        $pdf->setXY(45, 30);
        $pdf->Cell(0, 0,"TEL:24552266    FAX:24552449",0,1,"L");

        $pdf->SetFont('chi','U',16);
        $pdf->setXY(0, 40);
        $pdf->Cell(0, 0,$this->_reportTitle,0,0,"C");


        $pdf->SetFont('chi','', 9);
        $pdf->Code128(10,$pdf->h-15,$this->_uniqueid,50,10);
        $pdf->setXY(0, 5);
        $pdf->Cell(0, 0, sprintf("報告編號: %s", $this->_uniqueid), 0, 0, "R");

        $image = public_path('logo.jpg');
        $pdf->Cell( 40, 40, $pdf->Image($image, 15, 5, 28), 0, 0, 'L', false );
        
    }
    
    public function outputPDF()
    {

        $this->_reportMonth = date("n",$this->_date2);


       $times  = array();
        for($month = 1; $month <= 12; $month++) {
            $first_minute = mktime(0, 0, 0, $month, 1,date('Y'));
            $last_minute = mktime(23, 59, 0, $month, date('t', $first_minute),date('Y'));

            if($this->_reportMonth==$month)
                $last_minute =  $this->_date2;
            $times[$month] = array($first_minute, $last_minute);
        }




        $pdf = new PDF();
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);





        foreach($this->data as $client) {



                for ($i = $this->_reportMonth; $i > 0; $i--) {
                    $data[$i] = Invoice::whereBetween('deliveryDate', $times[$i])->where('paymentTerms', 2)->where('Invoice.customerId', $client['customer']['customerId'])->OrderBy('deliveryDate')->get();


                    foreach($data[$i] as $invoice)
                    {
                        $customerId = $invoice->customerId;
                        $this->_monthly[$i][$customerId][]= [
                            'accumulator' => (isset($this->_monthly[$i][$customerId]) ? end($this->_monthly[$i][$customerId])['accumulator'] : 0) + $invoice->realAmount-$invoice->paid
                        ];
                    }
                }

          //  pd($this->_monthly);

            $pdf->AddPage();
            $this->generateHeader($pdf);

            $y = 50;

            $pdf->SetFont('chi', '', 12);
            $pdf->setXY(15, $y);
            $pdf->Cell(0, 0, "M/S", 0, 0, "L");

            $pdf->setXY(30, $y);
            $pdf->Cell(0, 0, sprintf("%s(%s)", $client['customer']['customerName'],$client['customer']['customerId']), 0, 0, "L");

            $pdf->setXY(30, $y+5);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['customerAddress']), 0, 0, "L");

            $pdf->SetFont('chi', '', 14);
            $pdf->setXY(30, $y+20);
            $pdf->Cell(0, 0, "Tel:", 0, 0, "L");
            
            $pdf->SetFont('chi', '', 14);
            $pdf->setXY(40, $y+20);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['account_tel']), 0, 0, "L");
            
            $pdf->SetFont('chi', '', 14);
            $pdf->setXY(80, $y+20);
            $pdf->Cell(0, 0, "Fax:", 0, 0, "L");
            
            $pdf->SetFont('chi', '', 14);
            $pdf->setXY(90, $y+20);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['account_fax']), 0, 0, "L");

            $pdf->SetFont('chi', '', 14);
            $pdf->setXY(30, $y+14);
            $pdf->Cell(0, 0, "Attn:", 0, 0, "L");
            
            $pdf->SetFont('chi', '', 14);
            $pdf->setXY(50, $y+14);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['account_contact']), 0, 0, "L");


            $pdf->setXY(130, $y);
            $pdf->Cell(0, 0, '列印日期:', 0, 0, "L");

            $pdf->setXY(155, $y);
            $pdf->Cell(0, 0, date('Y-m-d', time()), 0, 0, "L");

            $pdf->setXY(130, $y+5);
            $pdf->Cell(0, 0, '由日期:', 0, 0, "L");

            $pdf->setXY(155, $y+5);
            //   $pdf->Cell(0, 0, date('Y-m-d', $client['breakdown'][0]['invoiceDate']), 0, 0, "L");
            $pdf->Cell(0, 0, date('Y-m-d', $this->_date1), 0, 0, "L");

            $pdf->setXY(130, $y+10);
            $pdf->Cell(0, 0, '至日期:', 0, 0, "L");

            $pdf->setXY(155, $y+10);
            // $pdf->Cell(0, 0, date('Y-m-d', $client['breakdown'][sizeof($client['breakdown']) - 1]['invoiceDate']), 0, 0, "L");
            $pdf->Cell(0, 0, date('Y-m-d', $this->_date2), 0, 0, "L");

            $y = 60;
            
             $pdf->SetFont('chi', '', 20);
            $pdf->setXY(130, $y+10);
            $pdf->Cell(0, 0, date('Y年m月', $this->_date1), 0, 0, "L");

            $pdf->SetFont('chi', '', 12);
            $pdf->setXY(10, $y+20);
            $pdf->Cell(0, 0, "發票日期", 0, 0, "L");

            $pdf->setXY(40, $y+20);
            $pdf->Cell(0, 0, "發票編號", 0, 0, "L");

            $pdf->setXY(105, $y+20);
            $pdf->Cell(0, 0, "借方", 0, 0, "L");

            $pdf->setXY(140, $y+20);
            $pdf->Cell(0, 0, "貸方", 0, 0, "L");

            $pdf->setXY(165, $y+20);
            $pdf->Cell(0, 0, "未清付金額", 0, 0, "L");


                $pdf->setXY(500, $pdf->h - 30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i + 1, count($this->data)), 0, 0, "R");



            $pdf->Line(10, $y+25, 190, $y+25);

            $y += 30;
            $amount = 0;
            $paid = 0;

            $bd = array_chunk($client['breakdown'],29,true);

            foreach ($bd as $k => $g) {
                $count = 0;
                if($k > 0) {
                    $pdf->AddPage();
                    $y = 20;
                }

                foreach ($g as $v) {

                    $pdf->SetFont('chi','',10);

                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, date('Y-m-d', $v['invoiceDate']), 0, 0, "L");

                    $ref = ($v['customerRef'] != '')?$v['customerRef']:'';
                    $ref = ($ref!='')?' ('.$ref.')':'';

                    $pdf->setXY(40, $y);
                    $pdf->Cell(0, 0, $v['invoice'].$ref, 0, 0, "L");

                    if($v['invoiceAmount']!=0)
                        $acm = "$" . number_format($v['invoiceAmount'], 2, '.', ',');
                    else
                        $acm = '';

                    if($v['paid']!=0)
                        $apaid = "$" . number_format($v['paid'], 2, '.', ',');
                    else
                        $apaid = '';


                    $pdf->setXY(105, $y);
                    $pdf->Cell(10, 0, $acm , 0, 0, "R");

                    $pdf->setXY(140, $y);
                    $pdf->Cell(10, 0,  $apaid, 0, 0, "R");

                    $pdf->setXY(170, $y);
                    $pdf->Cell(20, 0, "$" . number_format($v['accumulator'], 2, '.', ','), 0, 0, "R");

                    $amount += $v['invoiceAmount'];
                    $paid += $v['paid'];
                    $accu = $v['accumulator'];

                    $y += 6;
                    $count++;
                }



            }

            if($count > 22){
                $pdf->AddPage();
                $y = 20;
            }
            $pdf->Line(10, $y, 190, $y);

            $pdf->setXY(35, $y + 6);
            $pdf->Cell(0, 0, '未清付發票總金額(HKD):', 0, 0, "L");

            $pdf->SetFont('Arial','B',12);
            $pdf->setXY(105, $y + 6);
            $pdf->Cell(10, 0,  "$" . number_format($amount, 2, '.', ','), 0, 0, "R");

         //   $pdf->setXY(140, $y + 6);
          //  $pdf->Cell(10, 0,  "$" . number_format($paid, 2, '.', ','), 0, 0, "R");

            $pdf->setXY(170, $y + 6);
            $pdf->Cell(20, 0, "$" . number_format($accu, 2, '.', ','), 0, 0, "R");

            $pdf->Line(10, $y + 12, 190, $y + 12);

            $pdf->SetFont('Arial','',12);
            $pdf->setXY(10, $y + 18);
            $pdf->Cell(0, 0, 'The outstanding balance is aged by invoice date as ' . date('Y-m-d',  $this->_date2) . ' below:', 0, 0, "L");

            $pdf->SetFont('Arial','U',12);
            $pdf->setXY(10, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . $this->_reportMonth, 0, 0, "L");

            $pdf->setXY(50, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . ($this->_reportMonth - 1), 0, 0, "L");

            $pdf->setXY(90, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . ($this->_reportMonth - 2), 0, 0, "L");

            $pdf->setXY(130, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . ($this->_reportMonth - 3), 0, 0, "L");


            $pdf->SetFont('Arial','',12);
            $pdf->setXY(10, $y + 30);
            $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[$this->_reportMonth][$customerId])?end($this->_monthly[$this->_reportMonth][$customerId])['accumulator']:0, 2, '.', ','), 0, 0, "L");

            $pdf->setXY(50, $y + 30);
          $pdf->Cell(0, 0, '$' .  number_format(isset($this->_monthly[$this->_reportMonth-1][$customerId])?end($this->_monthly[$this->_reportMonth-1][$customerId])['accumulator']:0, 2, '.', ','), 0, 0, "L");
           // $pdf->Cell(0, 0, '$' . number_format(0, 2, '.', ','), 0, 0, "L");

            $pdf->setXY(90, $y + 30);
           $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[$this->_reportMonth-2][$customerId])?end($this->_monthly[$this->_reportMonth-2][$customerId])['accumulator']:0, 2, '.', ','), 0, 0, "L");
          //  $pdf->Cell(0, 0, '$' . number_format(0, 2, '.', ','), 0, 0, "L");

            $pdf->setXY(130, $y + 30);
            $pdf->Cell(0, 0, '$' .number_format(isset($this->_monthly[$this->_reportMonth-3][$customerId])?end($this->_monthly[$this->_reportMonth-3][$customerId])['accumulator']:0, 2, '.', ','), 0, 0, "L");
           // $pdf->Cell(0, 0, '$' .number_format(0, 2, '.', ','), 0, 0, "L");


            $pdf->setXY(10, $y + 36);
            $pdf->Cell(0, 0, 'Payment received after statement date not included', 0, 0, "L");


        }


        // output
        return [
            'pdf' => $pdf,
            'remark' => 'Credit Monthly Report',
            'uniqueId' => $this->_uniqueid,
            'zoneId' => $this->_zone,
            'associates' => null,
        ];


    }
}