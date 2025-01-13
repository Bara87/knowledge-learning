<?php

namespace App\Command;

use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:import-lesson-videos',
    description: 'Import des URLs de vidéos YouTube pour les leçons'
)]
class ImportLessonVideosCommand extends Command
{
    private $lessonRepository;
    private $entityManager;

    public function __construct(LessonRepository $lessonRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->lessonRepository = $lessonRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setDescription('Import des URLs de vidéos YouTube pour les leçons');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Définissez ici vos correspondances leçon -> vidéo
        $videoMapping = [
            1 => 'https://www.youtube.com/watch?v=uPsrFg1H7pQ', // ID leçon => URL YouTube
            2 => 'https://www.youtube.com/watch?v=XXXXX2',
            3 => 'https://www.youtube.com/watch?v=XXXXX3',
            4 => 'https://www.youtube.com/watch?v=XXXXX3',
            5 => 'https://www.youtube.com/watch?v=XXXXX3',
            6 => 'https://www.youtube.com/watch?v=XXXXX3',
            7 => 'https://www.youtube.com/watch?v=XXXXX3',
            8 => 'https://www.youtube.com/watch?v=XXXXX3',
            9 => 'https://www.youtube.com/watch?v=XXXXX3',
            10 => 'https://www.youtube.com/watch?v=XXXXX3',
            11 => 'https://www.youtube.com/watch?v=XXXXX3',
            12 => 'https://www.youtube.com/watch?v=XXXXX3',
            // Ajoutez autant de lignes que nécessaire
        ];

        foreach ($videoMapping as $lessonId => $videoUrl) {
            $lesson = $this->lessonRepository->find($lessonId);
            if ($lesson) {
                $lesson->setVideoUrl($videoUrl);
                $io->text("Mise à jour de la leçon {$lessonId} avec la vidéo : {$videoUrl}");
            }
        }

        $this->entityManager->flush();
        $io->success('Import terminé !');

        return Command::SUCCESS;
    }
}