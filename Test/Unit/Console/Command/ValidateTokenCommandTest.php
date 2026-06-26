<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Test\Unit\Console\Command;

use MaGuru\MonoCore\Api\Data\MerchantDetailsInterface;
use MaGuru\MonoCore\Api\MerchantInterface;
use MaGuru\MonoCore\Console\Command\ValidateTokenCommand;
use MaGuru\MonoCore\Exception\ApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ValidateTokenCommandTest
 *
 * @package MaGuru\MonoCore\Test\Unit\Console\Command
 */
class ValidateTokenCommandTest extends TestCase
{
    private MerchantInterface&MockObject $merchant;
    private ValidateTokenCommand $command;

    protected function setUp(): void
    {
        $this->merchant = $this->createMock(MerchantInterface::class);
        $this->command  = new ValidateTokenCommand($this->merchant);
    }

    public function testCommandNameAndDescription(): void
    {
        $this->assertSame('mono:acquiring:validate-token', $this->command->getName());
        $this->assertNotEmpty($this->command->getDescription());
    }

    public function testExecuteSuccessOutputsMerchantDetails(): void
    {
        $details = $this->createMock(MerchantDetailsInterface::class);
        $details->method('getMerchantId')->willReturn('mid-123');
        $details->method('getMerchantName')->willReturn('Test Shop');
        $details->method('getEdrpou')->willReturn('12345678');

        $this->merchant->method('getDetails')->willReturn($details);

        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->atLeastOnce())->method('writeln');

        $result = $this->command->run($input, $output);

        $this->assertSame(0, $result);
    }

    public function testExecuteApiExceptionReturnsFailure(): void
    {
        $this->merchant->method('getDetails')
            ->willThrowException(new ApiException('Invalid token', 403));

        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')
            ->with($this->stringContains('Token validation failed'));

        $result = $this->command->run($input, $output);

        $this->assertSame(1, $result);
    }
}
