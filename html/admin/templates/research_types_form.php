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

        <form method="POST"
              action="index.php?page=research_types&action=save<?php echo $id ? '&id=' . htmlspecialchars($id) : ''; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" id="title" class="form-control"
                       value="<?php echo htmlspecialchars($researchType->title ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Cost</label>
                <input type="number" name="cost" id="cost" class="form-control"
                       value="<?php echo htmlspecialchars($researchType->cost ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Requirements</label>
                <div id="selected-requirements">
                    <?php foreach ($researchType->requirements ?? [] as $req): ?>
                        <div class="d-inline-block bg-secondary text-dark p-1 m-1 rounded requirement-tag"
                             data-id="<?php echo htmlspecialchars($req->id); ?>">
                            <?php echo htmlspecialchars($req->title); ?>
                            <button type="button" class="btn btn-sm btn-danger remove-requirement">
                                Remove
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="input-group mb-2">
                    <select id="add-requirement-select" class="form-select">
                        <option value="">Select a requirement to add...</option>
                        <?php foreach ($researchTypes as $rt): ?>
                            <?php if (($researchType->id ?? null) !== $rt->id): ?>
                                <option value="<?php echo htmlspecialchars($rt->id); ?>">
                                    <?php echo htmlspecialchars($rt->title); ?> (ID: <?php echo htmlspecialchars($rt->id); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-requirement-btn" class="btn btn-outline-secondary">
                        Add
                    </button>
                </div>
                <div id="requirements-hidden">
                    <?php foreach ($researchType->requirements ?? [] as $req): ?>
                        <input type="hidden" name="requirements[]"
                               value="<?php echo htmlspecialchars($req->id); ?>">
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="m_top" class="form-label">Map Top</label>
                <input type="number" name="m_top" id="m_top" class="form-control"
                       value="<?php echo htmlspecialchars($researchType->m_top ?? 30); ?>">
            </div>
            <div class="mb-3">
                <label for="m_left" class="form-label">Map Left</label>
                <input type="number" name="m_left" id="m_left" class="form-control"
                       value="<?php echo htmlspecialchars($researchType->m_left ?? 0); ?>">
            </div>
            <div class="mb-3">
                <label for="age" class="form-label">Age</label>
                <input type="number" name="age" id="age" class="form-control"
                       value="<?php echo htmlspecialchars($researchType->age ?? 1); ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="age_need" id="age_need"
                       <?php echo ($researchType->age_need ?? true) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="age_need">Age Need</label>
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="index.php?page=research_types" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    function updateHiddenInputs() {
        $('#requirements-hidden').empty();
        $('#selected-requirements .requirement-tag').each(function() {
            var id = $(this).data('id');
            $('#requirements-hidden').append('<input type="hidden" name="requirements[]" value="' + id + '">');
        });
    }

    $('#add-requirement-btn').on('click', function() {
        var select = $('#add-requirement-select');
        var selectedId = select.val();
        var selectedText = select.find('option:selected').text();
        if (selectedId && $('#selected-requirements .requirement-tag[data-id="' + selectedId + '"]').length == 0) {
            $('#selected-requirements').append(
                '<div class="d-inline-block bg-secondary text-dark p-1 m-1 rounded requirement-tag" data-id="' + selectedId + '">' +
                selectedText + ' <button type="button" class="btn btn-sm btn-danger remove-requirement">Remove</button></div>'
            );
            $('#requirements-hidden').append('<input type="hidden" name="requirements[]" value="' + selectedId + '">');
            select.val('');
        }
    });

    $(document).on('click', '.remove-requirement', function() {
        var tag = $(this).closest('.requirement-tag');
        var id = tag.data('id');
        tag.remove();
        $('#requirements-hidden input[value="' + id + '"]').remove();
    });
});
</script>
