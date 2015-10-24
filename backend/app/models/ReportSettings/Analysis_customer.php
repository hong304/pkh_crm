<?php


class Analysis_customer {

    private $_reportTitle = "";
    private $_client_id='';
    private $_uniqueid = "";

    public function __construct($indata)
    {
        if(!Auth::user()->can('view_customerReport'))
            pd('Permission Denied');

        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;

        $this->_uniqueid = microtime(true);
    }

    public function registerTitle()
    {
        return $this->_reportTitle;
    }

    public function compileResults()
    {

        $input = Input::get('filterData');



        if(!isset($input['customerId']))
            $this->_client_id = '100002';
        else
            $this->_client_id = $input['customerId'];


        $accu = [];
        $a_data = [];
        $current_year = date('Y');
        $last_year = date('Y') - 1;

        // get invoice from that date and that zone

        // $reports = data_product::with('datawarehouse_product')->where('id',$this->_product_id)->first();
        $reports = datawarehouse_customer::with('Customer')->where('customer_id', $this->_client_id)->where(function ($query) {
            $query->where('year', date('Y'))
                ->orWhere('year', date('Y') - 1);
        })->get();


        $amount = 0;
        $qty = 0;

        for ($i = 1; $i <= 13; $i++) {
            $a_data[$i] = '';

        }


        if (count($reports) == 0) {
            $this->data = $a_data;
            return $this->data;
        }

        $product_name = $reports[0]->customer->customerName_chi;
        $product_id = $reports[0]->customer->customerId;
        $address = $reports[0]->customer->address_chi;
        $contact = $reports[0]->customer->phone_1;


        for ($i = 1; $i <= 12; $i++) {
            foreach ($reports as $v) {
                if ($v->month == $i) {
                    $a_data[$i][$v->year] = $v;
                }
            }
        }

        foreach ($a_data as $v) {
            if (isset($v[$last_year])) {
                $amount += $v[$last_year]['amount'];
                $qty += $v[$last_year]['qty'];
            }
        }

        $max = -9999999; //will hold max val
        $maxqty = -99999999;
        $maxsingle = -999999;
        $found_item = null; //will hold item with max val;

        foreach ($a_data as $k => $v)
            if (isset($v[$last_year])) {
                if ($v[$last_year]['amount'] > $max)
                    $max = $v[$last_year]['amount'];
                if ($v[$last_year]['qty'] > $maxqty)
                    $maxqty = $v[$last_year]['qty'];
                if ($v[$last_year]['amount'] / $v[$last_year]['qty'] > $maxsingle)
                    $maxsingle = $v[$last_year]['amount'] / $v[$last_year]['qty'];
            }


        $a_data[13][$last_year] = [
            'amount' => $amount,
            'qty' => $qty,
            'month' => '',
            'highest_amount' => $max,
            'highest_qty' => $maxqty,
            'highest_single' => $maxsingle,
        ];
        $accu[13][$last_year] = $a_data[13][$last_year];

        $max = -9999999; //will hold max val
        $maxqty = -99999999;
        $maxsingle = -999999;
        $amount = 0;
        $qty = 0;


        foreach ($a_data as $v) {
            if (isset($v[$current_year])) {
                $amount += $v[$current_year]['amount'];
                $qty += $v[$current_year]['qty'];
            }
        }

        foreach ($a_data as $k => $v)
            if (isset($v[$current_year])) {
                if ($v[$current_year]['amount'] > $max)
                    $max = $v[$current_year]['amount'];
                if ($v[$current_year]['qty'] > $maxqty)
                    $maxqty = $v[$current_year]['qty'];
                if ($v[$current_year]['amount'] / $v[$current_year]['qty'] > $maxsingle)
                    $maxsingle = $v[$current_year]['amount'] / $v[$current_year]['qty'];
            }

        $i = Invoice::with('staff', 'client')->where('customerId', $this->_client_id)->Orderby('deliveryDate', 'dest')->first();


        if (isset($i->client->created_at))
            $created_date = date('d-m-Y', strtotime($i->client->created_at));
        else
            $created_date = '';

        if (!isset($i->deliveryDate)) {
            $deliveryDate = '';
            $lasttonow = '';
        } else {
                     $deliveryDate = date('d-m-Y', $i->deliveryDate);
            $lasttonow = floor((time() - $i->deliveryDate) / 86400);
    }

            $a_data[13][$current_year] = [
            'amount' => $amount,
            'qty' => $qty,
            'month' => '',
            'product_name' => $product_name,
            'product_id' => $product_id,
            'highest_amount' => $max,
            'highest_qty' => $maxqty,
            'highest_single' => $maxsingle,
            'address' => $address,
            'contact' => $contact,

            'craete_date' => $created_date,
            'last_time' => $deliveryDate,
            'last_to_now' => $lasttonow,
            'saleman' => isset($i->staff->name)?$i->staff->name:'' ,
            'area_id' => isset($i->zoneId)?$i->zoneId: '',
            'area' => isset($i->zoneText)?$i->zoneText: '',
            'paymentTerm' => isset($i->client->paymentTermText)?$i->client->paymentTermText:''

        ];


       // pd( $a_data[13][$current_year]);

        $accu[13][$current_year]=$a_data[13][$current_year];

        for($i=0;$i<=12;$i++){
            $accu[$i][$current_year]['amount'] = 0;
            $accu[$i][$current_year]['qty'] = 0;
            $accu[$i][$last_year]['amount'] = 0;
            $accu[$i][$last_year]['qty'] = 0;
        }
        for ($i=1;$i<=12;$i++){
            if(isset($a_data[$i][$current_year]['amount'])){
                $accu[$i][$current_year]['amount'] = $accu[$i-1][$current_year]['amount'] + $a_data[$i][$current_year]['amount'];
            }
            if($accu[$i][$current_year]['amount'] == 0) $accu[$i][$current_year]['amount'] = $accu[$i-1][$current_year]['amount'];

            if(isset($a_data[$i][$current_year]['qty'])){
                $accu[$i][$current_year]['qty'] = $accu[$i-1][$current_year]['qty'] + $a_data[$i][$current_year]['qty'];
            }
            if($accu[$i][$current_year]['qty'] == 0) $accu[$i][$current_year]['qty'] =$accu[$i-1][$current_year]['qty'];

            if(isset($a_data[$i][$last_year]['amount'])){
                // if(!isset($accu[$i][$current_year]['amount']))$accu[$i][$current_year]['amount']=0;
                $accu[$i][$last_year]['amount'] = $accu[$i-1][$last_year]['amount'] + $a_data[$i][$last_year]['amount'];
            }
            if($accu[$i][$last_year]['amount'] == 0) $accu[$i][$last_year]['amount'] =$accu[$i-1][$last_year]['amount'];

            if(isset($a_data[$i][$last_year]['qty'])){
                // if(!isset($accu[$i][$current_year]['amount']))$accu[$i][$current_year]['amount']=0;
                $accu[$i][$last_year]['qty'] = $accu[$i-1][$last_year]['qty'] + $a_data[$i][$last_year]['qty'];
            }
            if($accu[$i][$last_year]['qty'] == 0) $accu[$i][$last_year]['qty'] =$accu[$i-1][$last_year]['qty'];

        }
//pd($accu)
      // pd($a_data);
        $action =  isset($input['action'])?$input['action']:'';

        if($action== 'yearend')
            $this->data = $accu;
        else
            $this->data = $a_data;



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
        $filterSetting = [
            [
                'type' => 'search_client',
                'label' => '搜尋'
            ]
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
                'type' => 'csv',
                'name' => '匯出  Excel 版本',
                'warning'   =>  false,
            ],
        ];

