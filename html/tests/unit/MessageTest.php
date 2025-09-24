<?php

/**
 * Тесты для класса Message
 */
class MessageTest extends TestBase
{
    /**
     * Тест конструктора
     */
    public function testConstruct(): void
    {
        $userData1 = $this->createTestUser(['login' => 'User1']);
        $userData2 = $this->createTestUser(['login' => 'User2']);

        $data = [
            'id' => 1,
            'from_id' => $userData1['id'],
            'to_id' => $userData2['id'],
            'text' => 'Test message',
            'type' => 'chat'
        ];

        $message = new Message($data);

        $this->assertEquals(1, $message->id);
        $this->assertEquals('Test message', $message->text);
        $this->assertEquals('chat', $message->type);
        $this->assertInstanceOf(User::class, $message->from);
        $this->assertInstanceOf(User::class, $message->to);
        $this->assertEquals('User1', $message->from->login);
        $this->assertEquals('User2', $message->to->login);
    }

    /**
     * Тест конструктора с системным сообщением
     */
    public function testConstructSystemMessage(): void
    {
        $userData = $this->createTestUser();

        $data = [
            'id' => 2,
            'to_id' => $userData['id'],
            'text' => 'System message',
            'type' => 'system'
        ];

        $message = new Message($data);

        $this->assertEquals(2, $message->id);
        $this->assertEquals('System message', $message->text);
        $this->assertEquals('system', $message->type);
        $this->assertFalse($message->from);
        $this->assertInstanceOf(User::class, $message->to);
    }

    /**
     * Тест сохранения нового сообщения
     */
    public function testSaveNew(): void
    {
        $userData1 = $this->createTestUser(['login' => 'Sender']);
        $userData2 = $this->createTestUser(['login' => 'Receiver']);

        $data = [
            'from_id' => $userData1['id'],
            'to_id' => $userData2['id'],
            'text' => 'New message',
            'type' => 'chat'
        ];

        $message = new Message($data);
        $message->save();

        $this->assertNotNull($message->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query("SELECT * FROM message WHERE id = :id", ['id' => $message->id], 'row');
        $this->assertEquals('New message', $savedData['text']);
        $this->assertEquals('chat', $savedData['type']);
        $this->assertEquals($userData1['id'], $savedData['from_id']);
        $this->assertEquals($userData2['id'], $savedData['to_id']);
    }

    /**
     * Тест сохранения системного сообщения
     */
    public function testSaveSystemMessage(): void
    {
        $userData = $this->createTestUser();

        $data = [
            'to_id' => $userData['id'],
            'text' => 'System alert',
            'type' => 'system'
        ];

        $message = new Message($data);
        $message->save();

        $this->assertNotNull($message->id);

        // Проверяем сохранение в БД
        $savedData = MyDB::query("SELECT * FROM message WHERE id = :id", ['id' => $message->id], 'row');
        $this->assertEquals('System alert', $savedData['text']);
        $this->assertEquals('system', $savedData['type']);
        $this->assertNull($savedData['from_id']);
        $this->assertEquals($userData['id'], $savedData['to_id']);
    }

    /**
     * Тест обновления существующего сообщения
     */
    public function testSaveUpdate(): void
    {
        $userData1 = $this->createTestUser(['login' => 'Sender']);
        $userData2 = $this->createTestUser(['login' => 'Receiver']);

        // Создаем сообщение
        $data = [
            'from_id' => $userData1['id'],
            'to_id' => $userData2['id'],
            'text' => 'Original message',
            'type' => 'chat'
        ];
        $message = new Message($data);
        $message->save();
        $originalId = $message->id;

        // Обновляем
        $message->text = 'Updated message';
        $message->save();

        $this->assertEquals($originalId, $message->id);

        // Проверяем обновление в БД
        $updatedData = MyDB::query("SELECT * FROM message WHERE id = :id", ['id' => $message->id], 'row');
        $this->assertEquals('Updated message', $updatedData['text']);
    }
}
