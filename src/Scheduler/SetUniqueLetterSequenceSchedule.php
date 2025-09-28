<?php

namespace App\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\RecurringMessage;

#[AsSchedule('set_unique_letter_sequence_schedule')]
class SetUniqueLetterSequenceSchedule
{
    public function __invoke(): Schedule
    {
        return new Schedule()
            ->add(
                RecurringMessage::every(
                    'PT1M',
                    new RunCommandMessage('app:language:sequence:set')
                )
            );
    }
}