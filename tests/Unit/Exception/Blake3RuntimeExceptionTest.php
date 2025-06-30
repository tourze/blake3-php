<?php

declare(strict_types=1);

namespace Tourze\Blake3\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Exception\Blake3RuntimeException;

class Blake3RuntimeExceptionTest extends TestCase
{
    public function testInstanceOfRuntimeException(): void
    {
        $exception = new Blake3RuntimeException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testWithMessage(): void
    {
        $message = "Test runtime exception message";
        $exception = new Blake3RuntimeException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testWithMessageAndCode(): void
    {
        $message = "Test message";
        $code = 456;
        $exception = new Blake3RuntimeException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testWithPreviousException(): void
    {
        $previous = new \Exception("Previous exception");
        $exception = new Blake3RuntimeException("Current exception", 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
}