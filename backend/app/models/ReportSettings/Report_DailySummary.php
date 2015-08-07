<?php


class Report_DailySummary
{

    private $_reportTitle = "";
    private $_date = "";
    private $_date1 = "";
    private $_zone = "";
    private $_invoices = [];
    private $_uniqueid = "";
    private $_output = '';
    private $_vansell = '';

    private $_sumcredit = 0;
    private $_sumcod = 0;
    private $_countcredit = 0;
    private $_countcod = 0;
    private $_countcodreturn = 0;
    private $_countcodreplace = 0;


    public function __construct($indata)
    {
        ini_set("memory_limit", "-1");

        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;




        $this->_output = $indata['output'];
        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_date1 = (isset($indata['filterData']['deliveryDate2']) ? strtotime($indata['filterData']['deliveryDate2']) : strtotime($this->_date));

        $permittedZone = explode(',', Auth::user()->temp_zone);
        $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        if (!in_array($this->_zone, $permittedZone)) {
            App::abort(401, "Unauthorized Zone");
        }

        $this->_shift = (isset($indata['filterData']['shift']) ? $indata['filterData']['shift']['value'] : '-1');


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

        $hi = Invoice::select('*')->whereIn('invoiceStatus', ['1', '2', '4', '11', '20', '21', '22', '23', '30', '98', '97', '96'])
            ->where('zoneId', $zone);

        if($this->_shift != '-1')
            $hi->where('shift',$this->_shift);

        if (Auth::user()->role[0]->id == 4)
            $hi->where('deliveryDate', $date);
        else
            $hi->whereBetween('deliveryDate', [$date, $this->_date1]);

          $hi->with('invoiceItem', 'products', 'client')
            ->chunk(5000, function ($invoicesQuery) {


                //  $this->_count = sizeof($invoicesQuery);
                // first of all process all products
                $productsQuery = array_pluck($invoicesQuery, 'products');


                foreach ($productsQuery as $productQuery) {
                    $productQuery = head($productQuery);

                    foreach ($productQuery as $pQ) {
                        $products[$pQ->productId] = $pQ;
                    }
                }

                // second process invoices
                foreach ($invoicesQuery as $invoiceQ) {


                    if ($invoiceQ->invoiceStatus == '98') {

                        if ($invoiceQ['client']->paymentTermId == 2 && $invoiceQ->paymentTerms == 2) {
                            $this->_sumcredit -= $invoiceQ->amount;
                            $this->_countcredit += 1;
                        } else {
                            $this->_sumcod -= $invoiceQ->amount;
                            $this->_countcodreturn += 1;
                        }


                        // second, separate 1F goods and 9F goods
                        foreach ($invoiceQ['invoiceItem'] as $item) {
                            // determin its product location
                            $productId = $item->productId;

                            $productDetail = $products[$productId];
                            $unit = $item->productQtyUnit;

                            $this->goods[$productId . '(退貨)'][$unit] = [
                                'productId' => $productId . '(退貨)',
                                'name' => $productDetail->productName_chi,
                                'productPrice' => $item->productPrice,
                                'unit' => $unit,
                                'unit_txt' => $item->productUnitName,
                                'counts' => ((isset($this->goods[$productId.'(退貨)'][$unit]) ? $this->goods[$productId.'(退貨)'][$unit]['counts'] : 0) - $item->productQty),
                            ];

                            //  pd($item);
                        }


                    } else {
                        $this->_invoices[] = $invoiceQ->invoiceId;

                        if ($invoiceQ['client']->paymentTermId == 2 && $invoiceQ->paymentTerms == 2) {
                            $this->_sumcredit += $invoiceQ->amount;
                            $this->_countcredit += 1;
                        } else {
                            $this->_sumcod += $invoiceQ->amount;
                            if ($invoiceQ->invoiceStatus == '96')
                                $this->_countcodreplace += 1;
                            else
                                $this->_countcod += 1;
                        }

                        foreach ($invoiceQ['invoiceItem'] as $item) {
                            // determin its product location
                            $productId = $item->productId;
                            $productDetail = $products[$productId];
                            $unit = $item->productQtyUnit;


                            if ($invoiceQ->invoiceStatus == '96') {
                                $this->goods[$productId . '(補貨)'][$unit] = [
                                    'productId' => $productId . '(補貨)',
                                    'name' => $productDetail->productName_chi,
                                    'productPrice' => $item->productPrice,
                                    'unit' => $unit,
                                    'unit_txt' => $item->productUnitName,
                                    'counts' => (isset($this->goods[$productId . '(補貨)'][$unit]) ? $this->goods[$productId . '(補貨)'][$unit]['counts'] : 0) + $item->productQty,
                                ];
                            }else if ($item->productPrice == 0) {
                                $this->goods[$productId . '(零元)'][$unit] = [
                                    'productId' => $productId . '(零元)',
                                    'name' => $productDetail->productName_chi,
                                    'productPrice' => $item->productPrice,
                                    'unit' => $unit,
                                    'unit_txt' => $item->productUnitName,
                                    'counts' => (isset($this->goods[$productId . '(零元)'][$unit]) ? $this->goods[$productId . '(零元)'][$unit]['counts'] : 0) + $item->productQty,
                                ];
                            }else
                                $this->goods[$productId][$unit] = [
                                    'productId' => $productId,
                                    'name' => $productDetail->productName_chi,
                                    'productPrice' => $item->productPrice,
                                    'unit' => $unit,
                                    'unit_txt' => $item->productUnitName,
                                    'counts' => (isset($this->goods[$productId][$unit]) ? $this->goods[$productId][$unit]['counts'] : 0) + $item->productQty,
                                ];


                        }


                    }
                    // first, store all invoices
                    //$invoiceId = $invoiceQ->invoiceId;
                    // $invoices[$invoiceId] = $invoiceQ;
                    // $client = $invoiceQ['client'];
                    // second, separate 1F goods and 9F goods
// . (isset($this->returnGoods[$productId][$unit]) ? " (-" . $this->returnGoods[$productId][$unit]['counts'] . ")" : '')


                }

            });


        if (count($this->goods) > 0)
            ksort($this->goods, SORT_STRING);

        $z = [];
        // $this->data = ;
        foreach ($this->goods as $v) {
            foreach ($v as $k) {
                $z[] = $k;
            }
        }
        if (count($z) > 0)
            $this->data['items'] = $z;
        else
            $this->data['items'] = [];
        //   pd($this->data['items']);
        // $this->data['returnitems'] =  isset($this->returnGoods)?$this->returnGoods:'';

//pd($this->data);

        $this->data['sumcredit'] = $this->_sumcredit;
        $this->data['sumcod'] = $this->_sumcod;
        $this->data['countcredit'] = $this->_countcredit;
        $this->data['countcod'] = $this->_countcod;
        $this->data['countcodreturn'] = $this->_countcodreturn;
        $this->data['countcodreplace'] = $this->_countcodreplace;
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
        foreach ($zones as $zone) {
            $availablezone[] = [
                'value' => $zone->zoneId,
                'label' => $zone->zoneName,
            ];
        }
        $ashift =[['value'=>'-1','label'=>'檢視全部'],['value'=>'1','label'=>'早班'],['value'=>'2','label'=>'晚班']];

        if (Auth::user()->role[0]->id == 4) {
            $ar = [
                'id' => 'deliveryDate',
                'type' => 'date-picker',
                'label' => '送貨日期',
                'model' => 'deliveryDate',
                'defaultValue' => date("Y-m-d", $this->_date),
            ];
        }else
        $ar =
            [
                'id' => 'deliveryDate',
                'type' => 'date-picker1',
                'label' => '送貨日期',
                'model' => 'deliveryDate',
                'id1' => 'deliveryDate2',
                'model1' => 'deliveryDate2',
            ];
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
            $ar


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
                'warning' => false,
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

        return View::make('reports/DailyReport')->with('data', $this->data)->render();

    }


