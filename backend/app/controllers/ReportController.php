<?php

class ReportController extends BaseController {
    
    
    
    public function loadAvailableReports()
    {


        if (Auth::user()->role[0]->id == 4){
            $filter = ['archivedreport','productReport','customerReport'];
            $reports = Report::select('*')->orderBy('id', 'asc')->whereNotIn('id',$filter)->get();
        }else{
            $reports = Report::select('*')->orderBy('id', 'asc')->get();
        }



        foreach($reports as $report)
        {
            $reportCustom[$report->group]['reports'][] = $report;
            $reportCustom[$report->group]['groupName'] = $report->group;
        }
        return Response::json($reportCustom);
    }
    
    
    public function loadReport()
    {

        $reportId = Input::get('reportId');
        $data = Input::all();
        $factory = new ReportFactory($reportId,$data);
        $factory->run();
        
        
    }
    
    public function viewArchivedReport()
    {
        $rid = Input::get('rid');
        
        $report = ReportArchive::where('id', $rid)->first();

        $url = CloudStorageTools::getPublicUrl($report->file, false);
        
        return Redirect::to($url);
        
    }
}