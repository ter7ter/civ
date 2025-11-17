<div id="research-window-content">
    <button type="button" class="close-btn" onclick="$('#research-window').hide();">✕</button>
    <div class="research-header">
        <strong>Дерево исследований (Эра: <?=$age_show?>)</strong>
        <select id="research-age-select">
            <?php for ($i = 1; $i <= $user->age + 1; $i++): // Показываем текущую эру и следующую ?>
                <option value="<?=$i?>" <?=($age_show == $i) ? 'selected' : ''?>>Эра <?=$i?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="research-tree-container">
        <?php foreach ($research as $res): ?>
            <div class="research-item research-status-<?=$res['status']?>"
                 data-id="<?=$res['id']?>" data-status="<?=$res['status']?>"
                 data-m_top="<?=$res['m_top']?>" data-m_left="<?=$res['m_left']?>"
                 data-req='<?=json_encode(array_column($res['req'], 'id'))?>'>
                <div class="research-title"><?=$res['title']?></div>
                <?php if (isset($res['turns'])): ?>
                    <div class="research-turns"><?=$res['turns']?> ходов</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    $(document).on('change', '#research-age-select', function() {
        var selectedAge = $(this).val();
        $.post('index.php?method=research', { age: selectedAge }, function(data) {
            $('#research-window').html(data);
            $('#research-window').trigger('researchWindowShown');
        });
    });

    $(document).on('click', '.research-item[data-status="can"]', function() {
        var researchId = $(this).data('id');
        $.post('index.php?method=research', { rid: researchId, age: $('#research-age-select').val() }, function(data) {
            $('#research-window').html(data);
            $('#research-window').trigger('researchWindowShown');
        });
    });
</script>
