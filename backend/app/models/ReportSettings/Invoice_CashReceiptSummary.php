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
    private $_expenses = [];
    private $_returnInvoices = [];
    private $_uniqueid = "";

    public function __construct($indata)
    {



        if(!Auth::user()->can('view_cashreceiptsummary'))
            pd('Permission Denied');

        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;


        $permittedZone = explode(',', Auth::user()->temp_zone);

        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        // $this->_date1 = (isset($indata['filterData']['deliveryDate2']) ? strtotime($indata['filterData']['deliveryDate2']) : strtotime("today"));
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




        if(Input::get('output') == 'excel')
            return '';

        $date = $this->_date;
        $zone = $this->_zone;




        //當天單,不是當天收錢
        $invoicesQuery = Invoice::select('invoiceId','invoice_payment.paid')->whereIn('invoiceStatus',['1','2','20','30','98','97','96'])->where('paymentTerms',1)->where('receiveMoneyZone', $zone);
        if($this->_shift != '-1')
            $invoicesQuery->where('shift',$this->_shift);
        $invoicesQuery = $invoicesQuery->leftJoin('invoice_payment', function ($join) {
            $join->on('invoice_payment.invoice_id', '=', 'Invoice.invoiceId');
        })->leftJoin('payments', function ($join) {
            $join->on('invoice_payment.payment_id', '=', 'payments.id');
        })->where('deliveryDate', '=', $date)
            ->where(function ($query) use($date) {
                $query->where('receive_date', '!=', date('y-m-d',$date));
            })->get();

        $uncheque = [];
        foreach($invoicesQuery as $v){
            if(!isset($uncheque[$v->invoiceId]))
                $uncheque[$v->invoiceId] = 0;
            $uncheque[$v->invoiceId] += $v->paid;
        }
        //當天單,不是當天收錢

        //當天單,收支票
        $invoicesQuery = Invoice::select('invoiceId','invoice_payment.paid')->whereIn('invoiceStatus',['1','2','20','30','98','97','96'])->where('paymentTerms',1)->where('receiveMoneyZone', $zone);
        if($this->_shift != '-1')
            $invoicesQuery->where('shift',$this->_shift);
        $invoicesQuery = $invoicesQuery->leftJoin('invoice_payment', function ($join) {
            $join->on('invoice_payment.invoice_id', '=', 'Invoice.invoiceId');
        })->leftJoin('payments', function ($join) {
            $join->on('invoice_payment.payment_id', '=', 'payments.id');
        })->where('deliveryDate', '=', $date)
            ->where(function ($query) use($date) {
                $query->where('receive_date', date('y-m-d',$date))
                    ->where('ref_number','!=', 'cash');
            })->get();

        $SameDayCollectCheque = [];
        foreach($invoicesQuery as $v){
            if(!isset($SameDayCollectCheque[$v->invoiceId]))
                $SameDayCollectCheque[$v->invoiceId] = 0;
            $SameDayCollectCheque[$v->invoiceId] += $v->paid;
        }
        //當天單,收支票

        $invoicesQuery = Invoice::whereIn('invoiceStatus',['1','2','20','30','98','97','96'])->where('paymentTerms',1)->where('receiveMoneyZone', $zone)->where('deliveryDate', $date);
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


            // 98 invoices
            if($invoiceQ->invoiceStatus == 98) {
                $this->_returnInvoices[] = [
                    'customerId' => $client->customerId,
                    'name' => $client->customerName_chi,
                    'invoiceNumber' => $invoiceId,
                    'deliveryDate' => date('Y-m-d',$invoiceQ->deliveryDate),
                    'amount' => number_format($invoiceQ->amount, 2, '.', ','),
                ];
            }

            if($invoiceQ->invoiceStatus == 20 || $invoiceQ->invoiceStatus == 30){
                $paid = $invoiceQ->paid - (isset($uncheque[$invoiceQ->invoiceId])?$uncheque[$invoiceQ->invoiceId]:0) - (isset($SameDayCollectCheque[$invoiceQ->invoiceId])?$SameDayCollectCheque[$invoiceQ->invoiceId]:0 );

                // not yet receive
                $acc1 +=  $invoiceQ->remain+(isset($uncheque[$invoiceQ->invoiceId])?$uncheque[$invoiceQ->invoiceId]:0) ;
                $this->_backaccount[] = [
                    'customerId' => $client->customerId,
                    'name' => $client->customerName_chi,
                    'invoiceNumber' => $invoiceId,
                    'accumulator' =>$acc1,
                    'amount' => number_format($invoiceQ->remain+(isset($uncheque[$invoiceQ->invoiceId])?$uncheque[$invoiceQ->invoiceId]:0) ,2,'.',','),
                ];

                /* }else if ($invoiceQ->invoiceStatus == 30){

               $paid = $invoiceQ->paid - (isset($uncheque[$invoiceQ->invoiceId])?$uncheque[$invoiceQ->invoiceId]:0) - (isset($SameDayCollectCheque[$invoiceQ->invoiceId])?$SameDayCollectCheque[$invoiceQ->invoiceId]:0 );

               if(isset($uncheque[$invoiceQ->invoiceId])){
                   //not yet receive
                   $acc1 +=  ($invoiceQ->invoiceStatus == '98')? -$invoiceQ->remain:$invoiceQ->remain+$uncheque[$invoiceQ->invoiceId];
                   $this->_backaccount[] = [
                       'customerId' => $client->customerId,
                       'name' => $client->customerName_chi,
                       'invoiceNumber' => $invoiceId,
                       'accumulator' =>number_format($acc1,2,'.',','),
                       'amount' => number_format($invoiceQ->remain+$uncheque[$invoiceQ->invoiceId],2,'.',','),
                   ];
               }
*/
            }else{
                $paid = $invoiceQ->remain;
            }

            $acc +=  $paid;

            //已收
            $this->_account[] = [
                'zoneId' =>$invoiceQ->zoneId,
                'receiveMoneyZone' =>$invoiceQ->receiveMoneyZone,
                'customerId' => $client->customerId,
                'name' => $client->customerName_chi,
                'invoiceNumber' => $invoiceId,
                'invoiceTotalAmount' => $paid ,
                'accumulator' =>$acc,
                'amount' => number_format($paid,2,'.',','),
            ];
            //end of 已收
        }

        foreach( $this->_backaccount as $k =>$v){
            if ($v['amount'] ==0){
                unset($this->_backaccount[$k]);
            }
        }

        //補收+代收+所有當天支票
        $invoicesQuery = Invoice::whereIn('invoiceStatus',['30','20','98'])->where('paymentTerms',1)->where('receiveMoneyZone', $zone);
        if($this->_shift != '-1')
            $invoicesQuery->where('shift',$this->_shift);

        $invoicesQuery = $invoicesQuery->with('client')->leftJoin('invoice_payment', function ($join) {
            $join->on('invoice_payment.invoice_id', '=', 'Invoice.invoiceId');
        })->leftJoin('payments', function ($join) {
            $join->on('invoice_payment.payment_id', '=', 'payments.id');
        })->where('receive_date', '=', date('Y-m-d',$date))->get();
        //  pd($invoicesQuery);
        //->WhereHas('payment', function($q) use($date)
        // {
        //     $q->where('receive_date', '=', date('Y-m-d',$date));
        // })->get();




        $acc = 0;
        $acc1 = 0;
        foreach($invoicesQuery as $invoiceQ)
        {


            if($invoiceQ->ref_number == 'cash' and $invoiceQ->receive_date == date('Y-m-d',$date) and ($invoiceQ->deliveryDate < $date || $invoiceQ->receiveMoneyZone != $invoiceQ->zoneId)){
                $acc +=  $invoiceQ->paid;
                $this->_invoices[] = $invoiceQ->invoiceId;
                $this->_zoneName = $invoiceQ->zone->zoneName;

                // first, store all invoices
                $invoiceId = $invoiceQ->invoiceId;
                $invoices[$invoiceId] = $invoiceQ;
                $client = $invoiceQ['client'];

                //補收+代收
                $this->_paidInvoice[] = [
                    'customerId' => $client->customerId,
                    'zoneId' =>$invoiceQ->zoneId,
                    'name' => $client->customerName_chi,
                    'deliveryDate' => date('Y-m-d',$invoiceQ->deliveryDate),
                    'invoiceNumber' => $invoiceId,
                    'invoiceTotalAmount' => $invoiceQ->paid ,
                    'accumulator' =>$acc,
                    'amount' => number_format($invoiceQ->paid,2,'.',','),
                ];
                //END OF 補收+代收

            }else if ($invoiceQ->receive_date == date('Y-m-d',$date) and $invoiceQ->ref_number != 'cash'){
                $acc1 +=  $invoiceQ->paid;
                $this->_invoices[] = $invoiceQ->invoiceId;
                $this->_zoneName = $invoiceQ->zone->zoneName;

                // first, store all invoices
                $invoiceId = $invoiceQ->invoiceId;
                $invoices[$invoiceId] = $invoiceQ;
                $client = $invoiceQ['client'];

                //所有支票
                $this->_paidInvoice_cheque[] = [
                    'customerId' => $client->customerId,
                    'name' => $client->customerName_chi,
                    'deliveryDate' => date('Y-m-d',$invoiceQ->deliveryDate),
                    'invoiceNumber' => $invoiceId,
                    'invoiceTotalAmount' => $invoiceQ->paid ,
                    'accumulator' =>$acc1,
                    'amount' => number_format($invoiceQ->paid,2,'.',','),
                    'bankCode' => $invoiceQ->bankCode,
                    'chequeNo' => $invoiceQ->ref_number,
                ];
            }


        }
        //補收+代收+所有當天支票


        $this->_expenses = expense::select('*')->where('deliveryDate',date('Y-m-d',$this->_date))->where('zoneId',$this->_zone)->first();
        if(isset($this->_expenses))
        {
            $this->_expenses->amount = $this->_expenses->cost1+$this->_expenses->cost2+$this->_expenses->cost3+$this->_expenses->cost4+$this->_expenses->cost5;
        }
        $this->data = $this->_account;
        return $this->data;

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



            $invoices = Invoice::where('paymentTerms',1)->whereIn('invoiceStatus',[20,30])->with('payment')
                ->leftJoin('invoice_payment', function ($join) {
                    $join->on('invoice_payment.invoice_id', '=', 'Invoice.invoiceId');
                })->leftJoin('payments', function ($join) {
                    $join->on('invoice_payment.payment_id', '=', 'payments.id');
                })->where('start_date', date('Y-m-d',$d))->get();


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
                    'totalAmount' => $info[$d]['totalAmount'] += $invoiceQ->amount,
                    'previous'=>isset($previous[$d])?$previous[$d]:0,
                    'paid' => $info[$d]['paid'] += $invoiceQ->paid,
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
                'type' => 'date-picker',
                'label' => '送貨日期',
                'model' => 'deliveryDate',

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
            /*[
                'type' => 'excel',
                'name' => '收帳日結表',
                'warning'   =>  false,
            ],
            [
                'type' => 'excel1',
                'name' => '收帳總結表',
                'warning'   =>  false,
            ],*/


        ];

        return $downloadSetting;
    }

    public function outputPreview()
    {
        return View::make('reports/CashReceiptSummary')->with('data', $this->_account)->with('paidInvoice',$this->_paidInvoice)->with('paidInvoiceCheque',$this->_paidInvoice_cheque)->with('backaccount',$this->_backaccount)->with('expenses',$this->_expenses)->render();
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

    /**
     * @return array
     */
    public function outputPDF()
    {

        $last1= 195;
        $last2 = 170;
        $last3 = 120;
        $last4 = 100;

        $pdf = new PDF();


        $pdf->AddFont('chi','','LiHeiProPC.ttf',true);

        // $datamart = array_chunk($this->data, 30, true);


        $pdf->AddPage();

        $this->generateHeader($pdf);


        $pdf->SetFont('chi','',12);
        $y=55;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, '應收現金:', 0, 0, "L");

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, sprintf("$%s + $%s - $%s = $%s",number_format(end($this->_account)['accumulator'],2,'.',','),number_format(end($this->_paidInvoice)['accumulator'],2,'.',','), ($this->_expenses['amount']<0)?"(".number_format($this->_expenses['amount']*-1,2,'.',',').")":$this->_expenses['amount'] , number_format(end($this->_paidInvoice)['accumulator']+end($this->_account)['accumulator']-$this->_expenses['amount'],2,'.',',')) , 0, 0, "L");

        $y+=5;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, '實收現金:', 0, 0, "L");

        $incomes = income::where('deliveryDate',date('Y-m-d',$this->_date))->where('zoneId',$this->_zone)->first();

        $cash = 0;
        $coins =0;

        if(count($incomes)>0){
            $cash = $incomes->notes;
            $coins = $incomes->coins;
        }

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, sprintf("紙幣:$%s  硬幣:$%s  總數:$%s", number_format($cash,2,'.',','),number_format($coins,2,'.',','), number_format($coins+$cash,2,'.',',')), 0, 0, "L");


        $sql = 'select count(CASE WHEN paymentTerms = 2 THEN 1 end) as count_credit,SUM(CASE WHEN paymentTerms = 2 THEN amount END) AS amount_credit, count(CASE WHEN paymentTerms = 1 THEN 1 end) as count_cod,SUM(CASE WHEN paymentTerms = 1 THEN amount END) AS amount_cod from invoice where invoiceStatus in (98,2,20,30,1,97,96) and zoneId='.$this->_zone.' and deliveryDate = '.$this->_date;
        $cod = DB::select(DB::raw($sql));
        foreach($cod as $v)
            $summary =  (array) $v;



        $y+=5;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, sprintf('月結單數:%s',$summary['count_credit']), 0, 0, "L");

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, sprintf('金額:$%s',number_format($summary['amount_credit'],2,'.',',')), 0, 0, "L");

        $y+=5;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, sprintf('現金單數:%s',$summary['count_cod']), 0, 0, "L");

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, sprintf('金額:$%s',number_format($summary['amount_cod'],2,'.',',')), 0, 0, "L");


        $y = 80;

        //98
        if(count($this->_returnInvoices) > 0){
        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0,'退貨單', 0, 0, "L");

        $pdf->SetFont('chi','',10);
        $y += 6;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, "訂單編號", 0, 0, "L");

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, "客戶", 0, 0, "L");

        $pdf->setXY($last3, $y);
        $pdf->Cell(0, 0, "送貨日期", 0, 0, "L");

        $pdf->setXY($last2, $y);
        $pdf->Cell(1, 0, "收回金額", 0, 0, "R");

        $pdf->Line(10, $y+4, 200, $y+4);

        $y += 8;


        foreach($this->_returnInvoices as $k => $v){

            $pdf->setXY(10, $y);
            $pdf->Cell(0, 0, $v['invoiceNumber'], 0, 0, "L");

            $pdf->setXY(40, $y);
            $pdf->Cell(0, 0, $v['name'], 0, 0, "L");

            $pdf->setXY($last3, $y);
            $pdf->Cell(0, 0, $v['deliveryDate'], 0, 0, "L");

            $pdf->setXY($last2, $y);
            $pdf->Cell(1, 0, $v['amount'], 0, 0, "R");

            $y += 5;

        }

        $pdf->Line(10, $y, 200, $y);
        $y+=10;
        }
        // 98



        //補收+代收
        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0,'補收及代收款項', 0, 0, "L");

        $pdf->SetFont('chi','',10);
        $y += 6;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, "訂單編號", 0, 0, "L");

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, "客戶", 0, 0, "L");

        $pdf->setXY($last3, $y);
        $pdf->Cell(0, 0, "送貨日期", 0, 0, "L");

        $pdf->setXY($last2, $y);
        $pdf->Cell(1, 0, "收回金額", 0, 0, "R");

        $pdf->setXY($last1, $y);
        $pdf->Cell(1, 0, "累計", 0, 0, "R");

        $pdf->Line(10, $y+4, 200, $y+4);

        $y += 8;


        foreach($this->_paidInvoice as $k => $v){

            $pdf->setXY(10, $y);
            $pdf->Cell(0, 0, $v['invoiceNumber'], 0, 0, "L");

            $pdf->setXY(40, $y);
            $pdf->Cell(0, 0, $v['name'], 0, 0, "L");

            $pdf->setXY($last3, $y);
            $pdf->Cell(0, 0, $v['deliveryDate'], 0, 0, "L");

            $pdf->setXY($last2, $y);
            $pdf->Cell(1, 0, $v['amount'], 0, 0, "R");

            $pdf->setXY($last1, $y);
            $pdf->Cell(1, 0, number_format($v['accumulator'],2,'.',','), 0, 0, "R");

            $y += 5;

        }

        $pdf->Line(10, $y, 200, $y);
        $y+=5;

        $pdf->SetFont('Arial','B',11);
        $pdf->setXY($last1, $y);
        $pdf->Cell(1, 0, sprintf("$%s", number_format(end($this->_paidInvoice)['accumulator'],2,'.',',')), 0, 0, "R");
        $pdf->SetFont('chi','',10);
        //end of 補收+代收

        //支出
        $y+=5;

        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0,'支出款項', 0, 0, "L");

        $pdf->SetFont('chi','',10);
        $y += 6;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, "停車場", 0, 0, "L");

        $pdf->setXY(30, $y);
        $pdf->Cell(0, 0, "隨道", 0, 0, "L");

        $pdf->setXY(50, $y);
        $pdf->Cell(0, 0, "採購貨品", 0, 0, "L");

        $pdf->setXY(70, $y);
        $pdf->Cell(0, 0, "採購貨品註解", 0, 0, "L");

        $pdf->setXY(110, $y);
        $pdf->Cell(0, 0, "雜費", 0, 0, "L");

        $pdf->setXY(120, $y);
        $pdf->Cell(0, 0, "雜費註解", 0, 0, "L");

        $pdf->setXY(160, $y);
        $pdf->Cell(0, 0, "司機", 0, 0, "L");

        $pdf->setXY(175, $y);
        $pdf->Cell(0, 0, "司機支出類型", 0, 0, "L");

        $pdf->Line(10, $y+4, 200, $y+4);

        $y += 8;



        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, '$'.$this->_expenses['cost1'], 0, 0, "L");

        $pdf->setXY(30, $y);
        $pdf->Cell(0, 0, '$'.$this->_expenses['cost2'], 0, 0, "L");

        $pdf->setXY(50, $y);
        $pdf->Cell(0, 0, '$'.$this->_expenses['cost3'], 0, 0, "L");

        $pdf->setXY(70, $y);
        $pdf->Cell(0, 0, $this->_expenses['cost3_remark'], 0, 0, "L");

        $pdf->setXY(110, $y);
        $pdf->Cell(0, 0, '$'.$this->_expenses['cost4'], 0, 0, "L");

        $pdf->setXY(120, $y);
        $pdf->Cell(0, 0, $this->_expenses['cost4_remark'], 0, 0, "L");

        $pdf->setXY(160, $y);
        $pdf->Cell(0, 0, '$'.$this->_expenses['cost5'], 0, 0, "L");


        $pdf->setXY(175, $y);
        $pdf->Cell(0, 0, $this->_expenses['cost5_remark'], 0, 0, "L");





        $y+=5;
        $pdf->Line(10, $y, 200, $y);
        $y+=5;
        $pdf->setXY($last1, $y);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(1, 0, sprintf("$%s", $this->_expenses['amount']), 0, 0, "R");
        $pdf->SetFont('chi','',10);
