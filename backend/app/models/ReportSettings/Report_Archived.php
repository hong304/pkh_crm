<?php


class Report_Archived { 
    
    private $_reportTitle = "";

    private $_uniqueid = "";
     
    public function __construct($indata)
    {
        
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
        
        // get invoice from that date and that zone

                
        $reports = ReportArchive::select('*');
        
        if(!Auth::user()->can('view_global_reportarchive'))
        {
            $reports = $reports->where('created_by', Auth::user()->id);
        }
        
        $reports = $reports->take(30)->orderby('id', 'desc')->get();
        
        
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
        $filterSetting = [
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