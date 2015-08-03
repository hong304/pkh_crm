
<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="10%">#</th>
            <th width="8%">Zone ID</th>
            <th width="8%">Zone Name</th>
            <th width="8%">Shift</th>
            <th width="40%">Remark</th>
            <th width="10%">Creator</th>
            <th width="15%">Time</th>
            <th width="5%">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row):?> 
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['zoneId']; ?></td>
                <td><?php echo $row['zone']['zoneName']; ?></td>
                <td><?php echo $row['shift']; ?></td>
                <td><?php echo $row['remark']; ?></td>
                <td><?php echo $row['user']['username']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td><a target="_blank" href="http://backend.pingkeehong.com/viewArchivedReport?rid=<?php echo $row['id']; ?>&shift=<?php echo $row['shift'];?>">View</a></td>
            </tr>  
         <?php endforeach; ?>
    </tbody>
</table>


