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
                <label class="form-label">Req Research</label>
                <div id="selected-req-research">
                    <?php foreach ($buildingType->req_research ?? [] as $req): ?>
                        <div class="d-inline-block bg-secondary text-dark p-1 m-1 rounded req-research-tag"
                             data-id="<?php echo htmlspecialchars($req->id); ?>"
                             data-title="<?php echo htmlspecialchars($req->title); ?> (ID: <?php echo htmlspecialchars($req->id); ?>)">
                            <?php echo htmlspecialchars($req->title); ?>
                            <button type="button" class="btn btn-sm btn-danger remove-req-research">
                                Remove
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="input-group mb-2">
                    <select id="add-req-research-select" class="form-select">
                        <option value="">Select a research to add...</option>
                        <?php
                        $selectedResearchIds = [];
                        if (isset($buildingType->req_research) && is_array($buildingType->req_research)) {
                            foreach ($buildingType->req_research as $req) {
                                $selectedResearchIds[] = $req->id;
                            }
                        }
                        ?>
                        <?php foreach ($researchTypes as $rt): ?>
                            <?php if (!in_array($rt->id, $selectedResearchIds)): ?>
                                <option value="<?php echo htmlspecialchars($rt->id); ?>">
                                    <?php echo htmlspecialchars($rt->title); ?> (ID: <?php echo htmlspecialchars($rt->id); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-req-research-btn" class="btn btn-outline-secondary">
                        Add
                    </button>
                </div>
                <div id="req-research-hidden">
                    <?php foreach ($buildingType->req_research ?? [] as $req): ?>
                        <input type="hidden" name="req_research[]"
                               value="<?php echo htmlspecialchars($req->id); ?>">
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Req Resources</label>
                <div id="selected-req-resources">
                    <?php foreach ($buildingType->req_resources ?? [] as $req): ?>
                        <div class="d-inline-block bg-secondary text-dark p-1 m-1 rounded req-resources-tag"
                             data-id="<?php echo htmlspecialchars($req->id); ?>"
                             data-title="<?php echo htmlspecialchars($req->title); ?> (ID: <?php echo htmlspecialchars($req->id); ?>)">
                            <?php echo htmlspecialchars($req->title); ?>
                            <button type="button" class="btn btn-sm btn-danger remove-req-resources">
                                Remove
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="input-group mb-2">
                    <select id="add-req-resources-select" class="form-select">
                        <option value="">Select a resource to add...</option>
                        <?php
                        $selectedResourceIds = [];
                        if (isset($buildingType->req_resources) && is_array($buildingType->req_resources)) {
                            foreach ($buildingType->req_resources as $req) {
                                $selectedResourceIds[] = $req->id;
                            }
                        }
                        ?>
                        <?php foreach ($resourceTypes as $rt): ?>
                            <?php if (!in_array($rt->id, $selectedResourceIds)): ?>
                                <option value="<?php echo htmlspecialchars($rt->id); ?>">
                                    <?php echo htmlspecialchars($rt->title); ?> (ID: <?php echo htmlspecialchars($rt->id); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-req-resources-btn" class="btn btn-outline-secondary">
                        Add
                    </button>
                </div>
                <div id="req-resources-hidden">
                    <?php foreach ($buildingType->req_resources ?? [] as $req): ?>
                        <input type="hidden" name="req_resources[]"
                               value="<?php echo htmlspecialchars($req->id); ?>">
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php?page=building_types" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    function updateReqResearchHiddenInputs() {
        $('#req-research-hidden').empty();
        $('#selected-req-research .req-research-tag').each(function() {
            var id = $(this).data('id');
            $('#req-research-hidden').append('<input type="hidden" name="req_research[]" value="' + id + '">');
        });
    }

    $('#add-req-research-btn').on('click', function() {
        var select = $('#add-req-research-select');
        var selectedId = select.val();
        var selectedText = select.find('option:selected').text();
        if (selectedId && $('#selected-req-research .req-research-tag[data-id="' + selectedId + '"]').length == 0) {
            $('#selected-req-research').append(
                '<div class="d-inline-block bg-secondary text-dark p-1 m-1 rounded req-research-tag" data-id="' + selectedId + '" data-title="' + selectedText + '">' +
                selectedText + ' <button type="button" class="btn btn-sm btn-danger remove-req-research">Remove</button></div>'
            );
            $('#req-research-hidden').append('<input type="hidden" name="req_research[]" value="' + selectedId + '">');
            select.find('option[value="' + selectedId + '"]').remove();
            select.val('');
        }
    });

    $(document).on('click', '.remove-req-research', function() {
        var tag = $(this).closest('.req-research-tag');
        var id = tag.data('id');
        var title = tag.data('title');
        tag.remove();
        $('#req-research-hidden input[value="' + id + '"]').remove();
        $('#add-req-research-select').append('<option value="' + id + '">' + title + '</option>');
    });

    $('#add-req-resources-btn').on('click', function() {
        var select = $('#add-req-resources-select');
        var selectedId = select.val();
        var selectedText = select.find('option:selected').text();
        if (selectedId && $('#selected-req-resources .req-resources-tag[data-id="' + selectedId + '"]').length == 0) {
            $('#selected-req-resources').append(
                '<div class="d-inline-block bg-secondary text-dark p-1 m-1 rounded req-resources-tag" data-id="' + selectedId + '" data-title="' + selectedText + '">' +
                selectedText + ' <button type="button" class="btn btn-sm btn-danger remove-req-resources">Remove</button></div>'
            );
            $('#req-resources-hidden').append('<input type="hidden" name="req_resources[]" value="' + selectedId + '">');
            select.find('option[value="' + selectedId + '"]').remove();
            select.val('');
        }
    });

    $(document).on('click', '.remove-req-resources', function() {
        var tag = $(this).closest('.req-resources-tag');
        var id = tag.data('id');
        var title = tag.data('title');
        tag.remove();
        $('#req-resources-hidden input[value="' + id + '"]').remove();
        $('#add-req-resources-select').append('<option value="' + id + '">' + title + '</option>');
    });
});
</script>
