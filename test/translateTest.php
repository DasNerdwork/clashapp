<?php
use PHPUnit\Framework\TestCase;
$_SERVER['SERVER_NAME'] = "clashscout.com";
require_once('/hdd1/clashapp/lang/translate.php');

class TranslateTest extends TestCase {
    /**
     * @covers __
     */
    public function testTranslate() {

        $translatedString = __("This is a test translation for %s");
        $this->assertEquals('This is a test translation for %s', $translatedString, "String was not translated properly");

        $germanTranslatedString = __("This is a test translation for %s", [], 'de_DE');
        $this->assertEquals('Das ist ein Übersetzungstest für %s', $germanTranslatedString, "String was not translated properly");

        $translatedStringWithArgs = __("This is a test translation for %s", ["PHPUnit"], 'de_DE');
        $this->assertEquals('Das ist ein Übersetzungstest für PHPUnit', $translatedStringWithArgs, "String was not translated properly");
    }
}