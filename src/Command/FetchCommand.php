<?php

namespace App\Command;

use App\Entity\ForecastLink;
use App\Message\FetchForecastMessage;
use App\Repository\ForecastLinkRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:fetch',
    description: 'Add a short description for your command',
)]
class FetchCommand extends Command
{
    public function __construct(
        private HttpClientInterface    $httpClient,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface    $messageBus
    )
    {
        parent::__construct(null);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $today = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $start = $today->setTime(8, 0, 0);
        $end = $today->setTime(16, 0, 0);

        if ($today < $start || $today > $end) {
            $output->writeln('Hors de l\'horaire d\'ouverture');
            return 0;
        }


        $response = $this->httpClient->request('GET', "https://forex.tradingsat.com/analyses-forex/");
        $content = $response->getContent();

        $crawler = new Crawler($content);
        // select .content-item.item--inline
        $firstItem = $crawler
            ->filter('.content-item.item--inline')->first()
            ->filter('.item')->first()
            ->filter('.title')->first();
        $link = $firstItem->filter('a')->attr('href');
        $title = $firstItem->text();

        $forcastLink = new ForecastLink();
        $forcastLink->setTitle($title)
            ->setUrl($link);
        try {

            $this->entityManager->persist($forcastLink);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            $output->writeln('Link already exists');
            return 0;
        }
        $this->messageBus->dispatch(new FetchForecastMessage($forcastLink));
        return Command::SUCCESS;
    }
}
