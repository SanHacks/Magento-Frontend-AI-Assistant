<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gundo\ProductInfoAgent\Api\SuggestionInterface;
use Magento\Framework\App\State as AppState;

class TestSmartSuggestions extends Command
{
    private const ARGUMENT_PRODUCT_ID = 'product-id';
    private const OPTION_ITERATIONS = 'iterations';
    private const OPTION_CUSTOMER_ID = 'customer-id';
    private const OPTION_SESSION_ID = 'session-id';

    private SuggestionInterface $suggestionService;
    private AppState $appState;

    public function __construct(
        SuggestionInterface $suggestionService,
        AppState $appState
    ) {
        $this->suggestionService = $suggestionService;
        $this->appState = $appState;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('gundo:product-agent:test-smart-suggestions')
            ->setDescription('Test the smart suggestion system with randomization and rotation.')
            ->addArgument(self::ARGUMENT_PRODUCT_ID, InputArgument::REQUIRED, 'Product ID to test suggestions for')
            ->addOption(self::OPTION_ITERATIONS, 'i', InputOption::VALUE_OPTIONAL, 'Number of iterations to test', 5)
            ->addOption(self::OPTION_CUSTOMER_ID, 'c', InputOption::VALUE_OPTIONAL, 'Customer ID to simulate', null)
            ->addOption(self::OPTION_SESSION_ID, 's', InputOption::VALUE_OPTIONAL, 'Session ID to simulate', 'test_session');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
            
            $productId = (int)$input->getArgument(self::ARGUMENT_PRODUCT_ID);
            $iterations = (int)$input->getOption(self::OPTION_ITERATIONS);
            $customerId = $input->getOption(self::OPTION_CUSTOMER_ID) ? (int)$input->getOption(self::OPTION_CUSTOMER_ID) : null;
            $sessionId = $input->getOption(self::OPTION_SESSION_ID);

            $output->writeln("<info>Testing smart suggestions for Product ID: {$productId}</info>");
            $output->writeln("Customer ID: " . ($customerId ?: 'Guest'));
            $output->writeln("Session ID: {$sessionId}");
            $output->writeln("Iterations: {$iterations}");
            $output->writeln(str_repeat('=', 60));

            $allSuggestions = [];
            
            for ($i = 1; $i <= $iterations; $i++) {
                $output->writeln("<comment>Iteration {$i}:</comment>");
                
                $suggestions = $this->suggestionService->getSuggestions($productId);
                
                if (empty($suggestions)) {
                    $output->writeln('<error>No suggestions returned</error>');
                    continue;
                }

                foreach ($suggestions as $index => $suggestion) {
                    $output->writeln("  " . ($index + 1) . ". {$suggestion}");
                    
                    // Track all suggestions we've seen
                    if (!in_array($suggestion, $allSuggestions)) {
                        $allSuggestions[] = $suggestion;
                    }
                }

                $output->writeln("  → Returned " . count($suggestions) . " suggestions");
                $output->writeln("");
                
                // Add a small delay to simulate real usage
                sleep(1);
            }

            $output->writeln(str_repeat('=', 60));
            $output->writeln("<info>Summary:</info>");
            $output->writeln("Total unique suggestions seen: " . count($allSuggestions));
            $output->writeln("Average suggestions per iteration: " . round(array_sum(array_map('count', array_fill(0, $iterations, $suggestions))) / $iterations, 2));
            
            $output->writeln("\n<info>All unique suggestions encountered:</info>");
            foreach ($allSuggestions as $index => $suggestion) {
                $output->writeln("  " . ($index + 1) . ". {$suggestion}");
            }

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $output->writeln('<error>Exception: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
} 