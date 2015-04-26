<?php

/*
 * Status Changes:
 * 1. Queued
 * 2. Noticed
 * 3. Sent
 * 4. Fired
 * 5. Neutralized
 */
class PrintQueueController extends BaseController {

    public $invoiceIds = [];


    public function jsonGetUnprintJobs()
    {
        Auth::onceUsingId("27");

        $returnCustom = [];
        $action = Input::get('action');

        if($action == 'update')
        {
           Printlog::wherein('job_id', explode(';', Input::get('ids')))->update(array('status' => Input::get('status')));
        }
        else
        {
            $jobs = Printlog::wherein('status', ['queued', 'fast-track'])->get();
            $returnCustom = [
                'currentTimeStamp' => time(),
                'jobs' => $jobs,
            ];

        }

        return Response::json($returnCustom);
    }

  /*  public function jsonGetUnprintJobs()
    {
        Auth::onceUsingId("27");
        
        $returnCustom = [];
        $action = Input::get('action');
        
        if($action == 'update')
        {
            DB::table('PrintQueue')->wherein('job_id', explode(';', Input::get('ids')))->update(array('status' => Input::get('status')));
        }
        else
        {
       $jobs = PrintQueue::wherein('status', ['queued', 'fast-track'])->where('target_time', '<', time())->OrderBy('file_path','desc')->get();
                        $returnCustom = [
                            'currentTimeStamp' => time(),
                            'jobs' => $jobs,
                        ];

        }







        return Response::json($returnCustom);
    } */

    //queryInvoice - 列印記錄
    public function instantPrint()
    {
        $jobId = Input::get('jobId');
        $job = PrintQueue::where('job_id', $jobId)->first();
        $job->target_time = time();
        $job->status = "downloaded;passive";
       // $job->status = "fast-track";
        $job->save();

        $jobs = PrintQueue::where('job_id', $jobId)->lists('invoiceId');
        if($jobs)
            $this->mergeImage($jobs);
    }
    
    public function getAllPrintJobsWithinMyZone()
    {
        // list jobs that are created since 3 days ago. 
      //  p(Auth::user()->temp_zone);

        $jobs = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))
                            ->where('insert_time', '>', strtotime("3 days ago"))
                            ->where('status','!=','dead:regenerated')
                            ->with('staff')->leftJoin('Invoice', function($join) {
                                    $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
                                })

            ->where(function($query){
                $query->where('Invoice.invoiceStatus','2')
                    ->orwhere('Invoice.invoiceStatus','4')
                    ->orwhere('Invoice.invoiceStatus','98')
                    ->orwhere('Invoice.invoiceStatus','97');
            })
                            ->orderBy('insert_time', 'desc')
                            ->get();
    //  pd($jobs);

        return Response::json($jobs);
    }
    
    public function printAllPrintJobsWithinMyZone()
    {
        if(Auth::guest())
            Auth::onceUsingId("46");



        $count = PrintQueue::select('invoiceId', DB::raw('count(*) as total'))->wherein('target_path', explode(',', Auth::user()->temp_zone))->wherein('status',['queued','fast-track'])
            ->groupBy('invoiceId')->having('total','>',1)
            ->get();

        if($count){
            foreach($count as $v){
                $delete = PrintQueue::where('invoiceId',$v->invoiceId)->orderBy('insert_time','desc')->first();
                $delete->delete();
            }
        }

        foreach(explode(',', Auth::user()->temp_zone) as $k => $v){
            $result = PrintQueue::select('Invoice.invoiceId')->where('target_path',$v)->where('insert_time', '>', strtotime("3 days ago"))
                ->wherein('status', ['queued', 'fast-track'])
                ->leftJoin('Invoice', function($join) {
                    $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
                })
                ->where(function($query){
                    $query->where('Invoice.invoiceStatus','2')
                        ->orwhere('Invoice.invoiceStatus','4')
                        ->orwhere('Invoice.invoiceStatus','97')
                        ->orwhere('Invoice.invoiceStatus','98');
                })
                ->lists('Invoice.invoiceId');


           if($result)
               $this->mergeImage($result);
        }
        $affected_jobs = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))
            ->wherein('status', ['queued', 'fast-track'])
            ->update(['target_time'=>time(),'status'=>'downloaded;passive']);
        //$affected_jobs = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))->update(['target_time'=>time()]);
       return Response::json(['affected'=>$affected_jobs]);
    }
    
    public function rePrint()
    {
        $invoiceId = Input::get('invoiceId');
        $class = New InvoiceManipulation();
        $class->generatePrintInvoiceImage($invoiceId);
        $class->generateInvoicePDF($invoiceId,Auth::user()->id);
    }

    public function mergeImage ($Ids){

        $invoiceImage = Invoice::select('invoicePrintImage', 'zoneId','routePlanningPriority')->whereIn('invoiceId', $Ids)->OrderBy('routePlanningPriority')->get();
        foreach($invoiceImage as $k => $v){
            $image[] = unserialize($v->invoicePrintImage);
        }

        $pagesize = "A5";
        $pdf = new Fpdf();

        foreach ($image as $k => $v){
            $section = 0;
            for($i = 1; $i <= 2; $i++)
            {
                foreach($v['print_storage'] as $index => $url)
                {


                    if($section == 0 || $section  % 2 == 0)
                    {
                        $pdf->AddPage();
                        $y = 0;

                    }

                    $pdf->Image($url, 3, $y -2, 207, 0, 'PNG');

                    // delete the image afterward
                    // @unlink($url);

                    if($pagesize == "A5")
                    {
                        $y += 148;
                    }
                    else
                    {
                        $y = 0;
                    }

                    $section++;

                }
            }
        }


        //  $this->pdf = $pdf;

        // $k = explode('-', $this->invoiceId);

        // $temp_filename = $k[0].'-'.str_pad($this->route, 2, "0", STR_PAD_LEFT).'-'.$k[1];

        $filename = 'pdf/'.Auth::user()->id.'-'.$invoiceImage[0]->zoneId.'-'.time().'.pdf';

        //$path = storage_path().'/invoices_images/'. str_replace('I', '', $k[0]) .'/'.$filename;

        $path = public_path($filename);



        $pdf->Output($path, "F");

            $print_log = new Printlog();
            $print_log->file_path = $_SERVER['backend'].'/'.$filename;
            $print_log->status = 'queued';
            $print_log->target_path = $invoiceImage[0]->zoneId;
            $print_log->invoiceIds = implode(',',$Ids);
            $print_log->save();
    }
    
}