//支出


        //未收款項
        $y+=5;
        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0,'未收款項', 0, 0, "L");

        $pdf->SetFont('chi','',10);
        $y += 6;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, "訂單編號", 0, 0, "L");

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, "客戶", 0, 0, "L");


        $pdf->setXY($last2, $y);
        $pdf->Cell(1, 0, "尚欠金額", 0, 0, "R");

        $pdf->setXY($last1, $y);
        $pdf->Cell(1, 0, "累計", 0, 0, "R");

        $pdf->Line(10, $y+4, 200, $y+4);

        $y += 8;


        foreach($this->_backaccount as $k => $v){

            $pdf->setXY(10, $y);
            $pdf->Cell(0, 0, $v['invoiceNumber'], 0, 0, "L");

            $pdf->setXY(40, $y);
            $pdf->Cell(0, 0, $v['name'], 0, 0, "L");

            $pdf->setXY($last2, $y);
            $pdf->Cell(1, 0, $v['amount'], 0, 0, "R");

            $pdf->setXY($last1, $y);
            $pdf->Cell(1, 0, number_format($v['accumulator'],2,'.',','), 0, 0, "R");

            $y += 5;

        }

        $pdf->Line(10, $y, 200, $y);
        $y+=5;
        $pdf->setXY($last1, $y);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(1, 0, sprintf("$%s", number_format(end($this->_backaccount)['accumulator'],2,'.',',')), 0, 0, "R");
        $pdf->SetFont('chi','',10);
