<?php
$today              = strtotime("00:00:00");
$yesterday          = strtotime("-1 day", $today);
$tomorrow = strtotime("+1 day",$today);
?>

<meta http-equiv="refresh" content="30">
<link href="http://frontend.pingkeehong.com/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
<div class="row" style="margin: 10px">
<div class="col-md-9">
<table class="table table-bordered" style="font-size:15px;">
    <tbody>
    <?php foreach($nr as $row):?>
    <tr>
        <?php foreach($row as $k => $v):?>

                      <td><?php printf('%s (%s)',$k,$v['name'])."<br/>"; ?>

                      <?php
                              foreach($v['date'] as $k1 => $v1){
                                  if($k1 == $yesterday){
                                      echo '<br/>Yday:' . $v1['volume']. "<br/>";
                                  }
                                  if($k1 == $today){
                                      echo 'Today:' . $v1['volume']. "<br/>";
                                  }
                                  if($k1 == $tomorrow){
                                      echo 'Tmr:' . $v1['volume']. "<br/>";
                                  }
                              }
                              ?>
                      </td>

        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="col-md-3">
    <table class="table table-bordered" style="font-size:15px;">

        <thead>
        <th>Total Volume</th>
        </thead>
        <tbody>


            <?php foreach($total as $k => $v):?>
            <tr>
             <td>
                    <?php

                        if($k == $yesterday){
                            echo 'Yday:' . $v['volume']. "<br/>";
                        }
                        if($k == $today){
                            echo 'Today:' . $v['volume']. "<br/>";
                        }
                        if($k == $tomorrow){
                            echo 'Tmr:' . $v['volume']. "<br/>";
                        }
             ?>
                </td>
            </tr>
            <?php endforeach; ?>


        </tbody>
    </table>
</div>
</div>