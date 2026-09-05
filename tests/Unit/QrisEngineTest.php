<?php

namespace Tests\Unit;

use App\Services\Qris\Crc16;
use App\Services\Qris\DTOs\FeeData;
use App\Services\Qris\QrisConverter;
use App\Services\Qris\QrisGenerator;
use App\Services\Qris\QrisParser;
use App\Services\Qris\QrisValidator;
use App\Services\Qris\TlvParser;
use PHPUnit\Framework\TestCase;

class QrisEngineTest extends TestCase
{
    private string $sampleStaticQris;

    protected function setUp(): void
    {
        parent::setUp();

        // Build a guaranteed valid Indonesian Static QRIS payload
        $base = '00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204541153033605802ID5919KREATIF ABADI STORE6013JAKARTA PUSAT61051011062070703A016304';
        $crc = Crc16::calculate($base);
        $this->sampleStaticQris = $base . $crc;
    }

    public function test_crc16_calculation_and_verification(): void
    {
        $data = '00020101021153033605802ID5904TEST6007JAKARTA6304';
        $crc = Crc16::calculate($data);

        $this->assertSame(4, strlen($crc));
        $this->assertSame(strtoupper($crc), $crc);
        $this->assertTrue(Crc16::verify($data . $crc));
        $this->assertFalse(Crc16::verify($data . '0000'));
    }

    public function test_tlv_parser_and_builder_roundtrip(): void
    {
        $elements = TlvParser::parse($this->sampleStaticQris);
        $this->assertNotEmpty($elements);

        $tag00 = $elements[0];
        $this->assertSame('00', $tag00->tag);
        $this->assertSame('01', $tag00->value);

        $rebuilt = TlvParser::build($elements);
        $this->assertSame($this->sampleStaticQris, $rebuilt);
    }

    public function test_qris_validator_accepts_valid_static_qris(): void
    {
        $validation = QrisValidator::validate($this->sampleStaticQris);
        $this->assertTrue($validation->valid);
        $this->assertEmpty($validation->errors);
    }

    public function test_qris_validator_detects_crc_mismatch(): void
    {
        $tampered = substr($this->sampleStaticQris, 0, -4) . 'FFFF';
        $validation = QrisValidator::validate($tampered);

        $this->assertFalse($validation->valid);
        $this->assertStringContainsString('CRC mismatch', implode(' ', $validation->errors));
    }

    public function test_qris_validator_detects_malformed_payload(): void
    {
        $invalid = 'NOT_A_QRIS_STRING';
        $validation = QrisValidator::validate($invalid);

        $this->assertFalse($validation->valid);
    }

    public function test_qris_parser_extracts_all_fields(): void
    {
        $parsed = QrisParser::parse($this->sampleStaticQris);

        $this->assertSame('01', $parsed->version);
        $this->assertSame('static', $parsed->method);
        $this->assertSame('KREATIF ABADI STORE', $parsed->merchantName);
        $this->assertSame('JAKARTA PUSAT', $parsed->merchantCity);
        $this->assertSame('5411', $parsed->merchantCategoryCode);
        $this->assertSame('360', $parsed->currency);
        $this->assertSame('ID', $parsed->countryCode);
        $this->assertSame('10110', $parsed->postalCode);
        $this->assertCount(2, $parsed->merchantAccountInfo);
    }

    public function test_convert_static_to_dynamic_with_amount(): void
    {
        $converter = new QrisConverter();
        $result = $converter->convert($this->sampleStaticQris, 50000);

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->dynamicPayload);
        $this->assertSame(50000, $result->amount);
        $this->assertSame(0, $result->fee);
        $this->assertSame(50000, $result->total);

        // Verify the dynamic payload is valid QRIS
        $validation = QrisValidator::validate($result->dynamicPayload);
        $this->assertTrue($validation->valid, 'Generated dynamic QRIS must pass validation: ' . implode(', ', $validation->errors));

        // Verify Tag 01 is now "12" (dynamic) and Tag 54 has 50000
        $parsed = QrisParser::parse($result->dynamicPayload);
        $this->assertSame('dynamic', $parsed->method);
        $this->assertSame('50000', $parsed->amount);
        $this->assertSame('KREATIF ABADI STORE', $parsed->merchantName);
    }

    public function test_convert_static_to_dynamic_with_fixed_fee(): void
    {
        $converter = new QrisConverter();
        $fee = new FeeData(type: 'fixed', value: 1500, mode: 'charged_to_customer');
        $result = $converter->convert($this->sampleStaticQris, 100000, $fee);

        $this->assertTrue($result->success);
        $this->assertSame(100000, $result->amount);
        $this->assertSame(1500, $result->fee);
        $this->assertSame(101500, $result->total);

        $parsed = QrisParser::parse($result->dynamicPayload);
        $this->assertSame('dynamic', $parsed->method);
        $this->assertSame('101500', $parsed->amount);
        $this->assertSame('fixed', $parsed->tipIndicator);
        $this->assertSame('1500', $parsed->tipFixed);

        $validation = QrisValidator::validate($result->dynamicPayload);
        $this->assertTrue($validation->valid);
    }

    public function test_convert_static_to_dynamic_with_percentage_fee(): void
    {
        $converter = new QrisConverter();
        $fee = new FeeData(type: 'percentage', value: 1.5, mode: 'charged_to_customer');
        $result = $converter->convert($this->sampleStaticQris, 100000, $fee);

        $this->assertTrue($result->success);
        $this->assertSame(100000, $result->amount);
        $this->assertSame(1500, $result->fee);
        $this->assertSame(101500, $result->total);

        $parsed = QrisParser::parse($result->dynamicPayload);
        $this->assertSame('percentage', $parsed->tipIndicator);
        $this->assertSame('1.5', $parsed->tipPercentage);

        $validation = QrisValidator::validate($result->dynamicPayload);
        $this->assertTrue($validation->valid);
    }

    public function test_convert_rejects_already_dynamic_qris(): void
    {
        $converter = new QrisConverter();
        $dynamicResult = $converter->convert($this->sampleStaticQris, 50000);
        $this->assertTrue($dynamicResult->success, 'First convert must succeed: ' . implode(', ', $dynamicResult->errors));
        $this->assertNotNull($dynamicResult->dynamicPayload);

        $secondConvert = $converter->convert($dynamicResult->dynamicPayload, 20000);
        $this->assertFalse($secondConvert->success);
        $this->assertContains('Provided QRIS is already dynamic', $secondConvert->errors);
    }

    public function test_qris_generator_produces_valid_svg(): void
    {
        $svg = QrisGenerator::generateSvg($this->sampleStaticQris);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);

        $dataUri = QrisGenerator::generateSvgDataUri($this->sampleStaticQris);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }
}
