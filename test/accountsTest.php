<?php
use PHPUnit\Framework\TestCase;
include_once('/hdd1/clashapp/accounts/qr-codes.php');

class AccountsTest extends TestCase {
    /**
     * @covers generateQR
     * @uses DB
     */
    public function testGenerateQR() {
        $actualData = generateQR("DasNerdwork");

        $this->assertArrayHasKey("qr", $actualData, "QR Code key is missing from array");
        $this->assertArrayHasKey("secret", $actualData, "Secret key is missing from array");
        $this->assertCount(2, $actualData, "Generated QR Code array has too few or too many keys");
    }
}