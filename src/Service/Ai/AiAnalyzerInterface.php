<?php

namespace App\Service\Ai;

use App\Dto\ContactAnalysis;
use App\Dto\ContactInput;

interface AiAnalyzerInterface
{
    public function analyze(ContactInput $input): ContactAnalysis;
}
