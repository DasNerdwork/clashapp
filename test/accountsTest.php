<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\UsesClass;
require_once('/hdd1/clashapp/accounts/qr-codes.php');

#[CoversFunction('generateQR')]
#[UsesClass(DB::class)]
class AccountsTest extends TestCase {
    public function testGenerateQR() {
        $actualData = generateQR("DasNerdwork");

        $this->assertArrayHasKey("qr", $actualData, "QR Code key is missing from array");
        $this->assertArrayHasKey("secret", $actualData, "Secret key is missing from array");
        $this->assertCount(2, $actualData, "Generated QR Code array has too few or too many keys");
    }
}