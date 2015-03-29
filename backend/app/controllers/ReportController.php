<?php
use google\appengine\api\cloud_storage\CloudStorageTools;
class ReportController extends BaseController {
    
    
    
    public function loadAvailableReports()
    {
        $reports = Report::select('*')->orderBy('id', 'asc')->get();
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