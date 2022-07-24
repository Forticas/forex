<?php

namespace App\Controller;

use App\Entity\Forecast;
use App\Entity\ForecastLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TestController extends AbstractController
{
    public function __construct(
        private HttpClientInterface    $httpClient,
        private EntityManagerInterface $entityManager,
        private TexterInterface        $texter
    )
    {
    }
    #[Route('/test/{id}', name: 'app_test')]
    public function index(ForecastLink $forecastLink): Response
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
}
