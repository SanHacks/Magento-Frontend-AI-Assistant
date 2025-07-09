<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\DirectoryList;
use Gundo\ProductInfoAgent\Logger\Logger;

class AudioPlayer extends AbstractHelper
{
    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    /**
     * Save audio data to file
     *
     * @param string $audioData Base64 encoded audio data
     * @param string|null $filename Custom filename (optional)
     * @return array ['success' => bool, 'filepath' => string, 'error' => string]
     */
    public function saveAudioFile(string $audioData, ?string $filename = null): array
    {
        try {
            // Decode audio data
            $decodedAudio = base64_decode($audioData);
            
            if ($decodedAudio === false) {
                return [
                    'success' => false,
                    'error' => 'Invalid base64 audio data'
                ];
            }

            // Generate filename if not provided
            if (!$filename) {
                $filename = 'voice_' . time() . '_' . uniqid() . '.wav';
            }

            // Create output path
            $rootDir = $this->directoryList->getRoot();
            $outputDir = $rootDir . '/var/tmp/audio';
            $outputFile = $outputDir . '/' . $filename;

            // Ensure directory exists
            if (!is_dir($outputDir)) {
                if (!mkdir($outputDir, 0755, true)) {
                    return [
                        'success' => false,
                        'error' => 'Failed to create audio directory: ' . $outputDir
                    ];
                }
            }

            // Save file
            if (file_put_contents($outputFile, $decodedAudio) === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to save audio file to: ' . $outputFile
                ];
            }

            return [
                'success' => true,
                'filepath' => $outputFile,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            $this->logger->error('AudioPlayer save error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Play audio file using available system players
     *
     * @param string $audioFile Path to audio file
     * @param string $preferredPlayer Preferred audio player (auto|ffplay|aplay|paplay|mpv|vlc)
     * @return array ['success' => bool, 'player' => string, 'error' => string]
     */
    public function playAudioFile(string $audioFile, string $preferredPlayer = 'auto'): array
    {
        try {
            if (!file_exists($audioFile)) {
                return [
                    'success' => false,
                    'error' => 'Audio file not found: ' . $audioFile
                ];
            }

            $players = $this->getAvailableAudioPlayers();
            
            if (empty($players)) {
                return [
                    'success' => false,
                    'error' => 'No audio players found. Please install one of: ffmpeg, alsa-utils, pulseaudio-utils, mpv, or vlc'
                ];
            }

            $selectedPlayer = null;
            $selectedCommand = null;

            // Use preferred player if available
            if ($preferredPlayer !== 'auto' && isset($players[$preferredPlayer])) {
                $selectedPlayer = $preferredPlayer;
                $selectedCommand = $players[$preferredPlayer];
            } else {
                // Auto-select best available player
                $playerPriority = ['ffplay', 'mpv', 'vlc', 'paplay', 'aplay'];
                
                foreach ($playerPriority as $playerName) {
                    if (isset($players[$playerName])) {
                        $selectedPlayer = $playerName;
                        $selectedCommand = $players[$playerName];
                        break;
                    }
                }
            }

            if (!$selectedPlayer || !$selectedCommand) {
                return [
                    'success' => false,
                    'error' => 'No suitable audio player found'
                ];
            }

            // Execute audio player
            $result = $this->executeAudioPlayer($selectedCommand, $audioFile);
            
            return [
                'success' => $result,
                'player' => $selectedPlayer,
                'error' => $result ? null : 'Audio playback failed'
            ];

        } catch (\Exception $e) {
            $this->logger->error('AudioPlayer playback error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available audio players on the system
     *
     * @return array
     */
    public function getAvailableAudioPlayers(): array
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
     * @param string $command
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
     * @param string $playerCommand
     * @param string $audioFile
     * @return bool
     */
    private function executeAudioPlayer(string $playerCommand, string $audioFile): bool
    {
        $command = $playerCommand . ' ' . escapeshellarg($audioFile) . ' 2>&1';
        
        $this->logger->info("Executing audio player: {$playerCommand}");
        
        // Execute command in background for non-blocking playback
        $result = shell_exec($command);
        $exitCode = 0;
        
        // Check if command was successful
        exec($command, $outputLines, $exitCode);
        
        if ($exitCode !== 0) {
            $this->logger->error("Audio player exited with code {$exitCode}. Output: " . ($result ?? 'N/A'));
            return false;
        }

        $this->logger->info('Audio playback completed successfully');
        return true;
    }

    /**
     * Clean up temporary audio files
     *
     * @param int $olderThanHours Remove files older than specified hours (default: 24)
     * @return int Number of files removed
     */
    public function cleanupTempAudioFiles(int $olderThanHours = 24): int
    {
        $rootDir = $this->directoryList->getRoot();
        $audioDir = $rootDir . '/var/tmp/audio';
        
        if (!is_dir($audioDir)) {
            return 0;
        }

        $filesRemoved = 0;
        $cutoffTime = time() - ($olderThanHours * 3600);

        try {
            $files = glob($audioDir . '/*');
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    if (unlink($file)) {
                        $filesRemoved++;
                        $this->logger->info('Cleaned up temporary audio file: ' . basename($file));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error cleaning up audio files: ' . $e->getMessage());
        }

        return $filesRemoved;
    }

    /**
     * Get audio file info
     *
     * @param string $audioFile
     * @return array
     */
    public function getAudioInfo(string $audioFile): array
    {
        if (!file_exists($audioFile)) {
            return [
                'exists' => false,
                'error' => 'File not found'
            ];
        }

        $fileSize = filesize($audioFile);
        $fileTime = filemtime($audioFile);
        
        $info = [
            'exists' => true,
            'filepath' => $audioFile,
            'filename' => basename($audioFile),
            'size' => $fileSize,
            'size_human' => $this->formatBytes($fileSize),
            'modified' => date('Y-m-d H:i:s', $fileTime),
            'age_seconds' => time() - $fileTime
        ];

        // Try to get additional info using ffprobe if available
        if ($this->commandExists('ffprobe')) {
            $command = 'ffprobe -v quiet -print_format json -show_format -show_streams ' . escapeshellarg($audioFile) . ' 2>/dev/null';
            $result = shell_exec($command);
            
            if ($result) {
                $ffprobeData = json_decode($result, true);
                if ($ffprobeData && isset($ffprobeData['format'])) {
                    $info['duration'] = $ffprobeData['format']['duration'] ?? null;
                    $info['bitrate'] = $ffprobeData['format']['bit_rate'] ?? null;
                    $info['format'] = $ffprobeData['format']['format_name'] ?? null;
                }
            }
        }

        return $info;
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $size
     * @return string
     */
    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
} 