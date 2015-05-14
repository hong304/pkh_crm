<?php

class ReportFactory{
    
    private $_reportId = "";
    private $_module;
    
    private $_filter = false;
    private $_download = false;
    private $_title = false;
    private $_compiledReport = false;

    public function __construct($reportId,$data)
    {
        $this->_reportId = $reportId;
        
        $report = Report::where('id', $this->_reportId)->firstOrFail();
        
        $module = $report->module;

        $this->_module = new $module(Input::all());
    }
    
    public function run()
    {
        // prepare filter
        $this->_prepareMenuFilter(); 
        
        // prepare title
        $this->_prepareTitle();
        
        // prepare action
        $this->_prepareAction();
        
        // prepare download function
        $this->_prepareDownload();
        
        // prepare output function
        $this->_prepareOutput();        
        
    }
    
    private function _prepareOutput()
    {
        $output = Input::get('output');
        
        if($output == 'setting')
        {
            $returnInfo = [
                'title' => $this->_title,
                'filterOptions' => $this->_filter,
                'downloadOptions' => $this->_download,
            ];
            
            echo json_encode($returnInfo);
            exit;
        }
        else 
        {
            // prepare data
            $this->_prepareData();
            if($output == 'preview')
            {
                echo $this->_module->outputPreview();
                exit;
            }
            elseif($output == 'pdf')
            {
                $function = "outputPDF";
                $reportOutput = $this->_module->outputPDF();
                
                //$filenameUn = $this->_reportId . '-' . str_random(10) . '-' . date("YmdHis");
                //$filenameUn = microtime(true);
                $filenameUn = $reportOutput['uniqueId'];
                $filename = $filenameUn . ".pdf";
                
                $path = storage_path() . '/report_archive/'.$this->_reportId.'/' . $filename;

                if(ReportArchive::where('id',$filenameUn)->count() == 0){
                    $archive = new ReportArchive();
                    $archive->id = $filenameUn;
                    $archive->report = $this->_reportId;
                    $archive->file = $path;
                    $archive->remark = $reportOutput['remark'];
                    $archive->created_by = Auth::user()->id;
                    $unid = explode("-",$reportOutput['uniqueId']);


                    if(isset($reportOutput['associates'])){

                        $neworder = json_decode($reportOutput['associates']);

                        if(isset($unid[1]) && $unid[1]>1){
                            $unid[1] -= 1;
                            $comma_separated = implode("-", $unid);
                            $chre = ReportArchive::where('id',$comma_separated)->first();
                            if(count($chre)>0){
                                $invoiceIds = json_decode(json_decode($chre->associates, true, true));
                                $neworder = array_diff($neworder,$invoiceIds);
                            }

                        }
                        $neworder = array_values($neworder);
                    }
//pd($neworder);




                    $archive->associates = isset($reportOutput['associates']) ? json_encode(json_encode($neworder)) : false;
                    $archive->save();
                }

                $pdf = $reportOutput['pdf'];
                $pdf->Output($path, "IF");
                //$pdf->Code128(10,3,$filenameUn,150,5);
                
                exit;
            }
        }
    }
    
    private function _prepareMenuFilter()
    {
        $mod = $this->_module;
        if(method_exists($mod, "registerFilter"))
        {
            $this->_filter = $mod->registerFilter();
        }
    }
    
    private function _prepareData()
    {
        $mod = $this->_module;
        
        if(method_exists($mod, "beforeCompilingResults"))
        {
            $mod->beforeCompilingResults();
        }
        
        if(method_exists($mod, "compileResults"))
        {
            $this->_compiledReport = $mod->compileResults(); 
        }
        else
        {
            App::abort(500, 'Unknown Data Results');
        }
        
        if(method_exists($mod, "afterCompilingResults"))
        {
            $mod->afterCompilingResults();
        }
    }
    
    private function _prepareTitle()
    {
        $mod = $this->_module;
        if(method_exists($mod, "registerTitle")) 
        {
            $this->_title = $mod->registerTitle();
        }
        else
        {
            App::abort(500, 'Unknown Report Title');
        }
    }
    
    private function _prepareAction()
    {
        $mod = $this->_module;
        if(method_exists($mod, "registerAction"))
        {
            
        }
    }
    
    private function _prepareDownload()
    {
        $mod = $this->_module;
        if(method_exists($mod, "registerDownload"))
        {
            $this->_download = $mod->registerDownload();
        }
    }
    
}