<?php


class Customer_MonthlyCreditSummary { 
    
    private $_reportTitle = "";

    private $_uniqueid = "";
    
    private $_unPaid = [];
     
    public function __construct($indata)
    {
        
        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        
        $permittedZone = explode(',', Auth::user()->temp_zone);
        $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        
        $this->_uniqueid = microtime(true);
    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }
    
    public function compileResults()
    {
        
                
        Invoice::leftJoin('Customer', function($join) {
            $join->on('Invoice.customerId', '=', 'Customer.customerId');
        })->where('paymentTermId',2)->with('client', 'invoiceItem')->where('zoneId',$this->_zone)->OrderBy('deliveryDate')->chunk(50, function($invoices){
            foreach($invoices as $invoice)
            {
                $customerId = $invoice['client']->customerId;
                $this->_unPaid[$customerId]['customer'] = [
                    'customerId' => $customerId,
                    'customerName' => $invoice['client']->customerName_chi,
                    'customerAddress' => $invoice['client']->address_chi,
                    
                ];
                $this->_unPaid[$customerId]['breakdown'][] = [
                    'invoiceDate' => $invoice->invoiceDate,
                    'invoice' => $invoice->invoiceId,
                    'invoiceAmount' => $invoice->invoiceTotalAmount,
                    'paid' => $invoice->paid,
                    'accumulator' => (isset($this->_unPaid[$customerId]['breakdown']) ? end($this->_unPaid[$customerId]['breakdown'])['accumulator'] : 0) + ($invoice->invoiceTotalAmount-$invoice->paid)
                ];
            }
        });
        //dd($this->_unPaid);
        $this->data = $this->_unPaid;

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
        ];
        
