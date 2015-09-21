<?php $data?>
<table border ="1">  

    <tr style="position: absolute;left: 35px;top: 70px;">
        <td rowspan="1" style="text-align:center;font-size:15px;width: 295px;">日期:</td>
        <?php
        
            $totalDate = 0;
            foreach($date as $k)
            {
                if($k == date("Y-m-d"))
                     echo '<td style="padding:5px;background-color:#CCFFCC;font-size:17px;">Today</td>';
                else
                {
                    $pos = strpos($k, '-')+1;
                    $new = substr($k,$pos);
                    echo '<td style="padding:5px;font-size:20px;">' . $new . '</td>';
                }
                    
                $totalDate++;
            }
         ?>
    </tr>
   
        <?php 
            foreach($data as $k=>$v)
            {
                echo "<tr>";
                echo '<td style="padding:5px;width:295px;cursor:pointer;font-size:15px;word-wrap: break-word;"  onclick="clickPo(\''.$k.'\')">' .$k. '</td>';
           
                $count = 0;
                while($totalDate > $count)
                {
                   if(isset($v[$date[$count]])){
                       
                       if($v[$date[$count]]['mode'] == 'actual')
                       {
                           echo "<td style='padding:5px;background-color:yellow;color:white;text-align:center;'></td>";
                       }else if($v[$date[$count]]['mode'] == 'eta')    
                       {
                           echo "<td style='padding:5px;background-color:red;color:white;text-align:center;'></td>";
                       }else    
                       {
                            echo "<td style='padding:5px;background-color:green;color:black;text-align:center;'></td>";
                       }
                   }else
                   {
                        echo "<td style='padding:5px;'></td>";
                   }
                    $count++;
                }
                 echo "</tr>";              
            }
        ?>
    
</table>
   