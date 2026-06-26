<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Test\Unit\Model;

use MaGuru\MonoCore\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 *
 * @package MaGuru\MonoCore\Test\Unit\Model
 */
class ConfigTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private EncryptorInterface&MockObject   $encryptor;
    private Config                          $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor   = $this->createMock(EncryptorInterface::class);
        $this->config      = new Config($this->scopeConfig, $this->encryptor);
    }

    public function testIsEnabledReturnsTrueWhenFlagIsSet(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->with('mono/core/enabled', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function testGetAcquiringTokenDecryptsValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/acquiring/api_token', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('encrypted_value');

        $this->encryptor->method('decrypt')
            ->with('encrypted_value')
            ->willReturn('plain_token');

        $this->assertSame('plain_token', $this->config->getAcquiringToken());
    }

    public function testGetAcquiringTokenReturnsEmptyStringWhenNotSet(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');
        $this->encryptor->expects($this->never())->method('decrypt');

        $this->assertSame('', $this->config->getAcquiringToken());
    }

    public function testGetChastSecretDecryptsValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                ['mono/chast/environment', ScopeInterface::SCOPE_STORE, 0, 'production'],
                ['mono/chast/secret',      ScopeInterface::SCOPE_STORE, 0, 'encrypted_secret'],
            ]);

        $this->encryptor->method('decrypt')
            ->with('encrypted_secret')
            ->willReturn('plain_secret');

        $this->assertSame('plain_secret', $this->config->getChastSecret());
    }

    public function testGetGuzzleTimeoutReturnsDefaultWhenZero(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('0');

        $this->assertSame(15, $this->config->getGuzzleTimeout());
    }

    public function testGetGuzzleTimeoutReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/core/guzzle_timeout', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('30');

        $this->assertSame(30, $this->config->getGuzzleTimeout());
    }

    public function testGetConnectTimeoutReturnsDefaultWhenZero(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('0');

        $this->assertSame(5, $this->config->getConnectTimeout());
    }

    public function testGetConnectTimeoutReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/core/connect_timeout', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('10');

        $this->assertSame(10, $this->config->getConnectTimeout());
    }

    public function testGetChastStoreIdReturnsConfiguredValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                ['mono/chast/environment', ScopeInterface::SCOPE_STORE, 0, 'production'],
                ['mono/chast/store_id',    ScopeInterface::SCOPE_STORE, 0, 'store_abc'],
            ]);

        $this->assertSame('store_abc', $this->config->getChastStoreId());
    }

    public function testGetChastSecretReturnsEmptyStringWhenNotSet(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');
        $this->encryptor->expects($this->never())->method('decrypt');

        $this->assertSame('', $this->config->getChastSecret());
    }

    public function testIsDebugLoggingEnabledReturnsTrueWhenSet(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->with('mono/core/debug_logging', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn(true);

        $this->assertTrue($this->config->isDebugLoggingEnabled());
    }

    public function testGetChastEnvironmentReturnsProductionWhenNotSet(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');

        $this->assertSame('production', $this->config->getChastEnvironment());
    }

    public function testGetChastEnvironmentReturnsProductionForUnknownValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/chast/environment', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('unknown_env');

        $this->assertSame('production', $this->config->getChastEnvironment());
    }

    public function testGetChastEnvironmentReturnsSandbox(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/chast/environment', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('sandbox');

        $this->assertSame('sandbox', $this->config->getChastEnvironment());
    }

    public function testGetChastEnvironmentReturnsStage(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/chast/environment', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('stage');

        $this->assertSame('stage', $this->config->getChastEnvironment());
    }

    public function testGetChastBaseUrlReturnsProductionUrl(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('production');

        $this->assertSame('https://u2.monobank.com.ua', $this->config->getChastBaseUrl());
    }

    public function testGetChastBaseUrlReturnsSandboxUrl(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/chast/environment', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('sandbox');

        $this->assertSame('https://u2-demo-ext.mono.st4g3.com', $this->config->getChastBaseUrl());
    }

    public function testGetChastBaseUrlReturnsStageUrl(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('mono/chast/environment', ScopeInterface::SCOPE_STORE, 0)
            ->willReturn('stage');

        $this->assertSame('https://u2-ext.mono.st4g3.com', $this->config->getChastBaseUrl());
    }

    public function testGetChastStoreIdReturnsSandboxValueWhenSandboxEnv(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                ['mono/chast/environment',    ScopeInterface::SCOPE_STORE, 0, 'sandbox'],
                ['mono/chast/store_id_sandbox', ScopeInterface::SCOPE_STORE, 0, 'test_store_with_confirm'],
            ]);

        $this->assertSame('test_store_with_confirm', $this->config->getChastStoreId());
    }

    public function testGetChastSecretReturnsSandboxValueWhenSandboxEnv(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                ['mono/chast/environment',   ScopeInterface::SCOPE_STORE, 0, 'sandbox'],
                ['mono/chast/secret_sandbox', ScopeInterface::SCOPE_STORE, 0, 'encrypted_sandbox_secret'],
            ]);
        $this->encryptor->method('decrypt')
            ->with('encrypted_sandbox_secret')
            ->willReturn('secret_98765432--123-123');

        $this->assertSame('secret_98765432--123-123', $this->config->getChastSecret());
    }

    public function testGetChastStoreIdReturnsStageValueWhenStageEnv(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                ['mono/chast/environment',    ScopeInterface::SCOPE_STORE, 0, 'stage'],
                ['mono/chast/store_id_stage', ScopeInterface::SCOPE_STORE, 0, 'stage_store_id'],
            ]);

        $this->assertSame('stage_store_id', $this->config->getChastStoreId());
    }

    public function testGetChastSecretReturnsStageValueWhenStageEnv(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                ['mono/chast/environment',  ScopeInterface::SCOPE_STORE, 0, 'stage'],
                ['mono/chast/secret_stage', ScopeInterface::SCOPE_STORE, 0, 'encrypted_stage_secret'],
            ]);
        $this->encryptor->method('decrypt')
            ->with('encrypted_stage_secret')
            ->willReturn('stage_secret_value');

        $this->assertSame('stage_secret_value', $this->config->getChastSecret());
    }
}
