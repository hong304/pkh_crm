<?php

class ReportController extends BaseController {
    
    
    
    public function loadAvailableReports()
    {


        if (Auth::user()->role[0]->id == 4){
            $filter = ['productReport','customerReport'];
            $reports = Report::select('*')->orderBy('id', 'asc')->whereNotIn('id',$filter)->get();
        }else{
            $reports = Report::select('*')->orderBy('id', 'asc')->get();
        }



        foreach($reports as $report)
        {
            $reportCustom[$report->group]['reports'][] = $report;
            $reportCustom[$report->group]['groupName'] = $report->group;
        }

      asort($reportCustom);

        return Response::json($reportCustom);
    }
    
    
    public function loadReport()
    {

        $reportId = Input::get('reportId');
        $data = Input::all();



        $factory = new ReportFactory($reportId,$data);
        $factory->run();
        
        
    }


public function loadvanSellReport(){
    $data = Input::all();
    $data['reportId'] = 'vanselllist';
    $factory = new ReportFactory('vanselllist',$data);
    $factory->run();
}

    
    public function viewArchivedReport()
    {
        $rid = Input::get('rid');



        $report = ReportArchive::where('id', $rid)->first();

        $content = file_get_contents($report->file);

        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen( $content ));
        header('Content-disposition: inline; filename="' . $report->file . '"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

        echo $content;
        
    }
}