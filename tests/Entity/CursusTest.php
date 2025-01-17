<?php

namespace App\Tests\Entity;

use App\Entity\Cursus;
use App\Entity\Lesson;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CursusTest extends TestCase
{
    private $cursus;

    protected function setUp(): void
    {
        $this->cursus = new Cursus();
    }

    public function testGetValidatedLessonsCount(): void
    {
        $user = new User();
        $lesson = new Lesson();
        
        $this->cursus->addLesson($lesson);
        
        $count = $this->cursus->getValidatedLessonsCount($user);
        $this->assertIsInt($count);
    }

    public function testGetProgressForUser(): void
    {
        $user = new User();
        
        $progress = $this->cursus->getProgressForUser($user);
        $this->assertIsFloat($progress);
        $this->assertGreaterThanOrEqual(0, $progress);
        $this->assertLessThanOrEqual(100, $progress);
    }
} 