<?php

namespace Gundo\ProductInfoAgent\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Gundo\ProductInfoAgent\Model\ProductInfoAgent;

class ModuleTestCreate extends Command
{
    /**
     * @var ProductInfoAgent
     */
    protected ProductInfoAgent $productInfoAgent;

    public function __construct(ProductInfoAgent $productInfoAgent)
    {
        $this->productInfoAgent = $productInfoAgent;
        parent::__construct();
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('app:agent:insert');
        $this->setDescription('Test');
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = [
            'message' => 'Test message',
            'user_id' => 1,
            'data' => 'Test model',
            'product_id' => 12,
            'updated_at' => Date('Y-m-d H:i:s'),
            'created_at' => Date('Y-m-d H:i:s')
        ];

        try {

            $item = $this->productInfoAgent;
            $item->setMessage('Test message');
            $item->setUserId(1);
            $item->setId(1);
            $item->setUpdatedAt(Date('Y-m-d H:i:s'));
            $item->setCreatedAt(Date('Y-m-d H:i:s'));
            $item->save();

            $output->writeln('Inserted Product ID: ');
            $output->writeln(' Message: ' . $item->getMessage());
            $output->writeln(' User: '. $item->getUserId());
        } catch (\Exception $e) {
            $output->writeln('<error>Error inserting item: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
