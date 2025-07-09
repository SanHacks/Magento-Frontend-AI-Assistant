# ProductInfoAgent Audio Functionality

This document describes the audio generation and playback functionality for the ProductInfoAgent module, optimized for Docker environments.

## Features

- **Voice Generation**: Convert text to speech using Deepgram API
- **Audio File Management**: Save, list, and manage generated audio files
- **Docker Optimization**: Designed to work efficiently in containerized environments
- **Multiple Storage Options**: Save to temporary storage or public media directories
- **Audio Player Detection**: Automatically detect and use available audio players
- **Web Access**: Option to save files for web-based access

## Components

### 1. Voice Interface (`VoiceInterface`)
- **Location**: `Api/VoiceInterface.php`
- **Purpose**: API contract for voice generation
- **Method**: `generateVoice(string $text, int $productId = null, string $sessionId = null): array`

### 2. Voice Model (`Voice`)
- **Location**: `Model/Voice.php`
- **Purpose**: Implementation of voice generation using Deepgram API
- **Features**:
  - Session-based caching (30-minute default)
  - Configurable voice models
  - Error handling and logging

### 3. AudioPlayer Helper (`AudioPlayer`)
- **Location**: `Helper/AudioPlayer.php`
- **Purpose**: Audio file management and playback utilities
- **Features**:
  - Save base64 audio data to files
  - Detect available audio players
  - Play audio files (where supported)
  - File information retrieval
  - Temporary file cleanup

## Console Commands

### 1. Test Voice (`gundo:product-agent:test-voice`)
Enhanced voice testing with audio playback options.

```bash
# Basic voice generation
php bin/magento gundo:product-agent:test-voice "Hello World"

# Generate and play audio
php bin/magento gundo:product-agent:test-voice "Hello World" --play

# Generate and save audio
php bin/magento gundo:product-agent:test-voice "Hello World" --save

# Generate, save, and play with custom output
php bin/magento gundo:product-agent:test-voice "Hello World" --play --save --output=/path/to/output.wav

# Use specific audio player
php bin/magento gundo:product-agent:test-voice "Hello World" --play --player=ffplay
```

### 2. Audio Player (`gundo:product-agent:audio`)
Audio file management and playback utilities.

```bash
# Play an audio file
php bin/magento gundo:product-agent:audio play /path/to/audio.wav

# List available audio players
php bin/magento gundo:product-agent:audio list-players

# Get audio file information
php bin/magento gundo:product-agent:audio info /path/to/audio.wav

# Clean up temporary files
php bin/magento gundo:product-agent:audio cleanup --cleanup-hours=24
```

### 3. Docker Voice (`gundo:product-agent:voice-docker`)
**Recommended for Docker environments**

```bash
# Generate and save to temporary storage
docker exec -it container_name php bin/magento gundo:product-agent:voice-docker "Hello World" --save

# Generate and save to public media (web accessible)
docker exec -it container_name php bin/magento gundo:product-agent:voice-docker "Hello World" --save --public

# Generate with custom filename
docker exec -it container_name php bin/magento gundo:product-agent:voice-docker "Hello World" --save --output=greeting.wav

# List all saved audio files
docker exec -it container_name php bin/magento gundo:product-agent:voice-docker --list

# Get information about a specific file
docker exec -it container_name php bin/magento gundo:product-agent:voice-docker --info=/path/to/audio.wav
```

## Docker Usage

### Installation of Audio Tools

The audio functionality requires audio tools to be installed in the Docker container:

```bash
# For CentOS/RHEL-based containers
docker exec -u root -it container_name dnf install -y alsa-utils

# For Ubuntu/Debian-based containers
docker exec -u root -it container_name apt-get update && apt-get install -y alsa-utils ffmpeg
```

### Audio File Access

Since Docker containers typically don't have audio output devices, the module provides several ways to access generated audio files:

#### 1. Copy Files from Container
```bash
# Copy specific file
docker cp container_name:/var/www/html/pub/media/voice/filename.wav ./

# Copy all voice files
docker cp container_name:/var/www/html/pub/media/voice/ ./voice_files/
```

#### 2. Web Access (Public Media)
When using `--public` option, files are saved to `pub/media/voice/` and can be accessed via web:
```
http://your-domain/media/voice/filename.wav
```

#### 3. Volume Mounting
Mount the media directory to host system:
```yaml
# docker-compose.yml
volumes:
  - ./media:/var/www/html/pub/media
```

