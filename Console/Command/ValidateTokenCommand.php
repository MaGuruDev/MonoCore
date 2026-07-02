<?php
/**
 * Copyright © MaGuru. All rights reserved.
 * This module is developed for Magento® by MaGuru.
 * Magento® is a trademark of Adobe Inc.
 */
declare(strict_types=1);

namespace MaGuru\MonoCore\Console\Command;

use MaGuru\MonoCore\Api\MerchantInterface;
use MaGuru\MonoCore\Exception\ApiException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ValidateTokenCommand
 *
 * @package MaGuru\MonoCore\Console\Command
 */
class ValidateTokenCommand extends Command
{
    /**
     * @param MerchantInterface $merchant
     */
    public function __construct(
        private readonly MerchantInterface $merchant
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('mono:acquiring:validate-token')
            ->setDescription('Validate Monobank Acquiring API token by calling GET /api/merchant/details');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $details = $this->merchant->getDetails();
            $output->writeln('<info>Token is valid!</info>');
            $output->writeln("Merchant ID:   <comment>{$details->getMerchantId()}</comment>");
            $output->writeln("Merchant Name: <comment>{$details->getMerchantName()}</comment>");
            $output->writeln("EDRPOU:        <comment>{$details->getEdrpou()}</comment>");
            return Command::SUCCESS;
        } catch (ApiException $e) {
            $output->writeln('<error>Token validation failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
