<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Test\Unit\Model;

use MaGuru\MonoCore\Api\AcquiringClientInterface;
use MaGuru\MonoCore\Api\Data\MerchantDetailsInterface;
use MaGuru\MonoCore\Model\Merchant;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class MerchantTest
 *
 * @package MaGuru\MonoCore\Test\Unit\Model
 */
class MerchantTest extends TestCase
{
    private AcquiringClientInterface&MockObject $client;
    private Merchant $merchant;

    protected function setUp(): void
    {
        $this->client   = $this->createMock(AcquiringClientInterface::class);
        $this->merchant = new Merchant($this->client);
    }

    public function testGetDetailsReturnsPopulatedDto(): void
    {
        $this->client->method('get')
            ->with('/api/merchant/details')
            ->willReturn([
                'merchantId'   => 'mid-123',
                'merchantName' => 'Test Shop',
                'edrpou'       => '12345678',
            ]);

        $details = $this->merchant->getDetails();

        $this->assertInstanceOf(MerchantDetailsInterface::class, $details);
        $this->assertSame('mid-123', $details->getMerchantId());
        $this->assertSame('Test Shop', $details->getMerchantName());
        $this->assertSame('12345678', $details->getEdrpou());
    }

    public function testGetDetailsWithMissingFieldsReturnEmptyStrings(): void
    {
        $this->client->method('get')
            ->with('/api/merchant/details')
            ->willReturn([]);

        $details = $this->merchant->getDetails();

        $this->assertSame('', $details->getMerchantId());
        $this->assertSame('', $details->getMerchantName());
        $this->assertSame('', $details->getEdrpou());
    }

    public function testGetDetailsCallsCorrectEndpoint(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('/api/merchant/details')
            ->willReturn(['merchantId' => 'x', 'merchantName' => 'y', 'edrpou' => 'z']);

        $this->merchant->getDetails();
    }
}
