<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Укажите имя')]
        #[Assert\Length(min: 2, max: 80, minMessage: 'Имя должно содержать минимум 2 символа', maxMessage: 'Имя не должно быть длиннее 80 символов')]
        public string $name,
        #[Assert\NotBlank(message: 'Укажите телефон')]
        #[Assert\Length(max: 32, maxMessage: 'Телефон не должен быть длиннее 32 символов')]
        #[Assert\Regex(pattern: '/^\+?(?=(?:\D*\d){7,15}\D*$)[0-9\s()\-]+$/', message: 'Укажите телефон в корректном формате')]
        public string $phone,
        #[Assert\NotBlank(message: 'Укажите email')]
        #[Assert\Email(message: 'Укажите корректный email')]
        #[Assert\Length(max: 180, maxMessage: 'Email не должен быть длиннее 180 символов')]
        public string $email,
        #[Assert\NotBlank(message: 'Добавьте комментарий')]
        #[Assert\Length(min: 10, max: 2000, minMessage: 'Комментарий должен содержать минимум 10 символов', maxMessage: 'Комментарий не должен быть длиннее 2000 символов')]
        public string $comment,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            self::stringValue($data, 'name'),
            self::stringValue($data, 'phone'),
            self::stringValue($data, 'email'),
            self::stringValue($data, 'comment'),
        );
    }

    private static function stringValue(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? trim($value) : '';
    }
}
