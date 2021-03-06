<?php

class Customer_MonthlyCreditSummary {

    private $_reportTitle = "";
    private $_uniqueid = "";
    private $_unPaid = [];
    private $_monthly = [];
    private $_reportMonth = '';
    private $_acc = [];
    private $time = '';
    private $month = '';

    public function __construct($indata) {

        if (!Auth::user()->can('view_creditsummary')) {
            pd('Permission Denied');
        }

//pd($indata);

        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        $this->_indata = $indata;

        //  $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_zone = Auth::user()->temp_zone;

        $this->_group = (isset($indata['filterData']['group']) ? $indata['filterData']['group'] : '');

        $this->_date1 = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_date2 = (isset($indata['filterData']['deliveryDate2']) ? strtotime($indata['filterData']['deliveryDate2']) : strtotime("today"));
        $this->_uniqueid = microtime(true);

       // $time_interval = [['0', '0'], ['1', '1'], ['2', '2'], ['5', '3'], ['11', '6'], ['120', '12']];

        $time_interval = [['0', '0'], ['1', '1'], ['2', '2'],['3','3'],['5', '4'], ['11', '6'], ['120', '12']];
        $first = true;

        $ymd = date('Y-m-d',$this->_date2);
        $m = date("m", $this->_date2);
        $y = date("Y", $this->_date2);


        foreach ($time_interval as $v) {
            if ($first) {
                $this->time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime($ymd . "-" . $v[0] . " month"));
                $this->time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][1] = date("Y-m-d", strtotime($ymd . "-" . $v[1] . " month"));
                $first = false;
            } else {
                $this->time[date("Y-m", mktime(0, 0, 0, $m-$v[1], 1, $y))][0] = date("Y-m-01", mktime(0, 0, 0, $m-$v[0], 1, $y));
                $this->time[date("Y-m", mktime(0, 0, 0, $m-$v[1], 1, $y))][1] = date("Y-m-t", mktime(0, 0, 0, $m-$v[1], 1, $y));
            }
        }

        $this->month[0] = substr( $this->time[key(array_slice( $this->time, -7, 1, true))][0],0,7);
        $this->month[1] = substr( $this->time[key(array_slice( $this->time, -6, 1, true))][0],0,7);
        $this->month[2] = substr( $this->time[key(array_slice( $this->time, -5, 1, true))][0],0,7);
        $this->month[3] = substr( $this->time[key(array_slice( $this->time, -4, 1, true))][0],0,7);
        $this->month[4] = substr( $this->time[key(array_slice( $this->time, -3, 1, true))][0],0,7). ' to '. substr( $this->time[key(array_slice( $this->time, -3, 1, true))][1],0,7);
        $this->month[5] = substr( $this->time[key(array_slice( $this->time, -2, 1, true))][0],0,7). ' to '. substr( $this->time[key(array_slice( $this->time, -2, 1, true))][1],0,7);
        $this->month[6] = key(array_slice( $this->time, -1, 1, true)) .' or over';

    }

    public function registerTitle() {
        return $this->_reportTitle;
    }

    public function compileResults() {

        $filter = $this->_indata['filterData'];

      /*  if ($this->_group == '' && $filter['name'] == '' && $filter['phone'] == '' && $filter['customerId'] == '') {
            $empty = true;
            $this->data = [];
        } else {
            $empty = false;
        }*/

      //  if (!$empty) {

            //select('Customer.customerId','customer.phone_1','account_tel','account_fax','account_contact','customer.address_chi','customerName_chi','invoiceDate','amount','paid','invoiceId','customerRef','invoiceStatus','customer_groups.name')

            $invoices = Invoice::leftJoin('Customer', function($join) {
                        $join->on('Customer.customerId', '=', 'Invoice.customerId');
                    })->leftJoin('customer_groups', function($join) {
                        $join->on('customer_groups.id', '=', 'Customer.customer_group_id');
                    })->where('Invoice.deliveryDate', '<=', $this->_date2);

            //->whereBetween('Invoice.deliveryDate', [$this->_date1,$this->_date2]);

            if ($this->_group != '')
                $invoices->where('customer_groups.name', 'LIKE', $this->_group . '%');

            if ($filter['name'] != '' || $filter['phone'] != '' || $filter['customerId'] != '') {
                $invoices->where(function ($query) use ($filter) {
                    $query
                            ->where('customerName_chi', 'LIKE', $filter['name'] . '%')
                            ->where('Customer.phone_1', 'LIKE', $filter['phone'] . '%')
                            ->where('Customer.customerId', 'LIKE', $filter['customerId'] . '%');
                });
            }

            $invoices = $invoices->where('paymentTerms', 2)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->OrderBy('invoice.customerId', 'asc')->orderBy('deliveryDate')->get();

            // $queries = DB::getQueryLog();
            //  $last_query = end($queries);
            //  pd($queries);

            foreach ($invoices as $invoice) {

                if(!isset($acc[$invoice->customerId]))
                    $acc[$invoice->customerId] = 0;

                if (!isset($this->_acc[$invoice->customerId]))
                    $this->_acc[$invoice->customerId] = 0;

                if ($invoice->deliveryDate < $this->_date1) {
                    $this->_acc[$invoice->customerId] += $invoice->realAmount - ($invoice->paid + $invoice->discount_taken);
                } elseif ($invoice->deliveryDate >= $this->_date1) {

                    $customerId = $invoice->customerId;

                    $this->_unPaid[$customerId]['customer'] = [
                        'customerId' => $customerId,
                        'customerName' => $invoice->customerName_chi,
                        'customerTel' => $invoice['client']->phone_1,
                        'customerAddress' => $invoice->address_chi,
                        'account_tel' => $invoice['client']->account_tel,
                        'account_fax' => $invoice['client']->account_fax,
                        'account_contact' => $invoice['client']->account_contact,
                    ];
                    $acc[$customerId] += $invoice->amount;

                    $this->_unPaid[$customerId]['breakdown'][] = [
                        'invoiceDate' => $invoice->invoiceDate,
                        'invoice' => $invoice->invoiceId,
                        'customerRef' => $invoice->customerRef,
                        'invoiceAmount' => ($invoice->invoiceStatus == '98') ? 0 : $invoice->amount,
                        'paid' => ($invoice->invoiceStatus == '98') ? $invoice->amount*-1 : $invoice->paid,
                        'accInvoiceAmount' => $acc[$customerId],
                        'accumulator' => $this->_acc[$customerId] += ($invoice->amount - ($invoice->paid + $invoice->discount_taken)),
                    ];
                }

                $store[]=$invoice->customerId;
            }

                $this->cusomterList = array_unique($store);

        foreach ($this->cusomterList as $client) {
            foreach ($this->time as $k => $v) {
                $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('paymentTerms', 2)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.customerId', $client)->OrderBy('deliveryDate')->get();
                foreach ($data[$k] as $invoice) {
                    $customerId = $invoice->customerId;
                    $this->_monthly1[$k][$customerId][] = [
                        'accumulator' => (isset($this->_monthly1[$k][$customerId]) ? end($this->_monthly1[$k][$customerId])['accumulator'] : 0) + $invoice->realAmount - ($invoice->paid + $invoice->discount_taken)
                    ];
                }
            }
        }

      //  pd($this->_monthly);

            $this->data = $this->_unPaid;
       // }
        return $this->data;
    }

    public function registerFilter() {
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
        foreach ($zones as $zone) {
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

            [
                'id' => 'submit',
                'type' => 'submit',
                'label' => '提交',
            ],
                /*  [
                  'id' => 'year',
                  'type' => 'single-dropdown',
                  'label' => '年份',
                  'model' => 'year',
                  'optionList' => [date("Y")-1 => date("Y")-1, date("Y") => date("Y"), date("Y")+1 => date("Y")+1],
                  'defaultValue' => date("Y"),
                  ], */
        ];

        return $filterSetting;
    }

    public function beforeCompilingResults() {
        // executes codes before compiling results function is executed
    }

    public function afterCompilingResults() {
        // executes codes after compiling results function is executed
    }

    public function registerDownload() {
        $downloadSetting = [
            [
                'type' => 'pdf',
                'name' => '列印 PDF 版本',
                'warning' => '匯出PDF後不能修改訂單',
            ],
            [
                'type' => 'excel1',
                'name' => '匯出 EXCEL 版本',
                'warning' => false,
            ],
            [
                'type' => 'csv',
                'name' => '匯出帳齡摘要 (PDF)',
                'warning' => false,
            ],
            [
                'type' => 'excel',
                'name' => '匯出帳齡摘要 (EXCEL)',
                'warning' => false,
            ],
        ];

        return $downloadSetting;
    }

    public function outputExcel1()
    {
       foreach ($this->data as $client) {
            foreach ($this->time as $k => $v) {
                $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('paymentTerms', 2)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.customerId', $client['customer']['customerId'])->OrderBy('deliveryDate')->get();
                foreach ($data[$k] as $invoice) {
                    $customerId = $invoice->customerId;
                    $this->_monthly[$k][$customerId][] = [
                        'accumulator' => (isset($this->_monthly[$k][$customerId]) ? end($this->_monthly[$k][$customerId])['accumulator'] : 0) + $invoice->realAmount - ($invoice->paid + $invoice->discount_taken)
                    ];
                }
            }
        }

        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';


        $objPHPExcel = new PHPExcel();

        $j=0;
        foreach ($this->data as $client) {
            $customerId = $client['customer']['customerId'];
            $i=6;
            $objPHPExcel->createSheet($j);
            $objPHPExcel->setActiveSheetIndex($j);
            $objWorkSheet = $objPHPExcel->getActiveSheet();

            $objWorkSheet->setCellValue('A1', "客戶名稱");
            $objWorkSheet->setCellValue('B1', $client['customer']['customerName']);

            $objWorkSheet->setCellValue('A2', "客戶地址");
            $objWorkSheet->setCellValue('B2', $client['customer']['customerAddress']);

            $objWorkSheet->setCellValue('A3', "由日期");
            $objWorkSheet->setCellValue('B3', date('Y-m-d',$this->_date1));

            $objWorkSheet->setCellValue('A4', "至日期");
            $objWorkSheet->setCellValue('B4', date('Y-m-d',$this->_date2));

            $objWorkSheet->setCellValue('A' . $i, "發票日期");
            $objWorkSheet->setCellValue('B' . $i, "發票編號");
            $objWorkSheet->setCellValue('C' . $i, "借方");
            $objWorkSheet->setCellValue('D' . $i, "貸方");
            $objWorkSheet->setCellValue('E' . $i, "未清付金額");

            $i++;

            foreach($client['breakdown'] as $k => $v){

                $ref = ($v['customerRef'] != '') ? $v['customerRef'] : '';
                $ref = ($ref != '') ? ' (' . $ref . ')' : '';

                $objWorkSheet->setCellValue('A' . $i, date('Y-m-d', $v['invoiceDate']));
                $objWorkSheet->setCellValue('B' . $i, $v['invoice'] . $ref);
                $objWorkSheet->setCellValue('C' . $i, $v['invoiceAmount']);
                $objWorkSheet->setCellValue('D' . $i, $v['paid']);
                $objWorkSheet->setCellValue('E' . $i, $v['accumulator']);

                $i++;
            }

            $t = $i-1;
            $objWorkSheet->setCellValue('C' . $i,"=SUM(C6:C".$t.")");
            $objWorkSheet->setCellValue('D' . $i,"=SUM(D6:D".$t.")");
            $objWorkSheet->setCellValue('E' . $i,$v['accumulator']);

            $objPHPExcel->getActiveSheet()
                ->getStyle('C6:E'.$i)
                ->getNumberFormat()
                ->setFormatCode(
                    '$#,##0.00;[Red]$#,##0.00'
                );

            $i=$i+2;
            $objWorkSheet->setCellValue('A' . $i, 'The outstanding balance is aged by invoice date as '.date('Y-m-d',$this->_date2).' below:');
            $objWorkSheet->mergeCells('A'.$i.':F'.$i);

            $i++;
            $objWorkSheet->setCellValue('A' . $i,  $this->month[0]);
            $objWorkSheet->setCellValue('B' . $i,  $this->month[1]);
            $objWorkSheet->setCellValue('C' . $i,  $this->month[2]);
            $objWorkSheet->setCellValue('D' . $i,  $this->month[3]);

            $i++;
            $objWorkSheet->setCellValue('A' . $i,'$' . number_format(isset($this->_monthly[$this->month[0]][$customerId]) ? end($this->_monthly[$this->month[0]][$customerId])['accumulator'] : 0, 2, '.', ',') );
            $objWorkSheet->setCellValue('B' . $i,'$' . number_format(isset($this->_monthly[$this->month[1]][$customerId]) ? end($this->_monthly[$this->month[1]][$customerId])['accumulator'] : 0, 2, '.', ',') );
            $objWorkSheet->setCellValue('C' . $i,'$' . number_format(isset($this->_monthly[$this->month[2]][$customerId]) ? end($this->_monthly[$this->month[2]][$customerId])['accumulator'] : 0, 2, '.', ',') );
            $objWorkSheet->setCellValue('D' . $i,'$' . number_format(isset($this->_monthly[$this->month[3]][$customerId]) ? end($this->_monthly[$this->month[3]][$customerId])['accumulator'] : 0, 2, '.', ',') );

            $i++;
            $objWorkSheet->setCellValue('A' . $i, 'Payment received after statement date not included' );
            $objWorkSheet->mergeCells('A'.$i.':F'.$i);

            $j++;

            foreach (range('A', $objWorkSheet->getHighestDataColumn()) as $col) {
                // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
                $objWorkSheet->getColumnDimension($col)->setAutoSize('15');
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . "monthly report" . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');

        //p($this->data);
        //pd($this->_monthly);
    }

    //aging excel
    public function outputExcel() {
        $time_interval = [['0', '0'], ['1', '1'], ['2', '2'], ['5', '3'], ['11', '6'], ['120', '12']];
        $dateRange = ['F', 'G', 'H', 'I', 'J', 'K'];

        $first = true;

        foreach ($time_interval as $v) {
            if ($first) {
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime("-" . $v[0] . " month"));
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][1] = date("Y-m-d", strtotime("-" . $v[1] . " month"));
                $first = false;
            } else {
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime("-" . $v[0] . " month"));
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][1] = date("Y-m-t", strtotime("-" . $v[1] . " month"));
            }
        }

        $month[0] = key(array_slice($time, -6, 1, true));
        $month[1] = key(array_slice($time, -5, 1, true));
        $month[2] = key(array_slice($time, -4, 1, true));
        $month[3] = key(array_slice($time, -3, 1, true));
        $month[4] = key(array_slice($time, -2, 1, true));
        $month[5] = key(array_slice($time, -1, 1, true));
        
        
        foreach ($this->data as $i => $v) {
            $storeDate[$i] = $v['customer']; 
        }

        if(isset($storeDate))
        {
        $total = 0;
        foreach ($storeDate as $kk => $client) {

            //for ($i = $this->_reportMonth; $i > 0; $i--) {
            $data = [];

            foreach ($time as $k => $v) {
                $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('paymentTerms', 2)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.customerId', $kk)->OrderBy('deliveryDate')->get();  

                foreach ($data[$k] as $invoice) {
                    $customerId = $invoice->customerId;
                    $customerName = $invoice->customerName;
                    $deliveryZone = $invoice->zoneId;

                    if (!isset($this->_monthly[$k]['byCustomer'][$customerId]))
                        $this->_monthly[$k]['byCustomer'][$customerId] = 0;

                    $this->_monthly[$k]['byCustomer'][$customerId] += ($invoice->realAmount - ($invoice->paid + $invoice->discount_taken));
                    $total += ($invoice->realAmount - ($invoice->paid + $invoice->discount_taken));
                    $this->_monthly['id'][$customerId] = $customerId;
                    $this->_monthly['name'][$customerId] = $customerName;
                    $this->_monthly['diliveryZone'][$customerId] = $deliveryZone;
                }
            }
        }
        
     //   pd($this->_monthly);

        $i = 5;
        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';


        $objPHPExcel = new PHPExcel();
        $this->generateExcelHeader($objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "Customer");  
        
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, "District");
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $month[0]);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $month[1]);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $month[2]);
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $month[3]);
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $month[4]);
        $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $month[5]);
        

        $j = $i + 1;
        $storeRow = $j;
        $total = 0;
        $a = $j;
        $b = $j;
        $c = $j;
        $d = $j;
        
      //  pd($this->_monthly);
        
        foreach ($this->_monthly as $ks => $vs) {
                
                foreach ($vs as $is => $vvs) {
                    if($ks == "id")
                    {
                        $objPHPExcel->getActiveSheet()->setCellValue('A' . $a, $vvs);
                        $a++;
                    }else if($ks == "name")
                    {
                        $objPHPExcel->getActiveSheet()->setCellValue('B' . $b, $vvs);
                        $b++;
                    }else if($ks == "diliveryZone")
                    {
                        $objPHPExcel->getActiveSheet()->setCellValue('E' . $d, $vvs);
                        $d++;
                    }else
                    {
                        $c = $j;
                        foreach ($vvs as $g => $k) {
                          //  $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, $g);
                            
                            for ($yy = 0; $yy < count($dateRange); $yy++) {
                                if ($ks == $month[$yy]) {
                                    $objPHPExcel->getActiveSheet()->setCellValue($dateRange[$yy] . $c, $vs['byCustomer'][$g]);
                                    $c++;
                                }
                            }
                        }
                    }
                }
            // $j++;
        }
        for($loopNum = 0;$loopNum<count($storeDate);$loopNum++)
        {
              $objPHPExcel->getActiveSheet()->setCellValue('L' . $j,"=SUM(F".$j.":K".$j.")");
              $j++;      
        }
         $j++; 
         
         
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, "Total all:(HK$):");
        
        $hh = $j -2;

        $styleArray = array(
            'font' => array(
                'underline' => PHPExcel_Style_Font::UNDERLINE_DOUBLE
            )
        );
        $dateRange[6] = "L";
        for($count = 0;$count < count($dateRange);$count++)
        {
            $objPHPExcel->getActiveSheet()->setCellValue($dateRange[$count].  $j,"=SUM(".$dateRange[$count].$storeRow.":".$dateRange[$count].$hh.")");
             $objPHPExcel->getActiveSheet()->getStyle($dateRange[$count].  $j)->applyFromArray($styleArray); 
        }

        unset($styleArray);
