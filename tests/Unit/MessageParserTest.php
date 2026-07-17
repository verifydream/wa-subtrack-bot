<?php

namespace Tests\Unit;

use App\Services\MessageParser;
use PHPUnit\Framework\TestCase;

class MessageParserTest extends TestCase
{
    private MessageParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MessageParser();
    }

    public function test_extracts_amount_and_currency_correctly()
    {
        $cases = [
            "Netflix 149 rb tanggal 15" => ['amount' => 149000.0, 'currency' => 'IDR'],
            "15 dollar tiap tanggal 1" => ['amount' => 15.0, 'currency' => 'USD'],
            "55000 tanggal 20" => ['amount' => 55000.0, 'currency' => 'IDR'],
            "1.5jt tanggal 1" => ['amount' => 1500000.0, 'currency' => 'IDR'],
            "Rp 149000 tanggal 15" => ['amount' => 149000.0, 'currency' => 'IDR'],
            "$15 tanggal 1" => ['amount' => 15.0, 'currency' => 'USD'],
            "15 euro tgl 5" => ['amount' => 15.0, 'currency' => 'EUR'],
            "149000 rb tanggal 15" => ['amount' => 149000.0, 'currency' => 'IDR'],
            "15k tanggal 10" => ['amount' => 15000.0, 'currency' => 'IDR'],
            "rp149000 tgl 2" => ['amount' => 149000.0, 'currency' => 'IDR'],
        ];

        foreach ($cases as $text => $expected) {
            $parsed = $this->parser->parse("ServiceName {$text}");
            $this->assertNotNull($parsed, "Failed to parse: {$text}");
            $this->assertEquals($expected['amount'], $parsed['amount'], "Failed amount on: {$text}");
            $this->assertEquals($expected['currency'], $parsed['currency'], "Failed currency on: {$text}");
        }
    }

    public function test_extracts_service_name_correctly()
    {
        $cases = [
            "Netflix 149 rb tanggal 15" => "Netflix",
            "Hosting digitalocean 15 dollar tiap tanggal 1" => "Hosting Digitalocean",
            "Spotify premium 55000 tanggal 20" => "Spotify Premium",
            "Youtube 1.5jt tanggal 1" => "Youtube",
            "Canva Rp 149000 tanggal 15" => "Canva",
            "Figma $15 tanggal 1" => "Figma",
            "VPS 15 euro tgl 5" => "VPS",
        ];

        foreach ($cases as $text => $expected) {
            $parsed = $this->parser->parse($text);
            $this->assertNotNull($parsed, "Failed to parse: {$text}");
            $this->assertEquals($expected, $parsed['service_name'], "Failed service name on: {$text}");
        }
    }

    public function test_extracts_billing_day_correctly()
    {
        $cases = [
            "tanggal 15" => 15,
            "tgl 1" => 1,
            "tiap tanggal 5" => 5,
            "setiap tanggal 31" => 31,
        ];

        foreach ($cases as $text => $expected) {
            $parsed = $this->parser->parse("Netflix 100000 {$text}");
            $this->assertNotNull($parsed, "Failed to parse: {$text}");
            $this->assertEquals($expected, $parsed['billing_day'], "Failed billing day on: {$text}");
        }
    }

    public function test_returns_null_for_invalid_inputs()
    {
        $invalidInputs = [
            "abc",
            "",
            "Netflix 1000",
            "tanggal 15",
            "100000 tanggal 15", // no service name
            "Netflix tanggal 15", // no amount
            "Netflix 100000 no date here", // no valid date
            "A 100000 tanggal 1", // service name too short -> this actually parses as null for service name in some regex logic but checking logic in code... wait it requires strlen(cleaned) >= 2
        ];

        foreach ($invalidInputs as $text) {
            $parsed = $this->parser->parse($text);
            $this->assertNull($parsed, "Expected null for: {$text}");
        }
    }
}
