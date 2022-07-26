<?php

namespace App\Command;

use App\Entity\Forecast;
use App\Entity\ForecastLink;
use App\Message\FetchForecastMessage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:fetch',
    description: 'Add a short description for your command',
)]
class FetchCommand extends Command
{
    public function __construct(
        private HttpClientInterface      $httpClient,
        private EntityManagerInterface   $entityManager,
        private readonly TexterInterface $texter,
        private MessageBusInterface      $messageBus
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
        $start = $today->setTime(9, 0, 0);
        $end = $today->setTime(18, 0, 0);

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
/*
    private function fetchForecast($forecastLink)
    {
        $request = $this->httpClient->request('GET', $forecastLink->getUrl());
        $response = $request->getContent();
        $crawler = new Crawler($response);

        $directionAndPrice = $crawler->filter('.item-conseil')->filter('.item-desc')->text();
        $caracteristics = $crawler->filter('.item-caracteristics')->filter('.item-row')->each(function (Crawler $node) {
            return $node->filter('.item-data')->text();
        });
        $forecast = new Forecast();
        if ($directionAndPrice === 'Neutre') {
            $forecast->setDirection('neutral');
        } else {
            if (str_contains($directionAndPrice, 'Négatif')) {
                $forecast->setDirection('down');
            } elseif (str_contains($directionAndPrice, 'Positif')) {
                $forecast->setDirection('up');
            }
            $entry = (float)substr($directionAndPrice, strpos($directionAndPrice, '1'));
            $forecast->setEntryPrice((float)$entry);
            $forecast->setStopLoss((float)substr($caracteristics[1], 0, 6));
            $forecast->setTakeProfit((float)substr($caracteristics[0], 0, 6));

        }

        $this->entityManager->persist($forecast);
        $this->entityManager->flush();
        $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        // convert date to string
        $date = $date->format('Y-m-d H:i:s');
        $forecastMessage = <<<EOT
EUR/USD {$date}
Votre prédiction est : {$forecast->getDirection()}
Prix d'entrée : {$forecast->getEntryPrice()}
Prix de stop loss : {$forecast->getStopLoss()}
Prix de take profit : {$forecast->getTakeProfit()}
EOT;

        $sms = new SmsMessage('0033766517707', $forecastMessage);
        $sms->transport('freemobile');
        $this->texter->send($sms);

    }
*/
}
