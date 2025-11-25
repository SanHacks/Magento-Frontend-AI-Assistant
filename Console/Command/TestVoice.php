<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gundo\ProductInfoAgent\Api\VoiceInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Filesystem\DirectoryList;

class TestVoice extends Command
{
    private const ARGUMENT_TEXT = 'text';
    private const OPTION_PLAY = 'play';
    private const OPTION_SAVE = 'save';
    private const OPTION_OUTPUT_FILE = 'output';
    private const OPTION_PLAYER = 'player';

    private VoiceInterface $voice;
    private AppState $appState;
    private DirectoryList $directoryList;

    public function __construct(
        VoiceInterface $voice,
        AppState $appState,
        DirectoryList $directoryList
    ) {
        $this->voice = $voice;
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('gundo:product-agent:test-voice')
            ->setDescription('Test voice generation functionality with audio playback options.')
            ->addArgument(self::ARGUMENT_TEXT, InputArgument::REQUIRED, 'Text to convert to speech')
            ->addOption(self::OPTION_PLAY, 'p', InputOption::VALUE_NONE, 'Play the generated audio')
            ->addOption(self::OPTION_SAVE, 's', InputOption::VALUE_NONE, 'Save the audio file to disk')
            ->addOption(self::OPTION_OUTPUT_FILE, 'o', InputOption::VALUE_OPTIONAL, 'Output file path (default: var/tmp/voice_output.wav)')
            ->addOption(self::OPTION_PLAYER, null, InputOption::VALUE_OPTIONAL, 'Audio player to use (auto|ffplay|aplay|paplay|mpv|vlc)', 'auto');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
            
            $text = $input->getArgument(self::ARGUMENT_TEXT);
            $shouldPlay = $input->getOption(self::OPTION_PLAY);
            $shouldSave = $input->getOption(self::OPTION_SAVE);
            $outputFile = $input->getOption(self::OPTION_OUTPUT_FILE);
            $player = $input->getOption(self::OPTION_PLAYER);

            $output->writeln("<info>Testing voice generation for: '{$text}'</info>");

            $result = $this->voice->generateVoice($text, 1, 'test_session');

            if ($result['success']) {
                $output->writeln('<info>Voice generation successful!</info>');
                $output->writeln("Model: " . ($result['model'] ?? 'N/A'));
                $output->writeln("From cache: " . ($result['from_cache'] ? 'Yes' : 'No'));
                $output->writeln("Audio data length: " . strlen($result['audio_data'] ?? ''));

                // Decode audio data
                $audioData = base64_decode($result['audio_data']);
                
                // Determine output file path
                if (!$outputFile) {
                    $rootDir = $this->directoryList->getRoot();
                    $outputFile = $rootDir . '/var/tmp/voice_output_' . time() . '.wav';
                }

                // Ensure directory exists
                $outputDir = dirname($outputFile);
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }

                // Save audio file if requested or needed for playback
                if ($shouldSave || $shouldPlay) {
                    if (file_put_contents($outputFile, $audioData) === false) {
                        $output->writeln('<error>Failed to save audio file to: ' . $outputFile . '</error>');
                        return Command::FAILURE;
                    }
                    
                    if ($shouldSave) {
                        $output->writeln('<info>Audio saved to: ' . $outputFile . '</info>');
                    }
                }

                // Play audio if requested
                if ($shouldPlay) {
                    $playResult = $this->playAudio($outputFile, $player, $output);
                    if (!$playResult) {
                        $output->writeln('<error>Failed to play audio. Make sure you have an audio player installed.</error>');
                        return Command::FAILURE;
                    }
                }

                // Clean up temporary file if not explicitly saved
                if ($shouldPlay && !$shouldSave) {
                    unlink($outputFile);
                }

            } else {
                $output->writeln('<error>Voice generation failed: ' . ($result['error'] ?? 'Unknown error') . '</error>');
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Exception: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Play audio file using available system players
     *
     * @param  string          $audioFile
     * @param  string          $preferredPlayer
     * @param  OutputInterface $output
     * @return bool
     */
    private function playAudio(string $audioFile, string $preferredPlayer, OutputInterface $output): bool
    {
        $players = $this->getAvailableAudioPlayers();
        
        if (empty($players)) {
            $output->writeln('<error>No audio players found. Please install one of: ffmpeg, alsa-utils, pulseaudio-utils, mpv, or vlc</error>');
            return false;
        }

        // Use preferred player if available
        if ($preferredPlayer !== 'auto' && isset($players[$preferredPlayer])) {
            $output->writeln("<info>Using {$preferredPlayer} to play audio...</info>");
            return $this->executeAudioPlayer($players[$preferredPlayer], $audioFile, $output);
        }

        // Auto-select best available player
        $playerPriority = ['ffplay', 'mpv', 'vlc', 'paplay', 'aplay'];
        
        foreach ($playerPriority as $playerName) {
            if (isset($players[$playerName])) {
                $output->writeln("<info>Using {$playerName} to play audio...</info>");
                return $this->executeAudioPlayer($players[$playerName], $audioFile, $output);
            }
        }

        return false;
    }

    /**
     * Get available audio players on the system
     *
     * @return array
     */
    private function getAvailableAudioPlayers(): array
    {
        $players = [];
        
        $possiblePlayers = [
            'ffplay' => 'ffplay -nodisp -autoexit',
            'aplay' => 'aplay',
            'paplay' => 'paplay',
            'mpv' => 'mpv --no-video --really-quiet',
            'vlc' => 'vlc --intf dummy --play-and-exit',
            'mplayer' => 'mplayer -really-quiet',
            'sox' => 'play'
        ];

        foreach ($possiblePlayers as $name => $command) {
            $binary = explode(' ', $command)[0];
            if ($this->commandExists($binary)) {
                $players[$name] = $command;
            }
        }

        return $players;
    }

    /**
     * Check if a command exists
     *
     * @param  string $command
     * @return bool
     */
    private function commandExists(string $command): bool
    {
        $result = shell_exec("which {$command} 2>/dev/null");
        return !empty($result);
    }

    /**
     * Execute audio player command
     *
     * @param  string          $playerCommand
     * @param  string          $audioFile
     * @param  OutputInterface $output
     * @return bool
     */
    private function executeAudioPlayer(string $playerCommand, string $audioFile, OutputInterface $output): bool
    {
        $command = $playerCommand . ' ' . escapeshellarg($audioFile) . ' 2>&1';
        
        $output->writeln("<comment>Executing: {$playerCommand} [audio_file]</comment>");
        
        $result = shell_exec($command);
        $exitCode = 0;
        
        // Check if command was successful
        exec($command, $outputLines, $exitCode);
        
        if ($exitCode !== 0) {
            $output->writeln("<error>Audio player exited with code {$exitCode}</error>");
            if ($result) {
                $output->writeln("<error>Output: {$result}</error>");
            }
            return false;
        }

        $output->writeln('<info>Audio playback completed successfully!</info>');
        return true;
    }
} 