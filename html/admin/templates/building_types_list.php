<div class="card">
    <div class="card-header">
        <h5>Building Types</h5>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <a href="index.php?page=building_types&action=add" class="btn btn-primary mb-3">Add New Building Type</a>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Cost</th>
                    <th>Culture</th>
                    <th>Upkeep</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buildingTypes as $bt): ?>
                <tr>
                    <td><?php echo htmlspecialchars($bt->id); ?></td>
                    <td><?php echo htmlspecialchars($bt->title); ?></td>
                    <td><?php echo htmlspecialchars($bt->cost); ?></td>
                    <td><?php echo htmlspecialchars($bt->culture); ?></td>
                    <td><?php echo htmlspecialchars($bt->upkeep); ?></td>
                    <td>
                        <a href="index.php?page=building_types&action=edit&id=<?php echo htmlspecialchars($bt->id); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="index.php?page=building_types&action=delete&id=<?php echo htmlspecialchars($bt->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
