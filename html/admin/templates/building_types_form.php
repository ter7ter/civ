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

        <form method="POST" action="index.php?page=building_types&action=save<?php echo $id ? '&id=' . htmlspecialchars($id) : ''; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($buildingType->title ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Cost</label>
                <input type="number" name="cost" id="cost" class="form-control" value="<?php echo htmlspecialchars($buildingType->cost ?? 0); ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="need_coastal" id="need_coastal" <?php echo ($buildingType->need_coastal ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="need_coastal">Need Coastal</label>
            </div>
            <div class="mb-3">
                <label for="culture" class="form-label">Culture</label>
                <input type="number" name="culture" id="culture" class="form-control" value="<?php echo htmlspecialchars($buildingType->culture ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="upkeep" class="form-label">Upkeep</label>
                <input type="number" name="upkeep" id="upkeep" class="form-control" value="<?php echo htmlspecialchars($buildingType->upkeep ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="culture_bonus" class="form-label">Culture Bonus</label>
                <input type="number" name="culture_bonus" id="culture_bonus" class="form-control" value="<?php echo htmlspecialchars($buildingType->culture_bonus ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="research_bonus" class="form-label">Research Bonus</label>
                <input type="number" name="research_bonus" id="research_bonus" class="form-control" value="<?php echo htmlspecialchars($buildingType->research_bonus ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="money_bonus" class="form-label">Money Bonus</label>
                <input type="number" name="money_bonus" id="money_bonus" class="form-control" value="<?php echo htmlspecialchars($buildingType->money_bonus ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"><?php echo htmlspecialchars($buildingType->description ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="req_research" class="form-label">Req Research (JSON)</label>
                <textarea name="req_research" id="req_research" class="form-control"><?php echo htmlspecialchars(json_encode($buildingType->req_research ?? [])); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="req_resources" class="form-label">Req Resources (JSON)</label>
                <textarea name="req_resources" id="req_resources" class="form-control"><?php echo htmlspecialchars(json_encode($buildingType->req_resources ?? [])); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php?page=building_types" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
