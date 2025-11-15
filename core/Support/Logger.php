<?php

namespace Core\Support;

/**
 * Logger Class
 *
 * A simple yet powerful logging system with support for multiple log levels
 * and automatic log rotation. Compatible with PSR-3 logging interface.
 */
class Logger
{
    /**
     * Log levels
     */
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    /**
     * The path to the log file
     */
    protected string $logPath;

    /**
     * The log channel name
     */
    protected string $channel;

    /**
     * Maximum file size before rotation (in bytes)
     * Default: 10MB
     */
    protected int $maxFileSize = 10485760;

    /**
     * Number of rotated log files to keep
     */
    protected int $maxFiles = 5;

    /**
     * Minimum log level to record
     */
    protected string $minLevel = self::DEBUG;

    /**
     * Log level hierarchy
     */
    protected array $levels = [
        self::DEBUG => 0,
        self::INFO => 1,
        self::NOTICE => 2,
        self::WARNING => 3,
        self::ERROR => 4,
        self::CRITICAL => 5,
        self::ALERT => 6,
        self::EMERGENCY => 7,
    ];

    /**
     * Create a new Logger instance
     *
     * @param string $channel
     * @param string|null $logPath
     */
    public function __construct(string $channel = 'app', ?string $logPath = null)
    {
        $this->channel = $channel;
        $this->logPath = $logPath ?? $this->getDefaultLogPath();

        // Ensure log directory exists
        $this->ensureLogDirectoryExists();
    }

    /**
     * Get the default log path
     *
     * @return string
     */
    protected function getDefaultLogPath(): string
    {
        $date = date('Y-m-d');
        return storage_path("logs/{$this->channel}-{$date}.log");
    }

    /**
     * Ensure the log directory exists
     *
     * @return void
     */
    protected function ensureLogDirectoryExists(): void
    {
        $directory = dirname($this->logPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Set the minimum log level
     *
     * @param string $level
     * @return self
     */
    public function setMinLevel(string $level): self
    {
        if (isset($this->levels[$level])) {
            $this->minLevel = $level;
        }

        return $this;
    }

    /**
     * Log a message at the given level
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Check if this level should be logged
        if (!$this->shouldLog($level)) {
            return;
        }

        // Rotate log file if needed
        $this->rotateLogIfNeeded();

        // Format the log message
        $formattedMessage = $this->formatMessage($level, $message, $context);

        // Write to log file
        file_put_contents($this->logPath, $formattedMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Check if a level should be logged
     *
     * @param string $level
     * @return bool
     */
    protected function shouldLog(string $level): bool
    {
        if (!isset($this->levels[$level])) {
            return false;
        }

        return $this->levels[$level] >= $this->levels[$this->minLevel];
    }

    /**
     * Format the log message
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatMessage(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        // Replace placeholders in message with context values
        $message = $this->interpolate($message, $context);

        // Build the log line
        $logLine = "[{$timestamp}] {$this->channel}.{$levelUpper}: {$message}";

        // Add context if present
        if (!empty($context)) {
            $logLine .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $logLine . PHP_EOL;
    }

    /**
     * Interpolate context values into the message placeholders
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Rotate log file if it exceeds maximum size
     *
     * @return void
     */
    protected function rotateLogIfNeeded(): void
    {
        if (!file_exists($this->logPath)) {
            return;
        }

        if (filesize($this->logPath) < $this->maxFileSize) {
            return;
        }

        // Rotate existing backup files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $this->logPath . '.' . $i;
            $newFile = $this->logPath . '.' . ($i + 1);

            if (file_exists($oldFile)) {
                if ($i + 1 > $this->maxFiles) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        // Rotate current log file
        rename($this->logPath, $this->logPath . '.1');
    }

    /**
     * System is unusable
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Get the current log file path
     *
     * @return string
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }

    /**
     * Get the channel name
     *
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Clear the current log file
     *
     * @return void
     */
    public function clear(): void
    {
        if (file_exists($this->logPath)) {
            file_put_contents($this->logPath, '');
        }
    }
}
