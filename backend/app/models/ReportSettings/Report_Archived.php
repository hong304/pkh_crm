<?php


class Report_Archived { 
    
    private $_reportTitle = "";
    private $data = '';
    private $_uniqueid = "";
     
    public function __construct($indata)
    {

        $report = Report::where('id', $indata['reportId'])->first();
        $this->_reportTitle = $report->name;
        $this->_shift =  (isset($indata['filterData']['shift']['value']))?$indata['filterData']['shift']['value']:'-1';
        if(isset( $indata['filterData']['zone']) && $indata['filterData']['zone']['value'] != '-1'){

            $this->_zone =  $indata['filterData']['zone']['value'];
            if(!in_array($this->_zone, explode(',', Auth::user()->temp_zone)))
            {
                App::abort(401, "Unauthorized Zone");
            }
        }else{
            $this->_zone =  Auth::user()->temp_zone;
        }
        
        $this->_uniqueid = microtime(true);
    }
    
    public function registerTitle() 
    {
        return $this->_reportTitle;
    }
    
    public function compileResults()
    {
        
        // get invoice from that date and that zone

        $reports = ReportArchive::select('*')->wherein('zoneId',explode(',', $this->_zone));
        
      /*  if(!Auth::user()->can('view_global_reportarchive'))
        {
            $reports = $reports->where('created_by', Auth::user()->id);
        }*/

        if($this->_shift != '-1')
            $reports->where('shift',$this->_shift);

        $reports = $reports->orderby('created_at', 'desc')->with('zone')->paginate(30);;

        $this->data = $reports;

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
        array_unshift($availablezone,['value'=>'-1','label'=>'檢視全部']);
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

        return View::make('reports/ArchivedReport')->with('data', $this->data)->render();
        
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