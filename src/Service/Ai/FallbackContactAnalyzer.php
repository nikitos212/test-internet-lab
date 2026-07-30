<?php

namespace App\Service\Ai;

use App\Dto\ContactAnalysis;
use App\Dto\ContactInput;

class FallbackContactAnalyzer implements AiAnalyzerInterface
{
    public function analyze(ContactInput $input): ContactAnalysis
    {
        $text = mb_strtolower($input->comment);
        $category = $this->category($text);
        $sentiment = $this->sentiment($text);

        $replies = [
            'project' => 'Спасибо за описание проекта. Я изучу задачу и свяжусь с вами, чтобы обсудить сроки и подход.',
            'job' => 'Спасибо за предложение. Я внимательно изучу детали и отвечу вам в ближайшее время.',
            'partnership' => 'Спасибо за идею сотрудничества. Я посмотрю материалы и предложу удобный формат обсуждения.',
            'other' => 'Спасибо за обращение. Я ознакомлюсь с сообщением и отвечу вам в ближайшее время.',
        ];

        return new ContactAnalysis($category, $sentiment, $replies[$category], 'fallback');
    }

    private function category(string $text): string
    {
        $groups = [
            'job' => ['вакан', 'работ', 'резюме', 'позици', 'команд'],
            'partnership' => ['партнер', 'сотруднич', 'коллаб', 'совмест'],
            'project' => ['проект', 'сайт', 'api', 'сервис', 'разработ', 'интеграц', 'бюджет'],
        ];

        foreach ($groups as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    private function sentiment(string $text): string
    {
        foreach (['срочно', 'ошибка', 'проблем', 'плохо', 'недовол'] as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'negative';
            }
        }

        foreach (['спасибо', 'отлич', 'интерес', 'нравит', 'класс'] as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'positive';
            }
        }

        return 'neutral';
    }
}
