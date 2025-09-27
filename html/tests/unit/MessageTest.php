<?php

namespace App\Tests;

use App\Message;
use App\User;
use App\MyDB;

/**
 * Тесты для класса Message
 */
class MessageTest extends TestBase
{
    /**
     * Тест конструктора Message с полными данными
     */
    public function testConstructorWithFullData(): void
    {
        $game = $this->createTestGame();
        $userFrom = $this->createTestUser(['game' => $game->id]);
        $userTo = $this->createTestUser(['game' => $game->id]);

        $data = [
            'id' => 1,
            'from_id' => $userFrom->id,
            'to_id' => $userTo->id,
            'text' => 'Test message',
            'type' => 'chat',
        ];

        $message = new Message($data);

        $this->assertEquals(1, $message->id);
        $this->assertInstanceOf(User::class, $message->from);
        $this->assertEquals($userFrom->id, $message->from->id);
        $this->assertInstanceOf(User::class, $message->to);
        $this->assertEquals($userTo->id, $message->to->id);
        $this->assertEquals('Test message', $message->text);
        $this->assertEquals('chat', $message->type);
    }

    /**
     * Тест конструктора Message без отправителя
     */
    public function testConstructorWithoutFrom(): void
    {
        $game = $this->createTestGame();
        $userTo = $this->createTestUser(['game' => $game->id]);

        $data = [
            'id' => 2,
            'to_id' => $userTo->id,
            'text' => 'System message',
            'type' => 'system',
        ];

        $message = new Message($data);

        $this->assertEquals(2, $message->id);
        $this->assertFalse($message->from);
        $this->assertInstanceOf(User::class, $message->to);
        $this->assertEquals($userTo->id, $message->to->id);
        $this->assertEquals('System message', $message->text);
        $this->assertEquals('system', $message->type);
    }

    /**
     * Тест конструктора Message без получателя
     */
    public function testConstructorWithoutTo(): void
    {
        $game = $this->createTestGame();
        $userFrom = $this->createTestUser(['game' => $game->id]);

        $data = [
            'id' => 3,
            'from_id' => $userFrom->id,
            'text' => 'Broadcast message',
            'type' => 'broadcast',
        ];

        $message = new Message($data);

        $this->assertEquals(3, $message->id);
        $this->assertInstanceOf(User::class, $message->from);
        $this->assertEquals($userFrom->id, $message->from->id);
        $this->assertFalse($message->to);
        $this->assertEquals('Broadcast message', $message->text);
        $this->assertEquals('broadcast', $message->type);
    }

    /**
     * Тест сохранения нового сообщения
     */
    public function testSaveNew(): void
    {
        $game = $this->createTestGame();
        $userFrom = $this->createTestUser(['game' => $game->id]);
        $userTo = $this->createTestUser(['game' => $game->id]);

        $data = [
            'from_id' => $userFrom->id,
            'to_id' => $userTo->id,
            'text' => 'New message to save',
            'type' => 'chat',
        ];

        $message = new Message($data);
        $message->save();

        $this->assertNotNull($message->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM message WHERE id = :id",
            ["id" => $message->id],
            "row"
        );
        $this->assertNotNull($savedData);
        $this->assertEquals($userFrom->id, $savedData['from_id']);
        $this->assertEquals($userTo->id, $savedData['to_id']);
        $this->assertEquals('New message to save', $savedData['text']);
        $this->assertEquals('chat', $savedData['type']);
    }

    /**
     * Тест обновления существующего сообщения
     */
    public function testSaveUpdate(): void
    {
        $game = $this->createTestGame();
        $userFrom = $this->createTestUser(['game' => $game->id]);
        $userTo = $this->createTestUser(['game' => $game->id]);

        // Создаем сообщение через БД
        $messageId = MyDB::insert('message', [
            'from_id' => $userFrom->id,
            'to_id' => $userTo->id,
            'text' => 'Original message',
            'type' => 'chat',
        ]);

        $data = [
            'id' => $messageId,
            'from_id' => $userFrom->id,
            'to_id' => $userTo->id,
            'text' => 'Original message',
            'type' => 'chat',
        ];

        $message = new Message($data);
        $message->text = 'Updated message';
        $message->save();

        // Проверяем обновление в БД
        $updatedData = MyDB::query(
            "SELECT * FROM message WHERE id = :id",
            ["id" => $message->id],
            "row"
        );
        $this->assertEquals('Updated message', $updatedData['text']);
        $this->assertEquals($userFrom->id, $updatedData['from_id']);
        $this->assertEquals($userTo->id, $updatedData['to_id']);
    }

    /**
     * Тест сохранения системного сообщения без отправителя
     */
    public function testSaveSystemMessage(): void
    {
        $game = $this->createTestGame();
        $userTo = $this->createTestUser(['game' => $game->id]);

        $data = [
            'to_id' => $userTo->id,
            'text' => 'System notification',
            'type' => 'system',
        ];

        $message = new Message($data);
        $message->save();

        $this->assertNotNull($message->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query(
            "SELECT * FROM message WHERE id = :id",
            ["id" => $message->id],
            "row"
        );
        $this->assertNull($savedData['from_id']);
        $this->assertEquals($userTo->id, $savedData['to_id']);
        $this->assertEquals('System notification', $savedData['text']);
        $this->assertEquals('system', $savedData['type']);
    }

    /**
     * Тест конструктора с минимальными данными
     */
    public function testConstructorMinimalData(): void
    {
        $data = [
            'text' => 'Minimal message',
            'type' => 'info',
        ];

        $message = new Message($data);

        $this->assertNull($message->id);
        $this->assertFalse($message->from);
        $this->assertFalse($message->to);
        $this->assertEquals('Minimal message', $message->text);
        $this->assertEquals('info', $message->type);
    }
}
