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
        
                
        Invoice::where('invoiceStatus', '20')->with('client', 'invoiceItem')->OrderBy('deliveryDate')->chunk(50, function($invoices){
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
            [
                'id' => 'year',
                'type' => 'single-dropdown',
                'label' => '年份',
                'model' => 'year',
                'optionList' => [date("Y")-1 => date("Y")-1, date("Y") => date("Y"), date("Y")+1 => date("Y")+1],
                'defaultValue' => date("Y"),
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
        $pdf = new PDF();
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);
        
        foreach($this->data as $client)
        {
            $pdf->AddPage();
            $this->generateHeader($pdf);
            
            $pdf->SetFont('chi','',12);
            $pdf->setXY(15, 35);
            $pdf->Cell(0, 0, "M/S", 0, 0, "L");
            
            $pdf->setXY(30, 35);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['customerName']), 0, 0, "L");
            
            $pdf->setXY(30, 40);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['customerAddress']), 0, 0, "L");
        }
        $pdf->Output();exit;
        // output
        return [
        ];
    }
}