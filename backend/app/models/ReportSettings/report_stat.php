<?php


class report_stat {
    
    private $_reportTitle = "";
    private $data = '';
    private $_uniqueid = "";
     
    public function __construct($indata)
    {


        $this->_date = (isset($indata['filterData']['deliveryDate']) ? strtotime($indata['filterData']['deliveryDate']) : strtotime("today"));
        $this->_date1 = (isset($indata['filterData']['deliveryDate2']) ? strtotime($indata['filterData']['deliveryDate2']) : strtotime("today"));

    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }
    
    public function compileResults()
    {
if(Auth::user()->id == 46 || Auth::user()->id == 23){
    $sql =  "SELECT COUNT(*) AS 'updated_amount',name as 'name' FROM invoice i LEFT JOIN users c ON i.updated_by=c.id WHERE deliveryDate BETWEEN ".$this->_date." AND ".$this->_date1." AND invoiceStatus !=99 GROUP BY NAME ORDER BY COUNT(*) DESC";
    $updated_amount = DB::select(DB::raw($sql));

    $sql = "SELECT COUNT(*) AS 'created_amount',name as 'name'  FROM invoice i LEFT JOIN users c ON i.created_by=c.id WHERE deliveryDate BETWEEN ".$this->_date." AND ".$this->_date1." AND invoiceStatus !=99 GROUP BY NAME ORDER BY COUNT(*) DESC";
    $created_amount = DB::select(DB::raw($sql));

    $sql = "SELECT COUNT(*) AS 'qty', sum(amount) as 'amount', i.zoneId, zoneName FROM invoice i LEFT JOIN  zone z ON i.zoneId = z.zoneId WHERE deliveryDate BETWEEN ".$this->_date." AND ".$this->_date1." AND invoiceStatus !=99 GROUP BY zoneName ORDER BY COUNT(*) DESC";
    $zone = DB::select(DB::raw($sql));
}else{
    die('permission denied');
}
        // get invoice from that date and that zone
  //SELECT COUNT(*) AS 'created amount',NAME FROM invoice i LEFT JOIN users c ON i.`created_by`=c.`id` WHERE deliveryDate BETWEEN UNIX_TIMESTAMP('2015-06-27') AND UNIX_TIMESTAMP('2015-07-04') AND invoiceStatus !=99 GROUP BY NAME ORDER BY COUNT(*) DESC;
//SELECT COUNT(*) AS 'amount', i.zoneId, zoneName,FROM_UNIXTIME(MIN(deliveryDate)) AS 'start date' FROM `invoice` i LEFT JOIN  zone z ON i.`zoneId` = z.`zoneId` WHERE deliveryDate BETWEEN UNIX_TIMESTAMP('2015-06-27') AND UNIX_TIMESTAMP('2015-07-04') AND invoiceStatus !=99 GROUP BY zoneName ORDER BY COUNT(*) DESC;


        $this->data['updated_amount'] = $updated_amount;
        $this->data['created_amount'] = $created_amount;
        $this->data['zone'] = $zone;


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
        ];
        
        return $downloadSetting;
    }
    
    public function outputPreview()
    {

        return View::make('reports/reportstat')->with('data', $this->data)->render();
        
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