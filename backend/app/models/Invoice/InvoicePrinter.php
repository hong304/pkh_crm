<?php


class InvoicePrinter {
    
    public function sendJobToPrinter($jobids = array())
    {
        
        $jobs = PrintQueue::select('job_id', 'target_path', 'file_path', 'invoiceId', 'status', 'complete_time', 'invoiceId')->wherein('job_id', $jobids)->get();

        // $this->sendJobViaFTP($jobs);

    }
    
    public function sendJobViaFTP($jobs)
    {
        $ftp_user_name = 'test.yatfai';
        $ftp_user_pass = 'test';
        $ftp_server = '128.199.192.177';
        $conn_id = ftp_connect($ftp_server);
        ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        
        foreach($jobs as $job)
        {
            DB::table('PrintQueue')->where('job_id', $job->job_id)->update(['status'=>'sending']);
        
            if (@ftp_put($conn_id, $job->target_path, $job->file_path, FTP_ASCII)) {
                ;
                $updates = ['status'=>'sent', 'complete_time'=>time()];
            } else {
                $updates = ['status'=>'fail'];
            }
        
            DB::table('PrintQueue')->where('job_id', $job->job_id)->update($updates);
            // syslog(date("Y-m-d H:i", time()) . $job->invoiceId .'('.$job->job_id.')' . ' - from: ' . $job->file_path . ' -to: ' . $job->target_path, LOG_INFO);
            //var_dump(DB::getQUeryLog());
        }
        
        ftp_close($conn_id);
    }

}