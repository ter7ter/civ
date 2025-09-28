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

        <form method="POST" action="index.php?page=unit_types&action=save<?php echo $id ? '&id=' . htmlspecialchars($id) : ''; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($unitType->title ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="points" class="form-label">Points</label>
                <input type="number" name="points" id="points" class="form-control" value="<?php echo htmlspecialchars($unitType->points ?? 1); ?>">
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Cost</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?php echo htmlspecialchars($unitType->cost ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="population_cost" class="form-label">Population Cost</label>
                <input type="number" name="population_cost" id="population_cost" class="form-control" value="<?php echo htmlspecialchars($unitType->population_cost ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="land" <?php echo ($unitType->type ?? 'land') == 'land' ? 'selected' : ''; ?>>Land</option>
                    <option value="water" <?php echo ($unitType->type ?? 'land') == 'water' ? 'selected' : ''; ?>>Water</option>
                    <option value="air" <?php echo ($unitType->type ?? 'land') == 'air' ? 'selected' : ''; ?>>Air</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="attack" class="form-label">Attack</label>
                <input type="number" name="attack" id="attack" class="form-control" value="<?php echo htmlspecialchars($unitType->attack ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="defence" class="form-label">Defence</label>
                <input type="number" name="defence" id="defence" class="form-control" value="<?php echo htmlspecialchars($unitType->defence ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="health" class="form-label">Health</label>
                <input type="number" name="health" class="form-control" value="<?php echo htmlspecialchars($unitType->health ?? 1); ?>">
            </div>
            <div class="mb-3">
                <label for="movement" class="form-label">Movement</label>
                <input type="number" name="movement" id="movement" class="form-control" value="<?php echo htmlspecialchars($unitType->movement ?? 1); ?>">
            </div>
            <div class="mb-3">
                <label for="upkeep" class="form-label">Upkeep</label>
                <input type="number" name="upkeep" id="upkeep" class="form-control" value="<?php echo htmlspecialchars($unitType->upkeep ?? 0); ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="can_found_city" id="can_found_city" <?php echo ($unitType->can_found_city ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="can_found_city">Can Found City</label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="can_build" id="can_build" <?php echo ($unitType->can_build ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="can_build">Can Build</label>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"><?php echo htmlspecialchars($unitType->description ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">Age</label>
                <input type="number" name="age" id="age" class="form-control" value="<?php echo htmlspecialchars($unitType->age ?? 1); ?>">
            </div>
            <div class="mb-3">
                <label for="missions" class="form-label">Missions (comma separated)</label>
                <input type="text" name="missions" id="missions" class="form-control" value="<?php echo htmlspecialchars(implode(',', $unitType->missions ?? [])); ?>">
            </div>
            <div class="mb-3">
                <label for="can_move" class="form-label">Can Move (JSON)</label>
                <textarea name="can_move" id="can_move" class="form-control"><?php echo htmlspecialchars(json_encode($unitType->can_move ?? [])); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php?page=unit_types" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
