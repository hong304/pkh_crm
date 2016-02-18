<?php


class Invoice_9FPickingList {

    private $_reportTitle = "";
    private $_date = "";
    private $_zone = "";
    private  $_shift = "";
    private $_invoices = [];
    private $_uniqueid = "";
    private $_version = '';

    public function __construct($indata)
    {



        $report = Report::where('id', $indata['reportId'])->first();

        $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_zone = (isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['value'] : $permittedZone[0]);
        $this->_zonename =(isset($indata['filterData']['zone']) ? $indata['filterData']['zone']['label'] : $permittedZone[0]);
        $this->_shift =  (isset($indata['filterData']['shift']['value']))?$indata['filterData']['shift']['value']:'1';

        // check if user has clearance to view this zone        
        if(!in_array($this->_zone, $permittedZone))
        {
            App::abort(401, "Unauthorized Zone");
        }
        // version & id
        $this->_uniqueid = date("Ymd", $this->_date) . $this->_zone;

        /*  $lastid = ReportArchive::where('id', 'like', $this->_uniqueid.'-%-9')->select('id')->orderby('created_at', 'desc')->first();
          $lastid = @explode('-', $lastid->id);

          $this->_version = isset($lastid[1]) ? $lastid[1]+1 : '1';
 */
        $lastid = pickingListVersionControl::where('zone',$this->_zone)->where('date',date("Y-m-d",$this->_date))->where('shift',$this->_shift)->first();



        //  $lastid = @explode('-', $lastid->id);

        $this->_version = isset($lastid->f9_version) ? $lastid->f9_version : '1';

        $this->_uniqueid = sprintf("%s-%s-9", $this->_uniqueid, $this->_version);

        $this->_reportTitle = sprintf("%s - v%s", $report->name,  $this->_version);
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
        $this->goods = ['1F'=>[], '9F'=>[]];
        $invoicesQuery = Invoice::select('*')->whereNotIn('version',[0,100])->where('shift',$this->_shift)->where('zoneId', $zone)->where('deliveryDate', $date)->with(['invoiceItem'=>function($query){
            $query->orderBy('productLocation')->orderBy('productQtyUnit')->withTrashed();
        }])->with('products', 'client')->get();



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

      /*  $invoice_del_item =[];
        $invoice_item = [];

        foreach($invoicesQuery as $invoiceQ)
        {
            if($invoiceQ->revised == true){
                foreach($invoiceQ['invoiceItem'] as $item)
                    {
                        if($item->deleted_at != '')
                            $invoice_del_item[$invoiceQ->invoiceId][$item->productId] = true;
                        else
                            $invoice_item[$invoiceQ->invoiceId][$item->productId] = true;
                    }
           }
        }*/

       // p($invoice_item);
       //pd($invoice_del_item);

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


                        if($productDetail->productLocation == '9' && $item->productQty > 0) {
                            $customerId = $client->customerId;

                                if($invoiceQ->version == $this->_version){


                                    if (isset($this->goods['9F'][$customerId . $invoiceId]['items'][$productId][$unit]) && $productDetail->allowSeparate) {
                                        if (!function_exists('fact')) {
                                            function fact($goods, $customerId, $productId, $invoiceId, $unit, $n)
                                            {
                                                if (isset($goods['9F'][$customerId . $invoiceId]['items'][$productId . '-' . $n][$unit])) {
                                                    return fact($goods, $customerId, $productId, $invoiceId, $unit, $n + 1);
                                                } else {
                                                    return $n;
                                                }
                                            }
                                        }

                                        $v = fact($this->goods, $customerId, $productId, $invoiceId, $unit, '1');

                                        $this->goods['9F'][$customerId . $invoiceId]['items'][$productId . '-' . $v][$unit] = [
                                            'productId' => $productId,
                                            'name' => $productDetail->productName_chi,

                                            'productPacking_carton' => $productDetail->productPacking_carton,
                                            'productPacking_inner' => $productDetail->productPacking_inner,
                                            'productPacking_unit' => $productDetail->productPacking_unit,
                                            'productPackingName_carton' => $productDetail->productPackingName_carton,
                                            'productPackingName_inner' => $productDetail->productPackingName_inner,
                                            'productPackingName_unit' => $productDetail->productPackingName_unit,
                                            'productPackingSize' => $productDetail->productPacking_size,

                                            'unit' => $unit,
                                            'unit_txt' => $item->productUnitName,
                                            'counts' => $item->productQty,
                                            'stdPrice' => $productDetail->productStdPrice[$unit],
                                        ];

                                    } else {

                                            if(isset($invoice_del_item[$invoiceQ->invoiceId])){
                                               // $intersec = array_intersect_key($invoice_item[$invoiceQ->invoiceId],$invoice_del_item[$invoiceQ->invoiceId]);
                                               // $dd = array_diff_key($invoice_del_item[$invoiceQ->invoiceId],$invoice_item[$invoiceQ->invoiceId]);
                                            }

                                   //     pd($dd);

                                        if($item->deleted_at == null){
                                           // if(isset($intersec[$productId]))

                                               // $productDetail->productName_chi = '(更正)'.$productDetail->productName_chi;

                                              //  $productDetail->productName_chi = '(新加)'.$productDetail->productName_chi;

                                            if($item->new_added=='2')
                                                $this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(更)'][$unit] = [
                                                    'productId' => $productId,
                                                    'name' => '(更)'.$productDetail->productName_chi,

                                                    'productPacking_carton' => $productDetail->productPacking_carton,
                                                    'productPacking_inner' => $productDetail->productPacking_inner,
                                                    'productPacking_unit' => $productDetail->productPacking_unit,
                                                    'productPackingName_carton' => $productDetail->productPackingName_carton,
                                                    'productPackingName_inner' => $productDetail->productPackingName_inner,
                                                    'productPackingName_unit' => $productDetail->productPackingName_unit,
                                                    'productPackingSize' => $productDetail->productPacking_size,

                                                    'unit' => $unit,
                                                    'unit_txt' => $item->productUnitName,
                                                    'counts' => (isset($this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(更)'][$unit]) ? $this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(更)'][$unit]['counts'] : 0) + $item->productQty,
                                                    'stdPrice' => $productDetail->productStdPrice[$unit],
                                                ];

                                            else if($item->new_added=='1')
                                                $this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(新)'][$unit] = [
                                                    'productId' => $productId,
                                                    'name' => '(新)'.$productDetail->productName_chi,

                                                    'productPacking_carton' => $productDetail->productPacking_carton,
                                                    'productPacking_inner' => $productDetail->productPacking_inner,
                                                    'productPacking_unit' => $productDetail->productPacking_unit,
                                                    'productPackingName_carton' => $productDetail->productPackingName_carton,
                                                    'productPackingName_inner' => $productDetail->productPackingName_inner,
                                                    'productPackingName_unit' => $productDetail->productPackingName_unit,
                                                    'productPackingSize' => $productDetail->productPacking_size,

                                                    'unit' => $unit,
                                                    'unit_txt' => $item->productUnitName,
                                                    'counts' => (isset($this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(新)'][$unit]) ? $this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(新)'][$unit]['counts'] : 0) + $item->productQty,
                                                    'stdPrice' => $productDetail->productStdPrice[$unit],
                                                ];

                                            else
                                            $this->goods['9F'][$customerId . $invoiceId]['items'][$productId][$unit] = [
                                                'productId' => $productId,
                                                'name' => $productDetail->productName_chi,

                                                'productPacking_carton' => $productDetail->productPacking_carton,
                                                'productPacking_inner' => $productDetail->productPacking_inner,
                                                'productPacking_unit' => $productDetail->productPacking_unit,
                                                'productPackingName_carton' => $productDetail->productPackingName_carton,
                                                'productPackingName_inner' => $productDetail->productPackingName_inner,
                                                'productPackingName_unit' => $productDetail->productPackingName_unit,
                                                'productPackingSize' => $productDetail->productPacking_size,

                                                'unit' => $unit,
                                                'unit_txt' => $item->productUnitName,
                                                'counts' => (isset($this->goods['9F'][$customerId . $invoiceId]['items'][$productId][$unit]) ? $this->goods['9F'][$customerId . $invoiceId]['items'][$productId][$unit]['counts'] : 0) + $item->productQty,
                                                'stdPrice' => $productDetail->productStdPrice[$unit],
                                            ];

                                        }else{
                                           // if(isset($dd[$productId]))
                                            if($item->new_added=='3')
                                                $this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(刪)'][$unit] = [
                                                    'productId' => $productId,
                                                    'name' => '(刪)'.$productDetail->productName_chi,

                                                    'productPacking_carton' => $productDetail->productPacking_carton,
                                                    'productPacking_inner' => $productDetail->productPacking_inner,
                                                    'productPacking_unit' => $productDetail->productPacking_unit,
                                                    'productPackingName_carton' => $productDetail->productPackingName_carton,
                                                    'productPackingName_inner' => $productDetail->productPackingName_inner,
                                                    'productPackingName_unit' => $productDetail->productPackingName_unit,
                                                    'productPackingSize' => $productDetail->productPacking_size,

                                                    'unit' => $unit,
                                                    'unit_txt' => $item->productUnitName,
                                                    'counts' => (isset($this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(刪)'][$unit]) ? $this->goods['9F'][$customerId . $invoiceId]['items'][$productId.'(刪)'][$unit]['counts'] : 0) + $item->productQty,
                                                    'stdPrice' => $productDetail->productStdPrice[$unit],
                                                ];
                                        }



                                    }

                                    $this->goods['9F'][$customerId . $invoiceId]['customerInfo'] = $client->toArray();
                                    $this->goods['9F'][$customerId . $invoiceId]['invoiceId'] = $invoiceId;

                                    if (isset($this->goods['9F'][$customerId . $invoiceId]))
                                        $this->goods['9F'][$customerId . $invoiceId]['revised'] = ($invoiceQ->revised == true) ? "(修正)" : '';
                                }


                            /* show carton summary on last page
                             if($unit == 'carton' && $item->productQty > 0.5){
                                 $this->goods['carton'][$productId]['items'] = [
                                     'productId' => $productId,
                                     'name' => $productDetail->productName_chi,
                                     'productPacking_carton' => $productDetail->productPacking_carton,
                                     'productPacking_inner' => $productDetail->productPacking_inner,
                                     'productPacking_unit' => $productDetail->productPacking_unit,
                                     'productPackingName_carton' => $productDetail->productPackingName_carton,
                                     'productPackingName_inner' => $productDetail->productPackingName_inner,
                                     'productPackingName_unit' => $productDetail->productPackingName_unit,
                                     'productPackingSize' => $productDetail->productPacking_size,
                                     'unit' => $unit,
                                     'unit_txt' => $item->productUnitName,
                                     'counts' => (isset($this->goods['carton'][$productId]['items']) ? $this->goods['carton'][$productId]['items']['counts'] : 0) + $item->productQty,

                                 ];
                                 $this->goods['carton'][$productId]['productDetail'] = $productDetail->toArray();
                                 $this->goods['carton'][$productId]['productPrice'] = $productDetail->productStdPrice[$unit];
                             } end of show carton summary on last page */

                            /*   group by route path
                                 if($unit == 'carton'){
                                     $this->goods['carton'][$productId]['items'][$invoiceQ->routePlanningPriority] = [
                                         'productId' => $productId,
                                         'name' => $productDetail->productName_chi,
                                         'productPacking_carton' => $productDetail->productPacking_carton,
                                         'productPacking_inner' => $productDetail->productPacking_inner,
                                         'productPacking_unit' => $productDetail->productPacking_unit,
                                         'productPackingName_carton' => $productDetail->productPackingName_carton,
                                         'productPackingName_inner' => $productDetail->productPackingName_inner,
                                         'productPackingName_unit' => $productDetail->productPackingName_unit,
                                         'productPackingSize' => $productDetail->productPacking_size,
                                         'unit' => $unit,
                                         'unit_txt' => $item->productUnitName,
                                         'counts' => (isset($this->goods['carton'][$productId][$invoiceQ->routePlanningPriority]) ? $this->goods['carton'][$productId][$invoiceQ->routePlanningPriority]['counts'] : 0) + $item->productQty,
                                         'stdPrice' => $productDetail->productStdPrice[$unit],
                                     ];
                                     $this->goods['carton'][$productId]['productDetail'] = $productDetail->toArray();
                                     $this->goods['carton'][$productId]['productPrice'] = $productDetail->productStdPrice[$unit];
                                 } */


                        }

                    }

                }


        usort($this->goods['9F'], function($elementA, $elementB) {
            return $elementA['customerInfo']['routePlanningPriority'] - $elementB['customerInfo']['routePlanningPriority'];
        });

        /* show carton summary on last page
        if(isset($this->goods['carton'])){
            ksort($this->goods['carton']);
            foreach ($this->goods['carton'] as &$v){
                ksort($v['items']);
            }
        }*/

        $this->data = $this->goods;
      //  pd($this->data);
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

        $ashift =[['value'=>'1','label'=>'早班'],['value'=>'2','label'=>'晚班']];


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
            [
                'id' => 'submit',
                'type' => 'submit',
                'label' => '提交',
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
                'name' => '匯出 Excel 核對表',
                'warning'   =>  false,
            ],
        ];

        return $downloadSetting;
    }

    public function outputPreview()
    {

        return View::make('reports/pickinglist9f')->with('data', $this->data)->render();

    }

    public function outputCSV(){
        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';

        $csv1 = DB::table('invoiceitem')->select('productQtyUnit','productPacking_carton','productPackingName_carton','productPacking_inner','productPackingName_inner','productPacking_unit','productPackingName_unit','productPacking_size','zoneId','deliveryDate','invoiceitem.productId as productId','productName_chi','productUnitName',DB::Raw('sum(productQty) as SumQty'))->leftjoin('invoice','invoice.invoiceId','=','invoiceitem.invoiceId')->leftjoin('product','product.productId','=','invoiceitem.productId')->where('deliveryDate',$this->_date)->where('zoneId',$this->_zone)->where('invoiceitem.productLocation',9)->whereNull('invoiceitem.deleted_at')->groupby('productId','productQtyUnit')->get();




        /* $csv = '車號,送貨日期,產品編號,產品名稱,數量,單位' . "\r\n";
           foreach ($csv1 as $v) {
               $csv .= $v->zoneId.',';
               $csv .= date('Y-m-d',$v->deliveryDate).',';
               $csv .= $v->productId.',';
               $csv .= $v->productName_chi.',';
               $csv .= $v->SumQty.',';
               $csv .= $v->productUnitName.',';
               $csv .= "\r\n";

           }
          echo "\xEF\xBB\xBF";

           $headers = array(
               'Content-Type' => 'text/csv',
               'Content-Disposition' => 'attachment; filename="9F_picking.csv"',
           );

          return Response::make(rtrim($csv, "\n"), 200, $headers);*/

$i=3;
        $objPHPExcel = new PHPExcel ();

        $objPHPExcel->getActiveSheet()->setCellValue('A1', '車號');
        $objPHPExcel->getActiveSheet()->setCellValue('A2', '送貨日期');

        $objPHPExcel->getActiveSheet()->setCellValue('B1', $this->_zone);
        $objPHPExcel->getActiveSheet()->setCellValue('B2', date('Y-m-d',$this->_date));

        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '車號');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, '送貨日期');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, '產品編號');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, '產品名稱');
        $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, '包裝');
        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, '數量');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, '單位');
        $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, '核對數');
        $objPHPExcel->getActiveSheet()->setCellValue('I'.$i, '核對差異');

        $i += 1;
        foreach ($csv1 as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $v->zoneId);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, date('Y-m-d',$v->deliveryDate));
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $v->productId);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $v->productName_chi);

            if($v->productUnitName == '斤'){
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, '');
            }else{

                if($v->productPacking_inner>1)
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v->productPackingName_carton.'/'.$v->productPacking_inner.$v->productPackingName_inner .' x '. $v->productPacking_unit.$v->productPackingName_unit .' ('.$v->productPacking_size.')');
                else
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v->productPackingName_carton.'/'.$v->productPacking_unit.$v->productPackingName_unit .' ('.$v->productPacking_size.')');

                /*if($v->productQtyUnit == 'carton'){
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v->productPacking_inner .' x '. $v->productPacking_unit .' ('.$v->productPacking_size.')');
                }

                if($v->productQtyUnit == 'inner'){
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v->productPacking_unit .' ('.$v->productPacking_size.')');
                }

                if($v->productQtyUnit == 'unit'){
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v->productPacking_size);
                }*/
            }

            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $v->SumQty);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $v->productUnitName);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, '=F'.$i.'-H'.$i);
            $i++;
        }

       /* foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
           // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }*/
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date('Ymd',$this->_date).'-'. $this->_zone.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
     //   pd($csv);

   }

    # PDF Section
    public function generateHeader($pdf)
    {
        $shift = ($this->_shift== 1)?'早班':'晚班';
        $pdf->SetFont('chi','',18);
        $pdf->Cell(0, 10,"炳記行貿易有限公司",0,1,"C");
        $pdf->SetFont('chi','U',16);
        $pdf->Cell(0, 10,$this->_reportTitle,0,1,"C");
        $pdf->SetFont('chi','U',13);
        $pdf->Cell(0, 10, "車號: " . str_pad($this->_zone, 2, '0', STR_PAD_LEFT)."(".$this->_zonename.")", 0, 2, "L");
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->_date)."(".$shift.")", 0, 2, "L");
        $pdf->setXY(0, 0);
        $pdf->SetFont('chi','', 9);
        $pdf->Code128(10,$pdf->h-15,$this->_uniqueid,50,10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");

    }

    public function outputPDF()
    {
        // handle 9F goods
        $ninef = $this->data['9F'];

        $newway = [];

        foreach($ninef as $k => $v){
            $temp = array_chunk($v['items'],35,true);
            if(count($temp)>1){
                foreach($temp as $kk => $vv){
                    if($kk == 0){
                        $newway[$k]['items'] = $vv;
                        $newway[$k]['customerInfo'] = $v['customerInfo'];
                        $newway[$k]['revised'] = $v['revised'];
                        $newway[$k]['invoiceId'] = $v['invoiceId'];

                    }else{
                        $newway[$k."-".$kk]['items'] = $vv;
                        $newway[$k."-".$kk]['customerInfo'] = $v['customerInfo'];
                        $newway[$k."-".$kk]['revised'] = $v['revised'];
                        $newway[$k."-".$kk]['invoiceId'] = $v['invoiceId'];
                    }
                }
            }else{
                $newway[$k]['items'] = $v['items'];
                $newway[$k]['customerInfo'] = $v['customerInfo'];
                $newway[$k]['revised'] = $v['revised'];
                $newway[$k]['invoiceId'] = $v['invoiceId'];
            }
        }
        $this->_newway = $newway;


        $consec = $j = 0;

        $pdf = new PDF();
        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);

        foreach( $this->_newway as $c=>$nf)
        {

            $consec += count($nf['items'])+2;
            $nf['consec'] = count($nf['items']);
            $nf['acccon'] = $consec;

            // we can have 20 items as most per section
            $ninefproducts[$j][] = $nf;
            if($consec > 40)
            {
                array_pop($ninefproducts[$j]);
                $nf['acccon'] = count($nf['items'])+2;
                $j++;
                $consec = $nf['acccon'];
                $ninefproducts[$j][] = $nf;
            }
        }
        //  pd($ninefproducts);
        foreach($ninefproducts as $index=>$order)
        {

            // if it is in left section, add a new page
            //  if($index % 2 == 0)
            //   {

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
            //    $pdf->Line(105, 45, 105, 280);

            $pdf->SetFont('chi','',10);
            $pdf->setXY(500, $pdf->h-30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $index+1, ceil(count($ninefproducts))) , 0, 0, "R");
            //   }

            //$pdf->Cell(50, 50, "NA", 0, 0, "L");

            // define left right position coordinate x differences
            $y = 55;
            $base_x = 10;
            /*  if($index % 2 == 0)
              {
                  $base_x = 5;
              }
              else
              {
                  $base_x = 110;
              }*/

            foreach($order as $o)
            {

                $pdf->setXY($base_x + 0, $y);
                $pdf->SetFont('chi','U',14);
                $pdf->Cell(0, 0, sprintf("%s - %s %s", $o['customerInfo']['routePlanningPriority'], $o['customerInfo']['customerName_chi'],$o['revised']), 0, 0, "L");

                 $pdf->SetFont('chi','',11);
                 $pdf->setXY($base_x + 84, $y);
                 $pdf->Cell(0, 0, sprintf("%s", $o['invoiceId']), 0, 0, "L");

                $pdf->SetFont('chi','',12);

                $y += 5;

                foreach($o['items'] as $itemUnitlv)
                {
                    foreach($itemUnitlv as $item)
                    {
                        $pdf->setXY($base_x + 0, $y);
                        if(preg_match('[新|更|刪]', $item['name']) != true) {
                            $item['name']=  "     " . $item['name'];
                        }
                        $pdf->Cell(0, 0,$item['name'], 0, 0, 'L');

                        $inner = '';
                        if($item['productPacking_inner']>1)
                            $inner = $item['productPacking_inner'] . $item['productPackingName_inner']."x";


                        // $pdf->setXY($base_x + 120, $y);
                        // $pdf->Cell(0, 0, "    $" . $item['stdPrice'], 0, 0, 'L');

                        $pdf->setXY($base_x + 70, $y);
                        $pdf->Cell(20, 0, "    " . sprintf("%s%s", $item['counts'],$item['unit_txt']), 0, 0, 'R');


                        if($item['unit_txt']=='斤'){

                        }else{

                            if($item['unit']=='unit'){
                                $pdf->setXY($base_x + 100, $y);
                                $pdf->Cell(0, 0,$item['productPackingSize'] , 0, 0, 'L');
                            }

                            if($item['unit']=='inner'){
                                $pdf->setXY($base_x + 100, $y);
                                $pdf->Cell(0, 0,$item['productPacking_unit'] . $item['productPackingName_unit']." x ".$item['productPackingSize'] , 0, 0, 'L');
                            }

                            if($item['unit']=='carton'){
                                $pdf->setXY($base_x + 100, $y);
                                $pdf->Cell(0, 0,$inner.$item['productPacking_unit'] . $item['productPackingName_unit']." x ".$item['productPackingSize'] , 0, 0, 'L');
                            }
                        }

                        $y +=  5;
                    }
                }

                $y += 5;

              //  $pdf->SetDash(1, 1);
              //  $pdf->Line($base_x + 2, $y-5, $base_x + 200, $y-5);
            }
        }


