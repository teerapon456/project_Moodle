<?php

// Manually require the library files since we didn't use composer
// Adjust path as needed based on where you extracted it
require_once __DIR__ . '/DeviceDetectorLib/autoload.php';
require_once __DIR__ . '/DeviceDetectorLib/Spyc.php';

use DeviceDetector\DeviceDetector as MatomoDeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;

class DeviceDetector
{
    private $detector;

    public function __construct($userAgent = null)
    {
        $userAgent = $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Initialize Matomo DeviceDetector
        $this->detector = new MatomoDeviceDetector((string)$userAgent);

        // Enable Client Hints support
        $clientHints = \DeviceDetector\ClientHints::factory($_SERVER ?? []);
        $this->detector->setClientHints($clientHints);

        // Optional: Set caching if available (skipping for now to keep it simple)
        // $this->detector->setCache(new \Doctrine\Common\Cache\PhpFileCache('./tmp/'));

        // Parse the user agent
        $this->detector->parse();
    }

    public function getDeviceType()
    {
        if ($this->detector->isBot()) {
            return 'bot';
        }
        $device = $this->detector->getDeviceName();
        return $device ?: 'unknown';
    }

    public function getOS()
    {
        if ($this->detector->isBot()) {
            return 'Bot';
        }
        $os = $this->detector->getOs();
        if ($os) {
            return trim(($os['name'] ?? '') . ' ' . ($os['version'] ?? ''));
        }
        return '';
    }

    public function getOSName()
    {
        if ($this->detector->isBot()) {
            return 'Bot';
        }
        $os = $this->detector->getOs();
        return $os['name'] ?? '';
    }

    public function getOSVersion()
    {
        if ($this->detector->isBot()) {
            return '';
        }
        $os = $this->detector->getOs();
        return $os['version'] ?? '';
    }

    public function getBrowser()
    {
        if ($this->detector->isBot()) {
            $botInfo = $this->detector->getBot();
            return $botInfo['name'] ?? 'Bot';
        }
        $client = $this->detector->getClient();
        if ($client) {
            return trim(($client['name'] ?? '') . ' ' . ($client['version'] ?? ''));
        }
        return '';
    }

    public function getClientName()
    {
        if ($this->detector->isBot()) {
            $botInfo = $this->detector->getBot();
            return $botInfo['name'] ?? 'Bot';
        }
        $client = $this->detector->getClient();
        return $client['name'] ?? '';
    }

    public function getClientVersion()
    {
        if ($this->detector->isBot()) {
            return '';
        }
        $client = $this->detector->getClient();
        return $client['version'] ?? '';
    }

    public function getDeviceBrand()
    {
        $brand = $this->detector->getBrandName();
        if (empty($brand) && $this->getDeviceType() === 'desktop') {
            return 'Desktop / PC';
        }
        return $brand ?: '';
    }

    public function getDeviceModel()
    {
        $model = $this->detector->getModel();
        if (empty($model) && $this->getDeviceType() === 'desktop') {
            return $this->getOS();
        }
        return $model ?: '';
    }

    // Additional helper to get full detail if needed
    public function getDetails()
    {
        return [
            'isBot' => $this->detector->isBot(),
            'client' => $this->detector->getClient(),
            'os' => $this->detector->getOs(),
            'device' => $this->detector->getDeviceName(),
            'brand' => $this->detector->getBrandName(),
            'model' => $this->detector->getModel(),
        ];
    }
}
