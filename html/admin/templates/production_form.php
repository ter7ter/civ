<div class="card">
    <div class="card-header">
        <h5>Создание типа производства</h5>
    </div>
    <div class="card-body">
        <form action="index.php?page=production" method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Название</label>
                <input type="text" name="title" id="title" class="form-control">
            </div>
            <div class="mb-3">
                <label for="production_time" class="form-label">Время производства</label>
                <input type="text" name="production_time" id="production_time" class="form-control" style="width: auto;">
            </div>
            <input type="hidden" name="need_items" id="need-items" value="{}">
            <div class="mb-3">
                <label class="form-label">Затраты</label>
                <div class="input-group mb-2">
                    <select id="need-items-select" class="form-select">
                        <?php foreach ($item_types as $item): ?>
                        <option value="<?php echo htmlspecialchars($item->id); ?>"><?php echo htmlspecialchars($item->getTitle()); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="need-items-amount" value="1" class="form-control" style="width: auto;">
                    <button type="button" id="need-items-add" class="btn btn-primary">Добавить</button>
                </div>
                <div id="need-items-info"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Требования</label>
                <div class="input-group mb-2">
                    <select id="required-items-select" class="form-select">
                        <?php foreach ($item_types as $item): ?>
                        <option value="<?php echo htmlspecialchars($item->id); ?>"><?php echo htmlspecialchars($item->getTitle()); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="required-items-amount" value="1" class="form-control" style="width: auto;">
                    <button type="button" id="required-items-add" class="btn btn-primary">Добавить</button>
                </div>
                <div id="required-items-info"></div>
            </div>
            <input type="hidden" name="required_items" id="required-items" value="{}">
            <input type="hidden" name="result_items">
            <input type="hidden" name="building_types">
            <button type="submit" class="btn btn-success">Сохранить</button>
        </form>
    </div>
</div>
