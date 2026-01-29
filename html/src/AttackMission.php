<?php

namespace App;

/**
 * Стратегия для миссии атаки другого юнита.
 */
class AttackMission implements MissionCompleteInterface
{
    /**
     * Завершить выполнение миссии атаки.
     * @param Unit $unit Атакующий юнит
     * @param string|null $title (не используется в атаке)
     * @return bool
     */
    public function complete(Unit &$unit, string|false $title = false): bool
    {
        // Получаем клетку, на которой находится юнит
        $cell = Cell::get($unit->x, $unit->y, $unit->planet);
        
        // Находим юнитов другого игрока на этой клетке
        $enemyUnits = [];
        $allUnitsInCell = MyDB::query(
            "SELECT id, user_id FROM unit WHERE x = :x AND y = :y AND planet = :planet AND id != :current_unit_id",
            [
                "x" => $unit->x,
                "y" => $unit->y,
                "planet" => $unit->planet,
                "current_unit_id" => $unit->id
            ]
        );
        
        foreach ($allUnitsInCell as $unitData) {
            $otherUnit = Unit::get($unitData["id"]);
            if ($otherUnit && $otherUnit->user->id != $unit->user->id) {
                $enemyUnits[] = $otherUnit;
            }
        }
        
        if (empty($enemyUnits)) {
            return false; // Нет врагов для атаки
        }
        
        // Выбираем первого вражеского юнита для атаки
        $targetUnit = $enemyUnits[0];
        
        // Вычисляем результат боя
        $battleResult = $this->performBattle($unit, $targetUnit);
        
        // Обновляем здоровье юнитов в соответствии с результатом боя
        if ($battleResult['attacker_damage'] > 0) {
            $unit->health -= $battleResult['attacker_damage'];
        }
        
        if ($battleResult['defender_damage'] > 0) {
            $targetUnit->health -= $battleResult['defender_damage'];
        }
        
        // Сохраняем текущие координаты для возможного перемещения
        $target_x = $unit->x;
        $target_y = $unit->y;
        
        // Проверяем, нужно ли удалить юниты
        $attacker_died = false;
        $defender_died = false;
        
        if ($unit->health <= 0) {
            $unit->remove();
            $unit = null;
            $attacker_died = true;
        } else {
            $unit->save();
        }
        
        if ($targetUnit->health <= 0) {
            $targetUnit->remove();
            $targetUnit = null;
            $defender_died = true;
        } else {
            $targetUnit->save();
        }
        
        // Если атакующий юнит выжил и защищающийся погиб, атакующий перемещается на клетку
        if (!$attacker_died && $defender_died) {
            // Проверяем, остались ли другие вражеские юниты на клетке
            $remainingEnemies = [];
            $allUnitsAfterBattle = MyDB::query(
                "SELECT id, user_id FROM unit WHERE x = :x AND y = :y AND planet = :planet AND id != :current_unit_id",
                [
                    "x" => $target_x,
                    "y" => $target_y,
                    "planet" => $unit->planet,
                    "current_unit_id" => $unit->id
                ]
            );
            
            foreach ($allUnitsAfterBattle as $unitData) {
                $otherUnit = Unit::get($unitData["id"]);
                if ($otherUnit && $otherUnit->user->id != $unit->user->id) {
                    $remainingEnemies[] = $otherUnit;
                }
            }
            
            // Если больше нет вражеских юнитов на клетке, атакующий юнит перемещается на неё
            if (empty($remainingEnemies)) {
                // Юнит уже находится на нужной клетке, просто обновляем его положение
                $unit->x = $target_x;
                $unit->y = $target_y;
                $unit->save();
            }
        }
        
        return true;
    }
    
    /**
     * Провести бой между двумя юнитами
     * @param Unit $attacker Атакующий юнит
     * @param Unit $defender Защищающийся юнит
     * @return array Результат боя
     */
    private function performBattle(Unit $attacker, Unit $defender): array
    {
        // Новая боевая система: в результате боя выживает только один юнит
        // attack - сила в атаке
        // defence - сила в защите
        
        // Сила атаки атакующего юнита (берем из UnitType)
        $attackerAttack = max(1, $attacker->type->attack ?? 1);
        // Сила защиты защищающегося юнита (берем из UnitType)
        $defenderDefense = max(1, $defender->type->defence ?? 1);
        
        // Сила атаки защищающегося юнита (берем из UnitType)
        $defenderAttack = max(1, $defender->type->attack ?? 1);
        // Сила защиты атакующего юнита (берем из UnitType)
        $attackerDefense = max(1, $attacker->type->defence ?? 1);
        
        // Рассчитываем эффективную силу каждого юнита в бою
        $attackerEfficiency = $attackerAttack - $defenderDefense;
        $defenderEfficiency = $defenderAttack - $attackerDefense;
        
        // Если оба юнита имеют нулевую эффективность, побеждает случайный
        if ($attackerEfficiency <= 0 && $defenderEfficiency <= 0) {
            // Если оба слабые, шанс 50/50
            if (rand(0, 1) === 0) {
                // Побеждает атакующий
                return [
                    'attacker_damage' => 0, // Атакующий не получает урона
                    'defender_damage' => $defender->health // Защищающийся получает смертельный урон
                ];
            } else {
                // Побеждает защищающийся
                return [
                    'attacker_damage' => $attacker->health, // Атакующий получает смертельный урон
                    'defender_damage' => 0 // Защищающийся не получает урона
                ];
            }
        } elseif ($attackerEfficiency > $defenderEfficiency) {
            // Побеждает атакующий
            return [
                'attacker_damage' => 0, // Атакующий не получает урона
                'defender_damage' => $defender->health // Защищающийся получает смертельный урон
            ];
        } elseif ($defenderEfficiency > $attackerEfficiency) {
            // Побеждает защищающийся
            return [
                'attacker_damage' => $attacker->health, // Атакующий получает смертельный урон
                'defender_damage' => 0 // Защищающийся не получает урона
            ];
        } else {
            // Силы равны, побеждает случайный
            if (rand(0, 1) === 0) {
                // Побеждает атакующий
                return [
                    'attacker_damage' => 0, // Атакующий не получает урона
                    'defender_damage' => $defender->health // Защищающийся получает смертельный урон
                ];
            } else {
                // Побеждает защищающийся
                return [
                    'attacker_damage' => $attacker->health, // Атакующий получает смертельный урон
                    'defender_damage' => 0 // Защищающийся не получает урона
                ];
            }
        }
    }
}