for($start = 0;$start < count($dateRange);$start++)
        {
            $objPHPExcel->getActiveSheet()
            ->getStyle($dateRange[$start].$storeRow.':'.$dateRange[$start].$j)
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            ); 
            $objPHPExcel->getActiveSheet()->getColumnDimension($dateRange[$start])->setWidth(15);
        }
       
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . "account sales summary" . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
         }
    }
    //aging excel

    public function outputPreview() {


        return View::make('reports/MonthlyCreditSummary')->with('data', $this->data)->with('month',$this->month)->with('monthly',$this->_monthly1)->with('date',date('Y-m-d', $this->_date2))->render();
    }

    /* public function outputCsv(){

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
      $data[$i] = Invoice::whereBetween('deliveryDate', $times[$i])->where('paymentTerms', 2)->where('amount','!=','paid')->where('Invoice.customerId', $client['customer']['customerId'])->OrderBy('deliveryDate')->get();

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


      } */

    public function agingHeader($pdf) {

        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        $pdf->SetFont('chi', '', 14);
        $pdf->setXY(10, 2);
        $pdf->Cell(0, 10, "炳記行貿易國際有限公司", 0, 1, "C");
        $pdf->setXY(10, 10);
        $pdf->SetFont('chi', 'U', 12);
        $pdf->Cell(0, 10, '帳齡分析搞要(應收)', 0, 1, "C");

        $y = 10;
        $pdf->SetFont('chi', '', 9);
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 10, '載至日期 : ' . date('Y-m-d', $this->_date2), 0, 1, "L");

        $pdf->setXY(10, $y + 6);
        $pdf->Cell(0, 10, '客戶組 : ' . $this->_group, 0, 1, "L");
    }

    //aging pdf
    public function outputCsv() {



      /*  $time_interval = [['0', '0'], ['1', '1'], ['2', '2'],['3','3'],['5', '4'], ['11', '6'], ['120', '12']];


        $first = true;

        foreach ($time_interval as $v) {
            if ($first) {
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime("-" . $v[0] . " month"));
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][1] = date("Y-m-d", strtotime("-" . $v[1] . " month"));
                $first = false;
            } else {
                $time[date("Y-m", strtotime("last day of -".$v[1]." month"))][0] = date("Y-m-01", strtotime("last day of -".$v[0]." month"));
                $time[date("Y-m", strtotime("last day of -".$v[1]." month"))][1] = date("Y-m-t", strtotime("last day of -".$v[1]." month"));
            }
        }*/

        $month[0] = key(array_slice($this->time, -7, 1, true));
        $month[1] = key(array_slice($this->time, -6, 1, true));
        $month[2] = key(array_slice($this->time, -5, 1, true));
        $month[3] = key(array_slice($this->time, -4, 1, true));
        $month[4] = key(array_slice($this->time, -3, 1, true));
        $month[5] = key(array_slice($this->time, -2, 1, true));
        $month[6] = key(array_slice($this->time, -1, 1, true));


        $this->_reportMonth = date("n", $this->_date2);



        /* for($month = 1; $month <= 12; $month++) {
          $first_minute = mktime(0, 0, 0, $month, 1,date('Y'));
          $last_minute = mktime(23, 59, 0, $month, date('t', $first_minute),date('Y'));

          if($this->_reportMonth==$month)
          $last_minute =  $this->_date2;
          $times[$month] = array($first_minute, $last_minute,date('Y'),$month,date('Y'),$month);
          } */


        /* for($month = 1; $month <= 12; $month++) {
          $first_minute = mktime(0, 0, 0, $month, 1,date('Y'));
          $last_minute = mktime(23, 59, 0, $month, date('t', $first_minute),date('Y'));

          if($this->_reportMonth==$month)
          $last_minute =  $this->_date2;
          $times[$month] = array($first_minute, $last_minute);
          } */

        $pdf = new PDF();
        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);



        foreach ($this->data as $kk => $client) {

            //for ($i = $this->_reportMonth; $i > 0; $i--) {

            $data = [];

            foreach ($this->time as $k => $v) {
                $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('paymentTerms', 2)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.customerId', $client['customer']['customerId'])->OrderBy('deliveryDate')->get();

                foreach ($data[$k] as $invoice) {
                    $customerId = $invoice->customerId;

                    if (!isset($this->_monthly[$k]['byCustomer'][$customerId]))
                        $this->_monthly[$k]['byCustomer'][$customerId] = 0;

                    $this->_monthly[$k]['byCustomer'][$customerId] += ($invoice->realAmount - ($invoice->paid + $invoice->discount_taken));
                }
            }
        }
        foreach ($this->time as $k => $v) {
            if (!isset($this->_monthly[$k]['total']))
                $this->_monthly[$k]['total'] = 0;
            if (isset($this->_monthly[$k]['byCustomer']))
                foreach ($this->_monthly[$k]['byCustomer'] as $v) {
                    $this->_monthly[$k]['total'] += $v;
                }
        }

        $bd = array_chunk($this->data, 17, true);

        $i = 1;
        $j = 1;
        $own_total = 0;

        foreach ($bd as $k => $g) {


            $pdf->AddPage('L');
            $this->agingHeader($pdf);
            $y = 30;

            $pdf->SetFont('chi', '', 8);
            $pdf->setXY(10, $y);
            $pdf->Cell(0, 0, "客户", 0, 0, "L");

            $pdf->setXY(100, $y);
            $pdf->Cell(0, 0, "結餘", 0, 0, "L");

            $pdf->setXY(130, $y);
            $pdf->Cell(0, 0, $month[0], 0, 0, "L");

            $pdf->setXY(155, $y);
            $pdf->Cell(0, 0, $month[1], 0, 0, "L");

            $pdf->setXY(180, $y);
            $pdf->Cell(0, 0, $month[2], 0, 0, "L");

            $pdf->setXY(205, $y);
            $pdf->Cell(0, 0, $month[3], 0, 0, "L");

            $pdf->setXY(230, $y);
            $pdf->Cell(0, 0, $month[4], 0, 0, "L");

            $pdf->setXY(255, $y);
            $pdf->Cell(0, 0, $month[5], 0, 0, "L");

            $pdf->Line(10, $y + 2, 285, $y + 2);


            $pdf->setXY(280, 10);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i, count($bd)), 0, 0, "R");

            $i++;


            foreach ($g as $kk => $client) {

                $amount = 0;
                $paid = 0;
                $accu = 0;

                foreach ($client['breakdown'] as $k => $v) {

                    $amount += $v['invoiceAmount'];
                    $paid += $v['paid'];
                    $accu = $v['accumulator'];
                }

                $own_total += $accu;

                $y += 4;

                $pdf->setXY(10, $y);
                $pdf->Cell(0, 0, $client['customer']['customerId'], 0, 0, "L");

                $pdf->setXY(30, $y);
                $pdf->Cell(0, 0, $client['customer']['customerName'], 0, 0, "L");

                $pdf->setXY(100, $y);
                $pdf->Cell(0, 0, '$' . number_format($accu, 2, '.', ','), 0, 0, "L");

                $pdf->setXY(130, $y);

                if (isset($this->_monthly[$month[0]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[0]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(155, $y);
                if (isset($this->_monthly[$month[1]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[1]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';
                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(180, $y);
                if (isset($this->_monthly[$month[2]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[2]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';
                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(205, $y);
                if (isset($this->_monthly[$month[3]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[3]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';
                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(230, $y);
                if (isset($this->_monthly[$month[4]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[4]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';
                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(255, $y);
                if (isset($this->_monthly[$month[5]]['byCustomer'][$client['customer']['customerId']]))
                    $numsum = '$' . number_format($this->_monthly[$month[5]]['byCustomer'][$client['customer']['customerId']], 1, '.', ',');
                else
                    $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->Line(10, $y + 7, 285, $y + 7);

                $y += 5;
            }


            if ($j == count($bd)) {

                $y += 5;
                $pdf->setXY(70, $y);
                $pdf->Cell(0, 0, '合共總額:', 0, 0, "L");

                $pdf->setXY(100, $y);
                $pdf->Cell(0, 0, '$' . number_format($own_total, 2, '.', ','), 0, 0, "L");

                $pdf->setXY(130, $y);
                $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[key(array_slice($this->time, -7, 1, true))]['total']) ? $this->_monthly[key(array_slice($this->time, -7, 1, true))]['total'] : 0, 1, '.', ','), 0, 0, "L");

                $pdf->setXY(155, $y);
                $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[key(array_slice($this->time, -6, 1, true))]['total']) ? $this->_monthly[key(array_slice($this->time, -6, 1, true))]['total'] : 0, 1, '.', ','), 0, 0, "L");

                $pdf->setXY(180, $y);
                $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[key(array_slice($this->time, -5, 1, true))]['total']) ? $this->_monthly[key(array_slice($this->time, -5, 1, true))]['total'] : 0, 1, '.', ','), 0, 0, "L");

                $pdf->setXY(205, $y);
                if (isset($this->_monthly[key(array_slice($this->time, -4, 1, true))]['total']))
                    if ($this->_monthly[key(array_slice($this->time, -4, 1, true))]['total'] != 0)
                        $numsum = '$' . number_format($this->_monthly[key(array_slice($this->time, -4, 1, true))]['total'], 1, '.', ',');
                    else
                        $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");

                $pdf->setXY(230, $y);
                if (isset($this->_monthly[key(array_slice($this->time, -3, 1, true))]['total']))
                    if ($this->_monthly[key(array_slice($this->time, -3, 1, true))]['total'] != 0)
                        $numsum = '$' . number_format($this->_monthly[key(array_slice($this->time, -3, 1, true))]['total'], 1, '.', ',');
                    else
                        $numsum = '';

                $pdf->setXY(255, $y);
                if (isset($this->_monthly[key(array_slice($this->time, -2, 1, true))]['total']))
                    if ($this->_monthly[key(array_slice($this->time, -2, 1, true))]['total'] != 0)
                        $numsum = '$' . number_format($this->_monthly[key(array_slice($this->time, -2, 1, true))]['total'], 1, '.', ',');
                    else
                        $numsum = '';

                $pdf->Cell(0, 0, $numsum, 0, 0, "L");
            }

            $j++;
        }

        $pdf->Output('', 'I');
        // pd( $this->_monthly);
    }
    //aging pdf

      public function generateExcelHeader($excel) {
        $today = date("Y-m-d");
        $excel->getActiveSheet()->setCellValue('A1', 'PING KEE HONG TRADING INTERNATIONAL LTD.');
        $excel->getActiveSheet()->mergeCells('A1:F1');
        $excel->getActiveSheet()->setCellValue('A2', 'Accounts Receivable Aging Report(Cash sales)');
        $excel->getActiveSheet()->mergeCells('A2:F2');
        $excel->getActiveSheet()->setCellValue('A3', 'As at[' . $today . "]");
        $excel->getActiveSheet()->mergeCells('A3:D3');
    }

    public function generateHeader($pdf) {

        $pdf->SetFont('chi', '', 18);
        $pdf->setXY(45, 10);
        $pdf->Cell(0, 0, "炳 記 行 貿 易 國 際 有 限 公 司", 0, 1, "L");

        $pdf->SetFont('chi', '', 18);
        $pdf->setXY(45, 18);
        $pdf->Cell(0, 0, "PING KEE HONG TRADING INTERNATIONAL LTD.", 0, 1, "L");

        $pdf->SetFont('chi', '', 9);
        $pdf->setXY(45, 25);
        $pdf->Cell(0, 0, "Flat B, 9/F., Wang Cheung Industrial Building, 6 Tsing Yeung St., Tuen Mun, N.T. Hong Kong.", 0, 1, "L");

        $pdf->SetFont('chi', '', 9);
        $pdf->setXY(45, 30);
        $pdf->Cell(0, 0, "TEL:24552266    FAX:24552449", 0, 1, "L");

        $pdf->SetFont('chi', 'U', 14);
        $pdf->setXY(0, 40);
        $pdf->Cell(0, 0, $this->_reportTitle, 0, 0, "C");


        $pdf->SetFont('chi', '', 9);
        $pdf->Code128(10, $pdf->h - 15, $this->_uniqueid, 50, 10);
        $pdf->setXY(0, 5);
        $pdf->Cell(0, 0, sprintf("報告編號: %s", $this->_uniqueid), 0, 0, "R");

        $image = public_path('logo.jpg');
        $pdf->Cell(40, 40, $pdf->Image($image, 15, 5, 28), 0, 0, 'L', false);
    }

    // monthly report pdf
    public function outputPDF() {

        $pdf = new PDF();
        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);

        foreach ($this->data as $client) {
            foreach ($this->time as $k => $v) {
                $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('paymentTerms', 2)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.customerId', $client['customer']['customerId'])->OrderBy('deliveryDate')->get();
                foreach ($data[$k] as $invoice) {
                    $customerId = $invoice->customerId;
                    $this->_monthly[$k][$customerId][] = [
                        'accumulator' => (isset($this->_monthly[$k][$customerId]) ? end($this->_monthly[$k][$customerId])['accumulator'] : 0) + $invoice->realAmount - ($invoice->paid + $invoice->discount_taken)
                    ];
                }
            }

            $pdf->AddPage();
            $this->generateHeader($pdf);

            $i=0;
            $y = 50;

            $pdf->SetFont('chi', '', 10);
            $pdf->setXY(15, $y);
            $pdf->Cell(0, 0, "M/S", 0, 0, "L");

            $pdf->setXY(30, $y);
            $pdf->Cell(0, 0, sprintf("%s(%s)", $client['customer']['customerName'], $client['customer']['customerId']), 0, 0, "L");

            $pdf->setXY(30, $y + 5);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['customerAddress']), 0, 0, "L");

            $pdf->setXY(30, $y + 20);
            $pdf->Cell(0, 0, "Tel:", 0, 0, "L");

            $pdf->setXY(40, $y + 20);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['account_tel']), 0, 0, "L");

            $pdf->setXY(80, $y + 20);
            $pdf->Cell(0, 0, "Fax:", 0, 0, "L");

            $pdf->setXY(90, $y + 20);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['account_fax']), 0, 0, "L");

            $pdf->setXY(30, $y + 14);
            $pdf->Cell(0, 0, "Attn:", 0, 0, "L");

            $pdf->setXY(50, $y + 14);
            $pdf->Cell(0, 0, sprintf("%s", $client['customer']['account_contact']), 0, 0, "L");


            $pdf->setXY(130, $y);
            $pdf->Cell(0, 0, '列印日期:', 0, 0, "L");

            $pdf->setXY(155, $y);
            $pdf->Cell(0, 0, date('Y-m-d', time()), 0, 0, "L");

            $pdf->setXY(130, $y + 5);
            $pdf->Cell(0, 0, '由日期:', 0, 0, "L");

            $pdf->setXY(155, $y + 5);
            //   $pdf->Cell(0, 0, date('Y-m-d', $client['breakdown'][0]['invoiceDate']), 0, 0, "L");
            $pdf->Cell(0, 0, date('Y-m-d', $this->_date1), 0, 0, "L");

            $pdf->setXY(130, $y + 10);
            $pdf->Cell(0, 0, '至日期:', 0, 0, "L");

            $pdf->setXY(155, $y + 10);
            // $pdf->Cell(0, 0, date('Y-m-d', $client['breakdown'][sizeof($client['breakdown']) - 1]['invoiceDate']), 0, 0, "L");
            $pdf->Cell(0, 0, date('Y-m-d', $this->_date2), 0, 0, "L");

            $y = 60;

            $pdf->SetFont('chi', '', 16);
            $pdf->setXY(130, $y + 8);
            $pdf->Cell(0, 0, date('Y年m月', $this->_date1), 0, 0, "L");

            $pdf->SetFont('chi', '', 10);
            $pdf->setXY(10, $y + 16);
            $pdf->Cell(0, 0, "發票日期", 0, 0, "L");

            $pdf->setXY(40, $y + 16);
            $pdf->Cell(0, 0, "發票編號", 0, 0, "L");

            $pdf->setXY(115, $y + 16);
            $pdf->Cell(0, 0, "借方", 0, 0, "L");

            $pdf->setXY(145, $y + 16);
            $pdf->Cell(0, 0, "貸方", 0, 0, "L");

            $pdf->setXY(170, $y + 16);
            $pdf->Cell(0, 0, "未清付金額", 0, 0, "L");


            $pdf->setXY(500, $pdf->h - 30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i + 1, count($this->data)), 0, 0, "R");



            $pdf->Line(10, $y + 20, 190, $y + 20);

            $y += 25;
            $amount = 0;
            $paid = 0;

            $bd = array_chunk($client['breakdown'], 29, true);

            foreach ($bd as $k => $g) {
                $count = 0;
                if ($k > 0) {
                    $pdf->AddPage();
                    $y = 20;
                }

                foreach ($g as $v) {

                    Invoice::where('invoiceId',$v['invoice'])->update(['lock'=>1]);

                    $pdf->SetFont('chi', '', 10);

                    $pdf->setXY(10, $y);
                    $pdf->Cell(0, 0, date('Y-m-d', $v['invoiceDate']), 0, 0, "L");

                    $ref = ($v['customerRef'] != '') ? $v['customerRef'] : '';
                    $ref = ($ref != '') ? ' (' . $ref . ')' : '';

                    if (substr($ref, 0, 2) == "CN" || substr($ref, 0, 2) == "DN") {
                        $v['invoice'] = '';
                    }

                    $pdf->setXY(40, $y);
                    $pdf->Cell(0, 0, $v['invoice'] . $ref, 0, 0, "L");

                    if ($v['invoiceAmount'] != 0)
                        $acm = "$" . number_format($v['invoiceAmount'], 2, '.', ',');
                    else
                        $acm = '';

                    if ($v['paid'] != 0)
                        $apaid = "$" . number_format($v['paid'], 2, '.', ',');
                    else
                        $apaid = '';


                    $pdf->setXY(115, $y);
                    $pdf->Cell(10, 0, $acm, 0, 0, "R");

                    $pdf->setXY(145, $y);
                    $pdf->Cell(10, 0, $apaid, 0, 0, "R");

                    $pdf->setXY(170, $y);
                    $pdf->Cell(20, 0, "$" . number_format($v['accumulator'], 2, '.', ','), 0, 0, "R");

                    $amount += $v['invoiceAmount'];
                    $paid += $v['paid'];
                    $accu = $v['accumulator'];

                    $y += 6;
                    $count++;
                }
            }

            if ($count > 22) {
                $pdf->AddPage();
                $y = 20;
            }
            $pdf->Line(10, $y, 190, $y);

            $pdf->setXY(35, $y + 6);
            $pdf->Cell(0, 0, '未清付發票總金額(HKD):', 0, 0, "L");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->setXY(105, $y + 6);
            $pdf->Cell(10, 0, "$" . number_format($amount, 2, '.', ','), 0, 0, "R");

            //   $pdf->setXY(140, $y + 6);
            //  $pdf->Cell(10, 0,  "$" . number_format($paid, 2, '.', ','), 0, 0, "R");

            $pdf->setXY(170, $y + 6);
            $pdf->Cell(20, 0, "$" . number_format($accu, 2, '.', ','), 0, 0, "R");

            $pdf->Line(10, $y + 12, 190, $y + 12);

            $pdf->SetFont('Arial', '', 12);
            $pdf->setXY(10, $y + 18);
            $pdf->Cell(0, 0, 'The outstanding balance is aged by invoice date as ' . date('Y-m-d', $this->_date2) . ' below:', 0, 0, "L");

            $pdf->SetFont('Arial', 'U', 12);
            $pdf->setXY(10, $y + 24);
            $pdf->Cell(0, 0, $this->month[0], 0, 0, "L");

            $pdf->setXY(40, $y + 24);
            $pdf->Cell(0, 0, $this->month[1], 0, 0, "L");

            $pdf->setXY(70, $y + 24);
            $pdf->Cell(0, 0, $this->month[2], 0, 0, "L");

            $pdf->setXY(100, $y + 24);
            $pdf->Cell(0, 0, $this->month[3], 0, 0, "L");

            $pdf->setXY(130, $y + 24);
            $pdf->Cell(0, 0, $this->month[4], 0, 0, "L");

            $pdf->SetFont('Arial', '', 12);
            $pdf->setXY(10, $y + 30);
            $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[$this->month[0]][$customerId]) ? end($this->_monthly[$this->month[0]][$customerId])['accumulator'] : 0, 2, '.', ','), 0, 0, "L");

            $pdf->setXY(40, $y + 30);
            $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[$this->month[1]][$customerId]) ? end($this->_monthly[$this->month[1]][$customerId])['accumulator'] : 0, 2, '.', ','), 0, 0, "L");
            // $pdf->Cell(0, 0, '$' . number_format(0, 2, '.', ','), 0, 0, "L");

            $pdf->setXY(70, $y + 30);
            $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[$this->month[2]][$customerId]) ? end($this->_monthly[$this->month[2]][$customerId])['accumulator'] : 0, 2, '.', ','), 0, 0, "L");
            //  $pdf->Cell(0, 0, '$' . number_format(0, 2, '.', ','), 0, 0, "L");

            $pdf->setXY(100, $y + 30);
            $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[$this->month[3]][$customerId]) ? end($this->_monthly[$this->month[3]][$customerId])['accumulator'] : 0, 2, '.', ','), 0, 0, "L");
            // $pdf->Cell(0, 0, '$' .number_format(0, 2, '.', ','), 0, 0, "L");

            $pdf->setXY(130, $y + 30);
            $pdf->Cell(0, 0, '$' . number_format(isset($this->_monthly[$this->month[4]][$customerId]) ? end($this->_monthly[$this->month[4]][$customerId])['accumulator'] : 0, 2, '.', ','), 0, 0, "L");
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
    // end of monthly report pdf
}
