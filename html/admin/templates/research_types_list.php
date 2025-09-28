<div class="card">
    <div class="card-header">
        <h5>Research Types</h5>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <a href="index.php?page=research_types&action=add"
           class="btn btn-primary mb-3">
            Add New Research Type
        </a>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Cost</th>
                    <th>Age</th>
                    <th>Age Need</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($researchTypes as $rt): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rt->id); ?></td>
                    <td><?php echo htmlspecialchars($rt->title); ?></td>
                    <td><?php echo htmlspecialchars($rt->cost); ?></td>
                    <td><?php echo htmlspecialchars($rt->age); ?></td>
                    <td><?php echo $rt->age_need ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a href="index.php?page=research_types&action=edit&id=<?php echo htmlspecialchars($rt->id); ?>"
                           class="btn btn-sm btn-warning">
                            Edit
                        </a>
                        <a href="index.php?page=research_types&action=delete&id=<?php echo htmlspecialchars($rt->id); ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure?')">
                            Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