/* show carton summary on last page
        if(isset($this->data['carton'])){
            $consec = $j = 0;
            foreach($this->data['carton'] as $c=>$nf)
            {

                $consec += 1;
                $nf['consec'] = count($nf['items']);
                $nf['acccon'] = $consec;

                // we can have 40 items as most per section
                $ninefproducts1[$j][] = $nf;
                if($consec > 20)
                {
                    array_pop($ninefproducts1[$j]);
                    $nf['acccon'] = 1;
                    $j++;
                    $consec = $nf['acccon'];
                    $ninefproducts1[$j][] = $nf;
                }
            }
            //   pd($ninefproducts1);

            foreach($ninefproducts1 as $index=>$order)
            {


                // if it is in left section, add a new page
                //  if($index % 2 == 0)
                //   {

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
                //    $pdf->Line(105, 45, 105, 280);

                $pdf->SetFont('chi','',10);
                $pdf->setXY(500, $pdf->h-30);
                $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $index+1, ceil(count($ninefproducts1))) , 0, 0, "R");
                //   }


                // define left right position coordinate x differences
                $y = 55;
                $base_x = 10;


                foreach($order as $k=>$o)
                {



                    $pdf->setXY($base_x + 0, $y);
                    $pdf->SetFont('chi','',12);
                    $pdf->Cell(0, 0, sprintf("%s - %s", $o['productDetail']['productId'],$o['productDetail']['productName_chi'], 0, 0, "L"));



                    $pdf->setXY($base_x + 100, $y);
                    $pdf->Cell(0, 0, "    " . sprintf("%s %s", $o['items']['counts'],$o['items']['unit_txt']), 0, 0, 'L');

                    $pdf->SetFont('chi','',14);
                    $pdf->setXY($base_x + 140, $y);
                    $pdf->Cell(0, 0, "[  ]   [  ]", 0, 0, 'L');

                    $y += 10;

                    $pdf->SetDash(1, 1);
                    $pdf->Line($base_x + 2, $y-5, $base_x + 200, $y-5);
                }

            }
        }
end of show carton summary on last page*/

