<?php

namespace Tests\Unit;

use App\Services\AI\QuizAdviceValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuizAdviceValidatorTest extends TestCase
{
    private QuizAdviceValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new QuizAdviceValidator;
    }

    #[Test]
    public function geldige_short_result_output_wordt_geaccepteerd(): void
    {
        $output = ['comboName' => 'Japandi met een Scandinavisch-invloed', 'intro' => str_repeat('Dit is een prettige, persoonlijke introductietekst. ', 3)];

        $this->assertTrue($this->validator->validate($output, 'short_result'));
    }

    #[Test]
    public function ontbrekend_veld_wordt_geweigerd(): void
    {
        $this->assertFalse($this->validator->validate(['comboName' => 'Japandi'], 'short_result'));
    }

    #[Test]
    public function een_gelekt_percentage_wordt_geweigerd(): void
    {
        $output = ['comboName' => 'Japandi', 'intro' => 'Je scoorde 42% op Japandi, wat een mooie basis vormt voor je interieur.'];

        $this->assertFalse($this->validator->validate($output, 'short_result'));
    }

    #[Test]
    public function pdf_full_zonder_roomadvice_wordt_geweigerd(): void
    {
        $output = ['comboName' => 'Japandi', 'intro' => str_repeat('Een persoonlijke introductie. ', 3)];

        $this->assertFalse($this->validator->validate($output, 'pdf_full'));
    }

    #[Test]
    public function pdf_full_met_geldige_roomadvice_wordt_geaccepteerd(): void
    {
        $output = [
            'comboName' => 'Japandi',
            'intro' => str_repeat('Een persoonlijke introductie. ', 3),
            'roomAdvice' => [
                'woonkamer' => str_repeat('Advies voor je woonkamer. ', 2),
                'eethoek' => null,
                'keuken' => null,
            ],
        ];

        $this->assertTrue($this->validator->validate($output, 'pdf_full'));
    }

    #[Test]
    public function niet_array_output_wordt_geweigerd(): void
    {
        $this->assertFalse($this->validator->validate('gewoon een string', 'short_result'));
    }
}
