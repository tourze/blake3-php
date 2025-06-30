<?php

declare(strict_types=1);

namespace Tourze\Blake3\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Exception\Blake3InvalidArgumentException;

class Blake3InvalidArgumentExceptionTest extends TestCase
{
    public function testInstanceOfInvalidArgumentException(): void
    {
        $exception = new Blake3InvalidArgumentException();
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testWithMessage(): void
    {
        $message = "Test invalid argument message";
        $exception = new Blake3InvalidArgumentException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testWithMessageAndCode(): void
    {
        $message = "Test message";
        $code = 123;
        $exception = new Blake3InvalidArgumentException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testWithPreviousException(): void
    {
        $previous = new \Exception("Previous exception");
        $exception = new Blake3InvalidArgumentException("Current exception", 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
}