    public function outputCsv(){

        $csv = 'Product Id,Product Name,Total Amount,Unit' . "\r\n";

        foreach ($this->data['items'] as $o) {
            $csv .= '"' . $o['productId'] . '",';
            $csv .= '"' . $o['name'] . '",';
            $csv .= '"' . $o['counts'] . '",';
            $csv .= '"' . $o['unit_txt'] . '",';
            $csv .= "\r\n";

        }
        $csv .= ',';
        $csv .= '現金總數 '.$this->data['countcod'] . " 單 "."$" .$this->data['sumcod'].',';
        $csv .= ',';
        $csv .= ',';
        $csv .= "\r\n";

        $num = $this->data['countcredit'] . " 單 "."$" . $this->data['sumcredit'];

        $csv .= ',';
        $csv .= '月結總數 '.$num.',';
        $csv .= ',';
        $csv .= ',';
        $csv .= "\r\n";

        echo "\xEF\xBB\xBF";

        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="DailyReport.csv"',
        );

        return Response::make(rtrim($csv, "\n"), 200, $headers);


    }

    # PDF Section
    public function generateHeader($pdf)
    {

        $pdf->SetFont('chi', '', 18);
        $pdf->Cell(0, 10, "炳記行貿易有限公司", 0, 1, "C");
        $pdf->SetFont('chi', 'U', 16);
        $pdf->Cell(0, 10, $this->_reportTitle, 0, 1, "C");
        $pdf->SetFont('chi', 'U', 13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->_zone, 2, '0', STR_PAD_LEFT), 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->_date).' 至 '.date('Y-m-d',$this->_date1), 0, 2, "L");
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi', '', 9);
        $pdf->Code128(10, $pdf->h - 15, $this->_uniqueid, 50, 10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");

    }

    public function outputPDF()
    {

        // Update it as generated into picking list

        // if(count($this->_invoices) > 0)
        //  {
        //Invoice::wherein('invoiceId', $this->_invoices)->update(['invoiceStatus'=>'4']);
        //   }


        $pdf = new PDF();

        $pdf->AddFont('chi', '', 'LiHeiProPC.ttf', true);
        // handle 1F goods
        $good = array_chunk($this->data['items'], 32, true);
        // $returngood = array_chunk($this->data['returnitems'], 30, true);
        // pd($this->data);

        $numItems = count($good);
        //  $numItems += count($returngood);
        $i = 0;

        foreach ($good as $ij => $f) {
            $i++;
            $pdf->AddPage();

            $this->generateHeader($pdf);

            $pdf->SetFont('chi', '', 10);

            $pdf->setXY(10, 50);
            $pdf->Cell(0, 0, "編號", 0, 0, "L");

            $pdf->setXY(50, 50);
            $pdf->Cell(0, 0, "貨品說明", 0, 0, "L");

            $pdf->setXY(168, 50);
            $pdf->Cell(0, 0, "發表出貨量", 0, 0, "L");

            $pdf->Line(10, 53, 190, 53);

            $y = 60;

            $pdf->setXY(500, $pdf->h - 30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i, $numItems), 0, 0, "R");


            foreach ($f as $u) {
                $pdf->setXY(10, $y);
                $pdf->Cell(0, 0, $u['productId'], 0, 0, "L");

                $pdf->setXY(50, $y);
                $pdf->Cell(0, 0, $u['name'], 0, 0, "L");

                $pdf->setXY(168, $y);
                $pdf->Cell(0, 0, sprintf("%s", $u['counts']), 0, 0, "L");

                $pdf->setXY(180, $y);
                $pdf->Cell(0, 0, str_replace(' ', '', $u['unit_txt']), 0, 0, "L");

                $y += 6;
            }


            // $y += 10;
            // Notes part


        }

        /*   foreach($returngood as $ij=>$f)
           {

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
               $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, $numItems) , 0, 0, "R");

               foreach($f as $id=>$e)
               {

                   foreach($e as $u)
                   {
                       $pdf->setXY(10, $y);
                       $pdf->Cell(0, 0, $id, 0, 0, "L");

                       $pdf->setXY(50, $y);
                       $pdf->Cell(0, 0, $u['name'], 0, 0, "L");

                       $pdf->setXY(168, $y);
                       $pdf->Cell(0, 0, sprintf("%s", '-'.$u['counts']), 0, 0, "L");

                       $pdf->setXY(180, $y);
                       $pdf->Cell(0, 0, str_replace(' ', '', $u['unit_txt']), 0, 0, "L");

                       $y += 6;
                   }
               }

               // $y += 10;
               // Notes part


           }*/


        if ($i === $numItems) {

            $pdf->Line(10, $y + 5, 190, $y + 5);

            $pdf->setXY(10, $y + 10);
            $pdf->Cell(0, 0, "現金總數:", 0, 0, "L");

            $pdf->setXY(30, $y + 10);
            $pdf->Cell(0, 0, $this->data['countcod'] . "單", 0, 0, "L");

            $pdf->setXY(50, $y + 10);
            $pdf->Cell(0, 0, "$" . number_format($this->data['sumcod'], 2, '.', ','), 0, 0, "L");

            $pdf->setXY(10, $y + 16);
            $pdf->Cell(0, 0, "月結總數:", 0, 0, "L");

            $pdf->setXY(30, $y + 16);
            $pdf->Cell(0, 0, $this->data['countcredit'] . "單", 0, 0, "L");

            $pdf->setXY(50, $y + 16);
            $pdf->Cell(0, 0, "$" . number_format($this->data['sumcredit'], 2, '.', ','), 0, 0, "L");

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
            'remark' => sprintf("Daily Summary, DeliveryDate = %s",date("Y-m-d", $this->_date)),
            'uniqueId' => $this->_uniqueid,
            'shift' => $this->_shift,
            'zoneId' => $this->_zone,
            'associates' => json_encode($this->_invoices),
        ];
    }
}