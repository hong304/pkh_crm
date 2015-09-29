<?php


class Analysis_product {
    
    private $_reportTitle = "";
    private $_product_id='';
    private $_uniqueid = "";
    private $_action = '';
     
    public function __construct($indata)
    {
        if(!Auth::user()->can('view_productReport'))
            pd('Permission Denied');
        
        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;

        $this->_product_id = $indata['filterData']['productId'];
        $this->_action = isset($indata['filterData']['action']);

        $this->_uniqueid = microtime(true);
    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }
    
    public function compileResults()
    {
        $input = Input::get('filterData');

        $accu = [];
        $a_data = [];
$current_year = date('Y');
        $last_year = date('Y')-1;

        // get invoice from that date and that zone

       // $reports = data_product::with('datawarehouse_product')->where('id',$this->_product_id)->first();
        $reports = datawarehouse_product::with('data_product')->where('data_product_id',$this->_product_id)->where(function($query){
            $query->where('year',date('Y'))
                ->orWhere('year', date('Y')-1);
        })->get();

        $amount = 0;
        $qty = 0;

$product_name = $reports[0]->data_product->productName_chi;
        $product_id = $reports[0]->data_product->id;
        for($i=1;$i<=12;$i++){
            $a_data[$i] = '';
        }



        for($i=1;$i<=12;$i++){
            foreach($reports as $v){
                if($v->month == $i){
                    $a_data[$i][$v->year] = $v;
                }
            }
        }
//pd($a_data);
        foreach($a_data as $v){
            if(isset($v[$last_year])){
                 $amount += $v[$last_year]['amount'];
                 $qty += $v[$last_year]['qty'];
            }
        }

        $max = -9999999; //will hold max val
        $maxqty = -99999999;
        $maxsingle = -999999;
        $found_item = null; //will hold item with max val;

        foreach($a_data as $k=>$v)
              if(isset($v[$last_year])) {
                  if ($v[$last_year]['amount'] > $max)
                      $max = $v[$last_year]['amount'];
                  if($v[$last_year]['qty']>$maxqty)
                      $maxqty = $v[$last_year]['qty'];
                  if($v[$last_year]['amount']/$v[$last_year]['qty']>$maxsingle)
                      $maxsingle = $v[$last_year]['amount']/$v[$last_year]['qty'];
              }


        $a_data[13][$last_year] = [
            'amount' => $amount,
            'qty' => $qty,
            'month' => '',
            'highest_amount' => $max,
            'highest_qty' => $maxqty,
            'highest_single' => $maxsingle,
        ];
        $accu[13][$last_year]=$a_data[13][$last_year];

        $max = -9999999; //will hold max val
        $maxqty = -99999999;
        $maxsingle = -999999;
        $amount = 0;
        $qty = 0;


        foreach($a_data as $v){
            if(isset($v[$current_year])){
                $amount += $v[$current_year]['amount'];
                $qty += $v[$current_year]['qty'];
            }
        }

        foreach($a_data as $k=>$v)
            if(isset($v[$current_year])){
                if($v[$current_year]['amount']>$max)
                    $max = $v[$current_year]['amount'];
                if($v[$current_year]['qty']>$maxqty)
                    $maxqty = $v[$current_year]['qty'];
                if($v[$current_year]['amount']/$v[$current_year]['qty']>$maxsingle)
                    $maxsingle = $v[$current_year]['amount']/$v[$current_year]['qty'];
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
        ];
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
       $action =  isset($input['action'])?$input['action']:'';

if($action== 'yearend')
    $this->data = $accu;
        else
        $this->data = $a_data;

        ksort ($this->data );
        unset($this->data[0]);
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
            'type' => 'search_product',
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
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '產品分析');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,)
        );

        $objPHPExcel->getActiveSheet()->setCellValue('A2', '產品名稱');
        $objPHPExcel->getActiveSheet()->setCellValue('B2', $this->data[13][$current_year]['product_name'] ."(".$this->data[13][$current_year]['product_id'].")");
      //  $objPHPExcel->getActiveSheet()->setCellValue('C2', 'To');
      //  $objPHPExcel->getActiveSheet()->setCellValue('D2', date('Y-m-d',$this->_date2));


        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '月份');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $last_year.'銷量');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, $last_year.'數量');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, $last_year.'單價');

        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, $current_year.'銷量');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, $current_year.'數量');
        $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, $current_year.'單價');

        $i += 1;
        for ($k = 1; $k < 13; $k++) {
            if( isset($this->data[$k][$last_year]) && $this->data[$k][$last_year]['qty'] > 0){
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $i,"HK$ ".number_format($this->data[$k][$last_year]['amount']));
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $i,number_format($this->data[$k][$last_year]['qty']));
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, "HK$ ". number_format($this->data[$k][$last_year]['amount']/$this->data[$k][$last_year]['qty'], 2, '.', ','));

            }else{
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $i,"HK$0");
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $i,0);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, "HK$0");
            }
            $i++;
        }

        $i=4;
        $i += 1;
        for ($k = 1; $k < 13; $k++) {
            if( isset($this->data[$k][$current_year]) && $this->data[$k][$current_year]['qty'] > 0 && $k <= date('n')){
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $i,"HK$ ".number_format($this->data[$k][$current_year]['amount']));
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $i,number_format($this->data[$k][$current_year]['qty']));
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, "HK$ ". number_format($this->data[$k][$current_year]['amount']/$this->data[$k][$current_year]['qty'], 2, '.', ','));

            }else if($k > date('n')){

            }else{
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $k);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $i,"HK$0");
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $i,0);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, "HK$0");
            }
            $i++;
        }
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
        if(Input::get('query.action') == 'yearend')
            return View::make('reports/ProductReportYearEnd')->with('data', $this->data)->render();
            else
              return View::make('reports/ProductReport')->with('data', $this->data)->render();
        
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