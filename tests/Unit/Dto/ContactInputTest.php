<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ContactInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class ContactInputTest extends TestCase
{
    public function testValidPayloadPassesValidation(): void
    {
        $input = ContactInput::fromArray([
            'name' => ' Анна ',
            'phone' => '+7 999 123-45-67',
            'email' => 'anna@example.com',
            'comment' => 'Нужна разработка API для нового сервиса',
        ]);

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($input);

        self::assertCount(0, $violations);
        self::assertSame('Анна', $input->name);
    }

    public function testInvalidPayloadReturnsFieldViolations(): void
    {
        $input = ContactInput::fromArray([
            'name' => 'A',
            'phone' => '-------',
            'email' => 'wrong',
            'comment' => 'Коротко',
        ]);

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($input);
        $fields = [];

        foreach ($violations as $violation) {
            $fields[] = $violation->getPropertyPath();
        }

        self::assertContains('name', $fields);
        self::assertContains('phone', $fields);
        self::assertContains('email', $fields);
        self::assertContains('comment', $fields);
    }
}
