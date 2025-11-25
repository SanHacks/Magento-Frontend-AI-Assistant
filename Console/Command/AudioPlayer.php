<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Gundo\ProductInfoAgent\Helper\AudioPlayer as AudioPlayerHelper;
use Magento\Framework\App\State as AppState;

class AudioPlayer extends Command
{
    private const ARGUMENT_ACTION = 'action';
    private const ARGUMENT_FILE = 'file';
    private const OPTION_PLAYER = 'player';
    private const OPTION_CLEANUP_HOURS = 'cleanup-hours';

    private AudioPlayerHelper $audioPlayerHelper;
    private AppState $appState;

    public function __construct(
        AudioPlayerHelper $audioPlayerHelper,
        AppState $appState
    ) {
        $this->audioPlayerHelper = $audioPlayerHelper;
        $this->appState = $appState;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('gundo:product-agent:audio')
            ->setDescription('Audio player management for ProductInfoAgent.')
            ->addArgument(self::ARGUMENT_ACTION, InputArgument::REQUIRED, 'Action to perform: play|list-players|info|cleanup')
            ->addArgument(self::ARGUMENT_FILE, InputArgument::OPTIONAL, 'Audio file path (required for play and info actions)')
            ->addOption(self::OPTION_PLAYER, 'p', InputOption::VALUE_OPTIONAL, 'Preferred audio player (auto|ffplay|aplay|paplay|mpv|vlc)', 'auto')
            ->addOption(self::OPTION_CLEANUP_HOURS, null, InputOption::VALUE_OPTIONAL, 'Hours to keep temp files during cleanup (default: 24)', 24)
            ->setHelp(
                '
This command provides audio playback and management functionality:

<info>Actions:</info>
  <comment>play</comment>         Play an audio file using available system players
  <comment>list-players</comment> List all available audio players on the system
  <comment>info</comment>         Get information about an audio file
  <comment>cleanup</comment>      Clean up temporary audio files

<info>Examples:</info>
  <comment>bin/magento gundo:product-agent:audio play /path/to/audio.wav</comment>
  <comment>bin/magento gundo:product-agent:audio play /path/to/audio.wav --player=ffplay</comment>
  <comment>bin/magento gundo:product-agent:audio list-players</comment>
  <comment>bin/magento gundo:product-agent:audio info /path/to/audio.wav</comment>
  <comment>bin/magento gundo:product-agent:audio cleanup --cleanup-hours=48</comment>
            '
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
            
            $action = $input->getArgument(self::ARGUMENT_ACTION);
            $file = $input->getArgument(self::ARGUMENT_FILE);
            $player = $input->getOption(self::OPTION_PLAYER);
            $cleanupHours = (int)$input->getOption(self::OPTION_CLEANUP_HOURS);

            switch ($action) {
            case 'play':
                return $this->playAudio($file, $player, $output);
                
            case 'list-players':
                return $this->listPlayers($output);
                
            case 'info':
                return $this->showAudioInfo($file, $output);
                
            case 'cleanup':
                return $this->cleanupTempFiles($cleanupHours, $output);
                
            default:
                $output->writeln('<error>Invalid action. Use: play|list-players|info|cleanup</error>');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $output->writeln('<error>Exception: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Play audio file
     *
     * @param  string|null     $file
     * @param  string          $player
     * @param  OutputInterface $output
     * @return int
     */
    private function playAudio(?string $file, string $player, OutputInterface $output): int
    {
        if (!$file) {
            $output->writeln('<error>File path is required for play action</error>');
            return Command::FAILURE;
        }

        $output->writeln("<info>Playing audio file: {$file}</info>");
        
        $result = $this->audioPlayerHelper->playAudioFile($file, $player);
        
        if ($result['success']) {
            $output->writeln("<info>Successfully played audio using: {$result['player']}</info>");
            return Command::SUCCESS;
        } else {
            $output->writeln("<error>Failed to play audio: {$result['error']}</error>");
            return Command::FAILURE;
        }
    }

    /**
     * List available audio players
     *
     * @param  OutputInterface $output
     * @return int
     */
    private function listPlayers(OutputInterface $output): int
    {
        $players = $this->audioPlayerHelper->getAvailableAudioPlayers();
        
        if (empty($players)) {
            $output->writeln('<error>No audio players found on this system</error>');
            $output->writeln('<comment>Consider installing one of the following packages:</comment>');
            $output->writeln('  - ffmpeg (provides ffplay)');
            $output->writeln('  - alsa-utils (provides aplay)');
            $output->writeln('  - pulseaudio-utils (provides paplay)');
            $output->writeln('  - mpv');
            $output->writeln('  - vlc');
            $output->writeln('  - sox (provides play)');
            return Command::FAILURE;
        }

        $output->writeln('<info>Available Audio Players:</info>');
        
        $table = new Table($output);
        $table->setHeaders(['Player', 'Command']);
        
        foreach ($players as $name => $command) {
            $table->addRow([$name, $command]);
        }
        
        $table->render();
        
        $output->writeln('');
        $output->writeln('<comment>Usage: Use --player=<name> to specify a preferred player</comment>');
        
        return Command::SUCCESS;
    }

    /**
     * Show audio file information
     *
     * @param  string|null     $file
     * @param  OutputInterface $output
     * @return int
     */
    private function showAudioInfo(?string $file, OutputInterface $output): int
    {
        if (!$file) {
            $output->writeln('<error>File path is required for info action</error>');
            return Command::FAILURE;
        }

        $info = $this->audioPlayerHelper->getAudioInfo($file);
        
        if (!$info['exists']) {
            $output->writeln("<error>File not found: {$file}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Audio File Information:</info>");
        
        $table = new Table($output);
        $table->setStyle('compact');
        
        $table->addRow(['File Path', $info['filepath']]);
        $table->addRow(['File Name', $info['filename']]);
        $table->addRow(['Size', $info['size_human'] . ' (' . number_format($info['size']) . ' bytes)']);
        $table->addRow(['Modified', $info['modified']]);
        $table->addRow(['Age', $this->formatAge($info['age_seconds'])]);
        
        if (isset($info['duration'])) {
            $table->addRow(['Duration', $this->formatDuration((float)$info['duration'])]);
        }
        
        if (isset($info['bitrate'])) {
            $table->addRow(['Bitrate', number_format((int)$info['bitrate']) . ' bps']);
        }
        
        if (isset($info['format'])) {
            $table->addRow(['Format', $info['format']]);
        }
        
        $table->render();
        
        return Command::SUCCESS;
    }

    /**
     * Clean up temporary audio files
     *
     * @param  int             $hours
     * @param  OutputInterface $output
     * @return int
     */
    private function cleanupTempFiles(int $hours, OutputInterface $output): int
    {
        $output->writeln("<info>Cleaning up temporary audio files older than {$hours} hours...</info>");
        
        $filesRemoved = $this->audioPlayerHelper->cleanupTempAudioFiles($hours);
        
        if ($filesRemoved > 0) {
            $output->writeln("<info>Successfully removed {$filesRemoved} temporary audio file(s)</info>");
        } else {
            $output->writeln('<info>No temporary audio files found to clean up</info>');
        }
        
        return Command::SUCCESS;
    }

    /**
     * Format age in seconds to human readable format
     *
     * @param  int $seconds
     * @return string
     */
    private function formatAge(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds ago';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . ' minutes ago';
        } elseif ($seconds < 86400) {
            return floor($seconds / 3600) . ' hours ago';
        } else {
            return floor($seconds / 86400) . ' days ago';
        }
    }

    /**
     * Format duration in seconds to human readable format
     *
     * @param  float $seconds
     * @return string
     */
    private function formatDuration(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes > 0) {
            return sprintf('%d:%05.2f', $minutes, $remainingSeconds);
        } else {
            return sprintf('%.2f seconds', $seconds);
        }
    }
} 