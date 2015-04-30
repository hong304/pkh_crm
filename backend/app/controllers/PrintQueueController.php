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

        $job = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))
                            ->where('insert_time', '>', strtotime("3 days ago"))
                            ->where('status','!=','dead:regenerated')
                             ->where('status','!=','downloaded;passive')
                            ->with('staff')->with('client')->leftJoin('Invoice', function($join) {
                                    $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
                                })

            ->where(function($query){
                $query->where('Invoice.invoiceStatus','2')
                    ->orwhere('Invoice.invoiceStatus','1')
                    //->orwhere('Invoice.invoiceStatus','4')
                    ->orwhere('Invoice.invoiceStatus','98')
                    ->orwhere('Invoice.invoiceStatus','97');
            })
                            ->orderBy('insert_time', 'desc')
                            ->get();

        $job1 = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))
            ->where('insert_time', '>', strtotime("3 days ago"))
            ->where('status','downloaded;passive')
            ->with('staff')->with('client')->leftJoin('Invoice', function($join) {
                $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
            })

            ->where(function($query){
                $query->where('Invoice.invoiceStatus','2')
                    ->orwhere('Invoice.invoiceStatus','1')
                    //->orwhere('Invoice.invoiceStatus','4')
                    ->orwhere('Invoice.invoiceStatus','98')
                    ->orwhere('Invoice.invoiceStatus','97');
            })
            ->orderBy('insert_time', 'desc')
            ->get();



        $jobs['queued'] = $job;
        $jobs['printed'] = $job1;
    //  pd($jobs);

        return Response::json($jobs);
    }


    public function printSelectedJobsWithinMyZone(){

        $jobId = Input::get('print');
        $newjobId[] = '';
        foreach ($jobId as $k=>$v)
                if($v['collect'])
                    $newjobId[] =  $v['id'];

        array_shift($newjobId);

            PrintQueue::wherein('job_id', $newjobId)->update(['target_time'=>time(),'status'=>'downloaded;passive']);


            $jobs = PrintQueue::wherein('job_id', $newjobId)->lists('invoiceId');
            if($jobs)
                $this->mergeImage($jobs);


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
                        //->orwhere('Invoice.invoiceStatus','4')
                        ->orwhere('Invoice.invoiceStatus','97')
                        ->orwhere('Invoice.invoiceStatus','98');
                })
                ->lists('Invoice.invoiceId');


           if($result){
               $this->mergeImage($result);
                Invoice::wherein('invoiceId',$result)->update(['printed'=>1]);
           }

        }
       PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))
            ->wherein('status', ['queued', 'fast-track'])
            ->update(['target_time'=>time(),'status'=>'downloaded;passive']);

      // return Response::json(['affected'=>$affected_jobs]);
    }
    
    public function rePrint()
    {
        $invoiceId = Input::get('invoiceId');
        $class = New InvoiceManipulation();
        $class->generatePrintInvoiceImage($invoiceId);
        $class->generateInvoicePDF($invoiceId,Auth::user()->id);
    }

    public function getInvoiceStatusMatchPrint(){

        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'))
            ->wherein('invoiceStatus', ['1', '3'])
            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
            ->groupBy('invoiceStatus', 'zoneId')
            ->with('zone')
            ->get();

        $summary['countInDataMart'] = 0;

        foreach($invoices as $invoice)
        {
            $summary[$invoice->invoiceStatus]['breakdown'][$invoice->zoneId] = [
                'zoneId' => $invoice->zoneId,
                'counts' => $invoice->counts,
                'zoneText' => $invoice->zone->zoneName,
            ];
            $summary[$invoice->invoiceStatus]['countInDataMart'] = (isset($summary[$invoice->invoiceStatus]['countInDataMart']) ? $summary[$invoice->invoiceStatus]['countInDataMart'] : 0) + $invoice->counts;

            $summary['countInDataMart'] += $invoice->counts;
        }

        if(!isset($summary[1]['countInDataMart']))$summary[1]['countInDataMart'] = 0;
        if(!isset($summary[3]['countInDataMart']))$summary[3]['countInDataMart'] = 0;


        return Response::json($summary);
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
            $print_log->count = count($Ids);
            $print_log->save();
    }
    
}