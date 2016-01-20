<?php

class PDF extends Fpdf
{
    function Footer()
    {
        $text_string = date("l jS \of F Y h:i:s A") . '//' . $this->pageNo();
        $this->SetY(-12);
        $this->SetFont('Arial','I',6);
        // Print centered page number
        $this->Cell(0,10,$text_string,0,0,'R');
    }
}

class Report extends Eloquent{
    
    protected $table = 'Report';
    
}