## Configuration

### Admin Configuration
Navigate to: **Stores > Configuration > Gundo > Product Info Agent > Voice Settings**

- **Enable Voice**: Enable/disable voice functionality
- **Deepgram API Key**: Your Deepgram API key for voice generation
- **Voice Model**: Select from available Deepgram voice models
- **Cache Lifetime**: How long to cache voice responses (minutes)

### Available Voice Models
- `aura-2-thalia-en` (default)
- `aura-2-luna-en`
- `aura-2-stella-en`
- `aura-2-athena-en`
- `aura-2-hera-en`
- `aura-2-orion-en`
- `aura-2-arcas-en`
- `aura-2-perseus-en`
- `aura-2-angus-en`
- `aura-2-orpheus-en`
- `aura-2-helios-en`
- `aura-2-zeus-en`

## Supported Audio Players

The system automatically detects and uses available audio players:

1. **ffplay** (from FFmpeg) - Recommended
2. **aplay** (ALSA utilities)
3. **paplay** (PulseAudio utilities)
4. **mpv** - Media player
5. **vlc** - VLC media player
6. **mplayer** - MPlayer
7. **play** (from SoX)

## API Endpoints

### REST API
- **POST** `/rest/V1/productinfoagent/voice`
  - Generate voice for text
  - Parameters: `text`, `productId`, `sessionId`

### GraphQL
```graphql
mutation {
  generateVoice(input: {
    text: "Hello World"
    productId: 1
    sessionId: "session123"
  }) {
    success
    audioData
    model
    fromCache
    error
  }
}
```

## File Storage Locations

### Temporary Storage
- **Path**: `var/tmp/audio/`
- **Purpose**: Temporary files for testing and development
- **Cleanup**: Automatic cleanup of old files available

### Public Media Storage
- **Path**: `pub/media/voice/`
- **Purpose**: Web-accessible audio files
- **URL**: `/media/voice/filename.wav`

## Troubleshooting

### Voice Generation Issues
1. **Check API Key**: Ensure Deepgram API key is configured correctly
2. **Check Network**: Verify container can reach api.deepgram.com
3. **Check Logs**: Review `var/log/ProductInfoAgent.log`

### Audio Playback Issues
1. **Docker Environment**: Audio playback typically doesn't work in containers
2. **Missing Audio Tools**: Install required audio utilities
3. **File Permissions**: Ensure proper file permissions for saved audio files

### Common Error Messages
- `"Voice feature is disabled"` - Enable voice in admin configuration
- `"Deepgram API key not configured"` - Set API key in admin configuration
- `"No audio players found"` - Install audio utilities in container
- `"Audio playback failed"` - Expected in Docker environments without audio devices

## Performance Considerations

### Caching
- Voice responses are cached by default for 30 minutes
- Cache key includes text, model, product ID, and session ID
- Cached responses return instantly

### File Management
- Temporary files should be cleaned up regularly
- Use the cleanup command to remove old files
- Consider file size limits for web-accessible files

### Docker Optimization
- Install only necessary audio tools to minimize container size
- Use volume mounts for persistent audio file storage
- Consider using public media storage for web applications

## Security Considerations

- **API Key Protection**: Store Deepgram API key securely
- **File Access**: Public media files are web-accessible
- **Input Validation**: Text input is validated before processing
- **Rate Limiting**: Consider implementing rate limiting for API calls

## Examples

### Complete Docker Workflow
```bash
# 1. Generate voice and save to public media
docker exec -it m2demo-php-fpm-1 php bin/magento gundo:product-agent:voice-docker \
  "Welcome to our store! How can I help you today?" --save --public

# 2. List saved files
docker exec -it m2demo-php-fpm-1 php bin/magento gundo:product-agent:voice-docker --list

# 3. Copy file to host for playback
docker cp m2demo-php-fpm-1:/var/www/html/pub/media/voice/filename.wav ./

# 4. Play on host system
aplay filename.wav
```

### Integration with Chat System
The voice functionality integrates with the ProductInfoAgent chat system:
- Voice buttons appear next to chat messages
- Audio is generated on-demand with caching
- Session-based storage for user-specific audio files

## Support

For issues or questions regarding the audio functionality:
1. Check the logs in `var/log/ProductInfoAgent.log`
2. Verify configuration in admin panel
3. Test with the provided console commands
4. Review Docker container audio capabilities 