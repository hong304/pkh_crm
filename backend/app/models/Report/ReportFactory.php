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
        return $this->_prepareOutput();


        
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
                'setting' => true
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
                $reportOutput = $this->_module->outputPDF();
                if(isset($reportOutput)) {
                    $this->recordPdf($reportOutput);
                    exit;
                }
            }else if($output == 'csv'){
                $reportOutput =  $this->_module->outputCsv();
                if(isset($reportOutput)) {
                    $this->recordPdf($reportOutput);
                    exit;
                }

               return $reportOutput;
            }else if($output == 'excel'){
                $reportOutput = $this->_module->outputExcel();
                if(isset($reportOutput)) {
                    $this->recordPdf($reportOutput);
                    exit;
                }

                return $reportOutput;
            }else if($output == 'excel1'){
                $reportOutput = $this->_module->outputExcel1();
                if(isset($reportOutput)) {
                    $this->recordPdf($reportOutput);
                    exit;
                }

                return $reportOutput;
            }
        }
    }

    private function recordPdf($reportOutput){
        $filenameUn = $reportOutput['uniqueId'];
        $filename = $filenameUn . ".pdf";
        $shift = '';
        if(isset($reportOutput['shift'])) {
            $shift = $reportOutput['shift'];
            if (!file_exists(storage_path() . '/report_archive/' . $this->_reportId . '/' . $reportOutput['shift']))
                mkdir(storage_path() . '/report_archive/' . $this->_reportId . '/' . $reportOutput['shift'], 0777, true);
            $path = storage_path() . '/report_archive/' . $this->_reportId . '/' . $reportOutput['shift'] . '/' . $filename;
        }else {
            if (!file_exists(storage_path() . '/report_archive/' . $this->_reportId))
                mkdir(storage_path() . '/report_archive/' . $this->_reportId, 0777, true);
            $path = storage_path() . '/report_archive/' . $this->_reportId . '/' . $filename;
        }
        if(ReportArchive::where('id',$filenameUn)->where('shift',$shift)->count() == 0){

            $archive = new ReportArchive();
            $archive->id = $filenameUn;
            $archive->report = isset($reportOutput['reportId'])?$reportOutput['reportId']:$this->_reportId;
            $archive->file = $path;
            $archive->remark = $reportOutput['remark'];
            if(isset($reportOutput['zoneId']))
                $archive->zoneId = $reportOutput['zoneId'];
            if(isset($reportOutput['shift']))
                $archive->shift = $reportOutput['shift'];
            $archive->deliveryDate = isset($reportOutput['deliveryDate'])?$reportOutput['deliveryDate']:'';
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
            $archive->associates = isset($reportOutput['associates']) ? json_encode(json_encode($neworder)) : false;
            $archive->save();
        }

        $pdf = $reportOutput['pdf'];
        $pdf->Output($path, "IF");
        //$pdf->Code128(10,3,$filenameUn,150,5);
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