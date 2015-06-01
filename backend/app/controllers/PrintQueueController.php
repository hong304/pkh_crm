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
    private $zone = '';
    private $shift = '';
   // private $temp = [];

    public function __construct()
    {

        if(isset(Auth::user()->temp_zone)){
            $this->zone = Auth::user()->temp_zone;
            $filter = Input::get('zone');
            $this->shift = Input::get('shift');
            if(isset($filter) && $filter != ''){
                $this->zone = $filter['zoneId'];
            }
            $count = PrintQueue::select('invoiceId', DB::raw('count(*) as total'))->wherein('target_path', explode(',', $this->zone))->wherein('status',['queued','fast-track'])
                ->groupBy('invoiceId')->having('total','>',1)
                ->get();

            if($count){
                foreach($count as $v){
                    $delete = PrintQueue::where('invoiceId',$v->invoiceId)->orderBy('insert_time','desc')->first();
                    $delete->delete();
                }
            }
        }
    }
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
        if($jobs) {
            $this->mergeImage($jobs);
            Invoice::wherein('invoiceId',$jobs)->update(['printed'=>1]);
        }
    }

    public function getAllPrintJobsWithinMyZone()
    {
        // list jobs that are created since 3 days ago. 



        $job =  PrintQueue::select('job_id','Invoice.invoiceId','customerName_chi','zoneId','Invoice.routePlanningPriority','PrintQueue.updated_at','deliveryDate','name','PrintQueue.status')
            ->wherein('target_path',explode(',', $this->zone))->where('insert_time', '>', strtotime("3 days ago"))
            ->where('PrintQueue.status','!=','dead:regenerated')
            ->where('PrintQueue.status','!=','downloaded;passive')
            ->leftJoin('Invoice', function($join) {
                $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
            })->leftJoin('users', function($join) {
                $join->on('users.id', '=', 'Invoice.updated_by');
            })->leftJoin('Customer', function($join) {
                $join->on('Customer.customerId', '=', 'Invoice.customerId');
            })

            ->where(function($query){
                $query->where('Invoice.invoiceStatus','2')
                    ->orwhere('Invoice.invoiceStatus','1')
                    //->orwhere('Invoice.invoiceStatus','4')
                    ->orwhere('Invoice.invoiceStatus','98')
                    ->orwhere('Invoice.invoiceStatus','96')
                    ->orwhere('Invoice.invoiceStatus','97');
            })
            ->where('Invoice.shift',$this->shift)
            ->orderBy('insert_time', 'desc')
            ->get();



        $job1 = PrintQueue::select('job_id','Invoice.invoiceId','customerName_chi','zoneId','Invoice.routePlanningPriority','PrintQueue.updated_at','deliveryDate','name','PrintQueue.status')
            ->wherein('target_path', explode(',', $this->zone))
            ->where('insert_time', '>', strtotime("1 days ago"))
            ->where('PrintQueue.status','downloaded;passive')
            ->leftJoin('Invoice', function($join) {
                $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
            })->leftJoin('users', function($join) {
                $join->on('users.id', '=', 'Invoice.updated_by');
            })->leftJoin('Customer', function($join) {
                $join->on('Customer.customerId', '=', 'Invoice.customerId');
            })

            ->where(function($query){
                $query->where('Invoice.invoiceStatus','2')
                    ->orwhere('Invoice.invoiceStatus','1')
                    //->orwhere('Invoice.invoiceStatus','4')
                    ->orwhere('Invoice.invoiceStatus','98')
                    ->orwhere('Invoice.invoiceStatus','96')
                    ->orwhere('Invoice.invoiceStatus','97');
            })
            ->where('Invoice.shift',$this->shift)
            ->orderBy('insert_time', 'desc')
            ->get();



        $jobs['queued'] = $job;
        $jobs['printed'] = $job1;
        //  pd($jobs);

        return Response::json($jobs);
    }


    public function printSelectedJobsWithinMyZone(){


        $mode = Input::get('mode');

        if($mode == 'today'){
            foreach(explode(',', $this->zone) as $k => $v){
                $result = PrintQueue::select('Invoice.invoiceId')->where('target_path',$v)->wherein('status', ['queued', 'fast-track'])
                    ->leftJoin('Invoice', function($join) {
                        $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
                    })
                    ->where(function($query){
                        $query->where('Invoice.invoiceStatus','2')
                            ->orwhere('Invoice.invoiceStatus','97')
                            ->orwhere('Invoice.invoiceStatus','96')
                            ->orwhere('Invoice.invoiceStatus','98');
                    })->where('Invoice.deliveryDate',strtotime("00:00:00"))
                    ->where('Invoice.shift',$this->shift)
                    ->lists('Invoice.invoiceId');


                if($result){
                    $this->mergeImage($result);
                    Invoice::wherein('invoiceId',$result)->update(['printed'=>1]);

                    $updatepqs = PrintQueue::where('target_path',$v)->wherein('status', ['queued', 'fast-track'])
                        ->leftJoin('Invoice', function($join) {
                            $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
                        })
                        ->where(function($query){
                            $query->where('Invoice.invoiceStatus','2')
                                ->orwhere('Invoice.invoiceStatus','97')
                                ->orwhere('Invoice.invoiceStatus','96')
                                ->orwhere('Invoice.invoiceStatus','98');
                        })->where('Invoice.deliveryDate',strtotime("00:00:00"))
                        ->where('Invoice.shift',$this->shift)
                        ->get();

                    foreach($updatepqs as $updatepq){
                        $updatepq->target_time =time();
                        $updatepq->status = 'downloaded;passive';
                        $updatepq->save();
                    }
                }


            }
        }

        if($mode == 'selected'){
            $ojobId = Input::get('print');
            $jobId[] = '';
            foreach ($ojobId as $k=>$v)
                if($v['collect'])
                    $jobId[] =  $v['id'];

            array_shift($jobId);


            $jobs = PrintQueue::wherein('job_id', $jobId)->get();

            foreach($jobs as $vv){
                $groupIds[$vv->target_path][]=$vv->invoiceId;
            }

          //  pd($groupIds);
            foreach($groupIds as $gg){
                $this->mergeImage($gg);
                Invoice::wherein('invoiceId',$gg)->update(['printed'=>1]);
            }
            PrintQueue::wherein('job_id', $jobId)->update(['target_time'=>time(),'status'=>'downloaded;passive']);

        }



    }

   /* public function printAllPrintJobsWithinMyZone()
    {
        //    if(Auth::guest())
        //      Auth::onceUsingId("46");


        foreach(explode(',', $this->zone) as $k => $v){
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
    }*/

    public function rePrint()
    {
        $invoiceId = Input::get('invoiceId');
        $class = New InvoiceManipulation();
        $class->generatePrintInvoiceImage($invoiceId);
        $class->generateInvoicePDF($invoiceId,Auth::user()->id);
    }

    public function getInvoiceStatusMatchPrint(){

        $mode = Input::get('mode');

        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'))
            ->wherein('invoiceStatus', ['1', '3'])
            ->where('shift',$this->shift)
            ->wherein('zoneId', explode(',', $this->zone))
            ->groupBy('invoiceStatus', 'zoneId');

            if($mode == 'today')
                $invoices = $invoices->where('deliveryDate',strtotime('00:00:00'));

        $invoices = $invoices->with('zone')->get();

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
        $print_log->status = 'ready_for_ftp';
        $print_log->target_path = $invoiceImage[0]->zoneId;
        $print_log->invoiceIds = implode(',',$Ids);
        $print_log->count = count($Ids);
        $print_log->shift = $this->shift;
        $print_log->save();

        $this->sendJobViaFTP($print_log->job_id);
    }

    public function sendJobViaFTP($job_id)
    {
        $job = Printlog::where('job_id', $job_id)->first();

        $ftp_user_name = 'pkh';
        $ftp_user_pass = 'pkh2015';
        $ftp_server = 'pingkeehong.asuscomm.com';
        $conn_id = ftp_connect($ftp_server);
        if(!$conn_id)
        {
            $debug = new Debug();
            $debug->content = 'Can not connect to FTP';
            $debug->save();
            DB::table('Printlogs')->where('job_id', $job->job_id)->update(['status'=>'queued']);
            die('Can not connect to FTP');
        }
        ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

        DB::table('Printlogs')->where('job_id', $job->job_id)->update(['status'=>'sending']);

        if (@ftp_put($conn_id, str_pad($job->target_path, 3, '0', STR_PAD_LEFT).'/'.$job->job_id.'-'.$job->shift.'-'.$job->count.'.pdf', $job->file_path, FTP_ASCII)) {
            $updates = ['status'=>'sent', 'complete_time'=>time()];
        } else {
            $updates = ['status'=>'queued'];
        }
        DB::table('Printlogs')->where('job_id', $job->job_id)->update($updates);
        ftp_close($conn_id);
        //var_dump(DB::getQUeryLog());
    }

}