/* group by route path
        if(isset($this->data['carton'])){
                $consec = $j = 0;
                foreach($this->data['carton'] as $c=>$nf)
                {

                    $consec += count($nf['items'])+2;
                    $nf['consec'] = count($nf['items']);
                    $nf['acccon'] = $consec;

                    // we can have 40 items as most per section
                    $ninefproducts1[$j][] = $nf;
                    if($consec > 40)
                    {
                        array_pop($ninefproducts1[$j]);
                        $nf['acccon'] = count($nf['items'])+2;
                        $j++;
                        $consec = $nf['acccon'];
                        $ninefproducts1[$j][] = $nf;
                    }
                }
                //   pd($ninefproducts1);

                foreach($ninefproducts1 as $index=>$order)
                {


                    // if it is in left section, add a new page
                    //  if($index % 2 == 0)
                    //   {

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
                    //    $pdf->Line(105, 45, 105, 280);

                    $pdf->SetFont('chi','',10);
                    $pdf->setXY(500, $pdf->h-30);
                    $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $index+1, ceil(count($ninefproducts1))) , 0, 0, "R");
                    //   }

                    //$pdf->Cell(50, 50, "NA", 0, 0, "L");

                    // define left right position coordinate x differences
                    $y = 55;
                    $base_x = 10;


                  foreach($order as $k=>$o)
                    {



                        $pdf->setXY($base_x + 0, $y);
                        $pdf->SetFont('chi','',12);
                        $pdf->Cell(0, 0, sprintf("%s - %s", $o['productDetail']['productId'],$o['productDetail']['productName_chi'], 0, 0, "L"));


                        $inner = '';
                        if($item['productPacking_inner']>1)
                            $inner = 'x'.$o['productDetail']['productPacking_inner'] . $o['productDetail']['productPackingName_inner'];

                        $pdf->setXY($base_x + 70, $y);
                        $pdf->Cell(0, 0,$o['productDetail']['productPacking_carton'] . $o['productDetail']['productPackingName_carton'].$inner."x".$o['productDetail']['productPacking_unit'] . $o['productDetail']['productPackingName_unit']."x".$o['productDetail']['productPacking_size'] , 0, 0, 'L');



                        $pdf->setXY($base_x + 120, $y);
                        $pdf->Cell(0, 0, "    $" . $o['productPrice'], 0, 0, 'L');

                        $pdf->SetFont('chi','',14);

                        $y += 5;

                        foreach($o['items'] as $kk=>$item){

                            $pdf->setXY($base_x + 0, $y);
                            $pdf->Cell(0, 0,sprintf("%s站",$kk), 0, 0, 'L');


                            $pdf->setXY($base_x + 50, $y);
                            $pdf->Cell(0, 0, "    " . sprintf("%s %s", $item['counts'],$item['unit_txt']), 0, 0, 'L');


                            $y +=  5;
                        }
                        $y += 5;

                        $pdf->SetDash(1, 1);
                        $pdf->Line($base_x + 2, $y-5, $base_x + 200, $y-5);
                    }

                }
        } */


        // output
        return [
            'pdf' => $pdf,
            'remark' => sprintf("9F Picking List, DeliveryDate = %s", date("Y-m-d", $this->_date)),
            'zoneId' => $this->_zone,
            'uniqueId' => $this->_uniqueid,
            'shift' => $this->_shift,
            'associates' => json_encode($this->_invoices),
        ];
    }
}