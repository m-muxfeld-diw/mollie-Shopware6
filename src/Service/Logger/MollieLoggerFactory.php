<?php

namespace Kiener\MolliePayments\Service\Logger;

use Kiener\MolliePayments\Service\Logger\Processors\AnonymousWebProcessor;
use Kiener\MolliePayments\Service\Logger\Services\URLAnonymizer;
use Kiener\MolliePayments\Service\SettingsService;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

class MollieLoggerFactory
{
    /**
     * this is the channel name that will be
     * displayed in the backend. It must not contain spaces
     */
    const CHANNEL = 'Mollie';


    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $dsn;


    /**
     * @param SettingsService $settingsService
     * @param string $filename
     * @param string $dsn
     */
    public function __construct(SettingsService $settingsService, string $filename, string $dsn)
    {
        $this->settingsService = $settingsService;
        $this->filename = $filename;
        $this->dsn = $dsn;
    }

    /**
     * @return LoggerInterface
     */
    public function createLogger(): LoggerInterface
    {
        if ($this->dsn === '' || $this->dsn === 'mysql://_placeholder.test') {
            // deployment server without database
            return new Logger(self::CHANNEL);
        }

        $config = $this->settingsService->getSettings();

        # 100 = DEBUG, 200 = INFO
        $minLevel = ($config->isDebugMode()) ? 100 : 200;
        $retentionDays = $config->getLogFileDays();

        $fileHandler = new RotatingFileHandler($this->filename, $retentionDays, $minLevel);

        $processors = [];
        $processors[] = new AnonymousWebProcessor(new WebProcessor(), new URLAnonymizer());
        $processors[] = new IntrospectionProcessor();

        /** @var callable $processor */
        foreach ($processors as $processor) {
            $fileHandler->pushProcessor($processor);
        }

        return new Logger(self::CHANNEL, [$fileHandler]);
    }
}
