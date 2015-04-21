<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="10%">#</th>
            <th width="50%">Remark</th>
            <th width="10%">Creator</th>
            <th width="15%">Time</th>
            <th width="10%">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row):?> 
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['remark']; ?></td>
                <td><?php echo $row['user']['username']; ?></td>
                <td><?php echo date("Y-m-d H:i:s", $row['created_at']); ?></td>
                <td><a target="_blank" href="<?php echo $_SERVER['backend'];?>/viewArchivedReport?rid=<?php echo $row['id']; ?>">View</a></td>
            </tr>  
         <?php endforeach; ?>
    </tbody>
</table>
