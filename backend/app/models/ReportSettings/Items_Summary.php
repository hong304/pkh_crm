<?php


class Items_Summary {
    
    private $_reportTitle = "";
    private $_date = "";
    private $_date1 = "";
    private $_date2 = "";
    private $_zone = "";
    private $_indata = [];
    private $_invoices = [];

    private $data = [];
    private $_account = [];
    private $_backaccount = [];
    private $_paidInvoice = [];
    private $_uniqueid = "";
    
    public function __construct($indata)
    {
        if(!Auth::user()->can('view_itemssummary'))
            pd('Permission Denied');

        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        $this->_indata = $indata;
        

        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_group = (isset($indata['filterData']['group']) ? $indata['filterData']['group'] : '');
        $this->productId = ($indata['filterData']['productId']=='') ? '' : $indata['filterData']['productId'];
        $this->productName = ($indata['filterData']['productName']=='') ? '' : $indata['filterData']['productName'];
        $this->_date1 = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_date2 = (isset($indata['filterData']['deliveryDate2']) ? strtotime($indata['filterData']['deliveryDate2']) : strtotime("today"));
        // check if user has clearance to view this zone        

        $this->_uniqueid = microtime(true);
    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }
    
    public function compileResults()
    {
        $invoiceitems = [];
        $filter = $this->_indata['filterData'];

//        if(strlen($this->_group) < 2 && strlen($filter['name']) < 4 && strlen($filter['phone']) < 4 && strlen($filter['customerId']) < 3 && $this->productId=='' && $this->productName==''){
//            $empty = true;
//            $this->data=[];
//        }else{
            $empty = false;
//        }

        if(!$empty){

        $invoicesQuery = Invoice::select('invoiceStatus','invoice.invoiceId')->whereIn('invoiceStatus',['1','2','20','30','96','97','98'])
            ->leftJoin('Customer', function($join) {
                $join->on('Customer.customerId', '=', 'Invoice.customerId');
            }) ->leftJoin('Invoiceitem', function($join) {
                $join->on('Invoiceitem.invoiceId', '=', 'Invoice.invoiceId');
            })->leftJoin('Product', function($join) {
                $join->on('Product.productId', '=', 'InvoiceItem.productId');
            })->leftJoin('customer_groups', function($join) {
                $join->on('customer_groups.id', '=', 'Customer.customer_group_id');
            })->whereBetween('Invoice.deliveryDate', [$this->_date1,$this->_date2]);

            if($this->_group != '')
                $invoicesQuery->where('customer_groups.name','LIKE','%'.$this->_group.'%');

            if($filter['customerId'] != '')
                $invoicesQuery->where('Invoice.customerId',$filter['customerId']);
            
            if($filter['name'] !='')
                $invoicesQuery->where('customerName_chi', 'LIKE', '%' . $filter['name'] . '%');

            if($this->productId!='')
                $invoicesQuery->where(function ($query){
                    $query->where('InvoiceItem.productId', 'LIKE', '%' . $this->productId . '%');
                });

            if($this->productName!='')
                $invoicesQuery->where('productName_chi', 'LIKE','%' . $this->productName. '%');

                  $invoicesQuery = $invoicesQuery->get();



         $return_goods =[];
            $normal_goods = [];
               foreach($invoicesQuery as $k=>$v){
                   if($v->invoiceStatus == '98'){
                       $return_goods[] = $v->invoiceId;
                   }else{
                       $normal_goods[] = $v->invoiceId;
                   }
               }
if(count($normal_goods)>0){
                              $invoiceitems = InvoiceItem::select('InvoiceItem.productId',DB::raw('SUM(productQty) AS productQtys'),DB::raw('SUM(productQty*ProductPrice) AS productAmount'),'productUnitName','productQtyUnit','productName_chi')->wherein('invoiceId',$normal_goods)

                                     ->leftJoin('Product', function($join) {
                                         $join->on('Product.productId', '=', 'InvoiceItem.productId');
                                     })->where('InvoiceItem.productId', 'LIKE','%' .  $this->productId. '%')
                                     ->where('productName_chi', 'LIKE','%' . $this->productName. '%')
                                     ->groupBy('productId')->groupBy('productQtyUnit')
                                     ->get()->toArray();
}


                   if(count($return_goods)>0){
                      $invoiceitems_return = InvoiceItem::select('productId',DB::raw('SUM(productQty) AS productQtys'),DB::raw('SUM(productQty*ProductPrice) AS productAmount'),'productUnitName','productQtyUnit')->wherein('invoiceId',$return_goods)->groupBy('productId')->groupBy('productQtyUnit')->get();

                       foreach($invoiceitems as &$invoiceQ)
                             {
                                 foreach($invoiceitems_return as $g){
                                     if($g->productId == $invoiceQ['productId'] && $g->productQtyUnit == $invoiceQ['productQtyUnit'] ){
                                         $invoiceQ['productQtys'] += $g->productQtys;
                                         $invoiceQ['productAmount'] += $g->productAmount;
                                     }
                                 }
                                }
                              }

       $this->data = $invoiceitems;

       return $this->data;
        }
    }
    
