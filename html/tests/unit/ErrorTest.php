<?php
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testExecutionError(): void
    {
        nonExistentFunction(); // Это вызовет фатальную ошибку
        $this->assertTrue(true);
    }
}
?>