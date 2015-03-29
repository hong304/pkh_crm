<?php


class Analysis_product {
    
    private $_reportTitle = "";
    private $_product_id='';
    private $_uniqueid = "";
     
    public function __construct($indata)
    {
        
        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;

        if(isset($indata['query']))
            $this->_product_id = $indata['query']['product'];
        
        $this->_uniqueid = microtime(true);
    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }
    
    public function compileResults()
    {
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
//pd($accu);

if(Input::get('query.action') == 'yearend')
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
        ];
        
        return $downloadSetting;
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