        return $downloadSetting;
    }

    public function outputCsv(){



        $current_year = date('Y');
        $last_year = date('Y')-1;

        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';

        $i=4;
        $objPHPExcel = new PHPExcel ();

        $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '客戶分析');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,)
        );

        $objPHPExcel->getActiveSheet()->setCellValue('A2', '客戶名稱');
        $objPHPExcel->getActiveSheet()->setCellValue('B2', $this->data[13][$current_year]['product_name'] ."(".$this->data[13][$current_year]['product_id'].")");
        //  $objPHPExcel->getActiveSheet()->setCellValue('C2', 'To');
        //  $objPHPExcel->getActiveSheet()->setCellValue('D2', date('Y-m-d',$this->_date2));


        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '月份');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $last_year.'銷量');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, $last_year.'發票量');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, $last_year.'單價');

        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, $current_year.'銷量');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, $current_year.'發票量');
        $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, $current_year.'單價');

        $i += 1;
        for ($k = 1; $k < 13; $k++) {
            if( isset($this->data[$k][$last_year]) && $this->data[$k][$last_year]['qty'] > 0){
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $i,$this->data[$k][$last_year]['amount']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $i,$this->data[$k][$last_year]['qty']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $i,$this->data[$k][$last_year]['amount']/$this->data[$k][$last_year]['qty']);

            }else{
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $i,0);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $i,0);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $i,0);
            }
            $i++;
        }

        $i=4;
        $i += 1;
        for ($k = 1; $k < 13; $k++) {
            if( isset($this->data[$k][$current_year]) && $this->data[$k][$current_year]['qty'] > 0 && $k <= date('n')){
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $i,$this->data[$k][$current_year]['amount'] );
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $i,$this->data[$k][$current_year]['qty']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $i,$this->data[$k][$current_year]['amount']/$this->data[$k][$current_year]['qty']);

            }else if($k > date('n')){

            }else{
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $i,0);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $i,0);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $i,0);
            }
            $i++;
        }

        $column = ['B','C','D','F','G','H'];

        foreach($column as $v)
            $objPHPExcel->getActiveSheet()->setCellValue($v.'18', "=SUM(".$v."6:".$v."16)");

        $objPHPExcel->getActiveSheet()
            ->getStyle('B5:B18')
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            );
        $objPHPExcel->getActiveSheet()
            ->getStyle('D5:D18')
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            );

        $objPHPExcel->getActiveSheet()
            ->getStyle('F5:F18')
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            );

        $objPHPExcel->getActiveSheet()
            ->getStyle('H5:H18')
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            );


        //    $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, '總計:');
        //  $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, sprintf("HK$ %s",end($this->data)['accumulator']));


        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }



        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$this->data[13][$current_year]['product_name'].'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');

    }

    public function outputPreview()
    {

       // pd($this->data);

        if(Input::get('query.action') == 'yearend')
            return View::make('reports/CustomerReportYearEnd')->with('data', $this->data)->render();
        else
            return View::make('reports/CustomerReport')->with('data', $this->data)->render();

    }


    # PDF Section
    public function generateHeader($pdf)
    {
    }

    public function outputPDF()
    {


        // output
        return [
        ];
    }
}