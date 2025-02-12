<?php

namespace Hamitovdaniil\PManager;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

trait PManager
{
    public function movePositionUp()
    {
        $rows = $this->getCollection();
        $position = 1;
        $prevRow = null;
        foreach ($rows as $row) {
            if ($row->id != $this->id) {
                $prevRow = $row;
                $row->update([$this->fieldName() => $position]);
                $position++;
                continue;
            }

            if ($prevRow) {
                $positionForPrevTariff = $position;
                $prevRow->update([$this->fieldName() => $positionForPrevTariff]);

                $positionForCurrentTariff = $position - 1;
                $row->update([$this->fieldName() => $positionForCurrentTariff]);
            }

            $position++;
        }
    }

    public function movePositionDown()
    {
        $rows = $this->getCollection();
        $position = 1;
        $prevRow = null;
        foreach ($rows as $row) {
            if ($row->id == $this->id) {
                $prevRow = $row;
                $position++;
                continue;
            }

            if ($prevRow) {
                $positionForCurrentTariff = $position;
                $prevRow->update([$this->fieldName() => $positionForCurrentTariff]);

                $positionForPrevTariff = $position - 1;
                $row->update([$this->fieldName() => $positionForPrevTariff]);

                $prevRow = null;
                $position++;
                continue;
            }

            $row->update([$this->fieldName() => $position]);

            $position++;
        }
    }
    public function moveToPosition(int $targetPosition)
    {
        $rows = $this->getCollection();

        // Проверка, что целевая позиция в допустимом диапазоне
        if ($targetPosition < 1 || $targetPosition > $rows->count()) {
            return;
        }

        // Начинаем транзакцию
        DB::beginTransaction();
        try {
            // Массив для хранения обновлений
            $updates = [];

            // Перебираем все строки
            foreach ($rows as $row) {
                if ($row->id == $this->id) {
                    // Если нашли текущий объект, обновляем его позицию
                    $updates[] = ['id' => $row->id, 'position' => $targetPosition];
                } else {
                    // Для остальных строк обновляем позицию
                    if ($row->position >= $targetPosition) {
                        // Перемещаем элементы вниз
                        $updates[] = ['id' => $row->id, 'position' => $row->position + 1];
                    } elseif ($row->position < $targetPosition) {
                        // Перемещаем элементы вверх
                        $updates[] = ['id' => $row->id, 'position' => $row->position - 1];
                    }
                }
            }

            // Выполняем обновления в базе данных
            foreach ($updates as $update) {
                $this->updatePosition($update['id'], $update['position']);
            }

            // После завершения обновлений, сортируем позиции
            $this->sortPositions();

            // Завершаем транзакцию
            DB::commit();
        } catch (\Exception $e) {
            // В случае ошибки откатываем изменения
            DB::rollBack();
            throw $e;
        }
    }

    public function updatePosition($id, $position)
    {
        // Обновляем позицию для строки с данным id
        $row = $this->getById($id);
        if ($row) {
            $row->update([$this->fieldName() => $position]);
        }
    }
    public function getById($id)
    {
        return static::find($id);
    }
    public function sortPositions()
    {
        $rows = $this->getCollection();
        $position = 1;

        // Сортируем позиции, присваивая их в порядке возрастания
        foreach ($rows as $row) {
            $row->update([$this->fieldName() => $position]);
            $position++;
        }
    }

    private function getCollection(): Collection
    {
        return static::orderBy($this->fieldName())->get();
    }

    private function fieldName(): string
    {
        return config('pmanager.field_name');
    }
}
