<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use App\Entity\Cursus;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class ThemeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création des leçons
        $lessonRefs = [];
        foreach ($this->getLessonData() as $key => $lessonData) {
            $lesson = new Lesson();
            $lesson->setTitle($lessonData['name']);
            $lesson->setPrice($lessonData['price']);
            $lesson->setContent($lessonData['content']);
            $manager->persist($lesson);
            $lessonRefs[$key] = $lesson;
        }

        // Création des cursus
        $cursusRefs = [];
        foreach ($this->getCursusData() as $key => $cursusData) {
            $cursus = new Cursus();
            $cursus->setTitle($cursusData['name']);
            $cursus->setPrice($cursusData['price']);
            foreach ($cursusData['lessons'] as $lessonKey) {
                $cursus->addLesson($lessonRefs[$lessonKey]);
            }
            $manager->persist($cursus);
            $cursusRefs[$key] = $cursus;
        }

        // Création des thèmes
        foreach ($this->getThemeData() as $themeData) {
            $theme = new Theme();
            $theme->setName($themeData['name']);
            foreach ($themeData['cursus'] as $cursusKey) {
                $theme->addCursus($cursusRefs[$cursusKey]);
            }
            $manager->persist($theme);
        }

        $manager->flush();
    }

    private function getLessonData(): array
    {
        return [
            'lesson_guitare_1' => [
                'name' => "Découverte de l'instrument",
                'price' => 26,
                'content' => "Contenu de la leçon de découverte de la guitare"
            ],
            'lesson_guitare_2' => [
                'name' => "Les accords et les gammes",
                'price' => 26,
                'content' => "Contenu de la leçon sur les accords et gammes de guitare"
            ],
            'lesson_piano_1' => [
                'name' => "Découverte de l'instrument",
                'price' => 26,
                'content' => "Contenu de la leçon de découverte du piano"
            ],
            'lesson_piano_2' => [
                'name' => "Les accords et les gammes",
                'price' => 26,
                'content' => "Contenu de la leçon sur les accords et gammes de piano"
            ],
            'lesson_web_1' => [
                'name' => "Les langages Html et CSS",
                'price' => 32,
                'content' => "Contenu de la leçon sur HTML et CSS"
            ],
            'lesson_web_2' => [
                'name' => "Dynamiser votre site avec Javascript",
                'price' => 32,
                'content' => "Contenu de la leçon sur JavaScript"
            ],
            'lesson_jardinage_1' => [
                'name' => "Les outils du jardinier",
                'price' => 16,
                'content' => "Contenu de la leçon sur les outils de jardinage"
            ],
            'lesson_jardinage_2' => [
                'name' => "Jardiner avec la lune",
                'price' => 16,
                'content' => "Contenu de la leçon sur le jardinage lunaire"
            ],
            'lesson_cuisine_1' => [
                'name' => "Les modes de cuisson",
                'price' => 23,
                'content' => "Contenu de la leçon sur les modes de cuisson"
            ],
            'lesson_cuisine_2' => [
                'name' => "Les saveurs",
                'price' => 23,
                'content' => "Contenu de la leçon sur les saveurs"
            ],
            'lesson_dressage_1' => [
                'name' => "Mettre en œuvre le style dans l'assiette",
                'price' => 26,
                'content' => "Contenu de la leçon sur le dressage des assiettes"
            ],
            'lesson_dressage_2' => [
                'name' => "Harmoniser un repas à quatre plats",
                'price' => 26,
                'content' => "Contenu de la leçon sur l'harmonisation des plats"
            ],
        ];
    }

    private function getCursusData(): array
    {
        return [
            'cursus_guitare' => [
                'name' => "Cursus d'initiation à la guitare",
                'price' => 50,
                'lessons' => ['lesson_guitare_1', 'lesson_guitare_2']
            ],
            'cursus_piano' => [
                'name' => "Cursus d'initiation au piano",
                'price' => 50,
                'lessons' => ['lesson_piano_1', 'lesson_piano_2']
            ],
            'cursus_dev_web' => [
                'name' => "Cursus d'initiation au développement web",
                'price' => 60,
                'lessons' => ['lesson_web_1', 'lesson_web_2']
            ],
            'cursus_jardinage' => [
                'name' => "Cursus d'initiation au jardinage",
                'price' => 30,
                'lessons' => ['lesson_jardinage_1', 'lesson_jardinage_2']
            ],
            'cursus_cuisine' => [
                'name' => "Cursus d'initiation à la cuisine",
                'price' => 44,
                'lessons' => ['lesson_cuisine_1', 'lesson_cuisine_2']
            ],
            'cursus_dressage' => [
                'name' => "Cursus d'initiation à l'art du dressage culinaire",
                'price' => 48,
                'lessons' => ['lesson_dressage_1', 'lesson_dressage_2']
            ],
        ];
    }

    private function getThemeData(): array
    {
        return [
            [
                'name' => 'Musique',
                'cursus' => ['cursus_guitare', 'cursus_piano']
            ],
            [
                'name' => 'Informatique',
                'cursus' => ['cursus_dev_web']
            ],
            [
                'name' => 'Jardinage',
                'cursus' => ['cursus_jardinage']
            ],
            [
                'name' => 'Cuisine',
                'cursus' => ['cursus_cuisine', 'cursus_dressage']
            ],
        ];
    }
}