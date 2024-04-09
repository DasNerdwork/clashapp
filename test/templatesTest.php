<?php
use PHPUnit\Framework\TestCase;
require_once('/hdd1/clashapp/templates/head.php');

class TemplatesTest extends TestCase {
    /**
     * @covers setCodeHeader
     */
    public function testSetCodeHeader() {
        ob_start(); // Start output buffering to capture the output
        setCodeHeader("Test Title", true, true, true, true);
        $output = ob_get_clean(); // Get the output and stop output buffering

        $this->assertStringContainsString('<title>Test Title – ClashScout.com</title>', $output, "Title was not correctly set");
        $this->assertStringContainsString('<link id="favicon" rel="shortcut icon" href=', $output, "Favicon was not correctly set");
        $this->assertStringContainsString('<script type="text/javascript" async src="/clashapp/alpine.min.js?version=', $output, "AlpineJS was not correctly loaded");
        $this->assertStringContainsString('<script type="text/javascript" async src="/clashapp/main.min.js?version=', $output, "MainJS was not correctly loaded");
        $this->assertStringContainsString('<script type="text/javascript" async src="/clashapp/clash.min.js?version=', $output, "ClashJS was not correctly loaded");
        $this->assertStringContainsString('<script type="text/javascript" async src="/clashapp/lazyhtml.min.js?version=', $output, "LazyHTML was not correctly loaded");
        $this->assertStringContainsString('<script defer src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8928684248089281" crossorigin="anonymous"></script>', $output, "GoogleAds was not correctly loaded");
    }
}
