<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Gundo\ProductInfoAgent\Api\VoiceInterface;
use Gundo\ProductInfoAgent\Helper\AudioPlayer;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Filesystem\DirectoryList;

class VoiceDocker extends Command
{
    private const ARGUMENT_TEXT = 'text';
    private const OPTION_SAVE = 'save';
    private const OPTION_OUTPUT_FILE = 'output';
    private const OPTION_PUBLIC = 'public';
    private const OPTION_INFO = 'info';
    private const OPTION_LIST = 'list';

    private VoiceInterface $voice;
    private AudioPlayer $audioPlayer;
    private AppState $appState;
    private DirectoryList $directoryList;

    public function __construct(
        VoiceInterface $voice,
        AudioPlayer $audioPlayer,
        AppState $appState,
        DirectoryList $directoryList
    ) {
        $this->voice = $voice;
        $this->audioPlayer = $audioPlayer;
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('gundo:product-agent:voice-docker')
            ->setDescription('Voice generation optimized for Docker environments.')
            ->addArgument(self::ARGUMENT_TEXT, InputArgument::OPTIONAL, 'Text to convert to speech')
            ->addOption(self::OPTION_SAVE, 's', InputOption::VALUE_NONE, 'Save the audio file')
            ->addOption(self::OPTION_OUTPUT_FILE, 'o', InputOption::VALUE_OPTIONAL, 'Custom output filename')
            ->addOption(self::OPTION_PUBLIC, 'p', InputOption::VALUE_NONE, 'Save to public media directory for web access')
            ->addOption(self::OPTION_INFO, 'i', InputOption::VALUE_OPTIONAL, 'Get info about an existing audio file')
            ->addOption(self::OPTION_LIST, 'l', InputOption::VALUE_NONE, 'List all saved audio files')
            ->setHelp('
This command provides voice generation functionality optimized for Docker environments:

<info>Examples:</info>
  <comment>Generate and save voice to temp directory:</comment>
  bin/magento gundo:product-agent:voice-docker "Hello World" --save

  <comment>Generate and save to public media (web accessible):</comment>
  bin/magento gundo:product-agent:voice-docker "Hello World" --save --public

  <comment>Generate with custom filename:</comment>
  bin/magento gundo:product-agent:voice-docker "Hello World" --save --output=greeting.wav

  <comment>List all saved audio files:</comment>
  bin/magento gundo:product-agent:voice-docker --list

  <comment>Get info about a specific file:</comment>
  bin/magento gundo:product-agent:voice-docker --info=/path/to/audio.wav

<info>Docker Usage:</info>
  Since Docker containers typically don\'t have audio output devices, this command focuses
  on generating and saving audio files that can be:
  - Downloaded from the container
  - Accessed via web if saved to public media
  - Transferred to host system for playback
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
            
            $text = $input->getArgument(self::ARGUMENT_TEXT);
            $shouldSave = $input->getOption(self::OPTION_SAVE);
            $outputFile = $input->getOption(self::OPTION_OUTPUT_FILE);
            $usePublic = $input->getOption(self::OPTION_PUBLIC);
            $infoFile = $input->getOption(self::OPTION_INFO);
            $listFiles = $input->getOption(self::OPTION_LIST);

            // Handle list option
            if ($listFiles) {
                return $this->listAudioFiles($output);
            }

            // Handle info option
            if ($infoFile) {
                return $this->showFileInfo($infoFile, $output);
            }

            // Require text for voice generation
            if (!$text) {
                $output->writeln('<error>Text argument is required for voice generation</error>');
                $output->writeln('Use --list to see saved files or --info=<file> for file information');
                return Command::FAILURE;
            }

            $output->writeln("<info>Generating voice for: '{$text}'</info>");
            $output->writeln("<comment>Docker Environment Detected - Audio playback not available</comment>\n");

            // Generate voice
            $result = $this->voice->generateVoice($text, 1, 'docker_session');

            if (!$result['success']) {
                $output->writeln('<error>Voice generation failed: ' . ($result['error'] ?? 'Unknown error') . '</error>');
                return Command::FAILURE;
            }

            $output->writeln('<info>✓ Voice generation successful!</info>');
            $output->writeln("  Model: " . ($result['model'] ?? 'N/A'));
            $output->writeln("  From cache: " . ($result['from_cache'] ? 'Yes' : 'No'));
            $output->writeln("  Audio data size: " . $this->formatBytes(strlen($result['audio_data'] ?? '')));

            // Save file if requested
            if ($shouldSave) {
                $saveResult = $this->saveAudioFile(
                    $result['audio_data'], 
                    $outputFile, 
                    $usePublic, 
                    $output
                );
                
                if (!$saveResult) {
                    return Command::FAILURE;
                }
            } else {
                $output->writeln("\n<comment>Use --save to save the audio file</comment>");
                $output->writeln("<comment>Use --save --public to save to web-accessible location</comment>");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Exception: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Save audio file with options for public or private storage
     */
    private function saveAudioFile(string $audioData, ?string $outputFile, bool $usePublic, OutputInterface $output): bool
    {
        try {
            $filename = $outputFile ?: 'voice_' . time() . '_' . uniqid() . '.wav';
            
            if ($usePublic) {
                // Save to public media directory
                $rootDir = $this->directoryList->getRoot();
                $publicDir = $rootDir . '/pub/media/voice';
                $filepath = $publicDir . '/' . $filename;
                
                // Ensure directory exists
                if (!is_dir($publicDir)) {
                    if (!mkdir($publicDir, 0755, true)) {
                        $output->writeln('<error>Failed to create public media directory</error>');
                        return false;
                    }
                }
                
                // Save file
                $decodedAudio = base64_decode($audioData);
                if (file_put_contents($filepath, $decodedAudio) === false) {
                    $output->writeln('<error>Failed to save audio file</error>');
                    return false;
                }
                
                $output->writeln("\n<info>✓ Audio saved to public media!</info>");
                $output->writeln("  File: {$filepath}");
                $output->writeln("  Web URL: /media/voice/{$filename}");
                $output->writeln("  Size: " . $this->formatBytes(filesize($filepath)));
                
            } else {
                // Use the AudioPlayer helper for private storage
                $saveResult = $this->audioPlayer->saveAudioFile($audioData, $filename);
                
                if (!$saveResult['success']) {
                    $output->writeln('<error>Failed to save audio file: ' . $saveResult['error'] . '</error>');
                    return false;
                }
                
                $output->writeln("\n<info>✓ Audio saved to temporary storage!</info>");
                $output->writeln("  File: " . $saveResult['filepath']);
                $output->writeln("  Name: " . $saveResult['filename']);
                
                // Get file info
                $info = $this->audioPlayer->getAudioInfo($saveResult['filepath']);
                if ($info['exists']) {
                    $output->writeln("  Size: " . $info['size_human']);
                }
            }
            
            $output->writeln("\n<comment>Docker Tips:</comment>");
            $output->writeln("  • Copy file from container: docker cp container_name:{$filepath} ./");
            $output->writeln("  • Access via web (if public): http://your-domain/media/voice/{$filename}");
            
            return true;
            
        } catch (\Exception $e) {
            $output->writeln('<error>Save error: ' . $e->getMessage() . '</error>');
            return false;
        }
    }

    /**
     * List all saved audio files
     */
    private function listAudioFiles(OutputInterface $output): int
    {
        $output->writeln('<info>Saved Audio Files:</info>');
        
        $rootDir = $this->directoryList->getRoot();
        $directories = [
            'Temporary Storage' => $rootDir . '/var/tmp/audio',
            'Public Media' => $rootDir . '/pub/media/voice'
        ];
        
        $totalFiles = 0;
        
        foreach ($directories as $label => $dir) {
            $output->writeln("\n<comment>{$label}:</comment>");
            
            if (!is_dir($dir)) {
                $output->writeln("  No files found (directory doesn't exist)");
                continue;
            }
            
            $files = glob($dir . '/*.{wav,mp3,ogg,m4a}', GLOB_BRACE) ?: [];
            
            if (empty($files)) {
                $output->writeln("  No audio files found");
                continue;
            }
            
            $table = new Table($output);
            $table->setHeaders(['Filename', 'Size', 'Modified', 'Age']);
            
            foreach ($files as $file) {
                $info = $this->audioPlayer->getAudioInfo($file);
                if ($info['exists']) {
                    $table->addRow([
                        $info['filename'],
                        $info['size_human'],
                        $info['modified'],
                        $this->formatAge($info['age_seconds'])
                    ]);
                    $totalFiles++;
                }
            }
            
            $table->render();
        }
        
        $output->writeln("\n<info>Total files found: {$totalFiles}</info>");
        
        if ($totalFiles > 0) {
            $output->writeln("\n<comment>Cleanup old files:</comment>");
            $output->writeln("  bin/magento gundo:product-agent:audio cleanup --cleanup-hours=24");
        }
        
        return Command::SUCCESS;
    }

    /**
     * Show information about a specific audio file
     */
    private function showFileInfo(string $filepath, OutputInterface $output): int
    {
        $info = $this->audioPlayer->getAudioInfo($filepath);
        
        if (!$info['exists']) {
            $output->writeln("<error>File not found: {$filepath}</error>");
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
        
        // Show Docker copy command
        $output->writeln("\n<comment>Docker Commands:</comment>");
        $output->writeln("  Copy to host: docker cp container_name:{$filepath} ./");
        
        return Command::SUCCESS;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Format age in seconds to human readable format
     */
    private function formatAge(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's ago';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm ago';
        } elseif ($seconds < 86400) {
            return floor($seconds / 3600) . 'h ago';
        } else {
            return floor($seconds / 86400) . 'd ago';
        }
    }

    /**
     * Format duration in seconds to human readable format
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