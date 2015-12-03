<style>
    td
    {
        border-style: groove;
    }
    
    #shipmentSch
    {
        overflow-y:auto;
    }
    
    .adjustWidth
    {
        width:60px;
    }
</style>
<table>
<?php var_dump($actualContent);?>
<tr>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td colspan="29"><?php echo $startToEnd[0]. "     To     ". $startToEnd[1]; ?></td>
</tr>
<tr>
    <td></td>
    <td></td>
    <td></td>
    <td>每天總數:</td>
    <td>ggg</td>
</tr>
<tr>
    <td></td>
    <td></td>
    <td></td>
    <td>
        日期:
    </td>
    
<?php

    foreach($daterange as $k=>$v)
    {
        $now = date("Y-m-d");
        $date = date("m-d",strtotime($v));
        
        if($now == $v)
        {
            echo "<td class = 'adjustWidth' style='background-color:rgb(204, 255, 204);'>Today</td>";
        }else
        {
            echo "<td class = 'adjustWidth'>" . $date . "</td>" ;
        } 
    }
    
?>
</tr>

   <?php 
       foreach($actualContent as $shippingId=>$content)
       {
            $store = $shippingId;
           
            foreach($content as $day=>$containerContent)
            {
                /*foreach($daterange as $index =>$actualDay)
                {
                    if($day == $actualDay)
                    {
                        
                    }else
                    {
                        echo "<td></td>";
                    }
                }*/
                if(count($containerContent['container']) > 0)
                {
                    foreach($containerContent['container'] as $containerId=>$productContent)
                    {
                       echo "<tr><td>".$store."</td>";
                       echo "<td>".$containerId."</td>";
                     
                    }
                }
                pd("");
            }
       }
   ?>
</tr>
</table>