 public function outputCsv(){

        $csv = '貨品,名稱,累計,單位,總額,平均' . "\r\n";
        $totalinvoice = count($this->data)+1;
        $ii = 2;
        foreach ($this->data as $o) {
            $csv .= '"' . $o['productId'] . '",';
            $csv .= '"' . $o['productName_chi'] . '",';
            $csv .= '"' . $o['productQtys'] . '",';
            $csv .= '"' . $o['productUnitName'] . '",';
            $csv .=  '"' .$o['productAmount'] . '",';
            $csv .= '"' . $o['productAmount']/$o['productQtys'] . '",';
            $csv .= "\r\n";
            $ii++;
        }

        echo "\xEF\xBB\xBF";

        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ItemSummary.csv"',
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
                'id' => 'product',
                'type' => 'search_product_detail',
                'label' => '產品資料',
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
        return View::make('reports/Items_Summary')->with('data', $this->data)->render();
    }
    
    
    # PDF Section
    public function generateHeader($pdf)
    {
    
        $pdf->SetFont('chi','',18);
        $pdf->Cell(0, 10,"炳記行貿易國際有限公司",0,1,"C");
        $pdf->SetFont('chi','U',16);
        $pdf->Cell(0, 10,$this->_reportTitle,0,1,"C");
        $pdf->SetFont('chi','U',12);
        $pdf->Cell(0, 5, "出車日期: " . date("Y-m-d", $this->_date1), 0, 2, "L");
        $pdf->Cell(0, 5, "至: " . date("Y-m-d", $this->_date2), 0, 2, "L");

        $pdf->setXY(0, 0);
        $pdf->SetFont('chi','', 9);
        $pdf->Code128(10,$pdf->h-15,$this->_uniqueid,50,10);
        $pdf->Cell(0, 10, sprintf("報告編號: %s", $this->_uniqueid), 0, 2, "R");

        $pdf->setXY(160, 30);
        $pdf->SetFont('chi','', 9);
        $pdf->Cell(0, 10, sprintf("集團: %s", $this->_group), 0, 2, "L");

        $pdf->setXY(160, 35);
        $pdf->SetFont('chi','', 9);
        $pdf->Cell(0, 10, sprintf("客户: %s", $this->_indata['filterData']['name']), 0, 2, "L");
        $pdf->setXY(168, 40);
        $pdf->SetFont('chi','', 9);
        $pdf->Cell(0, 10, sprintf("%s", $this->_indata['filterData']['customerId']), 0, 2, "L");

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
            $pdf->Cell(0, 0, "貨品", 0, 0, "L");
        
            $pdf->setXY(30, 50);
            $pdf->Cell(0, 0, "名稱", 0, 0, "L");
        
            $pdf->setXY(100, 50);
            $pdf->Cell(0, 0, "累計數量", 0, 0, "L");
        
            $pdf->setXY(120, 50);
            $pdf->Cell(0, 0, "單位", 0, 0, "L");
                
	    $pdf->setXY(140, 50);
            $pdf->Cell(0, 0, "總額", 0, 0, "L");

            $pdf->setXY(160, 50);
            $pdf->Cell(0, 0, "平均", 0, 0, "L");
            $pdf->Line(10, 53, 190, 53);
        
            $y = 60;
        
            //$pdf->setXY(10, $pdf->h-30);
            //$pdf->Cell(0, 0, "收帳人", 0, 0, "L");
        
            //$pdf->setXY(60, $pdf->h-30);
            //$pdf->Cell(0, 0, "核數人", 0, 0, "L");
        
            //$pdf->Line(10, $pdf->h-35, 50, $pdf->h-35);
            //$pdf->Line(60, $pdf->h-35, 100, $pdf->h-35);
        
            $pdf->setXY(500, $pdf->h-30);
            $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, count($datamart)) , 0, 0, "R");
        
        
            foreach($f as $id=>$e)
            {
       
                $pdf->setXY(10, $y);
                $pdf->Cell(0, 0, $e['productId'], 0, 0, "L");
    
                $pdf->setXY(30, $y);
                $pdf->Cell(0, 0, $e['productName_chi'], 0, 0, "L");
    
		$pdf->setXY(100, $y);
                $pdf->Cell(0, 0, $e['productQtys'], 0, 0, "L");
                
		$pdf->setXY(120, $y);
                $pdf->Cell(0, 0, $e['productUnitName'], 0, 0, "L");
                
		$pdf->setXY(140, $y);
                $pdf->Cell(0, 0, sprintf("HK$ %s", $e['productAmount']), 0, 0, "L");
    
                $pdf->setXY(160, $y);
                $pdf->Cell(0, 0, sprintf("HK$ %s", $e['productAmount']/$e['productQtys']), 0, 0, "L");
                //$lt = $e['productAmount'];
                $y += 6;
               
            }



          //  $y += 10;
            
        
        }
        $pdf->Line(10, $y, 190, $y);
        $pdf->setXY(152, $y+6);
        //$pdf->Cell(0, 0, sprintf("總數 HK$ %s", $lt), 0, 0, "L");
        // output
        return [
            'pdf' => $pdf,
	    'remark' => sprintf("Item Summary Archive for Zone %s, DeliveryDate between %s and %s created by %s on %s", $this->_zone, date("Y-m-d", $this->_date1), date("Y-m-d", $this->_date2), Auth::user()->username, date("r")),

            //'remark' => sprintf("Item Summary Archive for Zone %s, DeliveryDate = %s created by %s on %s", $this->_zone, date("Y-m-d", $this->_date), Auth::user()->username, date("r")),
            'associates' => json_encode($this->_invoices),
            'uniqueId' => $this->_uniqueid,
        ];
    }
}
