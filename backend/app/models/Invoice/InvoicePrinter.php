<?php


class InvoicePrinter {
    
    public function sendJobToPrinter()
    {
        
        $jobs = Printlog::where('job_id', '256')->get();

       $this->sendJobViaFTP($jobs);

    }
    
    public function sendJobViaFTP($jobs)
    {
        $ftp_user_name = 'pkh';
        $ftp_user_pass = 'pkh2015';
        $ftp_server = '192.168.1.199';
        $conn_id = ftp_connect($ftp_server);
        ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        
        foreach($jobs as $job)
        {
            DB::table('Printlogs')->where('job_id', $job->job_id)->update(['status'=>'sending']);
        
            if (@ftp_put($conn_id, '000/a.pdf', $job->file_path, FTP_ASCII)) {
                ;
                $updates = ['status'=>'sent', 'complete_time'=>time()];
            } else {
                $updates = ['status'=>'fail'];
            }
        
            DB::table('Printlogs')->where('job_id', $job->job_id)->update($updates);
            // syslog(date("Y-m-d H:i", time()) . $job->invoiceId .'('.$job->job_id.')' . ' - from: ' . $job->file_path . ' -to: ' . $job->target_path, LOG_INFO);
            //var_dump(DB::getQUeryLog());
        }
        
        ftp_close($conn_id);
    }

}