        return $downloadSetting;
    }
    
    public function outputPreview()
    {

        return View::make('reports/MonthlyCreditSummary')->with('data', $this->data)->render();
        
    }
    
    
    # PDF Section
    public function generateHeader($pdf)
    {
        $pdf->SetFont('chi','',18);
        $pdf->Cell(0, 10,"炳記行貿易有限公司",0,1,"C");
        $pdf->SetFont('chi','U',16);
        $pdf->Cell(0, 10,$this->_reportTitle,0,1,"C");
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi','', 9);
        $pdf->Code128(10,$pdf->h-15,$this->_uniqueid,50,10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");
        
    }
    
    public function outputPDF()
    {

        $times  = array();
        for($month = 1; $month <= 12; $month++) {
            $first_minute = mktime(0, 0, 0, $month, 1,date('Y'));
            $last_minute = mktime(23, 59, 0, $month, date('t', $first_minute),date('Y'));
            $times[$month] = array($first_minute, $last_minute);
        }

        for ($i = date('n'); $i>0; $i--){
            $data[$i] = Invoice::whereBetween('deliveryDate',$times[$i])->leftJoin('Customer', function($join) {
                $join->on('Invoice.customerId', '=', 'Customer.customerId');
            })->where('paymentTermId',2)->sum('amount');
        }
     // pd($data);

        $pdf = new PDF();
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);





        foreach($this->data as $client) {



                for ($i = date('n'); $i > 0; $i--) {
                    $data[$i] = Invoice::whereBetween('deliveryDate', $times[$i])->leftJoin('Customer', function ($join) {
                        $join->on('Invoice.customerId', '=', 'Customer.customerId');
                    })->where('paymentTermId', 2)->where('Invoice.customerId', $client['customer']['customerId'])->sum('amount');
                }

            $pdf->AddPage();
            $this->generateHeader($pdf);

            $pdf->SetFont('chi', '', 12);
            $pdf->setXY(15, 35);
            $pdf->Cell(0, 0, "M/S", 0, 0, "L");

            $pdf->setXY(30, 35);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['customerName']), 0, 0, "L");

            $pdf->setXY(30, 40);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['customerAddress']), 0, 0, "L");

            $pdf->setXY(10, 60);
            $pdf->Cell(0, 0, "發票日期", 0, 0, "L");

            $pdf->setXY(40, 60);
            $pdf->Cell(0, 0, "發票編號", 0, 0, "L");

            $pdf->setXY(100, 60);
            $pdf->Cell(0, 0, "借方", 0, 0, "L");

            $pdf->setXY(140, 60);
            $pdf->Cell(0, 0, "貸方", 0, 0, "L");

            $pdf->setXY(165, 60);
            $pdf->Cell(0, 0, "未清付金額", 0, 0, "L");


            $pdf->setXY(130, 35);
            $pdf->Cell(0, 0, '列印日期:', 0, 0, "L");

            $pdf->setXY(155, 35);
            $pdf->Cell(0, 0, date('Y-m-d', time()), 0, 0, "L");

            $pdf->setXY(130, 40);
            $pdf->Cell(0, 0, '由日期:', 0, 0, "L");

            $pdf->setXY(155, 40);
            $pdf->Cell(0, 0, date('Y-m-d', $client['breakdown'][0]['invoiceDate']), 0, 0, "L");

            $pdf->setXY(500, $pdf->h - 30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i + 1, count($this->data)), 0, 0, "R");


            $pdf->setXY(130, 45);
            $pdf->Cell(0, 0, '至日期:', 0, 0, "L");

            $pdf->setXY(155, 45);
            $pdf->Cell(0, 0, date('Y-m-d', $client['breakdown'][sizeof($client['breakdown']) - 1]['invoiceDate']), 0, 0, "L");


            $pdf->Line(10, 63, 190, 63);

            $y = 70;
            $amount = 0;
            $paid = 0;

            $bd = array_chunk($client['breakdown'],30,true);

            foreach ($bd as $k => $g) {
               // $pdf->AddPage();

                if($k > 0) {
                    $pdf->AddPage();
                    $y = 20;
                }

                foreach ($g as $v) {

                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, date('Y-m-d', $v['invoiceDate']), 0, 0, "L");

                    $pdf->setXY(40, $y);
                    $pdf->Cell(0, 0, $v['invoice'], 0, 0, "L");

                    $pdf->setXY(100, $y);
                    $pdf->Cell(10, 0, sprintf("%s", $v['invoiceAmount']), 0, 0, "R");

                    $pdf->setXY(140, $y);
                    $pdf->Cell(10, 0, $v['paid'], 0, 0, "R");

                    $pdf->setXY(165, $y);
                    $pdf->Cell(20, 0, $v['accumulator'], 0, 0, "R");

                    $amount += $v['invoiceAmount'];
                    $paid += $v['paid'];
                    $accu = $v['accumulator'];

                    $y += 6;

                }
            }
            $pdf->Line(10, $y, 190, $y);

            $pdf->setXY(40, $y + 6);
            $pdf->Cell(0, 0, '未清付發票總金額(HKD):', 0, 0, "L");

            $pdf->setXY(100, $y + 6);
            $pdf->Cell(10, 0, $amount, 0, 0, "R");

            $pdf->setXY(140, $y + 6);
            $pdf->Cell(10, 0, $paid, 0, 0, "R");

            $pdf->setXY(165, $y + 6);
            $pdf->Cell(20, 0, $accu, 0, 0, "R");

            $pdf->Line(10, $y + 12, 190, $y + 12);

            $pdf->setXY(10, $y + 18);
            $pdf->Cell(0, 0, 'The outstanding balance is aged by invoice date as ' . date('Y-m-d', time()) . ' below:', 0, 0, "L");

            $pdf->setXY(10, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . date('n'), 0, 0, "L");

            $pdf->setXY(10, $y + 30);
            $pdf->Cell(0, 0, '$' . $data[date('n')], 0, 0, "L");

            $pdf->setXY(40, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . (date('m') - 1), 0, 0, "L");

            $pdf->setXY(40, $y + 30);
            $pdf->Cell(0, 0, '$' . $data[date('n') - 1], 0, 0, "L");

            $pdf->setXY(70, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . (date('m') - 2), 0, 0, "L");

            $pdf->setXY(70, $y + 30);
            $pdf->Cell(0, 0, '$' . $data[date('n') - 2], 0, 0, "L");

            $pdf->setXY(100, $y + 24);
            $pdf->Cell(0, 0, date('Y') . '/' . (date('m') - 3), 0, 0, "L");

            $pdf->setXY(100, $y + 30);
            $pdf->Cell(0, 0, '$' . $data[date('n') - 3], 0, 0, "L");

            $pdf->setXY(10, $y + 36);
            $pdf->Cell(0, 0, 'Payment received after statement date not included', 0, 0, "L");


        }
        // output
        return [
            'pdf' => $pdf,
            'remark' => '',
            'uniqueId' => $this->_uniqueid,
            'associates' => null,
        ];


    }
}