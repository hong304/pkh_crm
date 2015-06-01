<?php

class ReportController extends BaseController {
    
    
    
    public function loadAvailableReports()
    {
        /*
        2 = SA
        3 = Manager
        4 = Sales
        5 = Supervisor
        */

        if (Auth::user()->role[0]->id == 4){
            $filter = ['productReport','customerReport','commission'];
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

    public function getPrintLog(){


            $mode = Input::get('mode');


        if($mode == 'reprint'){
            $update = Printlog::where('job_id',Input::get('filterData'))->first()->toArray();
            unset($update['job_id']);
            unset($update['created_at']);
            unset($update['updated_at']);
            unset($update['complete_time']);

            $update['status'] = 'ready_for_ftp';
            $update['created_at'] = new \DateTime;
            $update['updated_at'] = new \DateTime;

            DB::table('Printlogs')->insert(
                $update
            );

            $job_id = DB::getPdo()->lastInsertId();

            $class = new PrintQueueController();
            return $class->sendJobViaFTP($job_id);
        }
            if($mode == 'collection')
            {
                Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);

                $filter = Input::get('filterData');


                $Printlogs = Printlog::select('*')->where('updated_at','LIKE',$filter['onedate'].'%');

                // zone
                $permittedZone = explode(',', Auth::user()->temp_zone);

                if($filter['zone'] != '')
                {
                    // check if zone is within permission
                    if(!in_array($filter['zone']['zoneId'], $permittedZone))
                    {
                        // *** status code to be updated
                        App::abort(404);
                    }
                    else
                    {
                        $Printlogs->where('target_path', $filter['zone']['zoneId']);
                    }
                }
                else
                {
                    $Printlogs->wherein('target_path', $permittedZone);
                }

                // created by
                   $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
                $Printlogs = $Printlogs->with('zone')->orderBy('updated_at','desc')->paginate($page_length);

                foreach($Printlogs as $v)
                {
                    $v->view = '<a href="'.$v->file_path.'" target="_blank">View</a>';
                    $v->link = '<span onclick="reprint(\''.$v->job_id.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 重印</span>';
                }

                return Response::json($Printlogs);
            }




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
        $shift = Input::get('shift');



        $report = ReportArchive::where('id', $rid);
        if($shift!='')
            $report->where('shift',$shift);

        $report= $report->first();

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