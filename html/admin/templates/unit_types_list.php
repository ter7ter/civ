<div class="card">
    <div class="card-header">
        <h5>Unit Types</h5>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <a href="index.php?page=unit_types&action=add" class="btn btn-primary mb-3">Add New Unit Type</a>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Cost</th>
                    <th>Attack</th>
                    <th>Defence</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unitTypes as $ut): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ut->id); ?></td>
                    <td><?php echo htmlspecialchars($ut->title); ?></td>
                    <td><?php echo htmlspecialchars($ut->type); ?></td>
                    <td><?php echo htmlspecialchars($ut->cost); ?></td>
                    <td><?php echo htmlspecialchars($ut->attack); ?></td>
                    <td><?php echo htmlspecialchars($ut->defence); ?></td>
                    <td>
                        <a href="index.php?page=unit_types&action=edit&id=<?php echo htmlspecialchars($ut->id); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="index.php?page=unit_types&action=delete&id=<?php echo htmlspecialchars($ut->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
