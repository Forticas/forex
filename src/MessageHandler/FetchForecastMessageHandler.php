<?php

namespace App\MessageHandler;

use App\Entity\Forecast;
use App\Message\FetchForecastMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FetchForecastMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private HttpClientInterface    $httpClient,
        private EntityManagerInterface $entityManager,
        private TexterInterface        $texter
    )
    {
    }

    public function __invoke(FetchForecastMessage $message)
    {
        $request = $this->httpClient->request('GET', $message->getForecastLink()->getUrl());
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
}
