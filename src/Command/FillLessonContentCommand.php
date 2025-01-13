<?php

namespace App\Command;

use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fill-lesson-content',
    description: 'Remplit le contenu des leçons avec du Lorem Ipsum'
)]
class FillLessonContentCommand extends Command
{
    private $lessonRepository;
    private $entityManager;

    public function __construct(LessonRepository $lessonRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->lessonRepository = $lessonRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lessons = $this->lessonRepository->findAll();

        foreach ($lessons as $lesson) {
            $content = $this->generateLoremIpsum();
            $lesson->setContent($content);
            $io->text("Mise à jour du contenu de la leçon : {$lesson->getTitle()}");
        }

        $this->entityManager->flush();
        $io->success('Contenu des leçons mis à jour !');

        return Command::SUCCESS;
    }

    private function generateLoremIpsum(): string
    {
        return '<h2>Introduction</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
        
        <h2>1. Concepts Fondamentaux</h2>
        <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.</p>
        
        <div class="alert alert-info">
            <strong>À retenir :</strong> Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus.
        </div>
        
        <h3>1.1 Éléments Essentiels</h3>
        <ul>
            <li><strong>Premier élément :</strong> Nam libero tempore, cum soluta nobis est eligendi optio</li>
            <li><strong>Deuxième élément :</strong> At vero eos et accusamus et iusto odio dignissimos</li>
            <li><strong>Troisième élément :</strong> Et harum quidem rerum facilis est et expedita distinctio</li>
        </ul>
        
        <h2>2. Approfondissement</h2>
        <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga.</p>
        
        <h3>2.1 Points Importants</h3>
        <ol>
            <li>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</li>
            <li>Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.</li>
            <li>Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.</li>
        </ol>
        
        <div class="alert alert-warning my-4">
            <h4>Point d\'attention</h4>
            <p>Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae.</p>
        </div>
        
        <h2>3. Applications Pratiques</h2>
        <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>
        
        <div class="card bg-light my-4">
            <div class="card-body">
                <h4>Exemple Concret</h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
            </div>
        </div>
        
        <h2>4. Points Clés à Retenir</h2>
        <div class="key-points">
            <ul>
                <li><strong>Concept principal :</strong> Excepteur sint occaecat cupidatat non proident</li>
                <li><strong>Aspect important :</strong> Sunt in culpa qui officia deserunt mollit anim id est laborum</li>
                <li><strong>Point critique :</strong> Sed ut perspiciatis unde omnis iste natus error sit voluptatem</li>
                <li><strong>À ne pas oublier :</strong> Nemo enim ipsam voluptatem quia voluptas sit aspernatur</li>
            </ul>
        </div>
        
        <h2>Conclusion</h2>
        <p>En conclusion, ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur.</p>
        
        <div class="alert alert-success mt-4">
            <h4>Pour Aller Plus Loin</h4>
            <p>Pour approfondir ce sujet, nous vous recommandons de :</p>
            <ul>
                <li>Revoir les concepts fondamentaux présentés</li>
                <li>Mettre en pratique les points abordés</li>
                <li>Consulter les ressources complémentaires</li>
            </ul>
        </div>';
    }
}
