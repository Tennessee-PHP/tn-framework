<?php

namespace TN\TN_Core\CLI;

use League\CLImate\CLImate;

abstract class CLI extends CLImate
{

    public function __construct()
    {
        parent::__construct();
    }

    abstract public function run(): void;

    public function askQuestion(string $question): string
    {
        $this->out($question);
        return trim(fgets(STDIN));
    }

    public function askYesNoQuestion(string $question): bool
    {
        return in_array(strtolower($this->askQuestion($question . ' (y/n): ')), ['y', 'yes']);
    }

    /**
     * @param string $question
     * @param string[] $choices
     * @return string|null
     */
    public function askMultipleChoiceQuestion(string $question, array $choices): ?string
    {
        $question .= ' (enter a number from these choices): ';
        foreach ($choices as $i => $choice) {
            $question .= PHP_EOL . $i + 1 . '. ' . $choice;
        }
        $answer = (int)$this->askQuestion($question) - 1;
        return $choices[$answer] ?? null;
    }
}