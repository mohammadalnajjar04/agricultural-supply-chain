<?php
/**
 * Security Function Tests
 * Tests CSRF, input sanitization, and security utilities.
 */

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    public function testCsrfTokenGeneration(): void
    {
        // Clear any existing token
        unset($_SESSION['csrf_token']);

        $token1 = csrf_token();
        $this->assertNotEmpty($token1);
        $this->assertIsString($token1);
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
    }

    public function testCsrfTokenIsConsistent(): void
    {
        unset($_SESSION['csrf_token']);

        $token1 = csrf_token();
        $token2 = csrf_token();
        $this->assertEquals($token1, $token2);
    }

    public function testCsrfFieldContainsToken(): void
    {
        unset($_SESSION['csrf_token']);
        $token = csrf_token();

        $field = csrf_field();
        $this->assertStringContainsString('name="_csrf_token"', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString($token, $field);
    }

    public function testCsrfValidateSuccess(): void
    {
        unset($_SESSION['csrf_token']);
        $token = csrf_token();

        $_POST['_csrf_token'] = $token;
        $this->assertTrue(csrf_validate());
        unset($_POST['_csrf_token']);
    }

    public function testCsrfValidateFailure(): void
    {
        unset($_SESSION['csrf_token']);
        csrf_token(); // Generate token

        $_POST['_csrf_token'] = 'invalid_token';
        $this->assertFalse(csrf_validate());
        unset($_POST['_csrf_token']);
    }

    public function testCsrfValidateMissingToken(): void
    {
        unset($_SESSION['csrf_token']);
        csrf_token(); // Generate token

        unset($_POST['_csrf_token']);
        $this->assertFalse(csrf_validate());
    }

    public function testSanitizeString(): void
    {
        $this->assertEquals('Hello World', sanitize_string('  Hello World  '));
        $this->assertEquals('Hello', sanitize_string('<b>Hello</b>'));
        $this->assertEquals('alert', sanitize_string('<script>alert("xss")</script>alert'));
        $this->assertEquals('', sanitize_string(''));
    }

    public function testSanitizeEmail(): void
    {
        $this->assertEquals('test@example.com', sanitize_email('  test@example.com  '));
        $this->assertEquals('test@example.com', sanitize_email("test\x03@example.com"));
        $this->assertEquals('test@example.com', sanitize_email('test@example.com'));
    }

    public function testVerifyUploadSize(): void
    {
        $smallFile = ['size' => 1024];
        $largeFile = ['size' => 10 * 1024 * 1024]; // 10MB
        $emptyFile = ['size' => 0];

        $this->assertTrue(verify_upload_size($smallFile));
        $this->assertFalse(verify_upload_size($largeFile));
        $this->assertTrue(verify_upload_size($emptyFile));
    }

    public function testVerifyUploadSizeCustomLimit(): void
    {
        $file = ['size' => 2048];
        $this->assertTrue(verify_upload_size($file, 4096));
        $this->assertFalse(verify_upload_size($file, 1024));
    }
}