//未收款項完

        //支票
        if(count($this->_returnInvoices) + count($this->_paidInvoice)+count($this->_backaccount)+count($this->_paidInvoice_cheque) > 20){
            $pdf->AddPage();
            $this->generateHeader($pdf);
            $y=55;
        }else
            $y+=5;
        $pdf->SetFont('chi','',12);
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0,'支票', 0, 0, "L");

        $pdf->SetFont('chi','',10);
        $y += 6;
        $pdf->setXY(10, $y);
        $pdf->Cell(0, 0, "訂單編號", 0, 0, "L");

        $pdf->setXY(40, $y);
        $pdf->Cell(0, 0, "客戶", 0, 0, "L");

        $pdf->setXY($last4, $y);
        $pdf->Cell(0, 0, "支票號碼", 0, 0, "L");

        $pdf->setXY($last3, $y);
        $pdf->Cell(0, 0, "送貨日期", 0, 0, "L");

        $pdf->setXY($last2, $y);
        $pdf->Cell(1, 0, "收回金額", 0, 0, "R");

        $pdf->setXY($last1, $y);
        $pdf->Cell(1, 0, "累計", 0, 0, "R");

        $pdf->Line(10, $y+4, 200, $y+4);

        $y += 8;


        foreach($this->_paidInvoice_cheque as $k => $v){

            $pdf->setXY(10, $y);
            $pdf->Cell(0, 0, $v['invoiceNumber'], 0, 0, "L");

            $pdf->setXY(40, $y);
            $pdf->Cell(0, 0, $v['name'], 0, 0, "L");

            $pdf->setXY($last4, $y);
            $pdf->Cell(0, 0, $v['chequeNo'], 0, 0, "L");

            $pdf->setXY($last3, $y);
            $pdf->Cell(0, 0, $v['deliveryDate'], 0, 0, "L");

            $pdf->setXY($last2, $y);
            $pdf->Cell(1, 0, $v['amount'], 0, 0, "R");

            $pdf->setXY($last1, $y);
            $pdf->Cell(1, 0, number_format($v['accumulator'],2,'.',','), 0, 0, "R");

            $y += 5;

        }

        $pdf->Line(10, $y, 200, $y);

        $y+=5;

        $pdf->setXY($last1, $y);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(1, 0, sprintf("$%s", number_format(end($this->_paidInvoice_cheque)['accumulator'],2,'.',',')), 0, 0, "R");
        $pdf->SetFont('chi','',10);
//收支票完






        /*  foreach($datamart as $i=>$f)
          {


              $pdf->setXY(10, $pdf->h-30);
              $pdf->Cell(0, 0, "收帳人", 0, 0, "L");

              $pdf->setXY(60, $pdf->h-30);
              $pdf->Cell(0, 0, "核數人", 0, 0, "L");

              $pdf->Line(10, $pdf->h-35, 50, $pdf->h-35);
              $pdf->Line(60, $pdf->h-35, 100, $pdf->h-35);

              $pdf->setXY(500, $pdf->h-30);
              $pdf->Cell(0, 0, sprintf("頁數: %s / %s", $i+1, count($datamart)) , 0, 0, "R");

         }*/

        //  $pdf->Line(10, $y, 190, $y);
        //  $pdf->setXY(152, $y+6);
        //  $pdf->Cell(0, 0, sprintf("總數 HK$ %s", $lt), 0, 0, "L");
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